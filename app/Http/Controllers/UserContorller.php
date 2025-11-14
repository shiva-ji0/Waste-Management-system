<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserContorller extends Controller
{
    public function show(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not authenticated.',
        ], 401);
    }

    return response()->json([
        'status' => true,
        'message' => 'User details fetched successfully.',
        'data' => $user,
    ]);
}
public function wastes(Request $request)
{
    $user = $request->user();

    
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not authenticated.',
        ], 401);
    }
      $wastes = $user->waste()->latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'User waste records fetched successfully.',
            'data' => $wastes,
        ]);
}
}
