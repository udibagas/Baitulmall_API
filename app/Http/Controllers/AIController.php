<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;

class AIController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function generateDescription(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
        ]);

        $description = $this->geminiService->generateProductDescription(
            $request->input('name'),
            $request->input('category')
        );

        return response()->json([
            'description' => $description
        ]);
    }
}
