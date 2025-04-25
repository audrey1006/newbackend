<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteCollectorProfile extends Model
{
    use HasFactory;

    protected $primaryKey = 'collector_id';

    protected $fillable = [
        'user_id',
        'district_id',
        'photo_path',
        'is_verified',
        'availability_status'
    ];

    protected $casts = [
        'is_verified' => 'boolean'
    ];

    /**
     * Get the user that owns the waste collector profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the district associated with the waste collector.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    /**
     * Get the collection requests assigned to this collector.
     */
    public function collectionRequests(): HasMany
    {
        return $this->hasMany(CollectionRequest::class, 'collector_id', 'collector_id');
    }

    /**
     * Get the city through district relationship
     */
    public function city()
    {
        return $this->district->city();
    }

    /**
     * Get the ratings for the waste collector.
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class, 'collector_id', 'collector_id');
    }

    /**
     * Calculate the average rating for the waste collector.
     */
    public function calculateAverageRating()
    {
        $this->rating = $this->ratings()->avg('score') ?? 0;
        $this->save();
    }
}