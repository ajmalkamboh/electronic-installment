<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'display_name',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_has_roles');
    }

    public function primaryUsers(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    public function scopeAvailableForCompany(Builder $query, ?int $companyId): Builder
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id');
            if ($companyId) {
                $q->orWhere('company_id', $companyId);
            }
        });
    }

    public function givePermissionTo(Permission|string ...$permissions): self
    {
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = Permission::where('name', $permission)->firstOrFail();
            }
            $this->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return $this;
    }

    public function revokePermissionTo(Permission|string ...$permissions): self
    {
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = Permission::where('name', $permission)->first();
            }
            if ($permission) {
                $this->permissions()->detach($permission->id);
            }
        }

        return $this;
    }

    public function syncPermissions(array $permissions): self
    {
        $ids = [];
        foreach ($permissions as $permission) {
            if (is_numeric($permission)) {
                $ids[] = (int) $permission;
            } elseif (is_string($permission)) {
                $p = Permission::where('name', $permission)->first();
                if ($p) {
                    $ids[] = $p->id;
                }
            } elseif ($permission instanceof Permission) {
                $ids[] = $permission->id;
            }
        }

        $this->permissions()->sync($ids);

        return $this;
    }

    public function hasPermissionTo(Permission|string $permission): bool
    {
        $permName = $permission instanceof Permission ? $permission->name : $permission;

        return $this->permissions->contains('name', $permName);
    }
}
