<?php

namespace App\Filament\Admin\Resources\ShopOrders\Pages;

use App\Filament\Admin\Resources\ShopOrders\ShopOrderResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Schema;

class EditShopOrder extends EditRecord
{
    protected static string $resource = ShopOrderResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->getRecord()->loadMissing(['run', 'user', 'lines.product']);
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

    public function defaultInfolist(Schema $schema): Schema
    {
        if (! $schema->hasCustomColumns()) {
            $schema->columns(2);
        }

        return $schema->record($this->getRecord());
    }

    public function infolist(Schema $schema): Schema
    {
        return static::getResource()::infolist($schema);
    }

    public function content(Schema $schema): Schema
    {
        if ($this->hasCombinedRelationManagerTabsWithContent()) {
            return $schema
                ->components([
                    $this->getRelationManagersContentComponent(),
                ]);
        }

        return $schema
            ->components([
                $this->getFormContentComponent(),
                $this->getInfolistContentComponent(),
                $this->getRelationManagersContentComponent(),
            ]);
    }

    public function getInfolistContentComponent(): Component
    {
        return EmbeddedSchema::make('infolist');
    }
}
