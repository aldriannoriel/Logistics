<?php

namespace App\Http\Controllers;

use App\Models\DeliveryProof;
use App\Models\Parcel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryProofController extends Controller
{
    public function getProofs(Request $request, int $parcelId): JsonResponse
    {
        $sortingCenterId = $request->user()->sorting_center?->id;

        $parcel = Parcel::query()
            ->where('sorting_center_id', $sortingCenterId)
            ->whereKey($parcelId)
            ->first();

        if (!$parcel) {
            return response()->json(['message' => 'Parcel not found.'], 404);
        }

        $proofs = DeliveryProof::query()
            ->where('parcel_id', $parcelId)
            ->with(['rider:id,name,email'])
            ->orderByDesc('captured_at')
            ->get([
                'id',
                'parcel_id',
                'rider_id',
                'type',
                'file_path',
                'notes',
                'captured_at',
            ]);

        return response()->json([
            'message' => 'Delivery proofs retrieved successfully.',
            'data' => [
                'parcel' => [
                    'id' => $parcel->id,
                    'order_id' => $parcel->order_id,
                    'status' => $parcel->status,
                    'delivery_street' => $parcel->delivery_street,
                    'delivery_house_number' => $parcel->delivery_house_number,
                ],
                'proofs' => $proofs,
            ],
        ]);
    }

    public function getProof(Request $request, int $proofId): JsonResponse
    {
        $proof = DeliveryProof::query()
            ->with(['parcel', 'rider:id,name,email'])
            ->whereKey($proofId)
            ->first();

        if (!$proof) {
            return response()->json(['message' => 'Proof not found.'], 404);
        }

        $sortingCenterId = $request->user()->sorting_center?->id;
        if ($proof->parcel->sorting_center_id !== $sortingCenterId) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        return response()->json([
            'message' => 'Delivery proof retrieved successfully.',
            'data' => [
                'id' => $proof->id,
                'parcel_id' => $proof->parcel_id,
                'rider' => [
                    'id' => $proof->rider->id,
                    'name' => $proof->rider->name,
                    'email' => $proof->rider->email,
                ],
                'type' => $proof->type,
                'file_url' => asset('storage/' . $proof->file_path),
                'notes' => $proof->notes,
                'captured_at' => $proof->captured_at,
            ],
        ]);
    }
}
