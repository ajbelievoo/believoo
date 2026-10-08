<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-renew subscriptions via wallet deduction
// Runs daily at 5 AM to renew before suspension check
Schedule::command('billing:auto-renew')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/billing-auto-renew.log'));

// Auto-suspend overdue users (Hostinger-style billing)
// Runs daily at 6 AM to check and suspend non-paying customers
Schedule::command('billing:auto-suspend')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/billing-suspension.log'));

// Auto-generate invoices for upcoming renewals (3 days before due date)
Schedule::call(function () {
    \App\Services\BillingService::generateUpcomingInvoices();
})->dailyAt('01:00')
    ->name('generate-upcoming-invoices')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/billing-invoices.log'));

// ================= SELF-HEALING: SERVER HEALTH MONITORING =================
// Run health checks every minute
Schedule::command('monitor:health --once')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/health-monitor.log'));

// Send license expiry reminders (7 days before)
Schedule::command('licenses:reminders --days=7')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Send license expiry reminders (3 days before - urgent)
Schedule::command('licenses:reminders --days=3')
    ->dailyAt('09:30')
    ->withoutOverlapping();

// Send license expiry reminders (1 day before - critical)
Schedule::command('licenses:reminders --days=1')
    ->dailyAt('10:00')
    ->withoutOverlapping();

// Hosting/service renewal email reminders (15/7/3/1 days before expiry)
Schedule::command('hosting:renewal-reminders --days=15')
    ->dailyAt('09:00')
    ->withoutOverlapping();
Schedule::command('hosting:renewal-reminders --days=7')
    ->dailyAt('09:15')
    ->withoutOverlapping();
Schedule::command('hosting:renewal-reminders --days=3')
    ->dailyAt('09:30')
    ->withoutOverlapping();
Schedule::command('hosting:renewal-reminders --days=1')
    ->dailyAt('09:45')
    ->withoutOverlapping();

// Daily node resource check report
Schedule::call(function () {
    $monitor = app(\App\Services\HealthMonitorService::class);
    $summary = $monitor->getHealthSummary();
    
    \Illuminate\Support\Facades\Log::info('Daily Health Summary', $summary);
})->dailyAt('08:00')
    ->name('daily-health-summary');

// ================= CURRENCY EXCHANGE RATE UPDATES =================
// Fetch latest exchange rates every hour
Schedule::command('currency:fetch-rates')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/exchange-rates.log'));

// ================= DATACENTER AUTO-SYNC =================
// Sync Proxmox nodes every 5 minutes for real-time stats
Schedule::command('datacenter:sync --all')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/datacenter-sync.log'));

// ================= DNS AUTO-SYNC =================
// Sync all BelieVoo DNS zones to PowerDNS every 5 minutes
// This ensures any manual DB changes are always reflected
Schedule::command('dns:sync-zones')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/dns-sync.log'));

// ================= STREAM RECORDING AUTO-DELETE =================
// Auto-delete stream recordings older than 30 days, runs daily at midnight
Schedule::command('stream:cleanup-recordings')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/stream-recordings.log'));

// ================= OVH RESELLER BALANCE MONITORING =================
// Check OVHcloud wallet balance every 6 hours and alert admins when low
Schedule::command('ovh:check-balance --threshold=50')
    ->everySixHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ovh-balance.log'));

// Sync OVH VPS catalog daily at 03:00 so prices stay current
Schedule::command('ovh:sync-products --type=vps')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ovh-sync.log'));

// ================= PAYMENT REMINDER VIA CHAT =================
// Remind clients about due invoices inside the chat widget — daily at 10 AM
Schedule::command('payment:chat-reminder')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/payment-reminder.log'));

// ================= AI CHAT DAILY REPORT =================
// Send AI chat performance summary to admin every evening at 8 PM
Schedule::command('ai:daily-report')
    ->dailyAt('20:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ai-report.log'));

// Send scheduled announcements every minute
Schedule::command('announcement:send-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduled-announcements.log'));

// Evaluate A/B test winners every minute
Schedule::command('announcement:evaluate-ab')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/evaluate-ab-tests.log'));

// B-CONNECT subscription lifecycle: renewal invoices, reminders, grace, downgrade
Schedule::command('bconnect:subscription-lifecycle')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/bconnect-subscription-lifecycle.log'));

// Keep the explicit plan expiry command available for manual runs
Schedule::command('bconnect:plan-expiry')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/bconnect-plan-expiry.log'));
