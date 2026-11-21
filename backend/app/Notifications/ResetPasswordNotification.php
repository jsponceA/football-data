<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

// el correo de Laravel viene en inglés y su enlace apunta a una ruta del backend; aquí va en español y abre la SPA
class ResetPasswordNotification extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        $base = rtrim(config('app.frontend_url'), '/');

        return $base.'/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    public function toMail($notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Restablece tu contraseña')
            ->greeting('Hola, '.$notifiable->name)
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $this->resetUrl($notifiable))
            ->line("El enlace vence en {$minutes} minutos.")
            ->line('Si tú no lo pediste, ignora este correo: tu contraseña no cambia.')
            ->salutation('Football Data');
    }
}
