<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'oauth_provider',
        'oauth_id',
        'avatar',
        'role',
        'grade',
        'school',
        'referral_code',
        'referred_by_user_id',
        'partner_commission_percent',
        'partner_status',
        'partner_approved_at',
        'subscription_plan',
        'subscription_ends_at',
        'has_ai_addon',
        'trial_ends_at',
        'last_active_at',
        'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'partner_approved_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'last_active_at' => 'datetime',
        'has_ai_addon' => 'boolean',
    ];

    // Relationships

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function streak(): HasOne
    {
        return $this->hasOne(UserStreak::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class, 'teacher_id');
    }

    public function homeworkAssignments(): HasMany
    {
        return $this->hasMany(HomeworkAssignment::class, 'student_id');
    }

    public function leagueParticipations(): HasMany
    {
        return $this->hasMany(LeagueParticipant::class);
    }

    public function challengerDuels(): HasMany
    {
        return $this->hasMany(Duel::class, 'challenger_id');
    }

    public function opponentDuels(): HasMany
    {
        return $this->hasMany(Duel::class, 'opponent_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(TeacherStudent::class, 'teacher_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(TeacherStudent::class, 'student_id');
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(UserDailyStat::class);
    }

    // Helpers

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->trial_ends_at && $this->trial_ends_at->isFuture()) {
            return true;
        }

        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }

    public function getSubscriptionPlanLabel(): string
    {
        return match($this->subscription_plan) {
            'start' => 'Старт',
            'standard' => 'Стандарт',
            'premium' => 'Премиум',
            default => 'Бесплатный',
        };
    }
}
