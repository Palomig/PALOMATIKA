<?php

namespace App\Services;

use PDO;
use Illuminate\Database\Connection;
use DomainException;
use Illuminate\Support\Facades\Log;

final class PhoneLogin
{
    public function __construct(private Connection $db, private string $service, private string $key) {}

    public static function audit(string $event, string $challenge, int $actor, array $details = []): void
    {
        try {
            Log::channel('phone_login')->info($event, [
                'attempt' => $challenge === '' ? null : substr(hash('sha256', $challenge), 0, 24),
                'actor' => $actor ?: null,
            ] + $details);
        } catch (\Throwable) {
            error_log('Could not write phone login audit event: '.$event);
        }
    }

    private function query(string $sql, array $params): \PDOStatement
    {
        $statement = $this->db->getPdo()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public function start(string $input, string $ip, int $actor): string
    {
        $challenge = bin2hex(random_bytes(24));
        $phone = preg_replace('/[ ()-]/', '', trim($input));
        if (!preg_match('/^(?:\+7|7|8)([0-9]{10})$/D', $phone, $match)) {
            self::audit('call.invalid_phone', $challenge, $actor);
            throw new DomainException('Введите российский номер телефона.');
        }
        $phone = '7'.$match[1];
        $context = ['phone_suffix' => substr($phone, -4)];
        self::audit('call.requested', $challenge, $actor, $context);
        $this->db->beginTransaction();
        try {
            foreach (['phone:'.$phone => 3, 'ip:'.$ip => 12, 'total' => 200] as $scope => $limit) {
                $bucket = hash('sha256', $scope);
                $window = intdiv(time(), 3600);
                $this->query('INSERT INTO phone_login_limits (bucket, period, hits) VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE hits = IF(period = VALUES(period), hits + 1, 1), period = VALUES(period)', [$bucket, $window]);
                if ($this->query('SELECT hits FROM phone_login_limits WHERE bucket = ?', [$bucket])->fetchColumn() > $limit) {
                    self::audit('call.rate_limited', $challenge, $actor, $context + [
                        'scope' => explode(':', $scope, 2)[0],
                    ]);
                    throw new DomainException('Лимит звонков исчерпан. Попробуйте позже.');
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        $owner = (int)$this->query('SELECT user_id FROM phone_login_numbers WHERE phone = ?', [$phone])->fetchColumn();
        if ($actor) {
            $linked = $this->query('SELECT phone FROM phone_login_numbers WHERE user_id = ?', [$actor])->fetchColumn();
            if ($linked && $linked !== $phone) {
                self::audit('call.account_has_other_number', $challenge, $actor, $context);
                throw new DomainException('К кабинету уже привязан другой номер.');
            }
        }
        if ((!$actor && !$owner) || ($actor && $owner && $actor !== $owner)) {
            self::audit($actor ? 'call.number_bound_elsewhere' : 'call.number_unbound', $challenge, $actor, $context);
            return $challenge;
        }
        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->acceptJson()->withoutRedirecting()->timeout(10)
                ->post('https://zvonok.com/manager/cabapi_external/api/v1/phones/flashcall/', [
                    'public_key' => $this->key, 'campaign_id' => $this->service, 'phone' => '+'.$phone,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            self::audit('call.provider_unreachable', $challenge, $actor, $context);
            throw new DomainException('Не удалось заказать звонок. Попробуйте позже.');
        }
        $callId = $response->json('data.call_id');
        $callId = is_int($callId) || is_string($callId) ? (string) $callId : '';
        $context += [
            'http_status' => $response->status(),
            'call_id' => preg_match('/^[0-9]{1,24}$/D', $callId) ? $callId : null,
        ];
        $code = $response->json('data.pincode');
        if (!$response->successful() || $response->json('status') !== 'ok'
            || !is_string($code)
            || !preg_match('/^[0-9]{4}$/D', $code)) {
            self::audit('call.provider_rejected', $challenge, $actor, $context + [
                'reason' => !$response->successful() ? 'http_error'
                    : ($response->json('status') !== 'ok' ? 'api_error' : 'invalid_code_response'),
            ]);
            throw new DomainException('Не удалось заказать звонок. Попробуйте позже.');
        }
        self::audit('call.provider_accepted', $challenge, $actor, $context);
        $this->query('DELETE FROM phone_login_codes WHERE expires_at < UNIX_TIMESTAMP()', []);
        $this->query('INSERT INTO phone_login_codes (id, phone, actor, owner, digest, attempts, expires_at)
            VALUES (?, ?, ?, ?, ?, 5, UNIX_TIMESTAMP()+300)', [
            $challenge, $phone, $actor, $owner, hash_hmac('sha256', $challenge.$code, $this->key),
        ]);
        self::audit('call.challenge_saved', $challenge, $actor);
        return $challenge;
    }

    public function verify(string $challenge, string $code, int $actor): int
    {
        $this->db->beginTransaction();
        try {
            $row = $this->query('SELECT *, expires_at <= UNIX_TIMESTAMP() AS expired FROM phone_login_codes WHERE id = ? FOR UPDATE', [$challenge])->fetch(PDO::FETCH_ASSOC);
            $user = 0;
            $result = match (true) {
                $challenge === '' => 'no_session_challenge',
                !$row => 'challenge_missing',
                (int)$row['actor'] !== $actor => 'actor_mismatch',
                (bool)$row['expired'] => 'challenge_expired',
                (int)$row['attempts'] <= 0 => 'attempts_exhausted',
                default => 'ready',
            };
            $details = [];
            if ($result === 'ready') {
                if (hash_equals($row['digest'], hash_hmac('sha256', $challenge.$code, $this->key))) {
                    $this->query('DELETE FROM phone_login_codes WHERE id = ?', [$challenge]);
                    if ($actor) {
                        // Unique indexes prevent reassignment of another account or phone.
                        $this->query('INSERT INTO phone_login_numbers (user_id, phone) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = user_id', [$actor, $row['phone']]);
                    }
                    $owner = (int)$this->query('SELECT user_id FROM phone_login_numbers WHERE phone = ? FOR UPDATE', [$row['phone']])->fetchColumn();
                    $result = 'owner_mismatch';
                    if ($owner === ($actor ?: (int)$row['owner'])) {
                        $user = $owner;
                        $result = 'accepted';
                    }
                } else {
                    $this->query('UPDATE phone_login_codes SET attempts = attempts - 1 WHERE id = ?', [$challenge]);
                    $result = 'code_mismatch';
                    $details['attempts_left'] = (int)$row['attempts'] - 1;
                }
            }
            $this->db->commit();
            self::audit('verify.'.$result, $challenge, $actor, $details);
            return $user;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
