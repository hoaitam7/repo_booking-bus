<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    /**
     * Tìm kiếm chuyến xe (một chiều + khứ hồi)
     */
    public function searchTrips(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_city' => 'required|string',
            'to_city' => 'required|string',
            'departure_date' => 'required|date',
            'trip_type' => 'required|in:one_way,round_trip',
            'return_date' => 'required_if:trip_type,round_trip|date|after:departure_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors()
            ], 422);
        }

        // Tìm tuyến đi
        $departRoute = Route::where('from_city', $request->from_city)
            ->where('to_city', $request->to_city)
            ->first();

        // Tìm chuyến đi
        $departTrips = [];
        if ($departRoute) {
            $departTrips = Trip::with(['bus', 'route'])
                ->where('route_id', $departRoute->id)
                ->whereDate('departure_time', $request->departure_date)
                ->where('status', 'scheduled')
                ->where('available_seats', '>', 0)
                ->orderBy('departure_time')
                ->get();
        }

        // Tìm chuyến về (nếu là khứ hồi)
        $returnTrips = [];
        if ($request->trip_type === 'round_trip' && $request->return_date) {
            $returnRoute = Route::where('from_city', $request->to_city) // Đảo ngược
                ->where('to_city', $request->from_city)
                ->first();

            if ($returnRoute) {
                $returnTrips = Trip::with(['bus', 'route'])
                    ->where('route_id', $returnRoute->id)
                    ->whereDate('departure_time', $request->return_date)
                    ->where('status', 'scheduled')
                    ->where('available_seats', '>', 0)
                    ->orderBy('departure_time')
                    ->get();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'trip_type' => $request->trip_type,
                'depart_trips' => $departTrips,
                'return_trips' => $returnTrips,
                'search_info' => [
                    'from_city' => $request->from_city,
                    'to_city' => $request->to_city,
                    'departure_date' => $request->departure_date,
                    'return_date' => $request->return_date,
                ]
            ]
        ]);
    }
}
