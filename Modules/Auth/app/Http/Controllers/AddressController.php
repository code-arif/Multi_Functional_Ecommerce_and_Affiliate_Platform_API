<?php

namespace Modules\Auth\Http\Controllers;

use Modules\Auth\Models\Address;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->latest()->get();
        return $this->successResponse($addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label'          => 'nullable|string|max:50',
            'recipient_name' => 'required|string|max:100',
            'phone'          => 'required|string|max:20',
            'address_line1'  => 'required|string|max:255',
            'address_line2'  => 'nullable|string|max:255',
            'city'           => 'required|string|max:100',
            'state'          => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:20',
            'country'        => 'required|string|max:100',
            'is_default'     => 'boolean',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
        ]);

        $validated['user_id'] = $request->user()->id;

        // If setting as default, unset other defaults
        if (!empty($validated['is_default'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = Address::create($validated);

        return $this->createdResponse($address, 'Address created.');
    }

    public function show(string $address, Request $request): JsonResponse
    {
        $address = $request->user()->addresses()->where('uuid', $address)->firstOrFail();
        return $this->successResponse($address);
    }

    public function update(string $address, Request $request): JsonResponse
    {
        $address = $request->user()->addresses()->where('uuid', $address)->firstOrFail();

        $validated = $request->validate([
            'label'          => 'nullable|string|max:50',
            'recipient_name' => 'sometimes|string|max:100',
            'phone'          => 'sometimes|string|max:20',
            'address_line1'  => 'sometimes|string|max:255',
            'address_line2'  => 'nullable|string|max:255',
            'city'           => 'sometimes|string|max:100',
            'state'          => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:20',
            'country'        => 'sometimes|string|max:100',
            'is_default'     => 'boolean',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
        ]);

        if (!empty($validated['is_default'])) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return $this->successResponse($address->fresh(), 'Address updated.');
    }

    public function destroy(string $address, Request $request): JsonResponse
    {
        $address = $request->user()->addresses()->where('uuid', $address)->firstOrFail();
        $address->delete();

        return $this->noContentResponse('Address deleted.');
    }
}
