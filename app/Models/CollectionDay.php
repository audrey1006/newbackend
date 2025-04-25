<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionDay extends Model
{
    protected $table = 'collection_days';
    protected $primaryKey = 'collection_day_id';

    protected $fillable = [
        'request_id',
        'time_slot_id',
        'collection_date'
    ];

    protected $casts = [
        'collection_date' => 'date'
    ];

    /**
     * Get the collection request that owns this day.
     */
    public function collectionRequest(): BelongsTo
    {
        return $this->belongsTo(CollectionRequest::class, 'request_id', 'request_id');
    }

    /**
     * Get the time slot for this collection day.
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id', 'time_slot_id');
    }
}