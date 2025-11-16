<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Staff;
use App\Services\StampService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StampController extends Controller
{
    public function __construct(
        private StampService $stampService
    ) {}

    public function addStamp(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();
        $staff = $request->user('staff');

        if (!$staff || $staff->business_id !== $business->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'campaign_id' => 'required|uuid|exists:campaigns,id',
            'meta' => 'nullable|array',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $campaign = Campaign::where('business_id', $business->id)
            ->findOrFail($validated['campaign_id']);

        $stamp = $this->stampService->addStamp(
            $customer,
            $campaign,
            $staff,
            $validated['meta'] ?? []
        );

        return response()->json([
            'stamp' => $stamp->load(['customerCampaign', 'staff']),
            'customer_campaign' => $stamp->customerCampaign->load('campaign'),
        ], 201);
    }

    public function index(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $query = \App\Models\Stamp::query()
            ->whereHas('customerCampaign.campaign', fn($q) => $q->where('business_id', $business->id))
            ->with(['customerCampaign.customer', 'customerCampaign.campaign', 'staff']);

        if ($request->has('customer_id')) {
            $query->whereHas('customerCampaign', fn($q) => $q->where('customer_id', $request->customer_id));
        }

        if ($request->has('campaign_id')) {
            $query->whereHas('customerCampaign', fn($q) => $q->where('campaign_id', $request->campaign_id));
        }

        $stamps = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($stamps);
    }
}

