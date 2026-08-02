<?php

use Illuminate\Support\Facades\Route;

use Modules\Shipping\Http\Controllers\CourierController;
use Modules\Shipping\Http\Controllers\ZoneController;
use Modules\Shipping\Http\Controllers\RateController;
use Modules\Shipping\Http\Controllers\ShipmentController;
use Modules\Shipping\Http\Controllers\PickupController;

/*
|--------------------------------------------------------------------------
| Shipping Module API Routes
|--------------------------------------------------------------------------
*/

// ─── Public Routes ──────────────────────────────────────────────
Route::prefix('shipping')->middleware('throttle:api')->group(function () {

    // Track a shipment by its tracking number (no auth required)
    Route::get('track/{trackingNumber}', [ShipmentController::class, 'trackByNumber']);

    // Find available shipping rates for a destination
    Route::get('rates/find', [RateController::class, 'findRates']);

    // List active couriers (unauthenticated)
    Route::get('couriers', [CourierController::class, 'index']);
    Route::get('couriers/{courier}', [CourierController::class, 'show']);
});

// ─── Admin Routes ───────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'admin', 'banned'])
    ->prefix('admin/shipping')
    ->group(function () {

        // Couriers
        Route::get('couriers', [CourierController::class, 'index'])
            ->middleware('permission:shipping.view');
        Route::post('couriers', [CourierController::class, 'store'])
            ->middleware('permission:shipping.manage');
        Route::get('couriers/{courier}', [CourierController::class, 'show'])
            ->middleware('permission:shipping.view');
        Route::put('couriers/{courier}', [CourierController::class, 'update'])
            ->middleware('permission:shipping.manage');
        Route::delete('couriers/{courier}', [CourierController::class, 'destroy'])
            ->middleware('permission:shipping.manage');

        // Zones (individual routes for granular permission control)
        Route::get('zones', [ZoneController::class, 'index'])
            ->middleware('permission:shipping.view');
        Route::post('zones', [ZoneController::class, 'store'])
            ->middleware('permission:shipping.manage');
        Route::get('zones/{zone}', [ZoneController::class, 'show'])
            ->middleware('permission:shipping.view');
        Route::put('zones/{zone}', [ZoneController::class, 'update'])
            ->middleware('permission:shipping.manage');
        Route::delete('zones/{zone}', [ZoneController::class, 'destroy'])
            ->middleware('permission:shipping.manage');

        // Rates
        Route::get('rates', [RateController::class, 'index'])
            ->middleware('permission:shipping.view');
        Route::post('rates', [RateController::class, 'store'])
            ->middleware('permission:shipping.manage');
        Route::get('rates/{rate}', [RateController::class, 'show'])
            ->middleware('permission:shipping.view');
        Route::put('rates/{rate}', [RateController::class, 'update'])
            ->middleware('permission:shipping.manage');
        Route::delete('rates/{rate}', [RateController::class, 'destroy'])
            ->middleware('permission:shipping.manage');
        Route::post('rates/calculate', [RateController::class, 'calculate'])
            ->middleware('permission:shipping.view');

        // Shipments
        Route::get('shipments', [ShipmentController::class, 'index'])
            ->middleware('permission:shipping.view');
        Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])
            ->middleware('permission:shipping.view');
        Route::post('shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])
            ->middleware('permission:shipping.manage');
        Route::post('shipments/{shipment}/assign-courier', [ShipmentController::class, 'assignCourier'])
            ->middleware('permission:shipping.manage');

        // Pickup Requests (admin view)
        Route::get('pickups', [PickupController::class, 'adminIndex'])
            ->middleware('permission:shipping.view');
        Route::post('pickups/{pickup}/schedule', [PickupController::class, 'schedule'])
            ->middleware('permission:shipping.manage');
        Route::post('pickups/{pickup}/picked-up', [PickupController::class, 'markPickedUp'])
            ->middleware('permission:shipping.manage');
    });

// ─── Vendor Routes ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'vendor', 'banned'])
    ->prefix('vendor/shipping')
    ->group(function () {

        // Shipments (vendor's own — auto-scoped by vendor_id)
        Route::get('shipments', [ShipmentController::class, 'vendorIndex']);
        Route::get('shipments/{shipment}', [ShipmentController::class, 'vendorShow']);
        Route::post('shipments/{shipment}/status', [ShipmentController::class, 'vendorUpdateStatus']);

        // Tracking (vendor scoped)
        Route::get('shipments/{shipment}/tracking', [ShipmentController::class, 'vendorTracking']);

        // Pickup Requests (vendor's own)
        Route::get('pickups', [PickupController::class, 'index']);
        Route::post('pickups', [PickupController::class, 'store']);
        Route::post('pickups/{pickup}/cancel', [PickupController::class, 'cancel']);
    });
