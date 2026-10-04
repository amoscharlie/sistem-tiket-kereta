<?php

namespace App\Providers;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
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
        Event::listen(function (ConnectionEstablished $event) {
            if ($event->connection->getDriverName() === 'pgsql') {
                $event->connection->statement("SET TIME ZONE 'Asia/Jakarta'");
            }
        });
    }
}
