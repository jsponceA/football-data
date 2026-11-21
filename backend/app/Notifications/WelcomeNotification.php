<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// correo que recibe quien crea su cuenta desde el registro
class WelcomeNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $login = rtrim(config('app.frontend_url'), '/').'/login';

        return (new MailMessage)
            ->subject('Te damos la bienvenida a Football Data')
            ->greeting('Hola, '.$notifiable->name)
            ->line('Tu cuenta se creó con el correo '.$notifiable->email.'.')
            ->line('Ya puedes consultar las ligas, los equipos y los jugadores de las principales competiciones.')
            ->action('Iniciar sesión', $login)
            ->line('Tu cuenta es de solo consulta. Si necesitas más permisos, pídeselos a un administrador.')
            ->salutation('Football Data');
    }
}
