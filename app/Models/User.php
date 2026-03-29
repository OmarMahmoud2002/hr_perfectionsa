<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_id',
        'must_change_password',
        'last_password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at'        => 'datetime',
        'password'                 => 'hashed',
        'must_change_password'     => 'boolean',
        'last_password_changed_at' => 'datetime',
    ];

    // ========================
    // Helpers
    // ========================

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAdminLike(): bool
    {
        return in_array($this->role, ['admin', 'manager', 'hr'], true);
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isEvaluatorUser(): bool
    {
        return $this->role === 'user';
    }

    public function isViewer(): bool
    {
        // Backward-compatible alias for legacy checks.
        return in_array($this->role, ['viewer', 'employee', 'user'], true);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class, 'uploaded_by');
    }

    public function monthVotesGiven(): HasMany
    {
        return $this->hasMany(EmployeeMonthVote::class, 'voter_user_id');
    }

    public function monthAdminScoresCreated(): HasMany
    {
        return $this->hasMany(EmployeeMonthAdminScore::class, 'created_by');
    }

    public function monthTasksCreated(): HasMany
    {
        return $this->hasMany(EmployeeMonthTask::class, 'created_by');
    }

    public function monthTaskEvaluationsGiven(): HasMany
    {
        return $this->hasMany(EmployeeMonthTaskEvaluation::class, 'evaluator_user_id');
    }

    public function dailyPerformanceReviewsGiven(): HasMany
    {
        return $this->hasMany(DailyPerformanceReview::class, 'reviewer_user_id');
    }
}
