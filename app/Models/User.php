<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'username',
    'name',
    'email',
    'password',
    'contact_number',
    'about_me',
    'profile_picture',
    'role_id',
    'supplier_id',
    'status',
    'is_super_admin',
    'created_by',
    'updated_by',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'is_super_admin' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'user_suppliers');
    }

    public function userSupplierAssignments(): HasMany
    {
        return $this->hasMany(UserSupplier::class);
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'supplier_sites');
    }

    public function supplierSiteAssignments(): HasMany
    {
        return $this->hasMany(SupplierSite::class);
    }

    public function isFastAdmin(): bool
    {
        return $this->role?->slug === Role::SLUG_FAST_ADMINISTRATOR;
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isSupplier(): bool
    {
        return $this->role?->slug === Role::SLUG_SUPPLIER_USER;
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isFastAdmin()) {
            return true;
        }

        if ($this->relationLoaded('role')) {
            $role = $this->role;

            if ($role && $role->relationLoaded('permissions')) {
                return $role->permissions->contains('slug', $slug);
            }
        }

        return $this->role()
            ->whereHas('permissions', fn ($query) => $query->where('slug', $slug))
            ->exists();
    }

    /**
     * @return array<int, int>
     */
    public function assignedSupplierIds(): array
    {
        if ($this->relationLoaded('suppliers')) {
            $ids = $this->suppliers->pluck('id')->all();
        } else {
            $ids = $this->suppliers()->pluck('suppliers.id')->all();
        }

        if ($ids === [] && $this->supplier_id !== null) {
            return [$this->supplier_id];
        }

        return $ids;
    }

    /**
     * @return array<int, int>
     */
    public function assignedSiteIds(): array
    {
        return $this->sites()->pluck('sites.id')->all();
    }

    public function profilePictureUrl(): ?string
    {
        if (! $this->profile_picture || ! Storage::disk('public')->exists($this->profile_picture)) {
            return null;
        }

        return Storage::disk('public')->url($this->profile_picture);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
