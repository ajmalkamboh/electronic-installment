<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'ulid',
        'company_id',
        'branch_id',
        'role_id',
        'role',
        'status',
        'employee_code',
        'cnic',
        'designation',
        'joining_date',
        'salary',
        'phone',
        'avatar',
        'last_login_at',
        'last_login_ip',
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
            'last_login_at' => 'datetime',
            'joining_date' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (empty($user->ulid)) {
                $user->ulid = (string) Str::ulid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function roleRecord(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_has_roles');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompanyAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'company_owner', 'company_admin'], true)
            || ($this->roleRecord && in_array($this->roleRecord->name, ['super_admin', 'company_owner', 'company_admin'], true));
    }

    public function hasRole(string ...$roles): bool
    {
        if (in_array($this->role, $roles, true)) {
            return true;
        }

        if ($this->roleRecord && in_array($this->roleRecord->name, $roles, true)) {
            return true;
        }

        return $this->roles->contains(function ($role) use ($roles) {
            return in_array($role->name, $roles, true);
        });
    }

    public function hasPermissionTo(string $permission): bool
    {
        // Company Admins and Owners possess universal operational authority
        if ($this->isCompanyAdmin()) {
            return true;
        }

        // Check primary role permissions
        if ($this->roleRecord && $this->roleRecord->relationLoaded('permissions')) {
            if ($this->roleRecord->permissions->contains('name', $permission)) {
                return true;
            }
        } elseif ($this->roleRecord) {
            if ($this->roleRecord->permissions()->where('name', $permission)->exists()) {
                return true;
            }
        }

        // Check assigned pivot roles
        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('name', $permission);
        })->exists();
    }

    public function canAccessBranch(Branch|int $branch): bool
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        if ($this->isCompanyAdmin()) {
            return true;
        }

        return (int) $this->branch_id === (int) $branchId;
    }

    public function assignRole(Role|string $role): self
    {
        $roleModel = is_string($role) ? Role::where('name', $role)->firstOrFail() : $role;

        $this->role_id = $roleModel->id;
        $this->role = $roleModel->name;
        $this->save();

        $this->roles()->syncWithoutDetaching([$roleModel->id]);

        return $this;
    }

    public function syncRoles(array $roles): self
    {
        $roleIds = [];
        $primary = null;

        foreach ($roles as $role) {
            $model = is_string($role) ? Role::where('name', $role)->first() : $role;
            if ($model) {
                $roleIds[] = $model->id;
                if (!$primary) {
                    $primary = $model;
                }
            }
        }

        $this->roles()->sync($roleIds);

        if ($primary) {
            $this->role_id = $primary->id;
            $this->role = $primary->name;
            $this->save();
        }

        return $this;
    }
}
