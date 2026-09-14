<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AiMessage;
use App\Models\AiFeedback;
use App\Models\ChatAssignment;
use App\Models\Ticket;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class AiDailyReport extends Command
{
    protected $signature = 'ai:daily-report';
    protected $description = 'Send daily AI chat performance report to admin';

    public function handle()
    {
        $yesterday = now()->subDay();

        $stats = [
            'questions'    => AiMessage::where('type', 'user')->where('created_at', '>=', $yesterday)->count(),
            'ai_replies'   => AiMessage::where('type', 'ai')->where('created_at', '>=', $yesterday)->count(),
            'tickets'      => Ticket::where('created_at', '>=', $yesterday)->count(),
            'handoffs'     => ChatAssignment::where('assigned_at', '>=', $yesterday)->count(),
            'positive'     => AiFeedback::where('rating', 'positive')->where('created_at', '>=', $yesterday)->count(),
            'negative'     => AiFeedback::where('rating', 'negative')->where('created_at', '>=', $yesterday)->count(),
        ];

        $topWords = AiMessage::where('type', 'user')
            ->where('created_at', '>=', $yesterday)
            ->pluck('message')
            ->flatMap(fn($m) => preg_split('/\s+/', strtolower($m)))
            ->filter(fn($w) => strlen($w) >= 4)
            ->countBy()
            ->sortDesc()
            ->take(8)
            ->keys()
            ->implode(', ');

        $admin = \App\Models\User::where('is_admin', true)->first()
            ?? \App\Models\User::first();

        if ($admin && $admin->email) {
            $body = "AI Support Daily Report — " . now()->format('d M Y') . "\n\n";
            $body .= "Questions answered: {$stats['questions']}\n";
            $body .= "AI replies sent: {$stats['ai_replies']}\n";
            $body .= "Tickets created: {$stats['tickets']}\n";
            $body .= "Human handoffs: {$stats['handoffs']}\n";
            $body .= "Positive feedback: {$stats['positive']} | Negative: {$stats['negative']}\n";
            $body .= "Top topics: {$topWords}\n\n";
            $body .= "View full analytics: " . url('/admin/ai-analytics');

            try {
                Mail::raw($body, function ($m) use ($admin) {
                    $m->to($admin->email)->subject('Believoo AI Daily Report — ' . now()->format('d M Y'));
                });
                $this->info('Report sent to ' . $admin->email);
            } catch (\Exception $e) {
                $this->error('Mail failed: ' . $e->getMessage());
            }
        }
    }
}
