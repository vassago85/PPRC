<?php

use App\Enums\AgeRequirementType;
use App\Models\MembershipType;

it('allows juniors under the age cap', function () {
    $junior = new MembershipType([
        'has_age_requirement' => true,
        'age_requirement_type' => AgeRequirementType::Under,
        'age_max' => 21,
    ]);

    expect($junior->satisfiesAge(new DateTimeImmutable('-17 years')))->toBeTrue()
        ->and($junior->satisfiesAge(new DateTimeImmutable('-21 years')))->toBeFalse()
        ->and($junior->satisfiesAge(new DateTimeImmutable('-30 years')))->toBeFalse();
});

it('requires minimum age for pensioner', function () {
    $pensioner = new MembershipType([
        'has_age_requirement' => true,
        'age_requirement_type' => AgeRequirementType::AtLeast,
        'age_min' => 65,
    ]);

    expect($pensioner->satisfiesAge(new DateTimeImmutable('-70 years')))->toBeTrue()
        ->and($pensioner->satisfiesAge(new DateTimeImmutable('-65 years')))->toBeTrue()
        ->and($pensioner->satisfiesAge(new DateTimeImmutable('-50 years')))->toBeFalse();
});

it('rejects missing DOB when age rule is set', function () {
    $type = new MembershipType([
        'has_age_requirement' => true,
        'age_requirement_type' => AgeRequirementType::AtLeast,
        'age_min' => 18,
    ]);

    expect($type->satisfiesAge(null))->toBeFalse();
});

it('always passes when no age rule is configured', function () {
    $type = new MembershipType(['has_age_requirement' => false]);

    expect($type->satisfiesAge(null))->toBeTrue()
        ->and($type->satisfiesAge(new DateTimeImmutable('-5 years')))->toBeTrue();
});

it('enforces between-age inclusive bounds', function () {
    $type = new MembershipType([
        'has_age_requirement' => true,
        'age_requirement_type' => AgeRequirementType::Between,
        'age_min' => 18,
        'age_max' => 64,
    ]);

    expect($type->satisfiesAge(new DateTimeImmutable('-18 years')))->toBeTrue()
        ->and($type->satisfiesAge(new DateTimeImmutable('-64 years')))->toBeTrue()
        ->and($type->satisfiesAge(new DateTimeImmutable('-17 years')))->toBeFalse()
        ->and($type->satisfiesAge(new DateTimeImmutable('-65 years')))->toBeFalse();
});
