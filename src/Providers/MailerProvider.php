<?php

namespace Samcbdev\MailNotifier\Providers;

use Illuminate\Support\ServiceProvider;
use Samcbdev\MailNotifier\Mailer;

class MailerProvider extends ServiceProvider
{
    /**
     * bootstrap services
     *
     * @return void
     */

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishResources();
        }
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mailnotifier');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mail-notifier.php', 'mail-notifier');

        // Binding Mailer to the service container
        $this->app->singleton(Mailer::class, function ($app) {
            return new Mailer();
        });
    }

    public function publishResources()
    {
        $this->publishes([
            __DIR__ . '/../config/mail-notifier.php' => config_path('mail-notifier.php'),
            __DIR__ . '/../database/migrations/create_email_notifications_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_email_notifications_table.php'),
        ], 'mail-notifier');
    }
}
