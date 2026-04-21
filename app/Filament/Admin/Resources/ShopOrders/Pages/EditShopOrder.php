<?php

namespace App\Filament\Admin\Resources\ShopOrders\Pages;

use App\Filament\Admin\Resources\ShopOrders\ShopOrderResource;
use App\Models\ShopOrder;
use Filament\Resources\Pages\EditRecord;

class EditShopOrder extends EditRecord
{
    protected static string $resource = ShopOrderResource::class;

    /**
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ShopOrder $record */
        $record = $this->record;
        $record->loadMissing(['run', 'user', 'lines.product']);

        $linesText = $record->lines->isEmpty()
            ? '—'
            : $record->lines->map(function ($line): string {
                $n = $line->product?->name ?? 'Item';

                return "{$n} × {$line->quantity} @ R ".number_format($line->unit_price_cents / 100, 2)
                    .' = R '.number_format($line->line_total_cents / 100, 2);
            })->implode("\n");

        return array_merge($data, [
            'run_display' => $record->run?->title ?? '—',
            'user_display' => $record->user?->name ?? '—',
            'totals_display' => 'Subtotal R '.number_format($record->subtotal_cents / 100, 2)
                .' · Total R '.number_format($record->total_cents / 100, 2),
            'eft_display' => $record->eft_reference ?? '—',
            'proof_display' => $record->proof_path ?? '—',
            'lines_display' => $linesText,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! auth()->user()?->can('shop.orders.manage')) {
            unset($data['status']);
        }

        return $data;
    }
}
