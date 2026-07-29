<?php

namespace App\Providers;

use App\Models\Asistencia;
use App\Models\DiaFestivo;
use App\Models\Empleado;
use App\Models\LaborCalendarDay;
use App\Models\Nomina;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Models\WorkRule;
use App\Observers\AuditObserver;
use App\Services\LaborCalendarService;
use App\Services\WorkRuleResolver;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
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
        foreach ([
            Empleado::class,
            Asistencia::class,
            Nomina::class,
            DiaFestivo::class,
            User::class,
            WorkRule::class,
            LaborCalendarDay::class,
            PayrollPeriod::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }

        WorkRule::saved(fn () => WorkRuleResolver::forget());
        WorkRule::deleted(fn () => WorkRuleResolver::forget());
        LaborCalendarDay::saved(fn () => LaborCalendarService::forget());
        LaborCalendarDay::deleted(fn () => LaborCalendarService::forget());

        Event::listen(JobProcessing::class, function () {
            WorkRuleResolver::forget();
            LaborCalendarService::forget();
        });

        Vite::prefetch(concurrency: 3);
    }
}
