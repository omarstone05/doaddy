<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Models\MoneyMovement;
use App\Models\Invoice;
use App\Models\BudgetLine;
use App\Models\LeaveRequest;
use App\Models\GoodsAndService;
use App\Models\StockMovement;
use App\Models\Quote;
use App\Models\Payment;
use App\Models\PayrollRun;
use App\Models\MoneyAccount;
use App\Observers\MoneyMovementObserver;
use App\Observers\InvoiceObserver;
use App\Observers\BudgetLineObserver;
use App\Observers\LeaveRequestObserver;
use App\Observers\GoodsAndServiceObserver;
use App\Observers\StockMovementObserver;
use App\Observers\QuoteObserver;
use App\Observers\PaymentObserver;
use App\Observers\PayrollRunObserver;
use App\Observers\MoneyAccountObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        //
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register observers for cache invalidation and insight regeneration
        MoneyMovement::observe(MoneyMovementObserver::class);
        Invoice::observe(InvoiceObserver::class);
        BudgetLine::observe(BudgetLineObserver::class);
        LeaveRequest::observe(LeaveRequestObserver::class);
        GoodsAndService::observe(GoodsAndServiceObserver::class);
        StockMovement::observe(StockMovementObserver::class);
        Quote::observe(QuoteObserver::class);
        Payment::observe(PaymentObserver::class);
        PayrollRun::observe(PayrollRunObserver::class);
        MoneyAccount::observe(MoneyAccountObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

