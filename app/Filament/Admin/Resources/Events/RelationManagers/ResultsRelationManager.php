<?php

namespace App\Filament\Admin\Resources\Events\RelationManagers;

use App\Models\Event;
use App\Models\EventResult;
use App\Services\Events\EventResultsCsvImporter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'results';

    protected static ?string $title = 'Results';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('shooter_name')->required()->maxLength(150),
                Select::make('member_id')
                    ->label('Member')
                    ->relationship('member', 'membership_number')
                    ->searchable(['first_name', 'last_name', 'membership_number'])
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->first_name ?? '').' '.($record->last_name ?? ''))
                        .($record->membership_number ? " ({$record->membership_number})" : ''))
                    ->preload(),
                TextInput::make('division')->maxLength(80),
                TextInput::make('class')->maxLength(80),
                TextInput::make('rank')->numeric(),
                TextInput::make('score_hits')->numeric()->label('Hits'),
                TextInput::make('score_possible')->numeric()->label('Possible'),
                TextInput::make('score_points')->numeric()->label('Points'),
                TextInput::make('score_percentage')->numeric()->label('Percentage'),
                TextInput::make('score_time_ms')->numeric()->label('Time (ms)'),
                Toggle::make('dnf')->label('DNF')->inline(false),
                Toggle::make('dq')->label('DQ')->inline(false),
                Textarea::make('notes')->rows(2)->columnSpanFull(),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('rank')
            ->columns([
                TextColumn::make('rank')->sortable(),
                TextColumn::make('shooter_name')->searchable()->label('Shooter'),
                TextColumn::make('division')->badge()->toggleable()->searchable(),
                TextColumn::make('class')->badge()->toggleable(),
                TextColumn::make('score_display')
                    ->label('Score')
                    ->state(fn (EventResult $r) => $r->displayScore()),
                TextColumn::make('score_percentage')->label('%')->numeric(2)->toggleable(),
                TextColumn::make('member.membership_number')->label('PPRC #')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('division')
                    ->options(fn () => EventResult::query()
                        ->whereNotNull('division')
                        ->distinct()
                        ->pluck('division', 'division')
                        ->all()),
            ])
            ->headerActions([
                Action::make('upload_csv')
                    ->label('Upload CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->visible(fn () => auth()->user()?->can('results.manage'))
                    ->modalHeading('Upload results CSV')
                    ->modalDescription('Columns: rank, shooter_name, division, class, member_id, member_email, membership_number, hits, possible, points, percentage, time_ms, dnf, dq, notes. Extra columns are ignored.')
                    ->schema([
                        FileUpload::make('file')
                            ->label('CSV file')
                            ->disk('local')
                            ->directory('imports/results')
                            ->acceptedFileTypes(['text/csv', 'application/csv', 'application/vnd.ms-excel', 'text/plain'])
                            ->required(),
                        Toggle::make('replace')
                            ->label('Replace existing results for this match')
                            ->helperText('If on, all current results for this match are deleted before the CSV is imported.')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        /** @var Event $event */
                        $event = $this->getOwnerRecord();

                        $storagePath = $data['file'];
                        $absolute = Storage::disk('local')->path($storagePath);

                        $report = app(EventResultsCsvImporter::class)
                            ->import($event, $absolute, (bool) ($data['replace'] ?? false));

                        Storage::disk('local')->delete($storagePath);

                        $summary = "Created: {$report['created']} · Updated: {$report['updated']}";
                        if (! empty($report['errors'])) {
                            $summary .= ' · Errors: '.count($report['errors']);
                        }

                        Notification::make()
                            ->title('Results imported')
                            ->body($summary.(empty($report['errors']) ? '' : "\n".implode("\n", array_slice($report['errors'], 0, 5))))
                            ->success()
                            ->send();
                    }),
                Action::make('publish_results')
                    ->label('Publish results')
                    ->icon('heroicon-o-megaphone')
                    ->color('success')
                    ->visible(function () {
                        /** @var Event $event */
                        $event = $this->getOwnerRecord();

                        return auth()->user()?->can('results.publish')
                            && $event->results_published_at === null
                            && $event->results()->exists();
                    })
                    ->requiresConfirmation()
                    ->action(function () {
                        /** @var Event $event */
                        $event = $this->getOwnerRecord();
                        $event->update(['results_published_at' => now()]);

                        Notification::make()
                            ->title('Results published')
                            ->body('Results are now visible on the public site.')
                            ->success()
                            ->send();
                    }),
                CreateAction::make()
                    ->visible(fn () => auth()->user()?->can('results.manage')),
            ])
            ->recordActions([
                EditAction::make()->visible(fn () => auth()->user()?->can('results.manage')),
                DeleteAction::make()->visible(fn () => auth()->user()?->can('results.manage')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('results.manage')),
                ]),
            ]);
    }
}
