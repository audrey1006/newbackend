<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    use HasFactory;

    protected $primaryKey = 'client_id';

    protected $fillable = [
        'user_id',
        'district_id',
    ];

    /**
     * Get the user that owns the client profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the district that the client belongs to.
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    /**
     * Get the collection requests for the client.
     */
    public function collectionRequests()
    {
        return $this->hasMany(CollectionRequest::class, 'client_id', 'client_id');
    }

    /**
     * Get the ratings given by the client.
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class, 'client_id', 'client_id');
    }
}