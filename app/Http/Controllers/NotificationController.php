<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = Auth::id();
        $rows = DB::table('notifications')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $notifications = $rows->map(fn($n) => [
            'id'         => $n->id,
            'title'      => $n->title,
            'message'    => $n->message,
            'icon'       => $n->icon,
            'link'       => $n->link,
            'is_read'    => (bool)$n->is_read,
            'time_ago'   => \Carbon\Carbon::parse($n->created_at)->diffForHumans(),
            'date_label' => \Carbon\Carbon::parse($n->created_at)->format('F j, Y'),
            'time_label' => \Carbon\Carbon::parse($n->created_at)->format('g:i A'),
        ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => DB::table('notifications')
                                  ->where('user_id', $userId)
                                  ->where('is_read', false)
                                  ->count(),
        ]);
    }

    public function markRead(int $id): JsonResponse
    {
        DB::table('notifications')
            ->where('id', $id)->where('user_id', Auth::id())
            ->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        DB::table('notifications')
            ->where('user_id', Auth::id())
            ->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::table('notifications')
            ->where('id', $id)->where('user_id', Auth::id())
            ->delete();
        return response()->json(['success' => true]);
    }

    public function clearAll(): JsonResponse
    {
        DB::table('notifications')->where('user_id', Auth::id())->delete();
        return response()->json(['success' => true]);
    }
}