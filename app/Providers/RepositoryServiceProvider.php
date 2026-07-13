<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\ProjectRepositoryInterface;
use App\Repositories\ProjectRepository;
use App\Repositories\ClientRepositoryInterface;
use App\Repositories\ClientRepository;
use App\Repositories\ExpenseRepositoryInterface;
use App\Repositories\ExpenseRepository;
use App\Repositories\PurchaseRepositoryInterface;
use App\Repositories\PurchaseRepository;
use App\Repositories\IncomeRepositoryInterface;
use App\Repositories\IncomeRepository;
use App\Repositories\ActivityLogRepositoryInterface;
use App\Repositories\ActivityLogRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(ExpenseRepositoryInterface::class, ExpenseRepository::class);
        $this->app->bind(PurchaseRepositoryInterface::class, PurchaseRepository::class);
        $this->app->bind(IncomeRepositoryInterface::class, IncomeRepository::class);
        $this->app->bind(ActivityLogRepositoryInterface::class, ActivityLogRepository::class);
    }
}
