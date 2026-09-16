<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'team.permission' => \App\Http\Middleware\TeamPermission::class,
            'check.phone' => \App\Http\Middleware\CheckPhoneNumber::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'agent' => \App\Http\Middleware\AgentAuth::class,
            'api.key' => \App\Http\Middleware\ApiKeyAuth::class,
            'currency' => \App\Http\Middleware\CurrencyMiddleware::class,
            'block.ip' => \App\Http\Middleware\BlockIp::class,
            'bconnect' => \App\Http\Middleware\BconnectAuth::class,
            'bconnect.role' => \App\Http\Middleware\BconnectRole::class,
            'bconnect.permission' => \App\Http\Middleware\BconnectPermission::class,
            'bconnect.audit' => \App\Http\Middleware\BconnectAudit::class,
            '2fa' => \App\Http\Middleware\RequireTwoFactor::class,
            'log.admin' => \App\Http\Middleware\LogAdminActions::class,
        ]);

        // API rate limiting
        $middleware->throttleApi('60,1');

        // Currency detection middleware for web routes + IP blocking
        $middleware->web(append: [
            \App\Http\Middleware\CurrencyMiddleware::class,
            \App\Http\Middleware\BlockIp::class,
            \App\Http\Middleware\ResellerMiddleware::class,
        ]);

        // Exclude payment webhooks + external service webhooks from CSRF
        $middleware->validateCsrfTokens(except: [
            'payment/cashfree/webhook',
            'payment/razorpay/webhook',
            'payment/paypal/webhook',
            'payment/payu/callback',
            'webhook/telegram',
            'webhook/whatsapp',
            'webhook/meta',
            'webhook/inbound-email',
            'webhook/email-ticket',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
