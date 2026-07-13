<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use App\Models\Permission;

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
        Schema::defaultStringLength(191);

        // Dynamically register every permission slug as a Gate ability.
        // Each ability returns true if the authenticated user's role has that permission.
        // We use a lazy DB query so it only fires on first Gate::authorize() call.
        try {
            if (app()->runningInConsole()) {
                return;
            }

            $permissions = Permission::pluck('slug');
            foreach ($permissions as $slug) {
                Gate::define($slug, function ($user) use ($slug) {
                    return $user->hasPermission($slug);
                });
            }
        } catch (\Throwable $e) {
            // Silently skip during fresh migrations when permissions table may not yet exist
        }
    }
}
