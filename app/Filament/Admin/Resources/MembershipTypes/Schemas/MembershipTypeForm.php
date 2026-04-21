<?php

namespace App\Filament\Admin\Resources\MembershipTypes\Schemas;

use App\Enums\AgeRequirementType;
use App\Models\MembershipType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MembershipTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->description('Slug is the stable identifier for imports; do not change once used.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(120),
                        TextInput::make('slug')->required()->alphaDash()->maxLength(120)
                            ->unique(ignoreRecord: true)
                            ->helperText('e.g. full-member, junior, pensioner'),
                        Textarea::make('description')->columnSpanFull()->rows(2),
                    ]),

                Section::make('Pricing & duration')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price_cents')
                            ->label('Annual price (ZAR)')
                            ->required()
                            ->numeric()
                            ->prefix('R')
                            ->suffix('.00')
                            ->helperText('Stored as cents; enter whole Rand only')
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100))
                            ->formatStateUsing(fn ($state) => $state === null ? null : $state / 100),
                        TextInput::make('duration_months')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(12),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers show first on the public registration page'),
                    ]),

                Section::make('Committee rules')
                    ->description('Ported from the SSMM PPRC plugin.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_active')->default(true),
                        Toggle::make('show_on_registration')->default(true)
                            ->helperText('Visible on the public registration form'),
                        Toggle::make('requires_manual_approval')->default(true)
                            ->helperText('Committee must approve before membership is issued'),
                        Toggle::make('assign_membership_number_on_approval')->default(true),
                        Toggle::make('counts_as_member')->default(true)
                            ->helperText('Uncheck for sub-member types (e.g. juniors) that do not count toward quorum'),
                    ]),

                Section::make('Sub-members')
                    ->description('Parents have sub-members; sub-member types must be linked to an adult.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('allows_sub_members')
                            ->live()
                            ->helperText('Allow this type to parent sub-members (juniors, spouses)'),
                        TagsInput::make('allowed_sub_member_type_slugs')
                            ->placeholder('junior, spouse')
                            ->visible(fn (Get $get) => (bool) $get('allows_sub_members'))
                            ->formatStateUsing(function ($state): array {
                                if ($state === null || $state === '') {
                                    return [];
                                }
                                if (is_array($state)) {
                                    return array_values($state);
                                }
                                if ($state instanceof \ArrayObject || $state instanceof \Traversable) {
                                    return array_values(iterator_to_array($state));
                                }
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);
                                    if (is_array($decoded)) {
                                        return array_values($decoded);
                                    }
                                    return array_values(array_filter(array_map('trim', explode(',', $state))));
                                }
                                return [];
                            })
                            ->dehydrateStateUsing(fn ($state) => is_array($state) ? array_values($state) : [])
                            ->suggestions(fn () => MembershipType::query()
                                ->where('is_sub_membership', true)
                                ->pluck('slug')
                                ->all()),
                        Toggle::make('is_sub_membership')
                            ->live()
                            ->helperText('This type is itself a sub-member type (junior, spouse)'),
                        Toggle::make('free_while_linked_adult_active')
                            ->visible(fn (Get $get) => (bool) $get('is_sub_membership'))
                            ->helperText('Price is waived if the linked adult has an active membership (juniors)'),
                        TextInput::make('max_per_parent')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Get $get) => (bool) $get('is_sub_membership'))
                            ->helperText('Cap on sub-memberships of this type per parent (e.g. 4 juniors)'),
                    ]),

                Section::make('Age requirement')
                    ->columns(3)
                    ->schema([
                        Toggle::make('has_age_requirement')->live()->columnSpanFull(),
                        Select::make('age_requirement_type')
                            ->options(collect(AgeRequirementType::cases())
                                ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                                ->all())
                            ->visible(fn (Get $get) => (bool) $get('has_age_requirement'))
                            ->live(),
                        TextInput::make('age_min')->numeric()->minValue(0)->maxValue(120)
                            ->visible(fn (Get $get) => (bool) $get('has_age_requirement')
                                && in_array($get('age_requirement_type'), ['at_least', 'between'], true)),
                        TextInput::make('age_max')->numeric()->minValue(0)->maxValue(120)
                            ->visible(fn (Get $get) => (bool) $get('has_age_requirement')
                                && in_array($get('age_requirement_type'), ['under', 'between'], true)),
                    ]),
            ]);
    }
}
