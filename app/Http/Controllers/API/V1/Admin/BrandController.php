<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\User;
use App\Models\AffiliateProduct;
use App\Models\CmsPage;
use App\Models\Setting;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::withCount('products')->orderBy('name')->get();
        return $this->successResponse($brands);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:brands,name',
            'description' => 'nullable|string',
            'logo'        => 'nullable|image|max:2048',
            'website'     => 'nullable|url',
            'is_active'   => 'boolean',
        ]);
        $data = $request->except('logo');
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }
        $brand = Brand::create($data);
        return $this->createdResponse($brand, 'Brand created.');
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $data = $request->except('logo');
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }
        $brand->update($data);
        return $this->successResponse($brand, 'Brand updated.');
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();
        return $this->noContentResponse('Brand deleted.');
    }
}


// ─── SettingController ────────────────────────────────────────



// ─── ReportController ─────────────────────────────────────────

class ReportController extends BaseController
{
    public function __construct(private ReportService $reportService) {}

    public function sales(Request $request): JsonResponse
    {
        $data = $this->reportService->getSalesReport(
            $request->period ?? 'daily',
            $request->from,
            $request->to
        );
        return $this->successResponse($data);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $data = $this->reportService->getTopProducts($request->limit ?? 10, $request->from, $request->to);
        return $this->successResponse($data);
    }

    public function ordersByStatus(): JsonResponse
    {
        return $this->successResponse($this->reportService->getOrdersByStatus());
    }

    public function customerGrowth(Request $request): JsonResponse
    {
        return $this->successResponse($this->reportService->getCustomerGrowth($request->months ?? 12));
    }
}

// ─── UserController ───────────────────────────────────────────

class UserController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return $this->paginatedResponse(UserResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['roles', 'orders', 'addresses']);
        return $this->successResponse(new UserResource($user));
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $request->validate(['status' => 'required|in:active,inactive,banned']);
        $user->update(['status' => $request->status]);
        return $this->successResponse(null, "User status updated to {$request->status}.");
    }
}
