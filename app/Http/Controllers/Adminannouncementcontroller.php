<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminAnnouncementController extends Controller
{
    /**
     * Store a new announcement.
     * TODO: implement full DB logic when database is ready.
     */
    public function store(Request $request)
    {
        // ── Basic validation ──────────────────────────────────────────────
        $validated = $request->validate([
            'type'          => 'required|in:notice,process_done,caution,warning',
            'title'         => 'required|string|max:255',
            'message'       => 'required|string',
            'audience'      => 'required|in:everyone,department',
            'department_id' => 'nullable',   // change to exists:departments,id once DB is ready
        ]);

        // ── TODO: save to database once migration is done ─────────────────
        // Announcement::create([
        //     'type'          => $validated['type'],
        //     'title'         => $validated['title'],
        //     'message'       => $validated['message'],
        //     'audience'      => $validated['audience'],
        //     'department_id' => $validated['department_id'] ?? null,
        //     'created_by'    => auth()->id(),
        // ]);

        return response()->json(['message' => 'Announcement received.'], 200);
    }
}