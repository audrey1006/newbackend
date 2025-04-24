<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $primaryKey = 'district_id';

    protected $fillable = [
        'city_id',
        'name',
    ];

    /**
     * Get the city that owns the district.
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    /**
     * Get the client profiles for the district.
     */
    public function clientProfiles()
    {
        return $this->hasMany(ClientProfile::class, 'district_id', 'district_id');
    }

    /**
     * Get the waste collector profiles for the district.
     */
    public function wasteCollectorProfiles()
    {
        return $this->hasMany(WasteCollectorProfile::class, 'district_id', 'district_id');
    }

    /**
     * Get the collection requests for the district.
     */
    public function collectionRequests()
    {
        return $this->hasMany(CollectionRequest::class, 'district_id', 'district_id');
    }
}