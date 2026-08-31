<?php

namespace App\Notifications;

use App\Models\CompanySetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $company = CompanySetting::current();
        $name = $company->displayName();
        $subject = str_replace(['{nombre}', '%nombre%'], $name, $company->password_reset_subject ?: 'Restablecer contraseña - Registro Horario ' . $name);
        $url = url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()], false));

        $message = (new MailMessage)
            ->from($company->mailFromAddress(), $company->mailFromName())
            ->subject($subject)
            ->greeting('Restablecer contraseña')
            ->line('Has recibido este correo porque se ha solicitado restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line('Este enlace caducará en ' . config('auth.passwords.' . config('auth.defaults.passwords') . '.expire') . ' minutos.')
            ->line('Si no has solicitado este cambio, no es necesario realizar ninguna acción.');

        if ($replyTo = $company->mailReplyTo()) {
            $message->replyTo($replyTo, $company->mailFromName());
        }

        return $message;
    }
}
