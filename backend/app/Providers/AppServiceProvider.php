<?php

namespace App\Providers;

use App\Services\Otp\DevModeOtpDriver;
use App\Services\Otp\OtpDriverInterface;
use App\Services\Otp\SmtpEmailOtpDriver;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Per /PHASE2_CONTRACT.md §7.3.
     *
     * Driver selection is config-driven via `services.otp.driver`,
     * which falls back to env('OTP_DRIVER', 'dev'). Default is the
     * dev driver — production deploys MUST set OTP_DRIVER explicitly
     * (and the §7.4 guard below refuses to boot if they don't).
     */
    public function register(): void
    {
        $this->app->bind(OtpDriverInterface::class, function ($app) {
            $key = config('services.otp.driver') ?? env('OTP_DRIVER', 'dev');
            return match ($key) {
                'dev'        => new DevModeOtpDriver(),
                'smtp-email' => new SmtpEmailOtpDriver(new DevModeOtpDriver()),
                default      => throw new RuntimeException("Unknown OTP driver: {$key}"),
            };
        });
    }

    /**
     * Production safety guard per /PHASE2_CONTRACT.md §7.4.
     *
     * If the bound driver is the DevModeOtpDriver while APP_ENV is
     * production, refuse to boot. This makes shipping dev-OTP to
     * production structurally impossible — a misconfiguration fails
     * fast at app boot, before any HTTP request can succeed.
     */
    public function boot(): void
    {
        if (
            $this->app->environment('production')
            && $this->app->make(OtpDriverInterface::class) instanceof DevModeOtpDriver
        ) {
            throw new RuntimeException(
                'Refusing to boot: DevModeOtpDriver bound in production. '
                . 'Set OTP_DRIVER in production .env to a real driver '
                . '(see /PHASE2_CONTRACT.md §7.4).'
            );
        }
    }
}
