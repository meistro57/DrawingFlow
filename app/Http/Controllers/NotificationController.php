<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $notifications = $user
            ->notifications()
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $notifications,
            'unread_count' => $this->unreadCount($user),
        ]);
    }

    public function markAsRead(string $notification): JsonResponse
    {
        $user = auth()->user();
        $targetNotification = $user
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if ($targetNotification->read_at === null) {
            $targetNotification->markAsRead();
        }

        Cache::forget($this->unreadCountCacheKey($user));

        return response()->json([
            'success' => true,
            'unread_count' => $this->unreadCount($user->fresh()),
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();
        Cache::forget($this->unreadCountCacheKey($user));

        return response()->json([
            'success' => true,
            'unread_count' => $this->unreadCount($user->fresh()),
        ]);
    }

    private function unreadCount(User $user): int
    {
        return Cache::remember(
            $this->unreadCountCacheKey($user),
            now()->addSeconds(30),
            fn (): int => $user->unreadNotifications()->count()
        );
    }

    private function unreadCountCacheKey(User $user): string
    {
        return "users:{$user->id}:notification_unread_count";
    }
}
