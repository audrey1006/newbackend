<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Scope a query to only include pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'en attente')
            ->whereNull('collector_id');
    }

    /**
     * Scope a query to only include accepted requests.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'acceptée');
    }

    /**
     * Scope a query to only include completed requests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'effectuée');
    }

    /**
     * Get the client that owns the collection request.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class, 'client_id', 'client_id');
    }

    /**
     * Get the waste collector assigned to the collection request.
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(WasteCollectorProfile::class, 'collector_id', 'collector_id');
    }

    /**
     * Get the waste type for the collection request.
     */
    public function wasteType(): BelongsTo
    {
        return $this->belongsTo(WasteType::class, 'waste_type_id', 'waste_type_id');
    }

    /**
     * Get the district for the collection request.
     */
    public function district(): BelongsTo
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

    /**
     * Get the collection days for this request.
     */
    public function collectionDays(): HasMany
    {
        return $this->hasMany(CollectionDay::class, 'request_id', 'request_id');
    }
}