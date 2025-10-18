<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WhatsAppService;

class WhatsAppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService();
        });

        // Bind interface if needed
        $this->app->bind('whatsapp', WhatsAppService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../../config/whatsapp.php' => config_path('whatsapp.php'),
        ], 'whatsapp-config');

        // Load configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/whatsapp.php', 'whatsapp'
        );
    }
}