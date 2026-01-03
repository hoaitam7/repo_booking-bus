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
            'bus_type' => 'nullable|in:sleeper,standard,premium',
            'departure_time_range' => 'nullable|in:morning,afternoon,evening',
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
            $departQuery = Trip::with(['route', 'bus'])
                ->where('route_id', $departRoute->id)
                ->whereDate('departure_time', $request->departure_date)
                ->where('status', 'scheduled')
                ->where('available_seats', '>', 0);

            // Filter theo loại xe
            if ($request->filled('bus_type')) {
                $departQuery->whereHas('bus', function ($q) use ($request) {
                    $q->where('bus_type', $request->bus_type);
                });
            }

            // Filter theo khung giờ
            if ($request->filled('departure_time_range')) {
                $timeRange = $request->departure_time_range;

                if ($timeRange === 'morning') {
                    $departQuery->whereTime('departure_time', '>=', '06:00:00')
                        ->whereTime('departure_time', '<=', '12:00:00');
                } elseif ($timeRange === 'afternoon') {
                    $departQuery->whereTime('departure_time', '>=', '13:00:00')
                        ->whereTime('departure_time', '<=', '18:00:00');
                } elseif ($timeRange === 'evening') {
                    $departQuery->whereTime('departure_time', '>=', '19:00:00')
                        ->whereTime('departure_time', '<=', '21:00:00');
                }
            }

            $departTrips = $departQuery->orderBy('departure_time', 'asc')->get();
        }

        // Tìm chuyến về (nếu là khứ hồi)
        $returnTrips = [];
        if ($request->trip_type === 'round_trip' && $request->filled('return_date')) {
            $returnRoute = Route::where('from_city', $request->to_city)
                ->where('to_city', $request->from_city)
                ->first();

            if ($returnRoute) {
                $returnQuery = Trip::with(['route', 'bus'])
                    ->where('route_id', $returnRoute->id)
                    ->whereDate('departure_time', $request->return_date)
                    ->where('status', 'scheduled')
                    ->where('available_seats', '>', 0);

                // Áp dụng filter tương tự cho chuyến về
                if ($request->filled('bus_type')) {
                    $returnQuery->whereHas('bus', function ($q) use ($request) {
                        $q->where('bus_type', $request->bus_type);
                    });
                }

                if ($request->filled('departure_time_range')) {
                    $timeRange = $request->departure_time_range;

                    if ($timeRange === 'morning') {
                        $returnQuery->whereTime('departure_time', '>=', '06:00:00')
                            ->whereTime('departure_time', '<=', '12:00:00');
                    } elseif ($timeRange === 'afternoon') {
                        $returnQuery->whereTime('departure_time', '>=', '13:00:00')
                            ->whereTime('departure_time', '<=', '18:00:00');
                    } elseif ($timeRange === 'evening') {
                        $returnQuery->whereTime('departure_time', '>=', '19:00:00')
                            ->whereTime('departure_time', '<=', '21:00:00');
                    }
                }

                $returnTrips = $returnQuery->orderBy('departure_time', 'asc')->get();
            }
        }

        // Kiểm tra nếu không có chuyến nào
        if (empty($departTrips) && empty($returnTrips)) {
            return response()->json([
                'success' => true,
                'message' => 'Không tìm thấy chuyến xe phù hợp',
                'data' => [
                    'trip_type' => $request->trip_type,
                    'depart_trips' => [],
                    'return_trips' => []
                ]
            ]);
        }
        // Format response chỉ với data cần thiết
        $responseData = [
            'success' => true,
            'data' => [
                'trip_type' => $request->trip_type,
                'depart_trips' => $departTrips,
                'return_trips' => $returnTrips
            ]
        ];

        // Chỉ thêm message nếu có kết quả 1 chiều nhưng thiếu chiều về
        if ($request->trip_type === 'round_trip' && count($departTrips) > 0 && count($returnTrips) === 0) {
            $responseData['message'] = 'Tìm thấy chuyến đi nhưng không có chuyến về phù hợp';
        }

        return response()->json($responseData);
    }
}
