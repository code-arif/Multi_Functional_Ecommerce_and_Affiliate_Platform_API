<?php

namespace Modules\Core\Http\Controllers;

use Modules\Core\Models\Currency;
use Modules\Core\Http\Resources\CurrencyResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $currencies = Currency::active()->orderBy('name')->get();
        return $this->successResponse(CurrencyResource::collection($currencies));
    }

    public function show(Currency $currency): JsonResponse
    {
        return $this->successResponse(new CurrencyResource($currency));
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $currencies = Currency::orderBy('name')
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse(CurrencyResource::collection($currencies));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'code'          => 'required|string|max:10|unique:currencies,code',
            'symbol'        => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'precision'     => 'nullable|integer|min:0|max:8',
            'is_default'    => 'boolean',
            'is_active'     => 'boolean',
        ]);

        $currency = Currency::create($validated);

        if ($currency->is_default) {
            Currency::where('id', '!=', $currency->id)->update(['is_default' => false]);
        }

        return $this->createdResponse(new CurrencyResource($currency), 'Currency created.');
    }

    public function update(Currency $currency, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'code'          => 'sometimes|string|max:10|unique:currencies,code,' . $currency->id,
            'symbol'        => 'sometimes|string|max:10',
            'exchange_rate' => 'sometimes|numeric|min:0',
            'precision'     => 'nullable|integer|min:0|max:8',
            'is_default'    => 'boolean',
            'is_active'     => 'boolean',
        ]);

        $currency->update($validated);

        if (!empty($validated['is_default'])) {
            Currency::where('id', '!=', $currency->id)->update(['is_default' => false]);
        }

        return $this->successResponse(new CurrencyResource($currency->fresh()), 'Currency updated.');
    }

    public function destroy(Currency $currency): JsonResponse
    {
        if ($currency->is_default) {
            return $this->errorResponse('Cannot delete the default currency.', null, 400);
        }
        $currency->delete();
        return $this->noContentResponse('Currency deleted.');
    }
}
