<?php

namespace App\Http\Controllers;

use App\Models\Waste;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WasteController extends Controller
{
public function store(Request $request)
{ 
    $validator = Validator::make($request->all(), [
        'waste_type' => 'required|in:recyclable,non-recyclable',
        'user_id' => 'required|exists:users,id',
        'weight' => 'required|integer|min:1',
        'date' => 'required|date|after_or_equal:today',
        'shift' => 'in:9AM-12PM,12PM-3PM,3PM-6PM',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'address' => 'required|string|max:255',
        'status' => 'in:pending,accepted,rejected,re-scheduled'
    ]);

    if ($validator->fails()) {
        $errors = $validator->errors();

        return response()->json([
            'status' => false,
            'message' => 'Validation failed.',
            'errors' => $errors,
        ], 422);
    }

    $data = $validator->validated();

    if (!isset($data['status'])) {
        $data['status'] = 'pending';
    }

    $waste = Waste::create($data);

    return response()->json([
        'status' => true,
        'message' => 'Waste request submitted successfully.',
        'data' => $waste,
    ], 201);
}
}
