<?php

namespace App\Invoices;

use App\Enums\InvoiceType;
use Illuminate\Support\Facades\URL;

final class InvoiceUrl
{
    public static function portal(InvoiceType $type, int $id): string
    {
        return route('portal.invoices.show', ['type' => $type, 'id' => $id]);
    }

    public static function signed(InvoiceType $type, int $id): string
    {
        return URL::signedRoute('invoices.show', ['type' => $type, 'id' => $id]);
    }
}
