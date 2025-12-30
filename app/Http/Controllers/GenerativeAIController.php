<?php

namespace App\Http\Controllers;

use App\Services\AIService;
use Illuminate\Http\Request;

class GenerativeAIController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'attributes' => 'nullable|array',
        ]);

        $description = $this->aiService->generateDescription(
            $request->input('name'),
            $request->input('attributes', [])
        );

        return response()->json(['description' => $description]);
    }
}
