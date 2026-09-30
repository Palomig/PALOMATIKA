<?php

namespace App\Console\Commands;

use App\Services\FriendInviteService;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Строка доски зовущих «Позови друга» без кабинета супер-админа.
 * Разрешена в deploy-вебхуке: params {"program":"cash","user":51,"friends":1}.
 */
class FriendsBoardSet extends Command
{
    protected $signature = 'friends:board-set {program : cash (8–11) или discount (6–7)} {user : id ученика} {friends : число друзей, 0 — убрать с доски}';

    protected $description = 'Добавить ученика на доску зовущих или поменять число его друзей';

    public function handle(FriendInviteService $friends): int
    {
        try {
            $entry = $friends->setBoardEntry((string) $this->argument('program'), (int) $this->argument('user'), (int) $this->argument('friends'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info(sprintf('%s → %s: %s', $entry->user?->name, $entry->program, $friends->friendsWord($entry->friends)));

        return self::SUCCESS;
    }
}
