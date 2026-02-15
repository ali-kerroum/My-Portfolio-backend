<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * Store a new contact message (public endpoint).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $msg = ContactMessage::create($validated);

        return response()->json($msg, 201);
    }

    /**
     * List all messages (admin only).
     */
    public function index()
    {
        return ContactMessage::orderByDesc('created_at')->get();
    }

    /**
     * Mark a message as read.
     */
    public function markRead(ContactMessage $contactMessage)
    {
        $contactMessage->update(['read' => true]);

        return response()->json($contactMessage);
    }

    /**
     * Delete a message.
     */
    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return response()->json(['message' => 'Message deleted']);
    }

    /**
     * Get unread count.
     */
    public function unreadCount()
    {
        return response()->json([
            'count' => ContactMessage::where('read', false)->count(),
        ]);
    }
}
