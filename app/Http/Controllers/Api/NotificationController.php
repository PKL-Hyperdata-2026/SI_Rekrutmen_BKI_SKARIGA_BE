<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected ResponseService $response
    ) {}

    public function index(Request $request): Responsable
    {
        $limit = (int) $request->query('limit', 15);
        $user = Auth::user();

        $notifications = $this->notificationService->getUserNotifications($user, $limit);

        return $this->response->data(NotificationResource::collection($notifications)->response()->getData(true));
    }

    public function unread(): Responsable
    {
        $user = Auth::user();
        $unreadNotifications = $this->notificationService->getUnreadNotifications($user);

        return $this->response
            ->data(NotificationResource::collection($unreadNotifications))
            ->with('total_unread', $unreadNotifications->count());
    }

    public function markAsRead(string $notification): Responsable
    {
        $user = Auth::user();

        if (! Str::isUuid($notification)) {
            return $this->response
                ->success(false)
                ->message('Notifikasi tidak ditemukan.')
                ->code(404);
        }

        $notification = $this->notificationService->markAsRead($user, $notification);

        return $this->response
            ->success(true)
            ->message('Notification marked as read')
            ->data(new NotificationResource($notification))
            ->code(200);
    }

    public function markAllAsRead(): Responsable
    {
        $user = Auth::user();
        $updatedRows = $this->notificationService->markAllAsRead($user);

        return $this->response
            ->message('All notifications marked as read')
            ->with('affected_rows', $updatedRows)
            ->code(200);
    }
}
