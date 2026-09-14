<?php

namespace App\Notifications;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification
{

    public function __construct(
        public TeamInvitation $invitation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $team = $this->invitation->team;
        $inviter = $this->invitation->inviter;
        
        return (new MailMessage)
            ->subject("You're invited to join the team: {$team->name}")
            ->greeting("Hello!")
            ->line("{$inviter->name} has invited you to join their team '{$team->name}' on Believoo.")
            ->line("Team Description: " . ($team->description ?? 'No description provided'))
            ->line("Your Role: " . ucfirst($this->invitation->role))
            ->action('Accept Invitation', $this->invitation->accept_url)
            ->line('This invitation will expire in 7 days.')
            ->line('If you don\'t want to join this team, you can ignore this email or click the link below:')
            ->action('Decline Invitation', $this->invitation->decline_url)
            ->salutation('Best regards,\nTeam Believoo');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'team_name' => $this->invitation->team->name,
            'inviter_name' => $this->invitation->inviter->name,
            'email' => $this->invitation->email,
        ];
    }
}
