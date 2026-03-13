<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $notifications,
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(string $notification): JsonResponse
    {
        $targetNotification = auth()->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if ($targetNotification->read_at === null) {
            $targetNotification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread_count' => auth()->user()->fresh()->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }
}
