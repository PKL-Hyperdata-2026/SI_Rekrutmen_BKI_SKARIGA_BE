<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\isNull;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 15);

        $notifications = Notification::forUser(Auth::user())
            ->latest()
            ->paginate($limit);

        return response()->json([
            'data' => $notifications,
        ], 200);
    }

    public function unread(): JsonResponse
    {
        $unreadNotifications = Notification::forUser(Auth::user())
            ->unread()
            ->latest()
            ->get();

        return response()->json([
            'total_unread' => $unreadNotifications->count(),
            'data' => $unreadNotifications,
        ], 200);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $notification = Notification::forUser(Auth::user())
            ->where('id', $id)
            ->firstOrFail();

        if (isNull($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => $notification,
        ], 201);
    }

    public function markAllAsRead(): JsonResponse
    {
        $updatedRows = Notification::forUser(Auth::user())
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'affected_rows' => $updatedRows,
        ], 201);
    }
}
