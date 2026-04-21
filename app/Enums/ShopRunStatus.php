<?php

namespace App\Enums;

enum ShopRunStatus: string
{
    case Draft = 'draft';
    case Preview = 'preview';
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Preview => 'Preview',
            self::Open => 'Open for orders',
            self::Closed => 'Closed',
        };
    }
}
