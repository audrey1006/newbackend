<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringCollection extends Model
{
    use HasFactory;

    protected $primaryKey = 'recurring_id';

    protected $fillable = [
        'request_id',
        'frequency',
        'day_of_week',
        'day_of_month',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the collection request that owns the recurring collection.
     */
    public function collectionRequest()
    {
        return $this->belongsTo(CollectionRequest::class, 'request_id', 'request_id');
    }
}