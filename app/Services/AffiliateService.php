<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    )
    {
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param Merchant $merchant
     * @param string $email
     * @param string $name
     * @param float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        $discount_code = $this->apiService->createDiscountCode($merchant);
        $affiliate = Affiliate::where('merchant_id', $merchant->id)->first();
        if ($affiliate) :
            if ($affiliate->user->email == $email) :
                throw new AffiliateCreateException();
            else :
                Mail::to($email)->send(new AffiliateCreated($affiliate));
                return $affiliate;
            endif;
        endif;

        if ($merchant->user->email == $email):
            throw new AffiliateCreateException();
        else:
            $user = User::where('email', $email)->first();
            if (!$user):
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'type' => User::TYPE_AFFILIATE,
                ]);
            endif;
            $affiliate = Affiliate::create([
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
                'commission_rate' => $commissionRate,
                'discount_code' => $discount_code['code']
            ]);
            if ($affiliate):
                Mail::to($email)->send(new AffiliateCreated($affiliate));
            endif;
            return $affiliate;
        endif;
    }
}
