<?php

namespace App\Http\Controllers;

use App\Models\SortingCenter;
use Illuminate\Http\JsonResponse;

class SortingCenterDirectoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SortingCenter::query()
                ->where('approval_status', 'approved')
                ->orderBy('business_name')
                ->get(['id', 'business_name']),
        ]);
    }
}
