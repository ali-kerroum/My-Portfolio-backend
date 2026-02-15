<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function index()
    {
        return Experience::orderBy('sort_order')->orderByDesc('created_at')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|string|max:255',
            'period' => 'required|string|max:100',
            'organization' => 'required|string|max:255',
            'icon' => 'nullable|string|max:5000',
            'accent' => 'nullable|string|max:20',
            'points' => 'nullable|array',
            'points.*' => 'string',
            'sort_order' => 'nullable|integer',
        ]);

        $experience = Experience::create($validated);

        return response()->json($experience, 201);
    }

    public function show(Experience $experience)
    {
        return $experience;
    }

    public function update(Request $request, Experience $experience)
    {
        $validated = $request->validate([
            'role' => 'sometimes|required|string|max:255',
            'period' => 'sometimes|required|string|max:100',
            'organization' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:5000',
            'accent' => 'nullable|string|max:20',
            'points' => 'nullable|array',
            'points.*' => 'string',
            'sort_order' => 'nullable|integer',
        ]);

        $experience->update($validated);

        return response()->json($experience);
    }

    public function destroy(Experience $experience)
    {
        $experience->delete();

        return response()->json(['message' => 'Experience deleted successfully']);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:experiences,id',
        ]);

        foreach ($request->ids as $index => $id) {
            Experience::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Order updated']);
    }
}
