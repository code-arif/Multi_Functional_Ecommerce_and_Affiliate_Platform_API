<?php

namespace Modules\Shipping\Http\Controllers;

use Modules\Shipping\Models\Courier;
use Modules\Shipping\Http\Resources\CourierResource;
use Modules\Shipping\Http\Requests\StoreCourierRequest;
use Modules\Shipping\Http\Requests\UpdateCourierRequest;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $couriers = Courier::withCount('rates')
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('display_name', 'like', "%{$s}%")
            )
            ->when($request->boolean('active_only'), fn($q) => $q->active())
            ->ordered()
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse(CourierResource::collection($couriers));
    }

    public function show(Courier $courier): JsonResponse
    {
        $courier->load('rates');
        return $this->successResponse(new CourierResource($courier));
    }

    public function store(StoreCourierRequest $request): JsonResponse
    {
        $courier = Courier::create($request->validated());
        return $this->createdResponse(new CourierResource($courier), 'Courier created.');
    }

    public function update(UpdateCourierRequest $request, Courier $courier): JsonResponse
    {
        $courier->update($request->validated());
        return $this->successResponse(new CourierResource($courier->fresh()), 'Courier updated.');
    }

    public function destroy(Courier $courier): JsonResponse
    {
        if ($courier->rates()->count() > 0) {
            return $this->errorResponse('Cannot delete courier with active shipping rates.', null, 400);
        }
        $courier->delete();
        return $this->noContentResponse('Courier deleted.');
    }
}
