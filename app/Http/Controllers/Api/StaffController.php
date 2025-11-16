<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $staff = Staff::where('business_id', $business->id)
            ->when($request->has('active'), fn($q) => $q->where('active', $request->boolean('active')))
            ->paginate($request->get('per_page', 15));

        return response()->json($staff);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'passcode' => 'required|string|size:4',
            'active' => 'boolean',
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'passcode_hash' => Hash::make($validated['passcode']),
            'active' => $validated['active'] ?? true,
        ]);

        return response()->json($staff->makeHidden(['passcode_hash']), 201);
    }

    public function show(string $slug, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $staff = Staff::where('business_id', $business->id)->findOrFail($id);

        return response()->json($staff->makeHidden(['passcode_hash']));
    }

    public function update(Request $request, string $slug, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $staff = Staff::where('business_id', $business->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'passcode' => 'sometimes|string|size:4',
            'active' => 'boolean',
        ]);

        if (isset($validated['passcode'])) {
            $validated['passcode_hash'] = Hash::make($validated['passcode']);
            unset($validated['passcode']);
        }

        $staff->update($validated);

        return response()->json($staff->makeHidden(['passcode_hash']));
    }

    public function destroy(string $slug, string $id): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $staff = Staff::where('business_id', $business->id)->findOrFail($id);
        $staff->delete();

        return response()->json(['message' => 'Staff eliminado']);
    }
}

