<?php

namespace Modules\Core\Http\Controllers;

use Modules\Core\Models\Country;
use Modules\Core\Models\State;
use Modules\Core\Models\City;
use Modules\Core\Http\Resources\CountryResource;
use Modules\Core\Http\Resources\StateResource;
use Modules\Core\Http\Resources\CityResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController
{
    use ApiResponse;

    // ─── Countries ─────────────────────────────────────────────────

    public function countries(): JsonResponse
    {
        $countries = Country::active()
            ->withCount('states')
            ->orderBy('name')
            ->get();

        return $this->successResponse(CountryResource::collection($countries));
    }

    public function countryShow(Country $country): JsonResponse
    {
        $country->load(['states' => fn($q) => $q->active()->orderBy('name')]);
        return $this->successResponse(new CountryResource($country));
    }

    public function countryStates(Country $country): JsonResponse
    {
        $states = $country->states()
            ->active()
            ->withCount('cities')
            ->orderBy('name')
            ->get();

        return $this->successResponse(StateResource::collection($states));
    }

    // ─── States ────────────────────────────────────────────────────

    public function stateShow(State $state): JsonResponse
    {
        $state->load('country');
        return $this->successResponse(new StateResource($state));
    }

    public function stateCities(State $state): JsonResponse
    {
        $cities = $state->cities()
            ->active()
            ->orderBy('name')
            ->get();

        return $this->successResponse(CityResource::collection($cities));
    }

    // ─── Cities ────────────────────────────────────────────────────

    public function cityShow(City $city): JsonResponse
    {
        $city->load('state', 'country');
        return $this->successResponse(new CityResource($city));
    }
}
