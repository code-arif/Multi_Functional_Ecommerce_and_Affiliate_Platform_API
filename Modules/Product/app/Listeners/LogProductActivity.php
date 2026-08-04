<?php

namespace Modules\Catalog\Listeners;

use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Events\ProductDeleted;
use Illuminate\Support\Facades\Log;

class LogProductActivity
{
    public function handle(ProductCreated|ProductUpdated|ProductDeleted $event): void
    {
        $product = $event->product;

        Log::info("Product {$this->action($event)}: {$product->name} (ID: {$product->id})");
    }

    private function action(object $event): string
    {
        return match ($event::class) {
            ProductCreated::class => 'created',
            ProductUpdated::class => 'updated',
            ProductDeleted::class => 'deleted',
            default               => 'modified',
        };
    }
}
