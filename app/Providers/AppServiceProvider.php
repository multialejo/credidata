<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
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
        VerifyEmail::toMailUsing(static function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Verifica tu dirección de correo electrónico')
                ->greeting('Hola '.$notifiable->name)
                ->line('Gracias por registrarte en CrediData.')
                ->line('Para completar tu registro, verifica tu dirección de correo electrónico haciendo clic en el botón siguiente.')
                ->action('Verificar correo electrónico', $url)
                ->line('Este enlace de verificación expirará en 60 minutos.')
                ->salutation('Saludos');
        });

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
