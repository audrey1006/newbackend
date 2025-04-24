<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $primaryKey = 'message_id';

    public $timestamps = true;
    const UPDATED_AT = null; // Only need created_at for messages

    protected $fillable = [
        'request_id',
        'sender_id',
        'receiver_id',
        'content',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Get the collection request that owns the message.
     */
    public function collectionRequest()
    {
        return $this->belongsTo(CollectionRequest::class, 'request_id', 'request_id');
    }

    /**
     * Get the user that sent the message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    /**
     * Get the user that received the message.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id', 'user_id');
    }
}