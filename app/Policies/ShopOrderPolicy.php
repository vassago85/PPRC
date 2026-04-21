<?php

namespace App\Policies;

use App\Models\ShopOrder;
use App\Models\User;

class ShopOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shop.orders.view');
    }

    public function view(User $user, ShopOrder $shopOrder): bool
    {
        return $user->can('shop.orders.view');
    }

    public function update(User $user, ShopOrder $shopOrder): bool
    {
        return $user->can('shop.orders.manage');
    }
}
