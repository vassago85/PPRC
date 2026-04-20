<?php

namespace App\Models;

use App\Enums\AgeRequirementType;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipType extends Model
{
    /** @use HasFactory<\Database\Factories\MembershipTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_cents',
        'duration_months',
        'is_active',
        'show_on_registration',
        'requires_manual_approval',
        'assign_membership_number_on_approval',
        'counts_as_member',
        'allows_sub_members',
        'allowed_sub_member_type_slugs',
        'is_sub_membership',
        'free_while_linked_adult_active',
        'max_per_parent',
        'has_age_requirement',
        'age_requirement_type',
        'age_min',
        'age_max',
        'sort_order',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'duration_months' => 'integer',
        'is_active' => 'boolean',
        'show_on_registration' => 'boolean',
        'requires_manual_approval' => 'boolean',
        'assign_membership_number_on_approval' => 'boolean',
        'counts_as_member' => 'boolean',
        'allows_sub_members' => 'boolean',
        'allowed_sub_member_type_slugs' => AsArrayObject::class,
        'is_sub_membership' => 'boolean',
        'free_while_linked_adult_active' => 'boolean',
        'max_per_parent' => 'integer',
        'has_age_requirement' => 'boolean',
        'age_requirement_type' => AgeRequirementType::class,
        'age_min' => 'integer',
        'age_max' => 'integer',
        'sort_order' => 'integer',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function priceInRands(): float
    {
        return $this->price_cents / 100;
    }

    public function satisfiesAge(?\DateTimeInterface $dob, ?\DateTimeInterface $asOf = null): bool
    {
        if (! $this->has_age_requirement) {
            return true;
        }

        if (! $dob) {
            return false;
        }

        $asOf ??= new \DateTimeImmutable;
        $age = $asOf->diff($dob)->y;

        return match ($this->age_requirement_type) {
            AgeRequirementType::Under => $this->age_max !== null && $age < $this->age_max,
            AgeRequirementType::AtLeast => $this->age_min !== null && $age >= $this->age_min,
            AgeRequirementType::Between => $this->age_min !== null && $this->age_max !== null
                && $age >= $this->age_min && $age <= $this->age_max,
            null => true,
        };
    }
}
