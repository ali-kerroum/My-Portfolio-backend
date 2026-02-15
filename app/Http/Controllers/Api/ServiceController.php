<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return Service::orderBy('sort_order')->orderByDesc('created_at')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|string|max:10',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'items' => 'nullable|array',
            'items.*' => 'string',
            'icon' => 'nullable|string|max:5000',
            'sort_order' => 'nullable|integer',
        ]);

        $service = Service::create($validated);

        return response()->json($service, 201);
    }

    public function show(Service $service)
    {
        return $service;
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'number' => 'sometimes|required|string|max:10',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'items' => 'nullable|array',
            'items.*' => 'string',
            'icon' => 'nullable|string|max:5000',
            'sort_order' => 'nullable|integer',
        ]);

        $service->update($validated);

        return response()->json($service);
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json(['message' => 'Service deleted successfully']);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:services,id',
        ]);

        foreach ($request->ids as $index => $id) {
            Service::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Order updated']);
    }
}
