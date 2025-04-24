<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\CollectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Display a listing of messages for a collection request.
     */
    public function index($requestId)
    {
        $messages = Message::where('request_id', $requestId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['messages' => $messages]);
    }

    /**
     * Store a newly created message.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:collection_requests,request_id',
            'sender_id' => 'required|exists:users,user_id',
            'receiver_id' => 'required|exists:users,user_id',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier que l'expéditeur et le destinataire sont liés à la demande de collecte
        $collectionRequest = CollectionRequest::with(['client.user', 'collector.user'])->findOrFail($request->request_id);

        $validUsers = [
            $collectionRequest->client->user->user_id,
            $collectionRequest->collector ? $collectionRequest->collector->user->user_id : null
        ];

        if (!in_array($request->sender_id, $validUsers) || !in_array($request->receiver_id, $validUsers)) {
            return response()->json([
                'error' => 'Sender and receiver must be associated with the collection request'
            ], 400);
        }

        $message = Message::create([
            'request_id' => $request->request_id,
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => $message->load(['sender', 'receiver']),
            'status' => 'Message sent successfully'
        ], 201);
    }

    /**
     * Display the specified message.
     */
    public function show($id)
    {
        $message = Message::with(['sender', 'receiver'])->findOrFail($id);
        return response()->json(['message' => $message]);
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,message_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Message::whereIn('message_id', $request->message_ids)
            ->where('receiver_id', Auth::id())
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read successfully']);
    }

    /**
     * Get unread messages count for a user.
     */
    public function getUnreadCount($userId)
    {
        $count = Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Get conversation between two users for a specific collection request.
     */
    public function getConversation($requestId, $user1Id, $user2Id)
    {
        $messages = Message::where('request_id', $requestId)
            ->where(function ($query) use ($user1Id, $user2Id) {
                $query->where(function ($q) use ($user1Id, $user2Id) {
                    $q->where('sender_id', $user1Id)
                        ->where('receiver_id', $user2Id);
                })->orWhere(function ($q) use ($user1Id, $user2Id) {
                    $q->where('sender_id', $user2Id)
                        ->where('receiver_id', $user1Id);
                });
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['messages' => $messages]);
    }

    /**
     * Get all conversations for a user.
     */
    public function getUserConversations($userId)
    {
        $conversations = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver', 'collectionRequest'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('request_id');

        return response()->json(['conversations' => $conversations]);
    }

    /**
     * Delete a message.
     */
    public function destroy($id)
    {
        $message = Message::findOrFail($id);

        // Vérifier que l'utilisateur est l'expéditeur du message
        if ($message->sender_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized to delete this message'], 403);
        }

        $message->delete();

        return response()->json(['message' => 'Message deleted successfully']);
    }
}