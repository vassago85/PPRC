<?php

namespace App\Filament\Admin\Pages;

use App\Mail\SiteConfigTestMail;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

/**
 * Single consolidated settings page for runtime-configurable values:
 *  - Contact & social links (public site)
 *  - Bank details (manual EFT)
 *  - Mailgun credentials
 *  - S3 / MinIO storage credentials
 *  - Paystack keys
 *
 * All values are stored via SiteSetting::put(), which automatically
 * encrypts anything flagged `is_secret`. RuntimeConfigServiceProvider
 * reads these back on boot and overrides config() so the DB values
 * actually take effect at runtime (no .env changes required after deploy).
 */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'Site settings';

    protected static ?string $slug = 'settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.site.manage')
            || auth()->user()?->can('settings.integrations.manage')
            || auth()->user()?->hasRole('developer');
    }

    public function mount(): void
    {
        $this->form->fill([
            // Contact & social
            'contact' => [
                'email' => (string) SiteSetting::get('contact.email', ''),
                'physical_address' => (string) SiteSetting::get('contact.physical_address', ''),
                'social' => [
                    'facebook' => (string) SiteSetting::get('contact.social.facebook', ''),
                    'instagram' => (string) SiteSetting::get('contact.social.instagram', ''),
                    'whatsapp' => (string) SiteSetting::get('contact.social.whatsapp', ''),
                ],
            ],

            // Bank / EFT
            'bank' => [
                'account_name' => (string) SiteSetting::get('payments.bank.account_name', ''),
                'bank' => (string) SiteSetting::get('payments.bank.bank', ''),
                'account_number' => (string) SiteSetting::get('payments.bank.account_number', ''),
                'branch_code' => (string) SiteSetting::get('payments.bank.branch_code', ''),
                'account_type' => (string) SiteSetting::get('payments.bank.account_type', 'cheque'),
                'reference_format' => (string) SiteSetting::get('payments.bank.reference_format', 'PPRC-MEM-{id}'),
                'notes' => (string) SiteSetting::get('payments.bank.notes', ''),
            ],

            // Email delivery (runtime overrides — see RuntimeConfigServiceProvider)
            'mail' => [
                'transport' => (string) SiteSetting::get('mail.transport', ''),
                'from_address' => (string) SiteSetting::get('mail.from.address', ''),
                'from_name' => (string) SiteSetting::get('mail.from.name', 'PPRC'),
                'mailgun_domain' => (string) SiteSetting::get('mail.mailgun.domain', ''),
                'mailgun_secret' => (string) SiteSetting::get('mail.mailgun.secret', ''),
                'mailgun_endpoint' => (string) SiteSetting::get('mail.mailgun.endpoint', 'api.mailgun.net'),
                'smtp_host' => (string) SiteSetting::get('mail.smtp.host', ''),
                'smtp_port' => (string) SiteSetting::get('mail.smtp.port', '587'),
                'smtp_username' => (string) SiteSetting::get('mail.smtp.username', ''),
                'smtp_password' => (string) SiteSetting::get('mail.smtp.password', ''),
                'smtp_encryption' => (string) SiteSetting::get('mail.smtp.encryption', 'tls'),
            ],

            // S3 / MinIO
            'storage' => [
                'endpoint' => (string) SiteSetting::get('storage.s3.endpoint', ''),
                'region' => (string) SiteSetting::get('storage.s3.region', 'us-east-1'),
                'bucket' => (string) SiteSetting::get('storage.s3.bucket', ''),
                'access_key' => (string) SiteSetting::get('storage.s3.access_key', ''),
                'secret_key' => (string) SiteSetting::get('storage.s3.secret_key', ''),
                'url' => (string) SiteSetting::get('storage.s3.url', ''),
                'use_path_style' => (bool) SiteSetting::get('storage.s3.use_path_style', true),
            ],

            // Paystack
            'paystack' => [
                'public_key' => (string) SiteSetting::get('payments.paystack.public_key', ''),
                'secret_key' => (string) SiteSetting::get('payments.paystack.secret_key', ''),
                'webhook_secret' => (string) SiteSetting::get('payments.paystack.webhook_secret', ''),
                'currency' => (string) SiteSetting::get('payments.paystack.currency', 'ZAR'),
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $canManageIntegrations = auth()->user()?->can('settings.integrations.manage')
            || auth()->user()?->hasRole('developer');

        return $schema
            ->components([
                Tabs::make('settings')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Contact & social')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->schema([
                                Section::make('Public contact details')
                                    ->description('Shown on the website footer, contact page and in email signatures. The public contact form on /contact delivers to this email address.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('contact.email')
                                            ->label('Contact email')
                                            ->email()
                                            ->required()
                                            ->helperText('Where public contact-form submissions are delivered.')
                                            ->maxLength(255),
                                        TextInput::make('contact.physical_address')
                                            ->label('Physical address')
                                            ->columnSpanFull()
                                            ->maxLength(255),
                                    ]),

                                Section::make('Social links')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('contact.social.facebook')
                                            ->label('Facebook URL')
                                            ->url()
                                            ->prefix('https://')
                                            ->maxLength(255),
                                        TextInput::make('contact.social.instagram')
                                            ->label('Instagram URL')
                                            ->url()
                                            ->prefix('https://')
                                            ->maxLength(255),
                                        TextInput::make('contact.social.whatsapp')
                                            ->label('WhatsApp link (optional)')
                                            ->url()
                                            ->prefix('https://')
                                            ->maxLength(255),
                                    ]),
                            ]),

                        Tab::make('Bank (EFT)')
                            ->icon(Heroicon::OutlinedBanknotes)
                            ->schema([
                                Section::make('Club bank account')
                                    ->description('Shown to members on the EFT payment screen. Editable by roles with payment settings access (e.g. treasurer, chair, vice chair).')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('bank.account_name')
                                            ->label('Account name')
                                            ->required()
                                            ->maxLength(120),
                                        TextInput::make('bank.bank')
                                            ->label('Bank')
                                            ->required()
                                            ->maxLength(80),
                                        TextInput::make('bank.account_number')
                                            ->label('Account number')
                                            ->required()
                                            ->maxLength(40),
                                        TextInput::make('bank.branch_code')
                                            ->label('Branch code')
                                            ->maxLength(20),
                                        Select::make('bank.account_type')
                                            ->label('Account type')
                                            ->options([
                                                'cheque' => 'Cheque',
                                                'savings' => 'Savings',
                                                'current' => 'Current',
                                                'business' => 'Business',
                                            ])
                                            ->default('cheque'),
                                        TextInput::make('bank.reference_format')
                                            ->label('Reference format')
                                            ->helperText('Use {id} for member id, {number} for membership number, {year} for current year.')
                                            ->default('PPRC-MEM-{id}')
                                            ->maxLength(80),
                                        Textarea::make('bank.notes')
                                            ->label('Extra notes (optional)')
                                            ->helperText('Shown under the bank details, e.g. SWIFT code or international payment instructions.')
                                            ->columnSpanFull()
                                            ->rows(3),
                                    ]),
                            ]),

                        Tab::make('Email delivery')
                            ->icon(Heroicon::OutlinedPaperAirplane)
                            ->visible($canManageIntegrations)
                            ->schema([
                                Section::make('Transport')
                                    ->description('Automatic prefers Mailgun when domain + secret are set; otherwise SMTP when a host is configured; otherwise your .env default mailer.')
                                    ->schema([
                                        Select::make('mail.transport')
                                            ->label('Mailer preference')
                                            ->options([
                                                '' => 'Automatic',
                                                'mailgun' => 'Mailgun',
                                                'smtp' => 'SMTP',
                                                'log' => 'Log only (local testing)',
                                            ])
                                            ->native(false),
                                    ]),

                                Section::make('From address')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('mail.from_address')
                                            ->label('From email')
                                            ->email()
                                            ->placeholder('no-reply@pretoriaprc.co.za')
                                            ->maxLength(255),
                                        TextInput::make('mail.from_name')
                                            ->label('From name')
                                            ->default('PPRC')
                                            ->maxLength(80),
                                    ]),

                                Section::make('Mailgun credentials')
                                    ->description('Obtained from Mailgun → Sending → Domain settings → API security.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('mail.mailgun_domain')
                                            ->label('Domain')
                                            ->placeholder('mg.pretoriaprc.co.za')
                                            ->maxLength(120),
                                        Select::make('mail.mailgun_endpoint')
                                            ->label('Endpoint')
                                            ->options([
                                                'api.mailgun.net' => 'US (api.mailgun.net)',
                                                'api.eu.mailgun.net' => 'EU (api.eu.mailgun.net)',
                                            ])
                                            ->default('api.mailgun.net'),
                                        TextInput::make('mail.mailgun_secret')
                                            ->label('API key / secret')
                                            ->password()
                                            ->revealable()
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->helperText('Leave blank to keep the current value.')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('SMTP relay')
                                    ->description('Used when SMTP is chosen, or automatic mode with no Mailgun credentials.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('mail.smtp_host')
                                            ->label('Host')
                                            ->maxLength(120),
                                        TextInput::make('mail.smtp_port')
                                            ->label('Port')
                                            ->numeric()
                                            ->default(587),
                                        TextInput::make('mail.smtp_username')
                                            ->label('Username')
                                            ->maxLength(120),
                                        TextInput::make('mail.smtp_password')
                                            ->label('Password')
                                            ->password()
                                            ->revealable()
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->helperText('Leave blank to keep the current value.'),
                                        Select::make('mail.smtp_encryption')
                                            ->label('Encryption')
                                            ->options([
                                                'tls' => 'TLS',
                                                'ssl' => 'SSL',
                                                '' => 'None',
                                            ])
                                            ->default('tls'),
                                    ]),
                            ]),

                        Tab::make('Storage (S3 / MinIO)')
                            ->icon(Heroicon::OutlinedCloud)
                            ->visible($canManageIntegrations)
                            ->schema([
                                Section::make('S3-compatible storage')
                                    ->description('Works with AWS S3, MinIO, Wasabi, DigitalOcean Spaces, etc. For MinIO, set the endpoint to your MinIO URL and keep "Use path style" enabled.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('storage.endpoint')
                                            ->label('Endpoint URL')
                                            ->placeholder('http://pprc-minio:9000')
                                            ->helperText('S3 API URL the **server** uses (e.g. internal http://pprc-minio:9000 in Docker). Admin uploads use HTTPS to the app first, then PHP writes here — do not rely on the browser reaching this host.')
                                            ->maxLength(255),
                                        TextInput::make('storage.region')
                                            ->label('Region')
                                            ->default('us-east-1')
                                            ->maxLength(80),
                                        TextInput::make('storage.bucket')
                                            ->label('Bucket')
                                            ->required()
                                            ->maxLength(120),
                                        TextInput::make('storage.url')
                                            ->label('Public URL')
                                            ->helperText('HTTPS base for public media and for presigned S3 browser uploads (must match your Nginx / MinIO proxy). E.g. https://example.com/media/pprc-media')
                                            ->maxLength(255),
                                        TextInput::make('storage.access_key')
                                            ->label('Access key ID')
                                            ->password()
                                            ->revealable()
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->helperText('Leave blank to keep the current value.'),
                                        TextInput::make('storage.secret_key')
                                            ->label('Secret access key')
                                            ->password()
                                            ->revealable()
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->helperText('Leave blank to keep the current value.'),
                                        Toggle::make('storage.use_path_style')
                                            ->label('Use path-style URLs')
                                            ->helperText('Required for MinIO and most self-hosted S3 gateways.')
                                            ->default(true),
                                    ]),
                            ]),

                        Tab::make('Payments (Paystack)')
                            ->icon(Heroicon::OutlinedCreditCard)
                            ->visible($canManageIntegrations)
                            ->schema([
                                Section::make('Paystack API keys')
                                    ->description('Obtain live keys from Paystack → Settings → API Keys & Webhooks.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('paystack.public_key')
                                            ->label('Public key')
                                            ->placeholder('pk_live_...')
                                            ->maxLength(120),
                                        Select::make('paystack.currency')
                                            ->label('Currency')
                                            ->options([
                                                'ZAR' => 'South African Rand (ZAR)',
                                                'NGN' => 'Nigerian Naira (NGN)',
                                                'GHS' => 'Ghanaian Cedi (GHS)',
                                                'KES' => 'Kenyan Shilling (KES)',
                                                'USD' => 'US Dollar (USD)',
                                            ])
                                            ->default('ZAR'),
                                        TextInput::make('paystack.secret_key')
                                            ->label('Secret key')
                                            ->password()
                                            ->revealable()
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->helperText('Leave blank to keep the current value.')
                                            ->columnSpanFull(),
                                        TextInput::make('paystack.webhook_secret')
                                            ->label('Webhook secret (optional)')
                                            ->password()
                                            ->revealable()
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->helperText('Used to verify Paystack webhook signatures. Leave blank to keep the current value.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions())
                        ->alignment(Alignment::End)
                        ->key('form-actions'),
                ]),
        ]);
    }

    /** @return array<Action> */
    protected function getFormActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label('Send test email')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->modalHeading('Send a test email')
                ->modalDescription('Uses the mail configuration already saved to the database (plus .env where keys are empty). Save changes first if you just edited Mailgun or SMTP.')
                ->modalSubmitActionLabel('Send')
                ->fillForm(fn (): array => [
                    'to' => (string) (auth()->user()?->email ?? ''),
                ])
                ->schema([
                    TextInput::make('to')
                        ->label('Recipient')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    try {
                        Mail::to($data['to'])->send(new SiteConfigTestMail(auth()->user()));

                        Notification::make()
                            ->success()
                            ->title('Test email sent')
                            ->body('Check the inbox (and spam) for the recipient you entered.')
                            ->send();
                    } catch (\Throwable $e) {
                        report($e);

                        Notification::make()
                            ->danger()
                            ->title('Could not send test email')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }),
            Action::make('save')
                ->label('Save changes')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /**
         * Each row is:
         *   [state path in form, setting key, group, label, is_secret, optional: keep-empty-means-unchanged]
         *
         * keep-empty-means-unchanged → true skips writing the key when the form
         * value is empty, so secret rotation works without wiping the stored secret.
         */
        $map = [
            // --- Contact -----------------------------------------------------
            ['contact.email',             'contact.email',             'contact', 'Contact email',    false, false],
            ['contact.physical_address',  'contact.physical_address',  'contact', 'Physical address', false, false],
            ['contact.social.facebook',   'contact.social.facebook',   'contact', 'Facebook URL',     false, false],
            ['contact.social.instagram',  'contact.social.instagram',  'contact', 'Instagram URL',    false, false],
            ['contact.social.whatsapp',   'contact.social.whatsapp',   'contact', 'WhatsApp link',    false, false],

            // --- Bank --------------------------------------------------------
            ['bank.account_name',     'payments.bank.account_name',     'payments', 'Bank account name', false, false],
            ['bank.bank',             'payments.bank.bank',             'payments', 'Bank',              false, false],
            ['bank.account_number',   'payments.bank.account_number',   'payments', 'Account number',    false, false],
            ['bank.branch_code',      'payments.bank.branch_code',      'payments', 'Branch code',       false, false],
            ['bank.account_type',     'payments.bank.account_type',     'payments', 'Account type',      false, false],
            ['bank.reference_format', 'payments.bank.reference_format', 'payments', 'Reference format',  false, false],
            ['bank.notes',            'payments.bank.notes',            'payments', 'Bank notes',        false, false],

            // --- Email delivery ---------------------------------------------
            ['mail.transport',        'mail.transport',          'mail', 'Mail transport',   false, false],
            ['mail.from_address',     'mail.from.address',       'mail', 'From address',     false, false],
            ['mail.from_name',        'mail.from.name',          'mail', 'From name',        false, false],
            ['mail.mailgun_domain',   'mail.mailgun.domain',     'mail', 'Mailgun domain',   false, false],
            ['mail.mailgun_endpoint', 'mail.mailgun.endpoint',   'mail', 'Mailgun endpoint', false, false],
            ['mail.mailgun_secret',   'mail.mailgun.secret',     'mail', 'Mailgun secret',   true,  true],
            ['mail.smtp_host',        'mail.smtp.host',          'mail', 'SMTP host',        false, false],
            ['mail.smtp_port',        'mail.smtp.port',          'mail', 'SMTP port',        false, false],
            ['mail.smtp_username',    'mail.smtp.username',      'mail', 'SMTP username',    false, false],
            ['mail.smtp_password',    'mail.smtp.password',      'mail', 'SMTP password',    true,  true],
            ['mail.smtp_encryption',  'mail.smtp.encryption',    'mail', 'SMTP encryption',  false, false],

            // --- Storage -----------------------------------------------------
            ['storage.endpoint',       'storage.s3.endpoint',        'storage', 'S3 endpoint',       false, false],
            ['storage.region',         'storage.s3.region',          'storage', 'S3 region',         false, false],
            ['storage.bucket',         'storage.s3.bucket',          'storage', 'S3 bucket',         false, false],
            ['storage.url',            'storage.s3.url',             'storage', 'S3 public URL',     false, false],
            ['storage.use_path_style', 'storage.s3.use_path_style',  'storage', 'Use path style',    false, false],
            ['storage.access_key',     'storage.s3.access_key',      'storage', 'S3 access key',     true,  true],
            ['storage.secret_key',     'storage.s3.secret_key',      'storage', 'S3 secret key',     true,  true],

            // --- Paystack ----------------------------------------------------
            ['paystack.public_key',     'payments.paystack.public_key',     'payments', 'Paystack public key',     false, false],
            ['paystack.currency',       'payments.paystack.currency',       'payments', 'Paystack currency',       false, false],
            ['paystack.secret_key',     'payments.paystack.secret_key',     'payments', 'Paystack secret key',     true,  true],
            ['paystack.webhook_secret', 'payments.paystack.webhook_secret', 'payments', 'Paystack webhook secret', true,  true],
        ];

        foreach ($map as [$path, $key, $group, $label, $isSecret, $skipWhenEmpty]) {
            $value = data_get($data, $path);

            if ($skipWhenEmpty && (is_string($value) && $value === '')) {
                continue;
            }

            SiteSetting::put($key, $value, [
                'group' => $group,
                'label' => $label,
                'is_secret' => $isSecret,
            ]);
        }

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('Integration changes may take up to a minute to propagate across workers.')
            ->send();
    }
}
