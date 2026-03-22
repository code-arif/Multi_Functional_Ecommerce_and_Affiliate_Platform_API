<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use ApiResponse;
    
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->get();
        return $this->successResponse($addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'label'          => 'nullable|string|max:50',
            'recipient_name' => 'required|string|max:100',
            'phone'          => 'required|string|max:20',
            'email'          => 'nullable|email',
            'address_line1'  => 'required|string|max:500',
            'address_line2'  => 'nullable|string|max:500',
            'city'           => 'required|string|max:100',
            'state'          => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:100',
            'is_default'     => 'boolean',
        ]);

        if ($request->is_default) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($request->validated());
        return $this->createdResponse($address, 'Address saved.');
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        $this->authorizeAddress($request, $address);

        if ($request->is_default) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($request->all());
        return $this->successResponse($address, 'Address updated.');
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $this->authorizeAddress($request, $address);
        $address->delete();
        return $this->noContentResponse('Address deleted.');
    }

    private function authorizeAddress(Request $request, Address $address): void
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
