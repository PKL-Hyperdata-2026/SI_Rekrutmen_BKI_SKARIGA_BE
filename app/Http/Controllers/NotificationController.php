<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        protected ResponseService $response
    ) {}

    public function index(Request $request): Responsable
    {
        $limit = $request->query('limit', 15);

        $notifications = Notification::forUser(Auth::user())
            ->latest()
            ->paginate($limit);

        return $this->response->data($notifications);
    }

    public function unread(): Responsable
    {
        $unreadNotifications = Notification::forUser(Auth::user())
            ->unread()
            ->latest()
            ->get();

        return $this->response
            ->data($unreadNotifications)
            ->with('total_unread', $unreadNotifications->count());
    }

    public function markAsRead(string $id): Responsable
    {
        $notification = Notification::forUser(Auth::user())
            ->where('id', $id)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return $this->response
            ->success(true)
            ->message('Notification marked as read')
            ->data($notification)
            ->code(201);
    }

    public function markAllAsRead(): Responsable
    {
        $updatedRows = Notification::forUser(Auth::user())
            ->unread()
            ->update(['read_at' => now()]);

        return $this->response
            ->message('All notifications marked as read')
            ->with('affected_rows', $updatedRows)
            ->code(201);
    }
}
