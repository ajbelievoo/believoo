<?php

namespace App\Models;

use App\Services\HostingProvisioningService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'order_number',
        'service_name',
        'tier_name',
        'billing_months',
        'subtotal',
        'gst_amount',
        'gst_rate',
        'amount',
        'currency',
        'status',
        'payment_gateway',
        'payment_id',
        'transaction_id',
        'payment_response',
        'paid_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'metadata' => 'array',
        'gst_rate' => 'decimal:2',
        'paid_at' => 'datetime',
        'payment_response' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function hosting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserHosting::class);
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getTimelineAttribute(): array
    {
        $steps = [];
        $steps[] = [
            'status' => 'completed',
            'label' => 'Order Placed',
            'time' => $this->created_at,
            'desc' => 'Your order ' . $this->order_number . ' was received.',
        ];

        if ($this->isPaid()) {
            $steps[] = [
                'status' => 'completed',
                'label' => 'Payment Confirmed',
                'time' => $this->paid_at,
                'desc' => 'Payment received via ' . ucfirst($this->payment_gateway ?? 'online') . '.',
            ];

            $hosting = $this->hosting;
            if ($hosting) {
                $steps[] = [
                    'status' => 'completed',
                    'label' => 'Provisioned',
                    'time' => $hosting->created_at ?? $this->paid_at,
                    'desc' => 'Service ' . ($hosting->plan_name ?? $this->service_name) . ' is being set up.',
                ];

                if ($hosting->status === 'active' && ($hosting->expiry_date ? $hosting->expiry_date->isFuture() : true)) {
                    $steps[] = [
                        'status' => 'completed',
                        'label' => 'Active',
                        'time' => $hosting->start_date ?? $hosting->created_at,
                        'desc' => 'Your service is ready. Login details are available in the dashboard.',
                    ];
                } else {
                    $steps[] = [
                        'status' => 'current',
                        'label' => 'Activating',
                        'time' => null,
                        'desc' => 'Final activation is in progress.',
                    ];
                }
            } else {
                $steps[] = [
                    'status' => 'current',
                    'label' => 'Provisioning',
                    'time' => null,
                    'desc' => 'Service is being provisioned. You will receive an email when ready.',
                ];
            }
        } elseif (in_array($this->status, ['failed', 'cancelled', 'refunded'])) {
            $steps[] = [
                'status' => 'failed',
                'label' => ucfirst($this->status),
                'time' => $this->updated_at,
                'desc' => 'This order is ' . $this->status . '. Contact support if you need help.',
            ];
        } else {
            $steps[] = [
                'status' => 'current',
                'label' => 'Payment Pending',
                'time' => null,
                'desc' => 'Complete payment to continue with provisioning.',
            ];
        }

        return $steps;
    }

    public function getTotalAmount(): float
    {
        return floatval($this->amount);
    }

    public function getSubtotalAmount(): float
    {
        return floatval($this->subtotal ?? $this->amount / 1.18);
    }

    public function getGstAmount(): float
    {
        return floatval($this->gst_amount ?? $this->amount - $this->getSubtotalAmount());
    }

    public function markAsPaid(string $gateway, string $paymentId, ?string $transactionId = null, array $response = []): void
    {
        // Use DB transaction with lock to prevent race condition
        // between webhook and callback both calling markAsPaid simultaneously
        \Illuminate\Support\Facades\DB::transaction(function () use ($gateway, $paymentId, $transactionId, $response) {
            // Re-fetch with lock to check current status
            $fresh = self::lockForUpdate()->find($this->id);
            
            if (!$fresh || $fresh->isPaid()) {
                // Already paid by another request - skip
                \Illuminate\Support\Facades\Log::info('Order already paid, skipping duplicate markAsPaid', [
                    'order_id' => $this->id,
                    'gateway'  => $gateway,
                ]);
                return;
            }

            $fresh->update([
                'status'           => 'paid',
                'payment_gateway'  => $gateway,
                'payment_id'       => $paymentId,
                'transaction_id'   => $transactionId,
                'payment_response' => $response,
                'paid_at'          => now(),
            ]);

            // Refresh this instance
            $this->fill($fresh->fresh()->toArray());
        });

        // Provision outside transaction (can be slow)
        if ($this->fresh()->isPaid()) {
            $this->provisionHosting();
            $this->processStreamingAddon();
            $this->processReferralReward();
            $this->sendPaymentAlerts();
        }
    }

    protected function sendPaymentAlerts(): void
    {
        $user = $this->user;
        if (!$user) {
            return;
        }

        try {
            $user->notify(new \App\Notifications\OrderPaidNotification($this));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Order paid mail failed', [
                'order_id' => $this->id,
                'error'    => $e->getMessage(),
            ]);
        }

        if (!$user->phone) {
            return;
        }

        $message = 'Hi ' . $user->name . ', your order ' . $this->order_number . ' for ' . $this->service_name . ' has been confirmed. Amount paid: ₹' . number_format($this->amount, 2) . '. -Believoo';

        \App\Services\SmsService::send($user->phone, $message);
        \App\Services\WhatsAppService::send($user->phone, $message);
    }

    protected function processReferralReward(): void
    {
        $user = $this->user;
        if (!$user || !$user->referred_by) {
            return;
        }

        $referral = \App\Models\Referral::where('referred_id', $user->id)
            ->where('status', 'registered')
            ->first();

        if ($referral) {
            $referral->markAsConverted();
            $referral->markAsRewarded();
        }
    }

    /**
     * Provision hosting for this order
     */
    public function provisionHosting(): ?UserHosting
    {
        $service = app(HostingProvisioningService::class);
        return $service->provisionFromOrder($this);
    }

    /**
     * Activate streaming addon on existing VPS hosting if order metadata has context
     */
    public function processStreamingAddon(): void
    {
        $metadata = $this->metadata ?? [];
        $context = $metadata['streaming_addon_context'] ?? null;

        if (!$context || empty($context['hosting_id'])) {
            return;
        }

        try {
            $hosting = \App\Models\UserHosting::find($context['hosting_id']);
            if (!$hosting || $hosting->user_id !== $this->user_id) {
                \Illuminate\Support\Facades\Log::warning('Streaming addon: hosting not found or not owned by user', [
                    'order_id' => $this->id,
                    'hosting_id' => $context['hosting_id'] ?? null,
                ]);
                return;
            }

            // Activate streaming addon on hosting
            $hosting->update(['has_streaming_addon' => true]);

            // Get or create a streaming plan for this addon
            $plan = \App\Models\StreamingPlan::firstOrCreate(
                ['is_addon' => true, 'delivery_method' => 'vps_embedded'],
                [
                    'name' => 'VPS Streaming Addon',
                    'slug' => 'vps-streaming-addon',
                    'description' => 'Live streaming addon for VPS',
                    'max_viewers' => 1000,
                    'max_bitrate' => 8000,
                    'max_streams' => 10,
                    'price' => 0,
                    'is_addon' => true,
                    'addon_price' => $context['monthly_addon_price'] ?? 0,
                    'is_active' => true,
                    'delivery_method' => 'vps_embedded',
                ]
            );

            // Create streaming API keys for this addon
            $apiService = app(\App\Services\StreamingApiService::class);
            $apiKey = $apiService->attachToHosting($hosting, $plan, $this);

            \Illuminate\Support\Facades\Log::info('Streaming addon activated for VPS hosting', [
                'order_id' => $this->id,
                'hosting_id' => $hosting->id,
                'api_key_id' => $apiKey?->id,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to activate streaming addon', [
                'order_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
