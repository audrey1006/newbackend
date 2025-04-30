<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Error extends Model
{
    use HasFactory;

    protected $table = 'errors';

    protected $fillable = [
        'type',
        'message',
        'status',
        'resolved_at'
    ];

    protected $casts = [
        'resolved_at' => 'datetime'
    ];
}