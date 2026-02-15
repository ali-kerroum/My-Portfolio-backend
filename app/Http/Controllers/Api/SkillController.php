<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index()
    {
        return Skill::orderBy('sort_order')->orderByDesc('created_at')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'icon' => 'nullable|string|max:5000',
            'accent' => 'nullable|string|max:20',
            'items' => 'nullable|array',
            'items.*' => 'string',
            'sort_order' => 'nullable|integer',
        ]);

        $skill = Skill::create($validated);

        return response()->json($skill, 201);
    }

    public function show(Skill $skill)
    {
        return $skill;
    }

    public function update(Request $request, Skill $skill)
    {
        $validated = $request->validate([
            'category' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:5000',
            'accent' => 'nullable|string|max:20',
            'items' => 'nullable|array',
            'items.*' => 'string',
            'sort_order' => 'nullable|integer',
        ]);

        $skill->update($validated);

        return response()->json($skill);
    }

    public function destroy(Skill $skill)
    {
        $skill->delete();

        return response()->json(['message' => 'Skill deleted successfully']);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:skills,id',
        ]);

        foreach ($request->ids as $index => $id) {
            Skill::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Order updated']);
    }
}
