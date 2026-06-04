<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile_number',
        'password',
        'role',
        'max_discount_percentage',
        'provider_name',
        'provider_id',
        'source',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Hook into model events.
     */
    protected static function booted()
    {
        static::deleting(function ($user) {
            // Append timestamp to email if soft-deleting
            if (method_exists($user, 'isForceDeleting') && !$user->isForceDeleting()) {
                $user->email = $user->email . '.deleted.' . time();
                $user->save();
            }
        });
    }

    /**
     * Helper to check role.
     */
    public function hasRole(string|array $role): bool
    {
        if (is_array($role)) {
            return in_array($this->role, $role);
        }

        return $this->role === $role;
    }

    /**
     * Helper to check if admin (super or estimator).
     */
    public function isAdmin(): bool // @phpstan-ignore-line
    {
        // Add simple check to avoid errors if field is missing during initial seeding
        return in_array($this->role ?? 'estimator', ['super_admin', 'estimator_admin', 'admin']);
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return \App\Services\PermissionService::roleHasPermission($this->role, $permission);
    }

    /**
     * Get all permissions for this user's role.
     */
    public function getPermissions(): array
    {
        return \App\Services\PermissionService::getPermissionsForRole($this->role);
    }

    /**
     * Get role information including name, description, and color.
     */
    public function getRoleInfo(): array
    {
        $roles = \App\Services\PermissionService::getRoles();

        return $roles[$this->role] ?? [
            'name' => ucfirst(str_replace('_', ' ', $this->role)),
            'description' => '',
            'color' => 'gray',
        ];
    }

    public function estimates()
    {
        return $this->hasMany(Estimate::class, 'created_by');
    }

    public function approvals()
    {
        return $this->hasMany(EstimateApproval::class);
    }

    public function comments()
    {
        return $this->hasMany(EstimateComment::class);
    }

    public function pendingNotifications()
    {
        return $this->hasMany(PendingNotification::class);
    }

    public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function getRoleBadgeClassAttribute()
    {
        $styles = [
            'super_admin' => 'bg-purple-50 text-purple-700 ring-purple-700/10',
            'estimator_admin' => 'bg-indigo-50 text-indigo-700 ring-indigo-700/10',
            'estimator_manager' => 'bg-blue-50 text-blue-700 ring-blue-700/10',
            'estimator' => 'bg-slate-50 text-slate-700 ring-slate-600/20',
        ];

        return $styles[$this->role] ?? 'bg-slate-100 text-slate-600 ring-slate-500/10';
    }
}
