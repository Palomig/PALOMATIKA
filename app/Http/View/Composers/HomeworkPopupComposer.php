<?php

namespace App\Http\View\Composers;

use App\Models\HomeworkAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Раз в день показывает ученику центральный поп-ап о невыполненном ДЗ.
 * Привязан к layouts.pwa — срабатывает на любой студенческой странице, кроме
 * самой домашки и урока. Частота «раз в день» — через users.homework_popup_shown_on.
 */
class HomeworkPopupComposer
{
    public function compose(View $view): void
    {
        $view->with('homeworkPopup', $this->resolve());
    }

    private function resolve(): ?array
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'student') {
            return null;
        }

        // Не мешаем на самой домашке и на уроке.
        $routeName = (string) Route::currentRouteName();
        if (str_contains($routeName, 'homework') || str_contains($routeName, 'lesson')) {
            return null;
        }

        // Раз в день.
        if ($user->homework_popup_shown_on && $user->homework_popup_shown_on->isToday()) {
            return null;
        }

        // Ближайшее по сроку невыполненное ДЗ (без срока — в конце, затем новейшее).
        $assignment = HomeworkAssignment::where('student_id', $user->id)
            ->where('status', '!=', 'completed')
            ->join('homeworks', 'homeworks.id', '=', 'homework_assignments.homework_id')
            ->orderByRaw('homeworks.deadline_at IS NULL, homeworks.deadline_at ASC')
            ->orderByDesc('homeworks.assigned_at')
            ->select('homework_assignments.*')
            ->with('homework')
            ->first();

        if (!$assignment || !$assignment->homework) {
            return null;
        }

        $user->update(['homework_popup_shown_on' => today()]);

        $total = (int) ($assignment->tasks_total ?: $assignment->homework->tasks_count);
        return [
            'title'    => (string) $assignment->homework->title,
            'done'     => (int) $assignment->tasks_completed,
            'total'    => $total,
            'deadline' => $assignment->homework->deadline_at?->format('d.m'),
            'url'      => '/homework',
        ];
    }
}
