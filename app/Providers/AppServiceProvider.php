<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\AuditActivity;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        Password::defaults(fn (): Password => Password::min(10)->letters()->numbers());

        $this->registerGates();
        $this->registerAuthAuditListeners();
    }

    private function registerGates(): void
    {
        $clinicalRoles = [
            Role::Perawat,
            Role::DokterOperator,
            Role::DokterAnestesi,
            Role::KepalaRuangan,
        ];

        Gate::define('clinical.view', fn (User $user): bool => in_array($user->role, $clinicalRoles, true));
        Gate::define('clinical.create_case', fn (User $user): bool => $user->isPerawat());
        Gate::define('clinical.edit_nursing', fn (User $user): bool => $user->isPerawat());
        Gate::define('clinical.signoff_intraop', fn (User $user): bool => $user->isPerawat() || $user->isDokter());
        Gate::define('clinical.export_pdf', fn (User $user): bool => in_array($user->role, $clinicalRoles, true));
        Gate::define('reports.view', fn (User $user): bool => $user->isKepalaRuangan());
        Gate::define('audit.view', fn (User $user): bool => $user->isKepalaRuangan());
        Gate::define('users.manage', fn (User $user): bool => $user->isAdmin());
        Gate::define('master.manage', fn (User $user): bool => $user->isAdmin());
        Gate::define('master.view', fn (User $user): bool => $user->is_active);
    }

    private function registerAuthAuditListeners(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

                activity('auth')
                    ->causedBy($event->user)
                    ->event('login')
                    ->withProperties([
                        'ip' => request()->ip(),
                    ])
                    ->tap(function ($activity): void {
                        if ($activity instanceof AuditActivity) {
                            $activity->ip_address = request()->ip();
                        }
                    })
                    ->log('Pengguna masuk ke sistem');
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $logger = activity('auth')
                ->event('login_failed')
                ->withProperties([
                    'login' => $event->credentials['login'] ?? null,
                    'ip' => request()->ip(),
                ])
                ->tap(function ($activity): void {
                    if ($activity instanceof AuditActivity) {
                        $activity->ip_address = request()->ip();
                    }
                });

            if ($event->user instanceof User) {
                $logger->causedBy($event->user);
            } else {
                $logger->causedByAnonymous();
            }

            $logger->log('Percobaan masuk gagal');
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                activity('auth')
                    ->causedBy($event->user)
                    ->event('logout')
                    ->withProperties([
                        'ip' => request()->ip(),
                    ])
                    ->tap(function ($activity): void {
                        if ($activity instanceof AuditActivity) {
                            $activity->ip_address = request()->ip();
                        }
                    })
                    ->log('Pengguna keluar dari sistem');
            }
        });
    }
}
