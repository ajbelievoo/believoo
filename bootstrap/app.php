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

        // Trust Cloudflare's published edge ranges so $request->ip() resolves
        // the real client IP (rate limits, BlockIp, audit logs were keyed on
        // the shared edge IP otherwise). https://www.cloudflare.com/ips
        $middleware->trustProxies(at: [
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22',
            '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
            '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22',
            '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32',
            '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29',
            '2c0f:f248::/32',
        ]);

        // Exclude payment webhooks + external service webhooks from CSRF
        $middleware->validateCsrfTokens(except: [
            'payment/cashfree/webhook',
            'payment/razorpay/webhook',
            'payment/paypal/webhook',
            'payment/stripe/webhook',
            'payment/payu/callback',
            'webhook/telegram',
            'webhook/whatsapp',
            'webhook/meta',
            'webhook/inbound-email',
            'webhook/email-ticket',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API consumers (mobile agent, scripts) don't always send Accept:
        // application/json — never serve them HTML error pages.
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $e) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
