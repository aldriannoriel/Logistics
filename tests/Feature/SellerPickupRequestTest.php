<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SellerPickupRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_submit_pickup_request(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        Sanctum::actingAs($seller);

        $payload = [
            'order_id' => 'ORD-12345',
            'buyer_id' => $buyer->id,
            'delivery_province' => 'Metro Manila',
            'delivery_municipality' => 'Quezon City',
            'delivery_barangay' => 'Barangay 1',
            'delivery_street' => 'Sample Street',
            'delivery_house_number' => '12A',
        ];

        $this->postJson('/api/seller/pickup-requests', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['message' => 'Pickup request submitted successfully']);

        $this->assertDatabaseHas('parcels', [
            'order_id' => 'ORD-12345', 'status' => 'PLACED',
            'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'delivery_street' => 'Sample Street', 'delivery_house_number' => '12A',
        ]);
        $this->assertDatabaseHas('pickup_requests', ['status' => 'pending']);
    }

    public function test_unauthenticated_user_cannot_submit_pickup_request(): void
    {
        $this->postJson('/api/seller/pickup-requests', [])
            ->assertStatus(401);
    }

    public function test_buyer_reference_must_exist_and_have_the_buyer_role(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Sanctum::actingAs($seller);
        $payload = [
            'order_id' => 'INVALID-BUYER', 'delivery_province' => 'Metro Manila',
            'delivery_municipality' => 'Quezon City', 'delivery_barangay' => 'Barangay 1',
        ];
        foreach ([null, $seller->id, 999999] as $buyerId) {
            $this->postJson('/api/seller/pickup-requests', array_merge($payload, ['buyer_id' => $buyerId]))
                ->assertUnprocessable()->assertJsonValidationErrors('buyer_id');
        }
        $this->assertDatabaseCount('parcels', 0);
        $this->assertDatabaseCount('pickup_requests', 0);
    }

    public function test_non_seller_cannot_submit_pickup_request(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        Sanctum::actingAs($buyer);

        $this->postJson('/api/seller/pickup-requests', [])
            ->assertStatus(403);
    }
}
