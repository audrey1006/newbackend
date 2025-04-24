<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for a user.
     */
    public function index($userId)
    {
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['notifications' => $notifications]);
    }

    /**
     * Store a newly created notification.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'title' => 'required|string|max:100',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $notification = Notification::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'message' => $request->message,
        ]);

        return response()->json([
            'notification' => $notification,
            'message' => 'Notification created successfully'
        ], 201);
    }

    /**
     * Display the specified notification.
     */
    public function show($id)
    {
        $notification = Notification::findOrFail($id);
        return response()->json(['notification' => $notification]);
    }

    /**
     * Mark notifications as read.
     */
    public function markAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,notification_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Notification::whereIn('notification_id', $request->notification_ids)
            ->where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Notifications marked as read successfully']);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead($userId)
    {
        Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read successfully']);
    }

    /**
     * Get unread notifications count for a user.
     */
    public function getUnreadCount($userId)
    {
        $count = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Get unread notifications for a user.
     */
    public function getUnreadNotifications($userId)
    {
        $notifications = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['notifications' => $notifications]);
    }

    /**
     * Delete a notification.
     */
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);

        // Vérifier que l'utilisateur est le propriétaire de la notification
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized to delete this notification'], 403);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }

    /**
     * Delete all read notifications for a user.
     */
    public function deleteReadNotifications($userId)
    {
        Notification::where('user_id', $userId)
            ->where('is_read', true)
            ->delete();

        return response()->json(['message' => 'All read notifications deleted successfully']);
    }
}