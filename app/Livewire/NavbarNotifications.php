<?php

namespace App\Livewire;

use App\Support\NotificationLink;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NavbarNotifications extends Component
{
    public $lastUnreadCount = 0;

    public function mount()
    {
        if (Auth::check()) {
            $this->lastUnreadCount = Auth::user()->unreadNotifications->count();
        }
    }

    public function markNotificationsAsRead()
    {
        if (Auth::check()) {
            Auth::user()->unreadNotifications->markAsRead();
        }
    }

    public function openNotification(string $id)
    {
        $notification = Auth::user()?->notifications()->find($id);
        if (!$notification) {
            return;
        }
        $notification->markAsRead();
        $this->redirect(NotificationLink::url($notification));
    }

    public function checkNotifications()
    {
        if (Auth::check()) {
            $currentCount = Auth::user()->unreadNotifications->count();
            if ($currentCount > $this->lastUnreadCount) {
                $this->dispatch('notification-received');
                $this->dispatch('play-notification-sound');
            }
            $this->lastUnreadCount = $currentCount;
            $this->dispatch('notifications-updated'); // Force refresh of notification list
        }
    }

    public function render()
    {
        return view('livewire.navbar-notifications');
    }
}
