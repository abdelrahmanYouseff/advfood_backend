<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMsg;
use Illuminate\Http\Request;

class WhatsappMsgController extends Controller
{
    /**
     * Store a new WhatsApp message payload (deliver order + location).
     *
     * Example payload:
     * {
     *   "deliver_order": "Deliver Order #123",
     *   "location": "https://maps.google.com/... or any text"
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'deliver_order' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        $msg = WhatsappMsg::create([
            'deliver_order' => $validated['deliver_order'] ?? null,
            'location' => $validated['location'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp message saved successfully',
            'data' => [
                'id' => $msg->id,
                'deliver_order' => $msg->deliver_order,
                'location' => $msg->location,
                'created_at' => $msg->created_at,
            ],
        ], 201);
    }

    /**
     * Delete a WhatsApp message.
     */
    public function destroy($id)
    {
        try {
            $msg = WhatsappMsg::findOrFail($id);
            $msg->delete();

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp message deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete WhatsApp message: ' . $e->getMessage(),
            ], 404);
        }
    }
}


