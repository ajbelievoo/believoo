<?php

namespace App\Jobs;

use App\Mail\CampaignMail;
use App\Models\CampaignRecipient;
use App\Models\EmailCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCampaignEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 1800;

    public function __construct(public EmailCampaign $campaign)
    {
    }

    public function handle(): void
    {
        if ($this->campaign->status !== 'scheduled' && $this->campaign->status !== 'draft') {
            return;
        }

        $this->campaign->update(['status' => 'sending']);

        $recipients = $this->buildRecipientList();

        foreach ($recipients as $email => $data) {
            CampaignRecipient::firstOrCreate(
                [
                    'campaign_id' => $this->campaign->id,
                    'recipient_type' => $data['type'],
                    'recipient_id' => $data['id'],
                ],
                [
                    'email' => $email,
                    'status' => 'pending',
                ]
            );
        }

        $pending = $this->campaign->recipients()->whereIn('status', ['pending', 'bounced'])->cursor();

        foreach ($pending as $recipient) {
            try {
                $html = $this->injectTracking($recipient, $this->campaign->content_html ?? '');
                $unsubscribeUrl = $this->unsubscribeUrlFor($recipient);

                $mailable = new CampaignMail(
                    $this->campaign->subject,
                    $html,
                    $unsubscribeUrl,
                    $this->campaign->from_name,
                    $this->campaign->from_email
                );

                Mail::to($recipient->email)->send($mailable);

                $recipient->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);
                $this->campaign->increment('sent_count');
            } catch (\Throwable $e) {
                $recipient->update(['status' => 'bounced', 'error_message' => $e->getMessage()]);
            }
        }

        $this->campaign->update(['status' => 'sent', 'sent_at' => now()]);
    }

    protected function buildRecipientList(): array
    {
        $list = [];

        if (!empty($this->campaign->test_email)) {
            $list[$this->campaign->test_email] = ['type' => '', 'id' => 0];
            return $list;
        }

        $segment = $this->campaign->segment;

        if (in_array($segment, ['all', 'newsletter', 'active'])) {
            NewsletterSubscriber::active()->select(['id', 'email', 'name'])->cursor()->each(function ($s) use (&$list) {
                $list[$s->email] = ['type' => NewsletterSubscriber::class, 'id' => $s->id];
            });
        }

        if (in_array($segment, ['all', 'clients', 'active'])) {
            User::when($segment === 'active', fn ($q) => $q->where('is_suspended', false))
                ->select(['id', 'email', 'name'])
                ->cursor()
                ->each(function ($u) use (&$list) {
                    $list[$u->email] = ['type' => User::class, 'id' => $u->id];
                });
        }

        return $list;
    }

    protected function unsubscribeUrlFor(CampaignRecipient $recipient): ?string
    {
        if ($recipient->recipient_type === NewsletterSubscriber::class && $recipient->recipient) {
            return route('newsletter.unsubscribe', $recipient->recipient->unsubscribe_token);
        }

        return null;
    }

    protected function injectTracking(CampaignRecipient $recipient, string $html): string
    {
        $openPixel = route('campaign.pixel', $recipient->open_token);
        $pixel = '<img src="' . e($openPixel) . '" width="1" height="1" alt="" style="display:block;height:1px;width:1px;" />';

        if (str_contains($html, '</body>')) {
            $html = str_replace('</body>', $pixel . '</body>', $html);
        } else {
            $html .= $pixel;
        }

        $html = preg_replace_callback(
            '/<a\s+([^>]*?)href=["\']([^"\']+)["\']([^>]*)>(.*?)<\/a>/i',
            function ($matches) use ($recipient) {
                $url = $matches[2];
                if (str_starts_with($url, 'mailto:') || str_starts_with($url, '#')) {
                    return $matches[0];
                }
                $tracked = route('campaign.click', $recipient->click_token) . '?url=' . urlencode($url);
                return '<a ' . $matches[1] . 'href="' . $tracked . '"' . $matches[3] . '>' . $matches[4] . '</a>';
            },
            $html
        );

        return $html;
    }
}
