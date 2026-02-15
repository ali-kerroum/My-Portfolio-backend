<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactLink;
use Illuminate\Http\Request;

class ContactLinkController extends Controller
{
    public function index()
    {
        return ContactLink::orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'href' => 'required|string|max:500',
            'icon_svg' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $link = ContactLink::create($validated);

        return response()->json($link, 201);
    }

    public function show(ContactLink $contactLink)
    {
        return $contactLink;
    }

    public function update(Request $request, ContactLink $contactLink)
    {
        $validated = $request->validate([
            'label' => 'sometimes|required|string|max:100',
            'href' => 'sometimes|required|string|max:500',
            'icon_svg' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $contactLink->update($validated);

        return response()->json($contactLink);
    }

    public function destroy(ContactLink $contactLink)
    {
        $contactLink->delete();

        return response()->json(['message' => 'Contact link deleted successfully']);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:contact_links,id',
        ]);

        foreach ($request->ids as $index => $id) {
            ContactLink::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['message' => 'Order updated']);
    }
}
