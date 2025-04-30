<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $primaryKey = 'rating_id';

    protected $fillable = [
        'request_id',
        'client_id',
        'collector_id',
        'score',
        'comment'
    ];

    protected $casts = [
        'score' => 'integer'
    ];

    /**
     * Get the collection request associated with this rating.
     */
    public function collectionRequest()
    {
        return $this->belongsTo(CollectionRequest::class, 'request_id', 'request_id');
    }

    /**
     * Get the client who gave the rating.
     */
    public function client()
    {
        return $this->belongsTo(ClientProfile::class, 'client_id', 'client_id');
    }

    /**
     * Get the waste collector who received the rating.
     */
    public function collector()
    {
        return $this->belongsTo(WasteCollectorProfile::class, 'collector_id', 'collector_id');
    }
}