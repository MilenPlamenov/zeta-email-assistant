<?php

namespace App\Providers;

use App\Services\AI\EmailTaskInterpreter;
use App\Services\AI\MockEmailTaskInterpreter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmailTaskInterpreter::class, MockEmailTaskInterpreter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
