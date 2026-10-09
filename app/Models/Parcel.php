<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Parcel extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'buyer_id',
        'seller_id',
        'sorting_center_id',
        'status',
        'delivery_province',
        'delivery_municipality',
        'delivery_barangay',
        'delivery_street',
        'delivery_house_number',
        'sorted_at',
        'sorted_area_id',
        'assigned_rider_id',
        'pickup_rider_id',
        'assigned_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'sorted_at' => 'datetime',
            'assigned_at' => 'datetime',
        ];
    }

    public function scopeInSortingCenterWorkflow(Builder $query, int $sortingCenterId): Builder
    {
        return $query
            ->whereIn('status', [
                'AT_SORTING_CENTER',
                'OUT_FOR_DELIVERY',
                'DELIVERED',
                'FAILED_RETURNED',
            ])
            ->where('sorting_center_id', $sortingCenterId);
    }

    public function sortingCenter(): BelongsTo
    {
        return $this->belongsTo(SortingCenter::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class);
    }

    public function sortedArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'sorted_area_id');
    }

    public function assignedRider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, 'assigned_rider_id');
    }

    public function deliveryProofs(): HasMany
    {
        return $this->hasMany(DeliveryProof::class);
    }
}
