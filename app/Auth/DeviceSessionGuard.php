<?php

namespace App\Auth;

use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Recaller;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

/**
 * SessionGuard с «запомнить меня» по устройствам.
 *
 * Штатный guard хранит один remember_token на пользователя и перевыпускает его
 * при каждом logout(), поэтому выход на телефоне выкидывал ученика и с ноутбука,
 * и из PWA. Здесь у каждого устройства свой токен (RememberDevices), logout()
 * гасит только текущее, а кука продлевается при каждом восстановлении входа.
 */
class DeviceSessionGuard extends SessionGuard
{
    /** Токен устройства, который уйдёт в remember-куку этого ответа. */
    private ?string $deviceToken = null;

    protected function userFromRecaller($recaller)
    {
        if (! $recaller->valid() || $this->recallAttempted) {
            return;
        }

        $this->recallAttempted = true;

        $user = $this->userFromDeviceToken($recaller);

        $this->viaRemember = ! is_null($user);

        if ($user) {
            // Скользящий срок: каждое возвращение продлевает куку ещё на весь срок.
            $this->deviceToken = $recaller->token();
            $this->queueRecallerCookie($user);
        }

        return $user;
    }

    private function userFromDeviceToken(Recaller $recaller): ?AuthenticatableContract
    {
        $device = RememberDevices::find($recaller->id(), $recaller->token());

        if ($device) {
            $user = $this->provider->retrieveById($recaller->id());

            if ($user) {
                RememberDevices::touch($device, $this->request);
            }

            return $user;
        }

        // Кука, выданная до перехода на устройства: сверяем со старым общим
        // токеном и переносим её в устройство, чтобы следующий logout где-то
        // ещё её уже не задел.
        $user = $this->provider->retrieveByToken($recaller->id(), $recaller->token());

        if ($user) {
            RememberDevices::adopt($recaller->id(), $recaller->token(), $this->request);
        }

        return $user;
    }

    protected function ensureRememberTokenIsSet(AuthenticatableContract $user)
    {
        $current = $this->currentRecaller();

        // Повторный вход тем же пользователем на том же устройстве (мини-апп
        // перелогинивает при каждом открытии) — новую строку не плодим.
        if ($current && (string) $current->id() === (string) $user->getAuthIdentifier()
            && RememberDevices::find($current->id(), $current->token())) {
            $this->deviceToken = $current->token();

            return;
        }

        if ($current) {
            RememberDevices::forget($current->token());
        }

        $this->deviceToken = RememberDevices::issue($user, $this->request);

        if ($this->deviceToken === null) {
            // Таблица недоступна — работаем по-старому, с общим токеном.
            parent::ensureRememberTokenIsSet($user);
            $this->deviceToken = $user->getRememberToken();
        }
    }

    protected function queueRecallerCookie(AuthenticatableContract $user)
    {
        $this->getCookieJar()->queue($this->createRecaller(
            $user->getAuthIdentifier().'|'.$this->deviceToken.'|'.$user->getAuthPassword()
        ));
    }

    public function logout()
    {
        $user = $this->user();

        if ($current = $this->currentRecaller()) {
            RememberDevices::forget($current->token());
        }

        $this->clearUserDataFromStorage();

        if (isset($this->events)) {
            $this->events->dispatch(new Logout($this->name, $user));
        }

        $this->user = null;
        $this->deviceToken = null;

        $this->loggedOut = true;
    }

    private function currentRecaller(): ?Recaller
    {
        if (is_null($this->request)) {
            return null;
        }

        $recaller = $this->recaller();

        return $recaller && $recaller->valid() ? $recaller : null;
    }
}
