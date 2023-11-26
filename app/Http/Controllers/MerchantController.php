<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {
    }

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $order_stats = Order::whereBetween('created_at', [$request->from, $request->to]);
        $affiliate_check = Order::where('affiliate_id', null)->first();

        return response()->json([
            'count' => $order_stats->count(),
            'revenue' => $order_stats->sum('subtotal'),
            'commissions_owed' => $order_stats->sum('commission_owed') -  $affiliate_check->commission_owed
        ]);
    }
}
