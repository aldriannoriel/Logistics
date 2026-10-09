<?php

namespace Tests\Feature;

use App\Models\Parcel;
use App\Models\PickupRequest;
use App\Models\Rider;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiderPickupFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_parcels_waiting_for_pickup_appear_in_the_available_queue(): void
    {
        [$riderUser] = $this->approvedRider();
        $available = $this->parcel('PLACED');
        $this->confirmedPickup($available);
        $arrived = $this->parcel('AT_SORTING_CENTER');
        $this->confirmedPickup($arrived);

        Sanctum::actingAs($riderUser);

        $this->getJson('/api/rider/pickups/available')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $available->id);
    }

    public function test_rider_cannot_accept_a_pickup_after_the_parcel_has_arrived(): void
    {
        [$riderUser, $rider] = $this->approvedRider();
        $parcel = $this->parcel('AT_SORTING_CENTER');
        $this->confirmedPickup($parcel);

        Sanctum::actingAs($riderUser);

        $this->postJson("/api/rider/pickups/{$parcel->id}/accept")
            ->assertUnprocessable()
            ->assertJsonPath('current_status', 'AT_SORTING_CENTER');

        $this->assertDatabaseHas('parcels', [
            'id' => $parcel->id,
            'pickup_rider_id' => null,
        ]);
    }

    private function approvedRider(): array
    {
        $sortingCenterUser = User::factory()->create(['role' => 'sorting_center']);
        $sortingCenter = SortingCenter::create([
            'user_id' => $sortingCenterUser->id,
            'business_name' => 'Pickup Test Center',
            'approval_status' => 'approved',
        ]);
        $user = User::factory()->create(['role' => 'rider']);
        $rider = Rider::create([
            'user_id' => $user->id,
            'sorting_center_id' => $sortingCenter->id,
            'name' => 'Pickup Test Rider',
            'contact' => '09170000000',
            'vehicle_type' => 'motorcycle',
            'plate_number' => 'PICKUP-1',
            'approval_status' => 'approved',
            'is_active' => true,
        ]);

        return [$user, $rider];
    }

    private function parcel(string $status): Parcel
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        return Parcel::create([
            'order_id' => 'PICKUP-'.Str::uuid(),
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'status' => $status,
            'delivery_province' => 'Metro Manila',
            'delivery_municipality' => 'Quezon City',
            'delivery_barangay' => 'Bagumbayan',
        ]);
    }

    private function confirmedPickup(Parcel $parcel): void
    {
        PickupRequest::create([
            'parcel_id' => $parcel->id,
            'seller_id' => $parcel->seller_id,
            'status' => 'confirmed',
        ]);
    }
}
