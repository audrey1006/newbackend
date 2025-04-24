<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteCollectorProfile extends Model
{
    use HasFactory;

    protected $primaryKey = 'collector_id';

    protected $fillable = [
        'user_id',
        'district_id',
        'photo_url',
        'is_available',
        'rating',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'rating' => 'decimal:2',
    ];

    /**
     * Get the user that owns the waste collector profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the district that the waste collector belongs to.
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    /**
     * Get the collection requests for the waste collector.
     */
    public function collectionRequests()
    {
        return $this->hasMany(CollectionRequest::class, 'collector_id', 'collector_id');
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