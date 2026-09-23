<?php

namespace App\Providers;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (Schema::hasTable('company_settings')) {
            CompanySetting::current()->applyMailConfiguration();
        }

        Gate::define('view-admin-panel', function (User $user): bool {
            return (bool) $user->is_admin;
        });
    }
}
