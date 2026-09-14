<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VpsServerNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $event,
        private array  $data = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return match ($this->event) {

            'vm_created' => [
                'title' => '🚀 Your VPS is Ready!',
                'body'  => 'VM #' . ($this->data['vmid'] ?? '') . ' (' . ($this->data['plan'] ?? 'VPS') . ') has been created and is now running.',
                'icon'  => 'fa-server',
                'color' => 'green',
                'tab'   => 'hosting',
            ],

            'vm_started' => [
                'title' => '▶️ Server Started',
                'body'  => 'Your server ' . ($this->data['hostname'] ?? 'VPS') . ' is now running.',
                'icon'  => 'fa-play-circle',
                'color' => 'green',
                'tab'   => 'hosting',
            ],

            'vm_stopped' => [
                'title' => '⏹️ Server Stopped',
                'body'  => 'Your server ' . ($this->data['hostname'] ?? 'VPS') . ' has been stopped.',
                'icon'  => 'fa-stop-circle',
                'color' => 'yellow',
                'tab'   => 'hosting',
            ],

            'vm_restarted' => [
                'title' => '🔄 Server Restarted',
                'body'  => 'Your server ' . ($this->data['hostname'] ?? 'VPS') . ' has been restarted successfully.',
                'icon'  => 'fa-sync-alt',
                'color' => 'blue',
                'tab'   => 'hosting',
            ],

            'password_reset' => [
                'title' => '🔑 Root Password Reset',
                'body'  => 'Your root password for ' . ($this->data['hostname'] ?? 'VPS') . ' has been reset. Check your email for the new password.',
                'icon'  => 'fa-key',
                'color' => 'yellow',
                'tab'   => 'hosting',
            ],

            'vm_rebuilt' => [
                'title' => '🔨 Server Rebuild Complete',
                'body'  => 'Your server ' . ($this->data['hostname'] ?? 'VPS') . ' has been rebuilt with a fresh OS installation.',
                'icon'  => 'fa-hammer',
                'color' => 'orange',
                'tab'   => 'hosting',
            ],

            'vm_reinstalled' => [
                'title' => '💿 OS Reinstall Complete',
                'body'  => 'OS reinstallation for ' . ($this->data['hostname'] ?? 'VPS') . ' is complete. Server is ready.',
                'icon'  => 'fa-compact-disc',
                'color' => 'blue',
                'tab'   => 'hosting',
            ],

            'vm_provisioning' => [
                'title' => '⚙️ Server Setup Started',
                'body'  => 'Your ' . ($this->data['plan'] ?? 'VPS') . ' is being set up. This takes ~15 minutes.',
                'icon'  => 'fa-cog',
                'color' => 'blue',
                'tab'   => 'hosting',
            ],

            'payment_success' => [
                'title' => '✅ Payment Successful',
                'body'  => 'Payment of ₹' . ($this->data['amount'] ?? '') . ' for ' . ($this->data['plan'] ?? 'VPS') . ' confirmed. Server setup started.',
                'icon'  => 'fa-check-circle',
                'color' => 'green',
                'tab'   => 'hosting',
            ],

            'vm_migration_started' => [
                'title' => '🔀 Migration Started',
                'body'  => 'Your server is being migrated to ' . ($this->data['target_node'] ?? 'new node') . '. Brief interruption expected.',
                'icon'  => 'fa-exchange-alt',
                'color' => 'blue',
                'tab'   => 'hosting',
            ],

            'vm_migration_complete' => [
                'title' => '✅ Migration Complete',
                'body'  => 'Your server has been successfully migrated to ' . ($this->data['target_node'] ?? 'new node') . '.',
                'icon'  => 'fa-check-circle',
                'color' => 'green',
                'tab'   => 'hosting',
            ],

            default => [
                'title' => 'Server Update',
                'body'  => $this->data['message'] ?? 'Your server status has been updated.',
                'icon'  => 'fa-server',
                'color' => 'blue',
                'tab'   => 'hosting',
            ],
        };
    }
}
