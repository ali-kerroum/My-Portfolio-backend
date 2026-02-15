<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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
     * Check if Cloudinary is properly configured (not a placeholder).
     */
    private function isCloudinaryConfigured(): bool
    {
        $url = config('cloudinary.url');
        if (!$url) return false;
        $parsed = parse_url($url);
        $cloudName = $parsed['host'] ?? '';
        // Reject placeholder values
        return $cloudName && $cloudName !== 'CLOUD_NAME' && !str_contains($cloudName, 'YOUR_');
    }

    /**
     * Return a signed Cloudinary upload signature.
     * The frontend uploads directly to Cloudinary (bypasses Render's 30s timeout).
     */
    public function cloudinarySignature(Request $request)
    {
        if (!$this->isCloudinaryConfigured()) {
            return response()->json(['message' => 'Cloudinary not configured', 'use_server' => true], 422);
        }

        $request->validate([
            'folder' => 'nullable|string',
        ]);

        $parsed = parse_url(config('cloudinary.url'));
        $apiKey = $parsed['user'] ?? '';
        $apiSecret = urldecode($parsed['pass'] ?? '');
        $cloudName = $parsed['host'] ?? '';

        $timestamp = time();
        $folder = $request->input('folder', 'projects');

        $paramsToSign = "folder={$folder}&timestamp={$timestamp}";
        $signature = sha1($paramsToSign . $apiSecret);

        return response()->json([
            'signature' => $signature,
            'timestamp' => $timestamp,
            'cloud_name' => $cloudName,
            'api_key' => $apiKey,
            'folder' => $folder,
        ]);
    }

    /**
     * Upload a file (image or video) via server.
     * Uses Cloudinary if properly configured, otherwise local storage.
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,webp,mp4,webm,mov,avi|max:102400',
        ]);

        $file = $request->file('file');
        $isVideo = str_starts_with($file->getMimeType(), 'video/');

        if ($this->isCloudinaryConfigured()) {
            try {
                if ($isVideo) {
                    $uploadedFile = Cloudinary::uploadVideo($file->getRealPath(), [
                        'folder' => 'projects/videos',
                        'resource_type' => 'video',
                        'chunk_size' => 6000000,
                        'timeout' => 600,
                    ]);
                } else {
                    $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                        'folder' => 'projects/images',
                        'timeout' => 120,
                    ]);
                }
                $url = $uploadedFile->getSecurePath();
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Cloudinary upload failed: ' . $e->getMessage(),
                ], 500);
            }
        } else {
            // Local storage fallback
            $folder = $isVideo ? 'projects/videos' : 'projects/images';
            $path = $file->store($folder, 'public');
            $url = url('storage/' . $path);
        }

        return response()->json([
            'url' => $url,
            'type' => $isVideo ? 'video' : 'image',
            'name' => $file->getClientOriginalName(),
        ]);
    }
}
