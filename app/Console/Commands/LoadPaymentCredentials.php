<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class LoadPaymentCredentials extends Command
{
    protected $signature = 'payment:load-credentials';
    protected $description = 'Load PayU and Razorpay credentials from /www/wwwroot/.payment.env';

    public function handle(): void
    {
        $path = env('PAYMENT_ENV_FILE', '/www/wwwroot/.payment.env');

        if (!file_exists($path) || !is_readable($path)) {
            $this->error('Payment credentials file not found: '.$path);
            return;
        }

        $values = parse_ini_file($path, false, INI_SCANNER_RAW);
        if ($values === false) {
            $this->error('Failed to parse payment credentials file.');
            return;
        }

        $map = [
            'payu_key' => $values['PAYU_KEY'] ?? '',
            'payu_salt' => $values['PAYU_SALT'] ?? '',
            'payu_mode' => $values['PAYU_MODE'] ?? 'live',
            'payu_enabled' => '1',
            'razorpay_key_id' => $values['RAZORPAY_KEY_ID'] ?? '',
            'razorpay_key_secret' => $values['RAZORPAY_KEY_SECRET'] ?? '',
            'razorpay_mode' => $values['RAZORPAY_MODE'] ?? 'test',
            'razorpay_enabled' => '1',
        ];

        foreach ($map as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => 'textarea',
                    'group' => 'Payment',
                ]
            );
        }

        $this->info('Payment credentials loaded successfully.');
    }
}
