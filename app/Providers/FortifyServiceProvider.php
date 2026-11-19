<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        Fortify::loginView(fn () => Inertia::render('auth/login', [
            'status' => session('status'),
        ]));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));

        Fortify::authenticateUsing(function (Request $request): ?User {
            $login = trim((string) $request->input(Fortify::username()));
            $password = (string) $request->input('password');

            if ($login === '' || $password === '') {
                return null;
            }

            $user = User::query()
                ->where('username', $login)
                ->orWhere(function ($query) use ($login): void {
                    $query->whereNotNull('email')->where('email', $login);
                })
                ->first();

            if (! $user || ! Hash::check($password, $user->password)) {
                return null;
            }

            if (! $user->is_active) {
                event(new Failed(config('fortify.guard', 'web'), $user, [
                    Fortify::username() => $login,
                ]));

                throw ValidationException::withMessages([
                    Fortify::username() => [__('auth.inactive')],
                ]);
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request): Limit {
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
