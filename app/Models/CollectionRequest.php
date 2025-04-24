<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'request_id';

    protected $fillable = [
        'client_id',
        'collector_id',
        'waste_type_id',
        'district_id',
        'status',
        'collection_type',
        'frequency',
        'scheduled_date',
        'completed_date',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'completed_date' => 'datetime',
    ];

    /**
     * Get the client that owns the collection request.
     */
    public function client()
    {
        return $this->belongsTo(ClientProfile::class, 'client_id', 'client_id');
    }

    /**
     * Get the waste collector assigned to the collection request.
     */
    public function collector()
    {
        return $this->belongsTo(WasteCollectorProfile::class, 'collector_id', 'collector_id');
    }

    /**
     * Get the waste type for the collection request.
     */
    public function wasteType()
    {
        return $this->belongsTo(WasteType::class, 'waste_type_id', 'waste_type_id');
    }

    /**
     * Get the district for the collection request.
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    /**
     * Get the recurring collection settings for the collection request.
     */
    public function recurringCollection()
    {
        return $this->hasOne(RecurringCollection::class, 'request_id', 'request_id');
    }

    /**
     * Get the messages for the collection request.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'request_id', 'request_id');
    }

    /**
     * Get the rating for the collection request.
     */
    public function rating()
    {
        return $this->hasOne(Rating::class, 'request_id', 'request_id');
    }
}