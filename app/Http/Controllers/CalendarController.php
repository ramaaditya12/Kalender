<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Response;

class CalendarController extends Controller
{
    /**
     * Display the calendar page.
     */
    public function index(): View
    {
        $events = Event::all()->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start->toIso8601String(),
                'end' => $event->end?->toIso8601String(),
                'color' => $event->color,
            ];
        });

        return view('calendar.index', [
            'eventsJson' => json_encode($events)
        ]);
    }

    /**
     * Store a new event.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'start' => ['required', 'date'],
                'end' => ['nullable', 'date', 'after_or_equal:start'],
                'color' => ['nullable', 'string', 'max:7'],
            ]);

            // Ensure dates are properly formatted
            $validated['start'] = date('Y-m-d H:i:s', strtotime($validated['start']));
            if (!empty($validated['end'])) {
                $validated['end'] = date('Y-m-d H:i:s', strtotime($validated['end']));
            }

            $event = Event::create($validated);

            return response()->json($event, 201);
        } catch (\Exception $e) {
            \Log::error('Event creation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create event: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start' => ['required', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $event->update($validated);

        return response()->json($event);
    }

    /**
     * Remove the specified event.
     */
    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json(null, 204);
    }
}