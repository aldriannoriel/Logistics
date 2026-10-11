<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\PickupRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PickupRequestController extends Controller
{
    /**
     * Store a new pickup request and its associated parcel.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string|max:50|unique:parcels,order_id',
            'buyer_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'buyer')],
            'delivery_province' => 'required|string|max:100',
            'delivery_municipality' => 'required|string|max:100',
            'delivery_barangay' => 'required|string|max:100',
            'delivery_street' => 'nullable|string|max:255',
            'delivery_house_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        return DB::transaction(function () use ($request, $validated) {
            // Create the parcel
            $parcel = Parcel::create([
                'order_id' => $validated['order_id'],
                'buyer_id' => $validated['buyer_id'],
                'seller_id' => $request->user()->id,
                'delivery_province' => $validated['delivery_province'],
                'delivery_municipality' => $validated['delivery_municipality'],
                'delivery_barangay' => $validated['delivery_barangay'],
                'delivery_street' => $validated['delivery_street'] ?? null,
                'delivery_house_number' => $validated['delivery_house_number'] ?? null,
                'status' => 'PLACED',
            ]);

            // Create the pickup request
            $pickupRequest = PickupRequest::create([
                'parcel_id' => $parcel->id,
                'seller_id' => $request->user()->id,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Pickup request submitted successfully',
                'parcel' => $parcel,
                'pickup_request' => $pickupRequest
            ], 201);
        });
    }
}
