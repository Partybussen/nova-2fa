<?php

namespace Partybussen\Nova2fa\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Nova\Contracts\ImpersonatesUsers;
use Partybussen\Nova2fa\Google2FAAuthenticator;

class Google2fa
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws \PragmaRX\Google2FA\Exceptions\InsecureCallException
     */
    public function handle($request, Closure $next)
    {
        $paths2fa = [
            'nova/nova-2fa',
            'nova/nova-2fa/register',
            'nova/nova-2fa/recover',
            'nova-vendor/nova-2fa/confirm',
            'nova-vendor/nova-2fa/verify',
            'nova-vendor/nova-2fa/recover'
        ];

        $pathsUnregisteredOnly = [
            'nova/nova-2fa/register',
            'nova-vendor/nova-2fa/confirm'
        ];

        $pathsRecovery = [
            'nova/nova-2fa/recover',
            'nova-vendor/nova-2fa/recover'
        ];

        $impersonating = app(ImpersonatesUsers::class)->impersonating($request);

        if ($impersonating && in_array($request->path(), $paths2fa)) {
            return Inertia::location(config('nova.path'));
        }

        if ($impersonating) {
            return $next($request);
        }

        if (auth()->guest()) {
            return $next($request);
        }

        if (!config('nova2fa.enabled') && in_array($request->path(), $paths2fa)) {
            return Inertia::location(config('nova.path'));
        }

        if (!config('nova2fa.enabled')) {
            return $next($request);
        }

        // continue when user is not required to have 2fa
        if (config('nova2fa.requires_2fa_attribute') &&
            !auth()->user()->{config('nova2fa.requires_2fa_attribute')}) {
            return $next($request);
        }

        $authenticator = app(Google2FAAuthenticator::class)->boot($request);

        $user2faEnabled = auth()->user()->user2fa && auth()->user()->user2fa->google2fa_enable;

        $user2faAuthenticated = $authenticator->isAuthenticated() && $user2faEnabled;

        $recoveryEnabled = config('nova2fa.recovery_codes.enabled');

        if (Str::startsWith($request->path(), 'nova-api/scripts')) {
            return $next($request);
        }

        if (Str::startsWith($request->path(), 'nova-api/styles')) {
            return $next($request);
        }

        if (!$user2faEnabled && !in_array($request->path(), $pathsUnregisteredOnly)) {
            return Inertia::location('/nova/nova-2fa/register');
        }

        if (!$user2faAuthenticated && $user2faEnabled && in_array($request->path(), $pathsUnregisteredOnly)) {
            return Inertia::location('/nova/nova-2fa');
        }

        if (!$recoveryEnabled && !$user2faAuthenticated && in_array($request->path(), $pathsRecovery)) {
            return Inertia::location('/nova/nova-2fa');
        }

        if (!$user2faAuthenticated && in_array($request->path(), $paths2fa)) {
            return $next($request);
        }

        if ($user2faAuthenticated && in_array($request->path(), $paths2fa)) {
            return Inertia::location(config('nova.path'));
        }

        if ($user2faAuthenticated) {
            return $next($request);
        }

        // redirect user to authentication of 2fa code
        return Inertia::location('/nova/nova-2fa');
    }
}