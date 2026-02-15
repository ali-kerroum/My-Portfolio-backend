<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index()
    {
        return Project::orderBy('sort_order')->orderByDesc('created_at')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'technologies' => 'nullable|array',
            'image' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'link' => 'nullable|string|max:500',
            'github' => 'nullable|string|max:500',
            'videos' => 'nullable|array',
            'images' => 'nullable|array',
            'stats' => 'nullable|array',
            'skills' => 'nullable|array',
            'problem' => 'nullable|string',
            'solution' => 'nullable|array',
            'benefits' => 'nullable|array',
            'sections' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);

        $project = Project::create($validated);

        return response()->json($project, 201);
    }

    public function show(Project $project)
    {
        return $project;
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'technologies' => 'nullable|array',
            'image' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'link' => 'nullable|string|max:500',
            'github' => 'nullable|string|max:500',
            'videos' => 'nullable|array',
            'images' => 'nullable|array',
            'stats' => 'nullable|array',
            'skills' => 'nullable|array',
            'problem' => 'nullable|string',
            'solution' => 'nullable|array',
            'benefits' => 'nullable|array',
            'sections' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);

        $project->update($validated);

        return response()->json($project);
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }

    /**
     * Reorder projects by an array of IDs.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:projects,id',
        ]);

        foreach ($request->ids as $index => $id) {
            Project::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Order updated']);
    }

    /**
     * Upload a file (image or video) for projects.
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,webp,mp4,webm,mov,avi|max:51200',
        ]);

        $file = $request->file('file');
        $isVideo = str_starts_with($file->getMimeType(), 'video/');
        $folder = $isVideo ? 'projects/videos' : 'projects/images';
        $path = $file->store($folder, 'public');

        return response()->json([
            'url' => url('storage/' . $path),
            'type' => $isVideo ? 'video' : 'image',
            'name' => $file->getClientOriginalName(),
        ]);
    }
}
