<?php

namespace App\Policies;

use App\Models\ShopProduct;
use App\Models\User;

class ShopProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shop.products.manage');
    }

    public function view(User $user, ShopProduct $shopProduct): bool
    {
        return $user->can('shop.products.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('shop.products.manage');
    }

    public function update(User $user, ShopProduct $shopProduct): bool
    {
        return $user->can('shop.products.manage');
    }

    public function delete(User $user, ShopProduct $shopProduct): bool
    {
        return $user->can('shop.products.manage');
    }
}
