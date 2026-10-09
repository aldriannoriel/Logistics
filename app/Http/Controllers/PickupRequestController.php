<?php

namespace App\Http\Controllers;

use App\Models\PickupRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PickupRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');
        $sortingCenterId = $request->user()->sortingCenter->id;

        $pickupRequests = PickupRequest::query()
            ->with([
                'parcel:id,order_id,seller_id,status,delivery_province,delivery_municipality,delivery_barangay,delivery_street,delivery_house_number',
                'seller:id,name,email',
            ])
            ->where('status', $status)
            ->where(function ($query) use ($sortingCenterId): void {
                $query->whereNull('sorting_center_id')
                    ->orWhere('sorting_center_id', $sortingCenterId);
            })
            ->orderByDesc('requested_at')
            ->get();

        return response()->json([
            'message' => 'Pickup requests retrieved successfully.',
            'data' => $pickupRequests,
        ]);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $sortingCenterId = $request->user()->sortingCenter->id;
        $pickupRequest = DB::transaction(function () use ($id, $sortingCenterId): PickupRequest {
            $pickupRequest = PickupRequest::query()->whereKey($id)->lockForUpdate()->first();

            if (!$pickupRequest) {
                abort(response()->json([
                    'message' => 'Pickup request not found.',
                ], 404));
            }

            if ($pickupRequest->sorting_center_id !== null
                && (int) $pickupRequest->sorting_center_id !== (int) $sortingCenterId) {
                abort(response()->json([
                    'message' => 'This pickup request belongs to another sorting center.',
                ], 404));
            }

            if ($pickupRequest->status !== 'pending') {
                abort(response()->json([
                    'message' => 'Only pending pickup requests can be confirmed.',
                    'status' => $pickupRequest->status,
                ], 422));
            }

            $parcel = $pickupRequest->parcel()->lockForUpdate()->first();

            if ($parcel->sorting_center_id !== null
                && (int) $parcel->sorting_center_id !== (int) $sortingCenterId) {
                abort(response()->json([
                    'message' => 'This parcel is assigned to another sorting center.',
                ], 409));
            }

            $pickupRequest->update([
                'status' => 'confirmed',
                'sorting_center_id' => $sortingCenterId,
            ]);
            $parcel->update(['sorting_center_id' => $sortingCenterId]);

            return $pickupRequest->fresh([
                'parcel:id,order_id,seller_id,status,delivery_province,delivery_municipality,delivery_barangay,delivery_street,delivery_house_number',
                'seller:id,name,email',
            ]);
        });

        return response()->json([
            'message' => 'Pickup request confirmed successfully.',
            'data' => $pickupRequest,
        ]);
    }
}
