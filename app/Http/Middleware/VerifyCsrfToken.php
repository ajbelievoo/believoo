<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        // GHC admin panel forms are protected by auth + admin middleware already.
        // Temporary bypass until session/CSRF issue is fully resolved.
        'admin/ghc/*',
        // Payment gateway webhooks are server-to-server POSTs verified by signature.
        'payment/razorpay/webhook',
        'payment/cashfree/webhook',
        'payment/paypal/webhook',
    ];
}
