<?php

namespace App\Console\Commands;

use App\Models\HomeworkAssignment;
use App\Services\StudentNotifier;
use Illuminate\Console\Command;

class RemindHomeworkDeadlines extends Command
{
    protected $signature = 'homework:remind-deadlines';

    protected $description = 'Напоминает ученикам о невыполненных ДЗ, у которых срок сегодня/завтра.';

    public function handle(StudentNotifier $notifier): int
    {
        $today = today();
        $through = $today->copy()->addDay()->endOfDay(); // сегодня и завтра

        $assignments = HomeworkAssignment::where('homework_assignments.status', '!=', 'completed')
            ->join('homeworks', 'homeworks.id', '=', 'homework_assignments.homework_id')
            ->whereNotNull('homeworks.deadline_at')
            ->whereBetween('homeworks.deadline_at', [$today->copy()->startOfDay(), $through])
            ->where(function ($q) use ($today) {
                $q->whereNull('homework_assignments.reminded_at')
                  ->orWhere('homework_assignments.reminded_at', '<', $today->copy()->startOfDay());
            })
            ->select('homework_assignments.*')
            ->with(['homework', 'student'])
            ->get();

        $sent = 0;
        $homeworkUrl = 'https://student.' . config('app.base_domain') . '/homework';
        foreach ($assignments as $a) {
            if (!$a->student || !$a->homework) {
                continue;
            }
            $deadline = $a->homework->deadline_at?->format('d.m');
            $text = '⏰ Напоминание: домашка <b>' . e($a->homework->title) . '</b>'
                . ($deadline ? " — до {$deadline}." : '.');
            // reminded_at ставим всегда: это дедуп «не чаще раза в день», иначе
            // недоставляемый ученик будет пересчитываться на каждом прогоне.
            $delivered = $notifier->notify($a->student, $text, $homeworkUrl);
            $a->update(['reminded_at' => now()]);
            $sent += $delivered ? 1 : 0;
        }

        $this->info("Reminders sent: {$sent}/{$assignments->count()}.");
        return self::SUCCESS;
    }
}
