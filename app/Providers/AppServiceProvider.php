<?php

namespace App\Providers;

use App\Models\Bconnect\BconnectFile;
use App\Models\Bconnect\Invoice;
use App\Models\Bconnect\Meeting;
use App\Models\Bconnect\Member;
use App\Models\Bconnect\Message;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\RemoteSession;
use App\Models\Bconnect\Ticket;
use App\Models\Bconnect\TicketComment;
use App\Guards\ApiKeyGuard;
use App\Services\AuditService;
use App\Services\BconnectMail;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (\Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::all()->pluck('value', 'key')->toArray();

                // Mail Settings
                if (isset($settings['mail_host'])) {
                    $encryption = $settings['mail_encryption'] ?? env('MAIL_ENCRYPTION', 'tls');
                    $port = $settings['mail_port'] ?? env('MAIL_PORT', 587);

                    // Auto-fix encryption for common ports if not explicitly set correctly
                    if ($port == 465 && $encryption === 'tls') {
                        $encryption = 'ssl';
                    }

                    config([
                        'mail.default' => env('MAIL_MAILER', 'smtp'),
                        'mail.mailers.smtp.host' => $settings['mail_host'],
                        'mail.mailers.smtp.port' => $port,
                        'mail.mailers.smtp.encryption' => $encryption,
                        'mail.mailers.smtp.username' => $settings['mail_username'] ?: env('MAIL_USERNAME'),
                        'mail.mailers.smtp.password' => $settings['mail_password'] ?: env('MAIL_PASSWORD'),
                        'mail.from.address' => $settings['mail_from_address'] ?: env('MAIL_FROM_ADDRESS'),
                        'mail.from.name' => $settings['mail_from_name'] ?: config('app.name'),
                        'mail.mailers.smtp.verify_peer' => true,
                        'mail.mailers.smtp.verify_peer_name' => true,
                        'mail.mailers.smtp.allow_self_signed' => false,
                        'mail.mailers.smtp.stream' => [
                            'ssl' => [
                                'allow_self_signed' => false,
                                'verify_peer' => true,
                                'verify_peer_name' => true,
                            ],
                        ],
                    ]);
                }

                // Socialite Google Settings
                 if (isset($settings['google_client_id'])) {
                     config([
                         'services.google.client_id' => $settings['google_client_id'],
                         'services.google.client_secret' => $settings['google_client_secret'] ?? null,
                         'services.google.redirect' => $settings['google_redirect_url'] ?? 'https://believoo.com/auth/google/callback',
                         'services.google.redirect_bconnect' => $settings['google_redirect_bconnect_url'] ?? 'https://bc.believoo.com/auth/google/callback',
                     ]);
                 }

                 // Custom Auth Settings
                 if (isset($settings['enable_registration'])) {
                    config([
                        'auth.registration_enabled' => (bool) $settings['enable_registration'],
                        'auth.email_verification_enabled' => (bool) ($settings['enable_email_verification'] ?? false),
                        'auth.social_login_enabled' => (bool) ($settings['enable_social_login'] ?? true),
                    ]);
                 }

                 // Share currency symbol globally with all views
                 $currencySymbol = $settings['currency_symbol'] ?? '₹';
                 View::share('currencySymbol', $currencySymbol);

                 // Server Management Settings - Load from DB into config
                 if (isset($settings['whmcs_base_url'])) {
                     config([
                         'server-management.whmcs.base_url' => $settings['whmcs_base_url'],
                         'server-management.whmcs.api_identifier' => $settings['whmcs_api_identifier'] ?? null,
                         'server-management.whmcs.api_secret' => $settings['whmcs_api_secret'] ?? null,
                         'server-management.whmcs.cache_ttl' => $settings['whmcs_cache_ttl'] ?? 300,
                     ]);
                 }

                 if (isset($settings['virtualizor_base_url'])) {
                     config([
                         'server-management.virtualizor.base_url' => $settings['virtualizor_base_url'],
                         'server-management.virtualizor.api_key' => $settings['virtualizor_api_key'] ?? null,
                         'server-management.virtualizor.api_pass' => $settings['virtualizor_api_pass'] ?? null,
                         'server-management.virtualizor.port' => $settings['virtualizor_port'] ?? 4085,
                         'server-management.virtualizor.cache_ttl' => $settings['virtualizor_cache_ttl'] ?? 60,
                     ]);
                 }

                 if (isset($settings['server_dashboard_auto_refresh'])) {
                     config([
                         'server-management.dashboard.auto_refresh' => (bool) $settings['server_dashboard_auto_refresh'],
                         'server-management.dashboard.refresh_interval' => $settings['server_dashboard_refresh_interval'] ?? 30,
                     ]);
                 }

                 // B-CONNECT brand & SEO data to all views
                 View::share('bconnectBrand', \App\Helpers\BconnectHelper::brandData());
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AppServiceProvider boot settings error: ' . $e->getMessage());
        }

        // Send a branded welcome email when a new user registers
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Registered::class,
            function (\Illuminate\Auth\Events\Registered $event) {
                try {
                    \Illuminate\Support\Facades\Mail::to($event->user->email)
                        ->send(new \App\Mail\Welcome($event->user));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Welcome email failed: ' . $e->getMessage());
                }
            }
        );

        // Branded email verification mail
        \Illuminate\Auth\Notifications\VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $expire = config('auth.verification.expire', 60);

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->from(config('mail.from.address'), 'Believoo')
                ->replyTo('support@believoo.com', 'Believoo Support')
                ->subject('Please confirm your Believoo email address')
                ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
                ->line('Thanks for signing up! Please confirm that ' . $notifiable->getEmailForVerification() . ' is your email address by clicking the button below.')
                ->action('Verify Email Address', $url)
                ->line('This verification link will expire in ' . $expire . ' minutes.')
                ->line('If you did not create an account, no further action is required.')
                ->salutation('Best regards, The Believoo Team');
        });

        // Branded password reset mail
        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->from(config('mail.from.address'), 'Believoo')
                ->replyTo('support@believoo.com', 'Believoo Support')
                ->subject('Password reset for your Believoo account')
                ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
                ->line('You are receiving this email because we received a password reset request for your account.')
                ->action('Reset Password', $url)
                ->line('This password reset link will expire in ' . $expire . ' minutes.')
                ->line('If you did not request a password reset, no further action is required.')
                ->salutation('Best regards, The Believoo Team');
        });

        // Notify users when their password is changed (via reset or update)
        Event::listen(\Illuminate\Auth\Events\PasswordReset::class, function (\Illuminate\Auth\Events\PasswordReset $event) {
            $host = request()->getHost();
            try {
                if ($host && str_ends_with($host, 'bc.believoo.com')) {
                    \Illuminate\Support\Facades\Mail::to($event->user->email)
                        ->send(new \App\Mail\BconnectPasswordChanged($event->user));
                } else {
                    \Illuminate\Support\Facades\Mail::to($event->user->email)
                        ->send(new \App\Mail\PasswordChanged($event->user));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Password changed email failed: ' . $e->getMessage());
            }
        });

        // Add deliverability headers to all outgoing mail
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $email = $event->message;
            $headers = $email->getHeaders();
            $headers->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN');
            $headers->addTextHeader('Precedence', 'transactional');
            $headers->addTextHeader('X-Transaction-Email', 'yes');

            $from = $email->getFrom()[0] ?? null;
            if ($from && $from->getAddress() === 'support@believoo.com' && $email->getReplyTo() === []) {
                $email->replyTo(new \Symfony\Component\Mime\Address('support@believoo.com', 'Believoo Support'));
            }
        });

        $this->registerBconnectObservers();

        // Audit log: login / logout
        Event::listen(Login::class, function (Login $event) {
            try {
                AuditService::auth('login', $event->user->id, 'User logged in');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Audit login failed: ' . $e->getMessage());
            }
        });

        Event::listen(Logout::class, function (Logout $event) {
            try {
                AuditService::auth('logout', $event->user?->id, 'User logged out');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Audit logout failed: ' . $e->getMessage());
            }
        });

        // Register stateless API key guard for the public REST API.
        Auth::extend('api_key', function ($app, $name, array $config) {
            return new ApiKeyGuard(
                $app['request'],
                Auth::createUserProvider($config['provider'] ?? 'users')
            );
        });
    }

    /**
     * Register B-Connect model event observers to send branded emails
     * for ticket, project, meeting, file, remote, invoice, member and chat events.
     */
    protected function registerBconnectObservers(): void
    {
        try {
            // New ticket
            Ticket::created(function ($ticket) {
                $ticket->load(['reporter.user', 'project']);
                $subject = 'New B-Connect ticket: ' . $ticket->title;
                $lines = [
                    'A new ticket has been created in your workspace.',
                    'Project: ' . ($ticket->project?->name ?? 'N/A'),
                    'Priority: ' . ($ticket->priority ?? 'normal'),
                ];
                $url = route('bconnect.tickets.show', $ticket->id);
                BconnectMail::toMember($ticket->reporter, $subject, 'New B-Connect ticket created', $lines, $url, 'View Ticket');
                BconnectMail::toCompanyAdmins($ticket->company_id, $subject, 'New B-Connect ticket created', $lines, $url, 'View Ticket');
            });

            // New ticket comment
            TicketComment::created(function ($comment) {
                $comment->load(['ticket.reporter.user', 'ticket.assignee.user', 'ticket.project', 'member.user']);
                $ticket = $comment->ticket;
                if (!$ticket) {
                    return;
                }
                $subject = 'New comment on: ' . $ticket->title;
                $lines = [
                    '<strong>' . e($comment->member?->user?->name ?? 'Someone') . '</strong> added a comment:',
                    '"' . e($comment->message) . '"',
                ];
                $url = route('bconnect.tickets.show', $ticket->id);
                if ($ticket->reporter && $ticket->reporter->id != $comment->member_id) {
                    BconnectMail::toMember($ticket->reporter, $subject, 'New comment on your ticket', $lines, $url, 'View Comment');
                }
                if ($ticket->assignee && $ticket->assignee->id != $comment->member_id) {
                    BconnectMail::toMember($ticket->assignee, $subject, 'New comment on assigned ticket', $lines, $url, 'View Comment');
                }
            });

            // Ticket status changed
            Ticket::updated(function ($ticket) {
                if (!$ticket->isDirty('status')) {
                    return;
                }
                $ticket->load(['reporter.user', 'assignee.user', 'project']);
                $subject = 'Ticket status changed: ' . $ticket->title;
                $lines = [
                    'Status changed to: <strong>' . e($ticket->status) . '</strong>',
                    'Project: ' . ($ticket->project?->name ?? 'N/A'),
                ];
                $url = route('bconnect.tickets.show', $ticket->id);
                BconnectMail::toMember($ticket->reporter, $subject, 'Ticket status updated', $lines, $url, 'View Ticket');
                BconnectMail::toMember($ticket->assignee, $subject, 'Ticket status updated', $lines, $url, 'View Ticket');
            });

            // Project created
            Project::created(function ($project) {
                $project->load(['client.user', 'company']);
                $subject = 'New B-Connect project: ' . $project->name;
                $lines = ['A new project has been created in your workspace.'];
                $url = route('bconnect.projects.index');
                BconnectMail::toMember($project->client, $subject, 'New project created', $lines, $url, 'View Project');
                BconnectMail::toCompanyAdmins($project->company_id, $subject, 'New project created', $lines, $url, 'View Project');
            });

            // Project status changed
            Project::updated(function ($project) {
                if (!$project->isDirty('status')) {
                    return;
                }
                $project->load(['client.user', 'company']);
                $subject = 'Project status updated: ' . $project->name;
                $lines = ['Project status is now: <strong>' . e($project->status) . '</strong>'];
                $url = route('bconnect.projects.index');
                BconnectMail::toMember($project->client, $subject, 'Project status updated', $lines, $url, 'View Project');
                BconnectMail::toCompanyAdmins($project->company_id, $subject, 'Project status updated', $lines, $url, 'View Project');
            });

            // Meeting created
            Meeting::created(function ($meeting) {
                $meeting->load(['creator.user', 'project']);
                $subject = 'B-Connect meeting created: ' . $meeting->title;
                $lines = ['A meeting room has been created. Room ID: ' . $meeting->room_id];
                $url = route('bconnect.meeting.room', $meeting->room_id);
                BconnectMail::toMember($meeting->creator, $subject, 'Meeting room created', $lines, $url, 'Join Meeting');
                BconnectMail::toCompanyAdmins($meeting->company_id, $subject, 'Meeting room created', $lines, $url, 'Join Meeting');
            });

            // Meeting ended
            Meeting::updated(function ($meeting) {
                if (!$meeting->isDirty('ended_at') || !$meeting->ended_at) {
                    return;
                }
                $meeting->load(['creator.user']);
                $subject = 'B-Connect meeting ended: ' . $meeting->title;
                $lines = ['The meeting has ended. Summary and action items are available.'];
                $url = route('bconnect.meeting.room', $meeting->room_id);
                BconnectMail::toMember($meeting->creator, $subject, 'Meeting ended', $lines, $url, 'View Meeting');
                BconnectMail::toCompanyAdmins($meeting->company_id, $subject, 'Meeting ended', $lines, $url, 'View Meeting');
            });

            // New file upload
            BconnectFile::created(function ($file) {
                $file->load(['member.user', 'project']);
                $subject = 'New file uploaded to B-Connect';
                $lines = ['A new file has been uploaded: <strong>' . e($file->name ?? basename($file->path ?? 'file')) . '</strong>'];
                $url = route('bconnect.files');
                BconnectMail::toMember($file->member, $subject, 'New file uploaded', $lines, $url, 'View Files');
                if ($file->project) {
                    BconnectMail::toMember($file->project->client, $subject, 'New file uploaded', $lines, $url, 'View Files');
                }
                BconnectMail::toCompanyAdmins($file->company_id, $subject, 'New file uploaded', $lines, $url, 'View Files');
            });

            // Remote session requested
            RemoteSession::created(function ($session) {
                $session->load(['requester.user', 'target.user']);
                $subject = 'New remote control request';
                $lines = [
                    ($session->requester?->user?->name ?? 'Someone') . ' requested remote control of your session.',
                    'Code: ' . $session->session_code,
                ];
                $url = route('bconnect.remote');
                BconnectMail::toMember($session->target, $subject, 'Remote control request', $lines, $url, 'Respond');
                BconnectMail::toMember($session->requester, $subject, 'Remote control request sent', $lines, $url, 'View Request');
            });

            // Remote session status changed
            RemoteSession::updated(function ($session) {
                if (!$session->isDirty('status')) {
                    return;
                }
                $session->load(['requester.user', 'target.user']);
                $subject = 'Remote session status: ' . $session->status;
                $lines = ['Remote session ' . $session->session_code . ' is now <strong>' . e($session->status) . '</strong>.'];
                $url = route('bconnect.remote');
                BconnectMail::toMember($session->requester, $subject, 'Remote session updated', $lines, $url, 'View Session');
                BconnectMail::toMember($session->target, $subject, 'Remote session updated', $lines, $url, 'View Session');
            });

            // New invoice
            Invoice::created(function ($invoice) {
                $invoice->load(['client.user']);
                $subject = 'New B-Connect invoice #' . $invoice->id;
                $lines = [
                    'Amount: ' . ($invoice->amount ?? 0),
                    'Due: ' . ($invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A'),
                ];
                $url = route('bconnect.billing');
                BconnectMail::toMember($invoice->client, $subject, 'New invoice', $lines, $url, 'View Invoice');
            });

            // Invoice paid
            Invoice::updated(function ($invoice) {
                if (!$invoice->isDirty('status') || $invoice->status !== 'paid') {
                    return;
                }
                $invoice->load(['client.user']);
                $subject = 'Invoice paid: #' . $invoice->id;
                $lines = ['Your invoice has been marked as paid. Thank you!'];
                $url = route('bconnect.billing');
                BconnectMail::toMember($invoice->client, $subject, 'Invoice paid', $lines, $url, 'View Invoice');
            });

            // Member added / B-Connect welcome
            Member::created(function ($member) {
                $member->load('user');
                if (!$member->user) {
                    return;
                }
                $company = $member->company;
                $subject = 'Welcome to B-Connect' . ($company ? ' — ' . $company->name : '');
                $lines = [
                    'Your B-Connect workspace is ready.',
                    'Role: ' . ($member->role ?? 'member'),
                ];
                $url = route('bconnect.dashboard');
                BconnectMail::toMember($member, $subject, 'Welcome to B-Connect', $lines, $url, 'Open B-Connect');
            });

            // Chat message
            Message::created(function ($message) {
                $message->load(['member.user']);
                $channel = $message->channel;
                if (!$channel) {
                    return;
                }

                $senderName = $message->member?->user?->name ?? 'Someone';
                $commonLines = ['<strong>' . e($senderName) . '</strong>: ' . e($message->message)];

                if ($message->channel_type === Project::class) {
                    $channel->load('client.user');
                    $subject = 'New message in project: ' . $channel->name;
                    $url = route('bconnect.projects.chat', $channel->id);
                    if ($channel->client && $channel->client->id != $message->member_id) {
                        BconnectMail::toMember($channel->client, $subject, 'New project message', $commonLines, $url, 'View Chat');
                    }
                    BconnectMail::toCompanyAdmins($channel->company_id, $subject, 'New project message', $commonLines, $url, 'View Chat');
                } elseif ($message->channel_type === Ticket::class) {
                    $channel->load(['reporter.user', 'assignee.user']);
                    $subject = 'New message in ticket: ' . $channel->title;
                    $url = route('bconnect.tickets.show', $channel->id);
                    if ($channel->reporter && $channel->reporter->id != $message->member_id) {
                        BconnectMail::toMember($channel->reporter, $subject, 'New ticket message', $commonLines, $url, 'View Chat');
                    }
                    if ($channel->assignee && $channel->assignee->id != $message->member_id) {
                        BconnectMail::toMember($channel->assignee, $subject, 'New ticket message', $commonLines, $url, 'View Chat');
                    }
                }
            });
        } catch (\Throwable $e) {
            \Log::warning('B-Connect observers failed to register: ' . $e->getMessage());
        }
    }
}
