<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $amount = '₹' . number_format($order->amount, 2);

        return (new MailMessage)
            ->subject('Payment Confirmed - Order ' . $order->order_number)
            ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
            ->line('We have received your payment. Here are your order details:')
            ->line('Order Number: **' . $order->order_number . '**')
            ->line('Service: ' . $order->service_name)
            ->line('Amount Paid: **' . $amount . '**')
            ->line('Payment Method: ' . ucfirst($order->payment_gateway ?? 'online'))
            ->line('Paid At: ' . optional($order->paid_at)->format('d M Y, h:i A'))
            ->line('Your service is being provisioned and will be activated shortly. You will receive another notification once it is ready.')
            ->action('View My Orders', url('/client/orders'))
            ->line('If you did not make this payment, please contact support immediately.');
    }
}
