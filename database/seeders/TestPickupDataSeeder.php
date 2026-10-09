<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Parcel;
use App\Models\Rider;
use App\Models\SortingCenter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestPickupDataSeeder extends Seeder
{
    public function run(): void
    {
        $sortingCenterUser = User::updateOrCreate(
            ['email' => 'sortingcenter@test.com'],
            [
                'name' => 'Test Sorting Center',
                'password' => Hash::make('password'),
                'role' => 'sorting_center',
            ],
        );

        $sortingCenter = SortingCenter::updateOrCreate(
            ['user_id' => $sortingCenterUser->id],
            [
                'business_name' => 'Test Sorting Center',
                'approval_status' => 'approved',
            ],
        );

        $seller = User::updateOrCreate(
            ['email' => 'seller@test.com'],
            [
                'name' => 'Test Seller',
                'password' => Hash::make('password'),
                'role' => 'seller',
            ],
        );

        $buyer = User::updateOrCreate(
            ['email' => 'buyer@test.com'],
            [
                'name' => 'Test Buyer',
                'password' => Hash::make('password'),
                'role' => 'buyer',
            ],
        );

        $area = Area::updateOrCreate(
            [
                'sorting_center_id' => $sortingCenter->id,
                'name' => 'Makati',
            ],
        );

        $riderUser = User::updateOrCreate(
            ['email' => 'rider@test.com'],
            [
                'name' => 'Test Rider',
                'password' => Hash::make('password'),
                'role' => 'rider',
            ],
        );

        $rider = Rider::updateOrCreate(
            ['user_id' => $riderUser->id],
            [
                'sorting_center_id' => $sortingCenter->id,
                'name' => 'Test Rider',
                'contact' => '09170000000',
                'vehicle_type' => 'motorcycle',
                'plate_number' => 'TEST-777',
                'approval_status' => 'approved',
                'is_active' => true,
            ],
        );
        $now = now();
        $parcels = [
            'TEST-001' => [
                'status' => 'PICKED_UP',
                'sorting_center_id' => null,
            ],
            'TEST-002' => [
                'status' => 'PICKED_UP',
                'sorting_center_id' => null,
            ],
            'TEST-003' => [
                'status' => 'AT_SORTING_CENTER',
                'sorting_center_id' => $sortingCenter->id,
            ],
            'TEST-004' => [
                'status' => 'OUT_FOR_DELIVERY',
                'sorting_center_id' => $sortingCenter->id,
                'sorted_area_id' => $area->id,
                'assigned_rider_id' => $rider->id,
                'sorted_at' => $now->copy()->subMinutes(10),
                'assigned_at' => $now->copy()->subMinutes(5),
            ],
            'TEST-005' => [
                'status' => 'DELIVERED',
                'sorting_center_id' => $sortingCenter->id,
                'sorted_area_id' => $area->id,
                'assigned_rider_id' => $rider->id,
                'sorted_at' => $now->copy()->subHours(2),
                'assigned_at' => $now->copy()->subHour(),
            ],
            'TEST-006' => [
                'status' => 'AT_SORTING_CENTER',
                'sorting_center_id' => $sortingCenter->id,
                'sorted_area_id' => $area->id,
                'sorted_at' => $now,
            ],
        ];

        foreach ($parcels as $orderId => $workflow) {
            Parcel::updateOrCreate(
                ['order_id' => $orderId],
                [
                    'buyer_id' => $buyer->id,
                    'seller_id' => $seller->id,
                    'sorting_center_id' => $workflow['sorting_center_id'],
                    'status' => $workflow['status'],
                    'delivery_province' => 'Metro Manila',
                    'delivery_municipality' => 'Makati',
                    'delivery_barangay' => 'Test Barangay',
                    'delivery_street' => 'Test Street',
                    'delivery_house_number' => '123',
                    'sorted_area_id' => $workflow['sorted_area_id'] ?? null,
                    'assigned_rider_id' => $workflow['assigned_rider_id'] ?? null,
                    'sorted_at' => $workflow['sorted_at'] ?? null,
                    'assigned_at' => $workflow['assigned_at'] ?? null,
                ],
            );
        }

        $legacyArea = Area::where('sorting_center_id', $sortingCenter->id)
            ->where('name', 'Test Area')
            ->first();

        if ($legacyArea) {
            DB::transaction(function () use ($legacyArea, $sortingCenter, $rider, $area): void {
                $areaIds = [$area->id];
                $legacyParcels = Parcel::where('sorted_area_id', $legacyArea->id)
                    ->get(['id', 'delivery_municipality'])
                    ->groupBy(fn (Parcel $parcel): string => trim($parcel->delivery_municipality));

                foreach ($legacyParcels as $municipality => $parcelsForMunicipality) {
                    if ($municipality === '') {
                        throw new \RuntimeException('A legacy test parcel is missing its delivery municipality.');
                    }

                    $cityArea = Area::firstOrCreate([
                        'sorting_center_id' => $sortingCenter->id,
                        'name' => $municipality,
                    ]);
                    Parcel::whereIn('id', $parcelsForMunicipality->pluck('id'))
                        ->update(['sorted_area_id' => $cityArea->id]);
                    $areaIds[] = $cityArea->id;
                }

                $rider->areas()->sync(array_unique($areaIds));
                $legacyRiders = Rider::whereIn('id', DB::table('rider_areas')
                    ->where('area_id', $legacyArea->id)
                    ->select('rider_id'))
                    ->get();
                foreach ($legacyRiders as $legacyRider) {
                    $legacyRider->areas()->syncWithoutDetaching($areaIds);
                }

                if (!Parcel::where('sorted_area_id', $legacyArea->id)->exists()) {
                    $legacyArea->delete();
                }
            });
        }

        $municipalities = Parcel::where('sorting_center_id', $sortingCenter->id)
            ->whereNotNull('delivery_municipality')
            ->distinct()
            ->pluck('delivery_municipality');
        $cityAreaIds = Area::where('sorting_center_id', $sortingCenter->id)
            ->whereIn('name', $municipalities)
            ->pluck('id')
            ->all();
        $rider->areas()->sync(array_unique(array_merge([$area->id], $cityAreaIds)));

        $this->command?->info('Test sorting center, rider, area, and parcels are ready.');
    }
}
