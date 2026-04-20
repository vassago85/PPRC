<?php

namespace App\Filament\Admin\Resources\SaprfShooters\Pages;

use App\Filament\Admin\Resources\SaprfShooters\SaprfShooterResource;
use App\Models\SaprfShooter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListSaprfShooters extends ListRecords
{
    protected static string $resource = SaprfShooterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->schema([
                    FileUpload::make('file')
                        ->label('CSV file')
                        ->acceptedFileTypes(['text/csv', 'text/plain'])
                        ->disk('local')
                        ->directory('imports/saprf')
                        ->required()
                        ->helperText('Expected columns: membership_number, first_name, last_name, email, verified_on (YYYY-MM-DD). Header row required.'),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    $imported = 0;
                    $updated = 0;
                    $errors = 0;

                    if (($handle = fopen($path, 'r')) === false) {
                        Notification::make()->title('Could not read file')->danger()->send();

                        return;
                    }

                    $headers = array_map('strtolower', array_map('trim', (array) fgetcsv($handle)));
                    while (($row = fgetcsv($handle)) !== false) {
                        $row = array_combine($headers, $row);
                        $number = trim((string) ($row['membership_number'] ?? ''));
                        if ($number === '') {
                            $errors++;

                            continue;
                        }

                        $existed = SaprfShooter::query()->where('membership_number', $number)->exists();
                        SaprfShooter::updateOrCreate(
                            ['membership_number' => $number],
                            [
                                'first_name' => $row['first_name'] ?? null,
                                'last_name' => $row['last_name'] ?? null,
                                'email' => $row['email'] ?? null,
                                'verified_on' => ($row['verified_on'] ?? null) ?: null,
                                'imported_by_user_id' => auth()->id(),
                                'imported_at' => now(),
                            ],
                        );

                        $existed ? $updated++ : $imported++;
                    }
                    fclose($handle);

                    Storage::disk('local')->delete($data['file']);

                    Notification::make()
                        ->title('SAPRF whitelist updated')
                        ->body("{$imported} added, {$updated} updated, {$errors} skipped.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
