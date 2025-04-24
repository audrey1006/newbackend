<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $primaryKey = 'city_id';

    protected $fillable = [
        'name',
    ];

    /**
     * Get the districts for the city.
     */
    public function districts()
    {
        return $this->hasMany(District::class, 'city_id', 'city_id');
    }
}