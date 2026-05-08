<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'name_unverified',
        'email',
        'password',
        'oauth_provider',
        'oauth_id',
        'tg_username',
        'avatar',
        'role',
        'grade',
        'school',
        'referral_code',
        'referred_by_user_id',
        'partner_commission_percent',
        'partner_status',
        'partner_approved_at',
        'tg_premium_until',
        'tg_trial_used',
        'star_balance',
        'last_active_at',
        'timezone',
        'grade_num',
        'grade_letter',
        'school_number',
        'city',
        'onboarding_completed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'partner_approved_at' => 'datetime',
        'last_active_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'tg_premium_until' => 'datetime',
        'tg_trial_used' => 'boolean',
        'name_unverified' => 'boolean',
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

    public function skills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function homeworks(): HasMany
    {
        return $this->hasMany(Homework::class, 'teacher_id');
    }

    public function homeworkAssignments(): HasMany
    {
        return $this->hasMany(HomeworkAssignment::class, 'student_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(TeacherStudent::class, 'teacher_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(TeacherStudent::class, 'student_id');
    }

    public function ownedStudentGroups(): HasMany
    {
        return $this->hasMany(StudentGroup::class, 'owner_teacher_id');
    }

    public function studentGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudentGroup::class, 'student_group_members', 'student_id', 'group_id');
    }

    public function ownedOgeVariants(): HasMany
    {
        return $this->hasMany(OgeVariant::class, 'owner_teacher_id');
    }

    public function ownedJarvisMaterials(): HasMany
    {
        return $this->hasMany(JarvisMaterial::class, 'owner_teacher_id');
    }

    public function ogeAttempts(): HasMany
    {
        return $this->hasMany(OgeAttempt::class, 'student_id');
    }

    public function ogeGeneratorTemplates(): HasMany
    {
        return $this->hasMany(OgeGeneratorTemplate::class, 'user_id');
    }

    // Родитель видит этих учеников
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    // Родители ученика
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relation')
            ->withTimestamps();
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

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    public function isSuperuser(): bool
    {
        return $this->isAdmin();
    }

    public function starTransactions(): HasMany
    {
        return $this->hasMany(StarTransaction::class);
    }

    public function hasTgPremium(): bool
    {
        return $this->tg_premium_until && $this->tg_premium_until->isFuture();
    }
}
