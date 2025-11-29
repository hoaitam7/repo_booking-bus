<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TripController extends Controller
{
    //detail một chuyến xe
    public function show($id): JsonResponse
    {
        $trip = Trip::with([
            'route',
            'bus',
            'route.pickupPoints'
        ])->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Chuyến xe không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $trip
        ]);
    }
}
