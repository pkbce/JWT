<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;

class ResetPasswordNotification extends ResetPassword
{
    use Queueable;

    public function toMail($notifiable)
    {
        // Construct the frontend URL directly without using route() helper
        $frontendUrl = "http://localhost:9002/reset-password?token={$this->token}&email={$notifiable->getEmailForPasswordReset()}";

        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->view('auth.emails.password', ['url' => $frontendUrl]);
    }
}
