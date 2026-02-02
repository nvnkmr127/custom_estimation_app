<?php

namespace App\Http\Controllers;

use App\Models\EventLog;
use Illuminate\Http\Request;

class EventLogController extends Controller
{
    /**
     * Display a listing of event logs.
     */
    public function index(Request $request)
    {
        $query = EventLog::query()->select([
            'id',
            'event_id',
            'event_name',
            'entity_type',
            'entity_id',
            'triggered_by',
            'source',
            'status',
            'created_at',
            // Exclude 'payload' for performance
        ]);

        // Filters
        if ($request->filled('event_name')) {
            $query->where('event_name', 'like', '%' . $request->event_name . '%');
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $events = $query->latest()->paginate(50)->withQueryString();

        // Summary Stats for grouping
        $summary = \App\Models\EventLog::where('created_at', '>=', now()->subDays(7))
            ->select('event_name', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('event_name')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        $typeDistribution = \App\Models\EventLog::where('created_at', '>=', now()->subDays(7))
            ->select('entity_type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('entity_type')
            ->get();

        return view('events.index', compact('events', 'summary', 'typeDistribution'));
    }

    /**
     * Display the specified event log.
     */
    public function show(EventLog $eventLog)
    {
        // Recursively mask sensitive keys in the payload for display
        $maskSensitive = function (array $data) use (&$maskSensitive) {
            $sensitiveKeys = ['password', 'token', 'secret', 'key', 'auth', 'api_key', 'access_token'];

            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] = $maskSensitive($value);
                } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                    $data[$key] = '********';
                }
            }
            return $data;
        };

        $maskedPayload = $eventLog->payload ? $maskSensitive($eventLog->payload) : [];

        return view('events.show', compact('eventLog', 'maskedPayload'));
    }
}
