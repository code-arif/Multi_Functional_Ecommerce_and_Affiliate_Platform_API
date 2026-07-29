<?php

namespace Modules\Support\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Modules\Support\Models\Ticket;
use Modules\Support\Models\Faq;
use Modules\Support\Models\ChatRoom;
use Modules\AdminPanel\Models\Dispute;
use Modules\Support\Policies\TicketPolicy;
use Modules\Support\Policies\FaqPolicy;
use Modules\Support\Policies\ChatPolicy;
use Modules\Support\Policies\DisputePolicy as SupportDisputePolicy;
use Illuminate\Support\Facades\Gate;

class SupportServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Support';
    protected string $nameLower = 'support';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
        $this->registerPolicies();
    }

    private function registerPolicies(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Faq::class, FaqPolicy::class);
        Gate::policy(ChatRoom::class, ChatPolicy::class);

        // Register the dispute policy under a different gate name to avoid conflict with AdminPanel
        Gate::define('support.dispute.viewAny', [SupportDisputePolicy::class, 'viewAny']);
        Gate::define('support.dispute.view', [SupportDisputePolicy::class, 'view']);
        Gate::define('support.dispute.create', [SupportDisputePolicy::class, 'create']);
    }
}
