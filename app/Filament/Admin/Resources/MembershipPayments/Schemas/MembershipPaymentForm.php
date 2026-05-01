<?php

namespace App\Filament\Admin\Resources\MembershipPayments\Schemas;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MembershipPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment')
                ->columns(2)
                ->schema([
                    TextInput::make('reference')
                        ->label('Payment reference')
                        ->maxLength(120)
                        ->helperText('What the member must use as the EFT reference. Auto-generated when created via the portal.')
                        ->columnSpanFull(),

                    Select::make('provider')
                        ->label('Method')
                        ->options(collect(PaymentProvider::cases())
                            ->mapWithKeys(fn ($c) => [$c->value => match ($c) {
                                PaymentProvider::ManualEft => 'EFT',
                                PaymentProvider::Paystack => 'Paystack',
                                default => $c->value,
                            }])->all())
                        ->required(),

                    Select::make('status')
                        ->options(collect(PaymentStatus::cases())
                            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                        ->required(),

                    TextInput::make('amount_cents')
                        ->label('Amount (cents)')
                        ->numeric()
                        ->required()
                        ->helperText('Stored in cents. R 750.00 = 75000.'),

                    TextInput::make('currency')
                        ->maxLength(3)
                        ->default('ZAR'),

                    TextInput::make('paystack_reference')
                        ->label('Paystack reference')
                        ->maxLength(120)
                        ->visible(fn (callable $get) => $get('provider') === PaymentProvider::Paystack->value),
                ]),

            Section::make('Proof of payment')
                ->schema([
                    FileUpload::make('proof_path')
                        ->disk(\App\Support\MediaDisk::name())
                        ->directory('memberships/proofs')
                        ->openable()
                        ->downloadable()
                        ->maxSize(10240)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'application/pdf'])
                        ->helperText('Member-uploaded proof. PDF or image, max 10 MB.'),
                ]),

            Section::make('Audit trail')
                ->collapsed()
                ->columns(2)
                ->schema([
                    DateTimePicker::make('submitted_at')
                        ->label('Submitted at'),
                    DateTimePicker::make('confirmed_at')
                        ->label('Confirmed at'),
                    Textarea::make('notes')
                        ->label('Internal note')
                        ->rows(3)
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->helperText('Free-text note about this payment. Saved into meta.notes.'),
                ]),
        ])->columns(1);
    }
}
