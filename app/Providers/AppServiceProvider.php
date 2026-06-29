<?php

namespace App\Providers;

use App\Models\Asistencia;
use App\Models\DiaFestivo;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Support\Facades\Vite;
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
        foreach ([Empleado::class, Asistencia::class, Nomina::class, DiaFestivo::class, User::class] as $model) {
            $model::observe(AuditObserver::class);
        }

        Vite::prefetch(concurrency: 3);
    }
}
