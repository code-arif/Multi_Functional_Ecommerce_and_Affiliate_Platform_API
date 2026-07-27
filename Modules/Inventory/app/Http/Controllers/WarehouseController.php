<?php

namespace Modules\Inventory\Http\Controllers;

use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Http\Requests\StoreWarehouseRequest;
use Modules\Inventory\Http\Requests\UpdateWarehouseRequest;
use Modules\Inventory\Http\Resources\WarehouseResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController
{
    use ApiResponse;

    /**
     * GET /api/v1/vendor/warehouses
     * List warehouses for the authenticated vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $warehouses = Warehouse::byVendor($vendor->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return $this->successResponse(WarehouseResource::collection($warehouses));
    }

    /**
     * POST /api/v1/vendor/warehouses
     * Create a new warehouse.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $data = $request->validated();
        $data['vendor_id'] = $vendor->id;
        $data['slug'] = \Illuminate\Support\Str::slug($data['name']) . '-' . $vendor->id;

        // If this is the first warehouse or marked as default, handle defaults
        if ($data['is_default'] ?? false) {
            Warehouse::byVendor($vendor->id)->update(['is_default' => false]);
        } elseif (Warehouse::byVendor($vendor->id)->count() === 0) {
            $data['is_default'] = true;
        }

        $warehouse = Warehouse::create($data);

        return $this->createdResponse(new WarehouseResource($warehouse), 'Warehouse created.');
    }

    /**
     * GET /api/v1/vendor/warehouses/{warehouse}
     * Show a specific warehouse.
     */
    public function show(Warehouse $warehouse, Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (!$vendor || $warehouse->vendor_id !== $vendor->id) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(new WarehouseResource($warehouse));
    }

    /**
     * PUT /api/v1/vendor/warehouses/{warehouse}
     * Update a warehouse.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (!$vendor || $warehouse->vendor_id !== $vendor->id) {
            return $this->forbiddenResponse();
        }

        $data = $request->validated();

        if (($data['is_default'] ?? false) && !$warehouse->is_default) {
            Warehouse::byVendor($vendor->id)->update(['is_default' => false]);
        }

        $warehouse->update($data);

        return $this->successResponse(new WarehouseResource($warehouse->fresh()), 'Warehouse updated.');
    }

    /**
     * DELETE /api/v1/vendor/warehouses/{warehouse}
     * Delete a warehouse.
     */
    public function destroy(Warehouse $warehouse, Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (!$vendor || $warehouse->vendor_id !== $vendor->id) {
            return $this->forbiddenResponse();
        }

        if ($warehouse->is_default) {
            return $this->errorResponse('Cannot delete the default warehouse.', null, 400);
        }

        $warehouse->delete();

        return $this->noContentResponse('Warehouse deleted.');
    }
}
