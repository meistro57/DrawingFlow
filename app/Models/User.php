<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'role',
        'email',
        'phone',
        'title',
        'active',
        'password',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    // Relationships

    public function assignedDrawingRequests(): HasMany
    {
        return $this->hasMany(DrawingRequest::class, 'assigned_to_user_id');
    }

    public function requestedDrawingRequests(): HasMany
    {
        return $this->hasMany(DrawingRequest::class, 'requested_by_user_id');
    }

    public function submittals(): HasMany
    {
        return $this->hasMany(DrawingSubmittal::class, 'submitted_by_user_id');
    }

    public function fabQueueAssignments(): HasMany
    {
        return $this->hasMany(FabQueue::class, 'assigned_to_user_id');
    }

    // Role helpers

    public function hasRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($this->role, $roles);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function avatarUrl(): ?string
    {
        if ($this->avatar_path === null) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    public function hasPermission(string $permission): bool
    {
        // Simple role-based permission check
        $permissions = [
            'admin' => ['*'],
            'manager' => [
                'view_submittals', 'create_submittals', 'edit_submittals', 'delete_submittals',
                'view_requests', 'create_requests', 'edit_requests', 'delete_requests',
                'view_fab_queue', 'manage_fab_queue',
            ],
            'detailer' => [
                'view_submittals', 'create_submittals', 'edit_submittals',
                'view_requests', 'create_requests', 'edit_requests',
                'view_fab_queue',
            ],
            'viewer' => [
                'view_submittals', 'view_requests', 'view_fab_queue',
            ],
        ];

        $rolePermissions = $permissions[$this->role] ?? [];

        return in_array('*', $rolePermissions) || in_array($permission, $rolePermissions);
    }
}
