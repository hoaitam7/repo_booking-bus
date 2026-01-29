<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
            //tùy chọn
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

            // Filter theo khung giờ - CHUẨN VIỆT NAM (GMT+7)
            if ($request->filled('departure_time_range')) {
                $timeRange = $request->departure_time_range;

                // Giờ Việt Nam (Asia/Ho_Chi_Minh)
                $timeRangesVN = [
                    'morning' => ['06:00:00', '12:00:00'],    // 6h-12h VN
                    'afternoon' => ['13:00:00', '18:00:00'],   // 13h-18h VN
                    'evening' => ['19:00:00', '21:00:00'],     // 19h-21h VN
                ];

                if (isset($timeRangesVN[$timeRange])) {
                    [$startTimeVN, $endTimeVN] = $timeRangesVN[$timeRange];

               
                    $startTimeUTC = Carbon::createFromFormat('H:i:s', $startTimeVN, 'Asia/Ho_Chi_Minh')
                        ->format('H:i:s');

                    $endTimeUTC = Carbon::createFromFormat('H:i:s', $endTimeVN, 'Asia/Ho_Chi_Minh')
                        ->format('H:i:s');

                    $departQuery->whereTime('departure_time', '>=', $startTimeUTC)
                        ->whereTime('departure_time', '<=', $endTimeUTC);
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

                // Filter theo loại xe
                if ($request->filled('bus_type')) {
                    $returnQuery->whereHas('bus', function ($q) use ($request) {
                        $q->where('bus_type', $request->bus_type);
                    });
                }

                // Filter theo khung giờ - CHUẨN VIỆT NAM
                if ($request->filled('departure_time_range')) {
                    $timeRange = $request->departure_time_range;

                    $timeRangesVN = [
                        'morning' => ['06:00:00', '12:00:00'],
                        'afternoon' => ['13:00:00', '18:00:00'],
                        'evening' => ['19:00:00', '21:00:00'],
                    ];

                    if (isset($timeRangesVN[$timeRange])) {
                        [$startTimeVN, $endTimeVN] = $timeRangesVN[$timeRange];

                        $startTimeUTC = Carbon::createFromFormat('H:i:s', $startTimeVN, 'Asia/Ho_Chi_Minh')
                            ->setTimezone('UTC')
                            ->format('H:i:s');

                        $endTimeUTC = Carbon::createFromFormat('H:i:s', $endTimeVN, 'Asia/Ho_Chi_Minh')
                            ->setTimezone('UTC')
                            ->format('H:i:s');

                        $returnQuery->whereTime('departure_time', '>=', $startTimeUTC)
                            ->whereTime('departure_time', '<=', $endTimeUTC);
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

    /**
     * Helper function để convert giờ VN sang UTC
     */
    private function convertVNTimeToUTC($timeVN)
    {
        return Carbon::createFromFormat('H:i:s', $timeVN, 'Asia/Ho_Chi_Minh')
            ->setTimezone('UTC')
            ->format('H:i:s');
    }
}
