<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customers\SearchCustomerRequest;
use App\Http\Requests\Api\V1\Customers\StoreCustomerRequest;
use App\Http\Resources\Api\V1\Common\ApiResponse;
use App\Http\Resources\Api\V1\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\Outlet;
use App\Support\OutletScope;

class CustomerController extends Controller
{
    public function search(SearchCustomerRequest $request)
    {
        $v = $request->validated();
        $outletId = OutletScope::id($request) ?: (isset($v['outlet_id']) ? (string) $v['outlet_id'] : null);

        if (OutletScope::isLocked($request) && !empty($v['outlet_id']) && (string) $v['outlet_id'] !== (string) $outletId) {
            return ApiResponse::error('Outlet mismatch', 'OUTLET_MISMATCH', 403);
        }

        if (!$outletId || !Outlet::query()->whereKey($outletId)->exists()) {
            return ApiResponse::error('Please select an outlet', 'OUTLET_REQUIRED', 422);
        }

        $limit = max(1, min(50, (int) ($v['limit'] ?? 20)));
        $q = trim((string) ($v['q'] ?? ''));
        $phone = trim((string) ($v['phone'] ?? ''));

        $query = Customer::query()->where('outlet_id', $outletId);

        if ($q !== '') {
            $qPhone = preg_replace('/\D+/', '', $q);
            $query->where(function ($w) use ($q, $qPhone) {
                $w->where('name', 'like', '%' . $q . '%');
                if ($qPhone) {
                    $w->orWhere('phone', 'like', '%' . $qPhone . '%');
                }
            });
        } elseif ($phone !== '') {
            $query->where('phone', $phone);
        } else {
            return ApiResponse::ok(['items' => []], 'OK');
        }

        return ApiResponse::ok([
            'items' => CustomerResource::collection($query->orderBy('name')->limit($limit)->get()),
        ], 'OK');
    }

    public function store(StoreCustomerRequest $request)
    {
        $v = $request->validated();
        $outletId = OutletScope::id($request);

        if (!$outletId) {
            return ApiResponse::error('Please select an outlet', 'OUTLET_REQUIRED', 422);
        }

        if ((string) $v['outlet_id'] !== (string) $outletId) {
            return ApiResponse::error('Outlet mismatch', 'OUTLET_MISMATCH', 403);
        }

        $existing = Customer::query()
            ->where('outlet_id', $outletId)
            ->where('phone', (string) $v['phone'])
            ->first();

        if ($existing) {
            return ApiResponse::error('Phone already registered', 'PHONE_EXISTS', 409, [], [
                'customer' => new CustomerResource($existing),
            ]);
        }

        $customer = Customer::query()->create([
            'outlet_id' => $outletId,
            'phone' => (string) $v['phone'],
            'name' => (string) $v['name'],
        ]);

        return ApiResponse::ok(new CustomerResource($customer), 'Customer created', 201);
    }
}
