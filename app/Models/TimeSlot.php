<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSlot extends Model
{
    protected $table = 'collection_time_slots';
    protected $primaryKey = 'time_slot_id';

    protected $fillable = [
        'collection_time',
        'is_active'
    ];

    protected $casts = [
        'collection_time' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Get the collection days that use this time slot.
     */
    public function collectionDays(): HasMany
    {
        return $this->hasMany(CollectionDay::class, 'time_slot_id', 'time_slot_id');
    }
}