<?php

namespace App\Http\Controllers\Site;

use App\Enums\ShopRunStatus;
use App\Http\Controllers\Controller;
use App\Models\ShopRun;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(): View
    {
        $runs = ShopRun::query()
            ->where('status', '!=', ShopRunStatus::Draft->value)
            ->where(function ($q): void {
                $q->whereIn('status', [ShopRunStatus::Open->value, ShopRunStatus::Closed->value])
                    ->orWhere(function ($q2): void {
                        $q2->where('status', ShopRunStatus::Preview->value)
                            ->where('preview_visible', true);
                    });
            })
            ->orderByDesc('orders_open_at')
            ->orderByDesc('id')
            ->with(['activeProducts'])
            ->get();

        $featured = $runs->first();

        return view('site.shop.index', [
            'runs' => $runs,
            'featured' => $featured,
        ]);
    }

    public function show(ShopRun $run): View
    {
        abort_unless($run->catalogVisibleToPublic(), 404);

        $products = $run->activeProducts()->orderBy('sort_order')->get();

        return view('site.shop.show', [
            'run' => $run,
            'products' => $products,
            'acceptingOrders' => $run->isAcceptingOrders(),
        ]);
    }
}
