<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteType extends Model
{
    use HasFactory;

    protected $primaryKey = 'waste_type_id';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the collection requests for the waste type.
     */
    public function collectionRequests()
    {
        return $this->hasMany(CollectionRequest::class, 'waste_type_id', 'waste_type_id');
    }
}