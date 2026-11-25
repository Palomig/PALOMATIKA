<?php

namespace App\Services;

use PDO;
use Illuminate\Database\Connection;
use DomainException;

final class PhoneLogin
{
    public function __construct(private Connection $db, private string $service, private string $key) {}

    private function query(string $sql, array $params): \PDOStatement
    {
        $statement = $this->db->getPdo()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public function start(string $input, string $ip, int $actor): string
    {
        $phone = preg_replace('/[ ()-]/', '', trim($input));
        if (!preg_match('/^(?:\+7|7|8)([0-9]{10})$/D', $phone, $match)) {
            throw new DomainException('Введите российский номер телефона.');
        }
        $phone = '7'.$match[1];
        $this->db->beginTransaction();
        try {
            foreach (['phone:'.$phone => 3, 'ip:'.$ip => 12, 'total' => 200] as $scope => $limit) {
                $bucket = hash('sha256', $scope);
                $window = intdiv(time(), 3600);
                $this->query('INSERT INTO phone_login_limits (bucket, period, hits) VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE hits = IF(period = VALUES(period), hits + 1, 1), period = VALUES(period)', [$bucket, $window]);
                if ($this->query('SELECT hits FROM phone_login_limits WHERE bucket = ?', [$bucket])->fetchColumn() > $limit) {
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
                throw new DomainException('К кабинету уже привязан другой номер.');
            }
        }
        $challenge = bin2hex(random_bytes(24));
        if ((!$actor && !$owner) || ($actor && $owner && $actor !== $owner)) {
            return $challenge;
        }
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($this->key)
                ->withHeaders(['Client' => $this->service])->acceptJson()->withoutRedirecting()->timeout(10)
                ->post('https://restapi.plusofon.ru/api/v1/flash-call/send', ['phone' => $phone]);
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            throw new DomainException('Не удалось заказать звонок. Попробуйте позже.');
        }
        $code = $response->json('data.pin');
        if (!$response->successful() || $response->json('success') !== true
            || !is_string($code)
            || !preg_match('/^[0-9]{4}$/D', $code)) {
            throw new DomainException('Не удалось заказать звонок. Попробуйте позже.');
        }
        $this->query('DELETE FROM phone_login_codes WHERE expires_at < UNIX_TIMESTAMP()', []);
        $this->query('INSERT INTO phone_login_codes (id, phone, actor, owner, digest, attempts, expires_at)
            VALUES (?, ?, ?, ?, ?, 5, UNIX_TIMESTAMP()+300)', [
            $challenge, $phone, $actor, $owner, hash_hmac('sha256', $challenge.$code, $this->key),
        ]);
        return $challenge;
    }

    public function verify(string $challenge, string $code, int $actor): int
    {
        $this->db->beginTransaction();
        try {
            $row = $this->query('SELECT * FROM phone_login_codes WHERE id = ? AND expires_at > UNIX_TIMESTAMP() AND attempts > 0 FOR UPDATE', [$challenge])->fetch(PDO::FETCH_ASSOC);
            $user = 0;
            if ($row && (int)$row['actor'] === $actor) {
                if (hash_equals($row['digest'], hash_hmac('sha256', $challenge.$code, $this->key))) {
                    $this->query('DELETE FROM phone_login_codes WHERE id = ?', [$challenge]);
                    if ($actor) {
                        // Unique indexes prevent reassignment of another account or phone.
                        $this->query('INSERT INTO phone_login_numbers (user_id, phone) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = user_id', [$actor, $row['phone']]);
                    }
                    $owner = (int)$this->query('SELECT user_id FROM phone_login_numbers WHERE phone = ? FOR UPDATE', [$row['phone']])->fetchColumn();
                    if ($owner === ($actor ?: (int)$row['owner'])) {
                        $user = $owner;
                    }
                } else {
                    $this->query('UPDATE phone_login_codes SET attempts = attempts - 1 WHERE id = ?', [$challenge]);
                }
            }
            $this->db->commit();
            return $user;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
