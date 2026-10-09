<?php

namespace Tests\Feature;

use App\Models\Parcel;
use App\Models\DeliveryProof;
use App\Models\Rider;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiderDeliveryCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rider_can_mark_an_assigned_delivery_as_delivered(): void
    {
        [$user, $rider] = $this->riderUser();
        $parcel = $this->parcel($rider->id);
        DeliveryProof::create([
            'parcel_id' => $parcel->id,
            'rider_id' => $rider->id,
            'type' => 'PHOTO',
            'file_path' => 'delivery-proofs/test.jpg',
            'captured_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->postJson("/api/rider/deliveries/{$parcel->id}/deliver")
            ->assertOk()
            ->assertJsonPath('data.status', 'DELIVERED')
            ->assertJsonPath('message', 'Delivery marked as delivered.');

        $this->assertDatabaseHas('parcels', [
            'id' => $parcel->id,
            'status' => 'DELIVERED',
        ]);
    }

    public function test_rider_must_upload_proof_before_marking_delivery_as_delivered(): void
    {
        [$user, $rider] = $this->riderUser();
        $parcel = $this->parcel($rider->id);
        Sanctum::actingAs($user);

        $this->postJson("/api/rider/deliveries/{$parcel->id}/deliver")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Upload delivery proof before marking this parcel as delivered.');

        $this->assertDatabaseHas('parcels', [
            'id' => $parcel->id,
            'status' => 'OUT_FOR_DELIVERY',
        ]);
    }

    public function test_assigned_rider_can_start_a_delivery(): void
    {
        [$user, $rider] = $this->riderUser();
        $parcel = $this->parcel($rider->id, 'AT_SORTING_CENTER');
        $parcel->update(['assigned_at' => null]);
        Sanctum::actingAs($user);

        $this->postJson("/api/rider/deliveries/{$parcel->id}/start")
            ->assertOk()
            ->assertJsonPath('data.status', 'OUT_FOR_DELIVERY');

        $this->assertDatabaseHas('parcels', [
            'id' => $parcel->id,
            'status' => 'OUT_FOR_DELIVERY',
        ]);
    }

    public function test_rider_can_mark_an_assigned_delivery_failed_with_a_reason(): void
    {
        [$user, $rider] = $this->riderUser();
        $parcel = $this->parcel($rider->id);
        Sanctum::actingAs($user);

        $this->postJson("/api/rider/deliveries/{$parcel->id}/fail", [
            'reason' => 'Customer not available',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'FAILED_RETURNED')
            ->assertJsonPath('data.failure_reason', 'Customer not available');

        $this->assertDatabaseHas('parcels', [
            'id' => $parcel->id,
            'status' => 'FAILED_RETURNED',
            'failure_reason' => 'Customer not available',
        ]);
    }

    public function test_rider_cannot_update_another_riders_delivery(): void
    {
        [$user] = $this->riderUser();
        [, $owner] = $this->riderUser();
        $parcel = $this->parcel($owner->id);
        Sanctum::actingAs($user);

        $this->postJson("/api/rider/deliveries/{$parcel->id}/deliver")
            ->assertForbidden()
            ->assertJsonPath('message', 'This delivery is not assigned to you.');

        $this->assertDatabaseHas('parcels', [
            'id' => $parcel->id,
            'status' => 'OUT_FOR_DELIVERY',
        ]);
    }

    public function test_rider_cannot_update_a_delivery_that_is_not_out_for_delivery(): void
    {
        [$user, $rider] = $this->riderUser();
        $parcel = $this->parcel($rider->id, 'DELIVERED');
        Sanctum::actingAs($user);

        $this->postJson("/api/rider/deliveries/{$parcel->id}/deliver")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This delivery is no longer out for delivery.');
    }

    private function riderUser(): array
    {
        $sortingCenterUser = User::factory()->create(['role' => 'sorting_center']);
        $sortingCenter = SortingCenter::create([
            'user_id' => $sortingCenterUser->id,
            'business_name' => 'Delivery Test Center',
            'approval_status' => 'approved',
        ]);
        $user = User::factory()->create(['role' => 'rider']);
        $rider = Rider::create([
            'user_id' => $user->id,
            'sorting_center_id' => $sortingCenter->id,
            'name' => 'Test Rider',
            'contact' => '09170000000',
            'vehicle_type' => 'motorcycle',
            'plate_number' => 'TEST-777',
            'approval_status' => 'approved',
            'is_active' => true,
        ]);

        return [$user, $rider];
    }

    private function parcel(int $riderId, string $status = 'OUT_FOR_DELIVERY'): Parcel
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        return Parcel::create([
            'order_id' => 'TEST-DELIVERY-'.uniqid(),
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'status' => $status,
            'delivery_province' => 'Metro Manila',
            'delivery_municipality' => 'Taguig',
            'delivery_barangay' => 'Fort Bonifacio',
            'delivery_street' => 'Delivery Street',
            'delivery_house_number' => '101',
            'assigned_rider_id' => $riderId,
            'assigned_at' => now(),
        ]);
    }
}
