<?php

namespace App\Notifications;

use App\Mail\BconnectResetPassword as BconnectResetPasswordMail;
use Illuminate\Auth\Notifications\ResetPassword;

class BconnectResetPassword extends ResetPassword
{
    public function toMail($notifiable): BconnectResetPasswordMail
    {
        $url = url(route('bconnect.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new BconnectResetPasswordMail($url, $notifiable->name ?? 'there', $expire))
            ->to($notifiable->email, $notifiable->name ?? '');
    }
}
