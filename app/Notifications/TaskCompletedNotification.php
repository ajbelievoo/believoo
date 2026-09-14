<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCompletedNotification extends Notification
{
    use Queueable;

    protected $task;

    public function __construct($task)
    {
        $this->task = $task;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('✅ Task Completed - ' . $this->task->agreement->project_name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Great news! A task has been completed on your project.')
            ->line('Task: ' . $this->task->task_name)
            ->line('Project: ' . $this->task->agreement->project_name)
            ->action('View Progress', route('client.dashboard', ['tab' => 'agreements']))
            ->line('You can track the overall progress in your dashboard.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_name' => $this->task->task_name,
            'agreement_id' => $this->task->agreement_id,
            'project_name' => $this->task->agreement->project_name,
            'type' => 'task_completed',
            'action_url' => route('client.dashboard', ['tab' => 'agreements']),
        ];
    }
}
