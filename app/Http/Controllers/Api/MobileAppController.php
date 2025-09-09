<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileAppController extends Controller
{
    /**
     * Test authentication
     */
    public function testAuth(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Authentication working',
            'user' => $request->user()
        ]);
    }
}
