<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $query = Customer::query()
            ->whereHas('customerTokens', fn($q) => $q->where('business_id', $business->id))
            ->with(['customerCampaigns.campaign', 'customerStreaks']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('short_code', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate($request->get('per_page', 15));

        return response()->json($customers);
    }

    public function show(string $slug, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $customer = Customer::whereHas('customerTokens', fn($q) => $q->where('business_id', $business->id))
            ->with([
                'customerCampaigns.campaign',
                'customerCampaigns.stamps',
                'customerStreaks.campaign',
                'customerFieldValues.customField',
            ])
            ->findOrFail($id);

        return response()->json($customer);
    }

    public function update(Request $request, string $slug, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $customer = Customer::whereHas('customerTokens', fn($q) => $q->where('business_id', $business->id))
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|min:10|max:15',
        ]);

        $customer->update($validated);

        return response()->json($customer);
    }
}

