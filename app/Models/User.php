<?php

namespace App\Models;

use App\Models\OrderAssignment;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'password','is_active',])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'assessed_by');
    }

    public function followUpsRecorded(): HasMany
    {
        return $this->hasMany(FollowUp::class, 'recorded_by');
    }

    public function contactNotesRecorded(): HasMany
    {
        return $this->hasMany(ContactNote::class, 'recorded_by');
    }

    public function productSpecificationsCreated(): HasMany
    {
        return $this->hasMany(ProductSpecification::class, 'created_by');
    }

    public function specificationArtifactsUploaded(): HasMany
    {
        return $this->hasMany(
            ProductSpecificationArtifact::class,
            'uploaded_by'
        );
    }
    public function assignments(): HasMany
    {
        return $this->hasMany(OrderAssignment::class, 'user_id');
    }

    public function procurementOffers(): HasMany
    {
        return $this->hasMany(ProcurementOffer::class);
    }

}