<?php

namespace App\Filament\Admin\Pages;

use App\Enums\MemberStatus;
use App\Mail\ClubBroadcastMail;
use App\Models\Member;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class SendClubBroadcast extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Send broadcast';

    protected static ?string $title = 'Send club broadcast';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('settings.site.manage');
    }

    public function mount(): void
    {
        $this->form->fill([
            'audience' => 'active_members',
            'subject' => '',
            'body' => '',
            'custom_emails' => '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compose')
                    ->description('Sends one email per address. Keep messages short; large attachments should go through your normal mailbox instead.')
                    ->schema([
                        Select::make('audience')
                            ->label('Audience')
                            ->options([
                                'active_members' => 'Active members (member profile status)',
                                'all_members' => 'All members with a login email',
                                'custom' => 'Custom list (emails below)',
                            ])
                            ->required()
                            ->native(false),
                        Textarea::make('custom_emails')
                            ->label('Custom email addresses')
                            ->rows(4)
                            ->helperText('One email per line. Used when audience is Custom.')
                            ->columnSpanFull(),
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->label('Message')
                            ->rows(10)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('send')
                ->footer([
                    Actions::make([
                        Action::make('send')
                            ->label('Send emails')
                            ->submit('send')
                            ->color('primary'),
                    ])
                        ->alignment(Alignment::End),
                ]),
        ]);
    }

    public function send(): void
    {
        $data = $this->form->getState();

        $emails = $this->resolveEmails($data);

        if ($emails === []) {
            Notification::make()
                ->warning()
                ->title('No recipients')
                ->body('Choose an audience with at least one email address, or add custom addresses.')
                ->send();

            return;
        }

        $paragraphs = array_values(array_filter(array_map(
            'trim',
            preg_split("/\r\n|\r|\n/", (string) $data['body']) ?: [],
        )));

        $htmlBody = '<p>'.collect($paragraphs)->map(fn (string $p) => e($p))->implode('</p><p>').'</p>';

        $sent = 0;
        $failed = 0;

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new ClubBroadcastMail((string) $data['subject'], $htmlBody));
                $sent++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        Notification::make()
            ->success()
            ->title('Broadcast finished')
            ->body("Sent {$sent}".($failed ? ", failed {$failed}" : '').'.')
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function resolveEmails(array $data): array
    {
        $audience = (string) ($data['audience'] ?? '');

        if ($audience === 'custom') {
            $raw = (string) ($data['custom_emails'] ?? '');
            $lines = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            return collect($lines)
                ->map(fn (string $e) => strtolower(trim($e)))
                ->filter(fn (string $e) => filter_var($e, FILTER_VALIDATE_EMAIL))
                ->unique()
                ->values()
                ->all();
        }

        $query = Member::query()->whereHas('user', fn ($q) => $q->whereNotNull('email'));

        if ($audience === 'active_members') {
            $query->where('status', MemberStatus::Active);
        }

        return $query
            ->with('user:id,email')
            ->get()
            ->pluck('user.email')
            ->filter()
            ->map(fn (string $e) => strtolower(trim($e)))
            ->unique()
            ->values()
            ->all();
    }
}
