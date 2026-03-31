<?php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\EmployeeShift;

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

    public function checkShift(): JsonResponse
    {
        $user     = Auth::user();
        $employee = DB::table('employees')->where('user_id', $user->id)->first();
        if (!$employee) return response()->json(['checked' => true]);

        $now       = Carbon::now();
        $today     = $now->toDateString();
        $time      = $now->format('H:i');
        $todayName = $now->dayName;

        $empShift = EmployeeShift::with('shift')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->where('effective_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->latest('effective_date')
            ->first();

        if (!$empShift || !$empShift->shift) return response()->json(['checked' => true]);

        // Skip if today is a day off
        $daysOff = $empShift->days_off ?? [];
        if (is_string($daysOff)) $daysOff = json_decode($daysOff, true) ?? [];
        if (in_array($todayName, $daysOff)) return response()->json(['checked' => true]);

        $shift         = $empShift->shift;
        $breakSchedule = $empShift->break_schedule ?? $shift->break_schedule ?? null;
        if (is_string($breakSchedule)) $breakSchedule = json_decode($breakSchedule, true);

        $notifications = [];

        // Shift end
        if (Carbon::parse($shift->end_time)->format('H:i') === $time) {
            $notifications[] = [
                'title'   => 'Shift Ended',
                'message' => "Your shift ({$shift->name}) has ended. Don't forget to clock out!",
                'icon'    => 'notice',
            ];
        }

        // Break start
        if ($breakSchedule && isset($breakSchedule['start']) &&
            Carbon::parse($breakSchedule['start'])->format('H:i') === $time) {
            $end = $breakSchedule['end'] ?? '';
            $notifications[] = [
                'title'   => 'Break Time',
                'message' => "It's break time!" . ($end ? " Your break runs until {$end}." : ''),
                'icon'    => 'notice',
            ];
        }

        // Break end
        if ($breakSchedule && isset($breakSchedule['end']) &&
            Carbon::parse($breakSchedule['end'])->format('H:i') === $time) {
            $notifications[] = [
                'title'   => 'Break Over',
                'message' => 'Your break has ended. Time to get back to work!',
                'icon'    => 'notice',
            ];
        }

        foreach ($notifications as $notif) {
            $exists = DB::table('notifications')
                ->where('user_id', $user->id)
                ->where('title', $notif['title'])
                ->whereDate('created_at', $today)
                ->exists();

            if (!$exists) {
                DB::table('notifications')->insert([
                    'user_id'    => $user->id,
                    'title'      => $notif['title'],
                    'message'    => $notif['message'],
                    'icon'       => $notif['icon'],
                    'is_read'    => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['checked' => true]);
    }
}