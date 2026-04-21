<?php

namespace App\Policies;

use App\Models\ShopRun;
use App\Models\User;

class ShopRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shop.products.manage');
    }

    public function view(User $user, ShopRun $shopRun): bool
    {
        return $user->can('shop.products.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('shop.products.manage');
    }

    public function update(User $user, ShopRun $shopRun): bool
    {
        return $user->can('shop.products.manage');
    }

    public function delete(User $user, ShopRun $shopRun): bool
    {
        return $user->can('shop.products.manage');
    }

    public function notifyWaitlist(User $user, ShopRun $shopRun): bool
    {
        return $user->can('shop.products.manage');
    }
}
