<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        $customer_email = $data['customer_email'];
        $customer_name = $data['customer_name'];
        $sub_total = $data['subtotal_price'];
        $order_id = $data['order_id'];


        $order = Order::where('external_order_id', $order_id)->first();
        if (!$order){
            $user = User::firstOrCreate(['email' => $customer_email, 'name' => $customer_name, 'type' => User::TYPE_AFFILIATE]);
            $merchant = Merchant::firstOrCreate(['domain' => $data['merchant_domain']]);

            $affiliate = $this->affiliateService->register(
                $merchant,
                $customer_email,
                $customer_name,
                $merchant->default_commission_rate
            );

            $affiliate = Affiliate::where('merchant_id', $merchant->id)->first();

            Order::create([
                'merchant_id' => $merchant->id,
                'affiliate_id' => $affiliate->id,
                'subtotal' => $sub_total,
                'commission_owed' => $sub_total * $affiliate->commission_rate,
                'payout_status' => Order::STATUS_PAID,
                'discount_code' => $data['discount_code'],
                'external_order_id' => $order_id
            ]);
        }
    }
}
