<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterSortingCenterController;
use App\Http\Controllers\LoginSortingCenterController;
use App\Http\Controllers\RegisterRiderController;
use App\Http\Controllers\LoginRiderController;
use App\Http\Controllers\RiderPickupController;
use App\Http\Controllers\RiderDeliveryController;
use App\Http\Controllers\RiderManagementController;
use App\Http\Controllers\PickupRequestController;
use App\Http\Controllers\IncomingParcelController;
use App\Http\Controllers\ParcelSortingController;
use App\Http\Controllers\ParcelAssignmentController;
use App\Http\Controllers\ParcelMonitoringController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SortingCenterDirectoryController;
use App\Http\Controllers\DeliveryProofController;
use App\Http\Controllers\MessageController;

// Public Sorting Center Auth
Route::get('/sorting-centers', [SortingCenterDirectoryController::class, 'index']);
Route::post('/sorting-center/register', [RegisterSortingCenterController::class, 'register']);
Route::post('/sorting-center/login', [LoginSortingCenterController::class, 'login']);

// Public Rider Registration (for seeding & future Flutter Rider App)
Route::post('/rider/register', [RegisterRiderController::class, 'register']);
Route::post('/rider/login', [LoginRiderController::class, 'login']);

Route::middleware(['auth:sanctum', 'rider.approved'])->prefix('rider')->group(function () {
    Route::get('/pickups/available', [RiderPickupController::class, 'available']);
    Route::get('/pickups/mine', [RiderPickupController::class, 'mine']);
    Route::post('/pickups/{parcelId}/accept', [RiderPickupController::class, 'accept']);
    Route::post('/pickups/{parcelId}/confirm', [RiderPickupController::class, 'confirm']);
    Route::get('/deliveries', [RiderDeliveryController::class, 'index']);
    Route::post('/deliveries/{parcelId}/start', [RiderDeliveryController::class, 'start']);
    Route::post('/deliveries/{parcelId}/deliver', [RiderDeliveryController::class, 'deliver']);
    Route::post('/deliveries/{parcelId}/upload-proof', [RiderDeliveryController::class, 'uploadProof']);
    Route::post('/deliveries/{parcelId}/fail', [RiderDeliveryController::class, 'fail']);
    Route::get('/messages/recipients', [MessageController::class, 'recipients']);
    Route::get('/messages/conversations', [MessageController::class, 'conversations']);
    Route::get('/messages/conversations/{conversationId}', [MessageController::class, 'show']);
    Route::post('/messages', [MessageController::class, 'send']);
    Route::post('/messages/conversations/{conversationId}', [MessageController::class, 'send']);
});

// Protected Sorting Center Management
Route::middleware(['auth:sanctum', 'sorting_center.approved'])->group(function () {
    Route::get('/sorting-center/messages/recipients', [MessageController::class, 'recipients']);
    Route::get('/sorting-center/messages/conversations', [MessageController::class, 'conversations']);
    Route::get('/sorting-center/messages/conversations/{conversationId}', [MessageController::class, 'show']);
    Route::post('/sorting-center/messages', [MessageController::class, 'send']);
    Route::post('/sorting-center/messages/conversations/{conversationId}', [MessageController::class, 'send']);
    Route::get('/sorting-center/riders', [RiderManagementController::class, 'index']);
    Route::post('/sorting-center/riders/{id}/approve', [RiderManagementController::class, 'approve']);
    Route::post('/sorting-center/riders/{id}/reject', [RiderManagementController::class, 'reject']);
    Route::post('/sorting-center/riders/{id}/activate', [RiderManagementController::class, 'activate']);
    Route::post('/sorting-center/riders/{id}/deactivate', [RiderManagementController::class, 'deactivate']);
    Route::get('/sorting-center/pickup-requests', [PickupRequestController::class, 'index']);
    Route::post('/sorting-center/pickup-requests/{id}/confirm', [PickupRequestController::class, 'confirm']);
    Route::post('/sorting-center/parcels/mark-arrived', [IncomingParcelController::class, 'markArrived']);
    Route::get('/sorting-center/parcels/lookup/{orderId}', [IncomingParcelController::class, 'lookup']);
    Route::get('/sorting-center/parcels', [ParcelSortingController::class, 'index']);
    Route::get('/sorting-center/areas', [ParcelSortingController::class, 'areas']);
    Route::post('/sorting-center/areas', [ParcelSortingController::class, 'createArea']);
    Route::post('/sorting-center/parcels/{id}/sort', [ParcelSortingController::class, 'sort']);
    Route::get('/sorting-center/parcels/{id}/eligible-riders', [ParcelAssignmentController::class, 'eligibleRiders']);
    Route::post('/sorting-center/parcels/{id}/assign', [ParcelAssignmentController::class, 'assign']);
    Route::get('/sorting-center/parcels/monitoring', [ParcelMonitoringController::class, 'index']);
    Route::get('/sorting-center/dashboard', [DashboardController::class, 'index']);
    Route::get('/sorting-center/reports', [ReportController::class, 'index']);
    Route::get('/sorting-center/reports/delivery-stats', [ReportController::class, 'deliveryStats']);
    Route::get('/sorting-center/reports/transactions', [ReportController::class, 'transactions']);
    Route::get('/sorting-center/parcels/{parcelId}/proofs', [DeliveryProofController::class, 'getProofs']);
    Route::get('/sorting-center/proofs/{proofId}', [DeliveryProofController::class, 'getProof']);
});
