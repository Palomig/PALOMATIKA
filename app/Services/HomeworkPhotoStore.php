<?php

namespace App\Services;

use App\Models\HomeworkAssignment;
use App\Models\HomeworkTopicTask;

/**
 * Общий секрет с сервисом hw-photos (VPS), где лежат фото решений домашки.
 *
 * Почему не через диск Laravel: прод — шаред-хостинг Timeweb, там `public/storage`
 * не симлинк (по `/storage/...` всегда 404), а канал и место дорогие. Ученик льёт
 * снимок прямо в сервис, Laravel в передаче файла не участвует.
 *
 * Три подписи на одном секрете, сетевых вызовов между сторонами не требуется:
 *   upload-токен — подписываем мы, проверяет сервис (кому можно грузить);
 *   photo_id     — подписывает сервис, проверяем мы (чьё это фото);
 *   read-ссылка  — подписываем мы, проверяет сервис (кому можно смотреть).
 *
 * Если секрет не задан — `enabled()` = false, и домашка работает по старому пути
 * с сохранением файла на хостинге. Тот же путь остаётся фолбэком, когда сервис
 * недоступен: сдача ДЗ не должна зависеть от отдельной машины.
 */
class HomeworkPhotoStore
{
    public function enabled(): bool
    {
        return $this->secret() !== '' && $this->baseUrl() !== '';
    }

    /**
     * Токен на загрузку одного фото для конкретной задачи конкретного ученика.
     *
     * @return array{upload_url:string, token:string, expires_at:int}|null
     */
    public function uploadTicket(HomeworkAssignment $assignment, HomeworkTopicTask $task, int $studentId): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        $expiresAt = time() + max(60, (int) config('services.hw_photos.upload_ttl', 900));

        return [
            'upload_url' => $this->baseUrl() . '/v1/photos',
            'token' => $this->signPayload('t', [
                'a' => (int) $assignment->id,
                'k' => (int) $task->id,
                's' => $studentId,
                'e' => $expiresAt,
            ]),
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Фото принимаем только если сервис подтвердил подписью, что оно загружено
     * для этой задачи этим учеником — иначе чужой photo_id можно было бы
     * подставить в свою домашку.
     */
    public function verifyPhotoId(string $photoId, HomeworkAssignment $assignment, HomeworkTopicTask $task, int $studentId): bool
    {
        $meta = $this->readPayload('p', $photoId);

        if ($meta === null) {
            return false;
        }

        return (int) ($meta['a'] ?? 0) === (int) $assignment->id
            && (int) ($meta['k'] ?? 0) === (int) $task->id
            && (int) ($meta['s'] ?? 0) === $studentId;
    }

    /**
     * Короткоживущая ссылка на просмотр. Права проверяет Laravel до её выдачи —
     * публичных ссылок на тетради учеников не существует.
     */
    public function readUrl(string $photoId, ?int $width = null): ?string
    {
        if (!$this->enabled() || $photoId === '') {
            return null;
        }

        $expiresAt = time() + max(60, (int) config('services.hw_photos.read_ttl', 3600));
        $signature = $this->hmac($photoId . '.' . $expiresAt);

        $query = [
            'exp' => $expiresAt,
            'sig' => $signature,
        ];
        if ($width !== null) {
            $query['w'] = $width;
        }

        return $this->baseUrl() . '/v1/photo/' . rawurlencode($photoId) . '?' . http_build_query($query);
    }

    private function signPayload(string $kind, array $payload): string
    {
        $body = $this->b64u(json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $kind . '.' . $body . '.' . $this->hmac($body);
    }

    private function readPayload(string $kind, string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3 || $parts[0] !== $kind) {
            return null;
        }
        if (!hash_equals($this->hmac($parts[1]), $parts[2])) {
            return null;
        }

        $decoded = json_decode($this->b64uDecode($parts[1]), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function hmac(string $data): string
    {
        return $this->b64u(hash_hmac('sha256', $data, $this->secret(), true));
    }

    private function b64u(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function b64uDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }

    private function secret(): string
    {
        return trim((string) config('services.hw_photos.secret'));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.hw_photos.url'), '/');
    }
}
