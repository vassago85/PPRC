<?php

namespace App\Filament\Admin\Resources\Members\Schemas;

use App\Enums\MemberStatus;
use App\Models\Member;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(190)
                            ->helperText('On create, a user account is provisioned automatically. On edit, changes here update the linked user\'s login email.'),
                        Select::make('status')
                            ->options(collect(MemberStatus::cases())
                                ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                                ->all())
                            ->required()
                            ->default(MemberStatus::Pending->value),
                        TextInput::make('membership_number')
                            ->helperText('Leave blank for auto-assignment when a membership becomes active. Numbers are sequential; resigned or deleted members do not free their number for reuse.'),
                        Toggle::make('send_welcome_email')
                            ->label('Send welcome email on save')
                            ->helperText('Sends the account-claim invite so the member can set their own password.')
                            ->default(true)
                            ->visible(fn ($operation) => $operation === 'create'),
                        Hidden::make('user_id'),
                    ]),

                Section::make('Personal details')
                    ->columns(3)
                    ->schema([
                        TextInput::make('first_name')->required()->maxLength(80),
                        TextInput::make('last_name')->required()->maxLength(80),
                        TextInput::make('known_as')->label('Known as')->maxLength(80),
                        DatePicker::make('date_of_birth')
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('d M Y'),
                        TextInput::make('id_number')
                            ->label('ID number')
                            ->maxLength(32)
                            ->helperText('SA ID number — used on endorsement letters.'),
                        TextInput::make('phone_country_code')->default('+27')->maxLength(8),
                        TextInput::make('phone_number')->tel()->maxLength(32),
                        FileUpload::make('profile_photo_path')
                            ->image()
                            ->disk(\App\Support\MediaDisk::name())
                            ->directory('members/profile-photos')
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),

                Section::make('Address')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('address_line1')->label('Address line 1')->maxLength(160),
                        TextInput::make('address_line2')->label('Address line 2')->maxLength(160),
                        TextInput::make('city')->maxLength(80),
                        TextInput::make('province')->maxLength(80),
                        TextInput::make('postal_code')->maxLength(16),
                        TextInput::make('country')->default('South Africa')->maxLength(64),
                    ]),

                Section::make('Shooting profile')
                    ->columns(2)
                    ->schema([
                        TagsInput::make('shooting_disciplines')
                            ->helperText('PPRC runs PRS (centerfire) and PR22 (rimfire) only.')
                            ->suggestions(['PRS', 'PR22'])
                            ->formatStateUsing(function ($state) {
                                if ($state === null || $state === '') {
                                    return [];
                                }
                                if (is_array($state)) {
                                    return array_values($state);
                                }
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);

                                    return is_array($decoded) ? array_values($decoded) : [];
                                }
                                if ($state instanceof \Traversable) {
                                    return array_values(iterator_to_array($state));
                                }

                                return [];
                            })
                            ->dehydrateStateUsing(fn ($state) => is_array($state) ? array_values($state) : [])
                            ->columnSpanFull(),
                        DatePicker::make('join_date')->native(false)->displayFormat('d M Y'),
                        DatePicker::make('expiry_date')->native(false)->displayFormat('d M Y'),
                    ]),

                Section::make('Junior linkage')
                    ->description('For juniors and sub-members, link to the parent/adult member record.')
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('linked_adult_member_id')
                            ->label('Linked adult member')
                            ->relationship('linkedAdult', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Member $record) => $record->fullName().' ('.($record->membership_number ?? '—').')')
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('SAPRF')
                    ->description('If the member has a SAPRF membership number and it matches the admin-maintained SAPRF shooter whitelist, they automatically qualify for SAPRF-tier pricing on SAPRF-hosted events.')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('saprf_membership_number'),
                        DatePicker::make('saprf_verified_at')
                            ->label('Manually verified on'),
                        Textarea::make('saprf_notes')->columnSpanFull()->rows(2),
                    ]),

                Section::make('Committee notes')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')->rows(3)->columnSpanFull()
                            ->helperText('Internal notes. Not visible to the member.'),
                    ]),
            ]);
    }
}
