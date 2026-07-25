<?php

namespace App\Providers;

use App\Repositories\AuditLogRepositoryInterface;
use App\Repositories\EloquentAuditLogRepository;
use App\Repositories\EloquentRoleRepository;
use App\Repositories\EloquentUserLoginRepository;
use App\Repositories\EloquentUserRepository;
use App\Repositories\RoleRepositoryInterface;
use App\Repositories\UserLoginRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Services\AuditLogger;
use App\Services\AuditLoggerInterface;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, EloquentRoleRepository::class);
        $this->app->bind(UserLoginRepositoryInterface::class, EloquentUserLoginRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, EloquentAuditLogRepository::class);
        $this->app->bind(AuditLoggerInterface::class, AuditLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('canSee', fn (string $permission): bool => ! config('app.auth_enabled') || (auth()->user()?->hasPermission($permission) ?? false)
        );
    }
}
