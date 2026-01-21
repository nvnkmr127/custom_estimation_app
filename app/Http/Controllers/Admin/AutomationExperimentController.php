<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Automation;

class AutomationExperimentController extends Controller
{
    public function index()
    {
        $experiments = DB::table('automation_experiments')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('admin.automation.experiments.index', compact('experiments'));
    }

    public function create()
    {
        $events = [
            'estimate.created',
            'estimate.updated',
            'estimate.viewed',
            'estimate.accepted',
            'invoice.paid'
        ];
        return view('admin.automation.experiments.create', compact('events'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'trigger_event' => 'required|string',
            'variants' => 'required|array|min:2',
            'variants.*.automation_id' => 'required|exists:automations,id',
            'variants.*.weight' => 'required|integer|min:0|max:100',
        ]);

        DB::transaction(function () use ($request) {
            $experimentId = DB::table('automation_experiments')->insertGetId([
                'name' => $request->name,
                'trigger_event' => $request->trigger_event,
                'goal_event' => $request->goal_event,
                'description' => $request->description,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($request->variants as $variant) {
                DB::table('automation_experiment_variants')->insert([
                    'experiment_id' => $experimentId,
                    'automation_id' => $variant['automation_id'],
                    'weight' => $variant['weight'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()->route('automation.experiments.index')
            ->with('success', 'Experiment created successfully.');
    }

    public function show($id, \App\Services\Automation\ExperimentService $service)
    {
        $experiment = DB::table('automation_experiments')->where('id', $id)->first();
        if (!$experiment)
            abort(404);

        $variants = DB::table('automation_experiment_variants')
            ->join('automations', 'automation_experiment_variants.automation_id', '=', 'automations.id')
            ->where('experiment_id', $id)
            ->select('automation_experiment_variants.*', 'automations.name as automation_name')
            ->get();

        $confidence = 0;
        if ($variants->count() == 2) {
            $confidence = $service->calculateSignificance($variants[0], $variants[1]);
        }

        return view('admin.automation.experiments.show', compact('experiment', 'variants', 'confidence'));
    }

    public function start($id)
    {
        DB::table('automation_experiments')->where('id', $id)->update([
            'status' => 'running',
            'started_at' => now(),
            'updated_at' => now()
        ]);

        return back()->with('success', 'Experiment started.');
    }

    public function stop($id)
    {
        DB::table('automation_experiments')->where('id', $id)->update([
            'status' => 'paused',
            'ended_at' => now(),
            'updated_at' => now()
        ]);

        return back()->with('success', 'Experiment stopped.');
    }
}
