<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Route;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; //tìm hiểu


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

    /**
     * Lấy danh sách chuyến đi (cho admin)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Trip::with(['route', 'bus']);

        // Filter theo ngày
        if ($request->has('date')) {
            $date = Carbon::parse($request->date)->startOfDay();
            $query->whereDate('departure_time', $date);
        }

        // Filter theo route
        if ($request->has('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        // Filter theo status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Chỉ lấy chuyến tương lai (cho user)
        if ($request->has('upcoming') && $request->upcoming == true) {
            $query->where('departure_time', '>=', now())
                ->where('status', 'scheduled');
        }
        $trips = $query->orderBy('departure_time', 'asc')->paginate(10);
        return response()->json([
            'success' => true,
            'data' => $trips
        ]);
    }

    /**
     * Tạo 1 chuyến đơn lẻ (cho admin)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'route_id' => 'required|exists:routes,id',
            'bus_id' => 'required|exists:buses,id',
            'departure_time' => 'required|date|after_or_equal:now',
            'ticket_price' => 'required|numeric|min:0',
            'available_seats' => 'required|integer|min:0',
            'status' => 'required|in:scheduled,cancelled,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $trip = Trip::create([
            'route_id' => $request->route_id,
            'bus_id' => $request->bus_id,
            'departure_time' => $request->departure_time,
            'ticket_price' => $request->ticket_price,
            'available_seats' => $request->available_seats,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo chuyến đi thành công',
            'data' => $trip->load(['route', 'bus'])
        ], 201);
    }

    /**
     * TẠO NHIỀU CHUYẾN CÙNG LÚC (quan trọng)
     * Admin chỉ cần tạo 1 lần cho nhiều ngày
     */
    // public function bulkCreate(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'route_id' => 'required|exists:routes,id',
    //         'bus_id' => 'required|exists:buses,id',
    //         'departure_time' => 'required|date_format:H:i', // Giờ cố định: 06:00
    //         'ticket_price' => 'required|numeric|min:0',
    //         'start_date' => 'required|date|after_or_equal:today',
    //         'end_date' => 'required|date|after:start_date',
    //         'repeat_days' => 'required|array',
    //         'repeat_days.*' => 'integer|min:1|max:7', // 1=CN, 7=T7
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Dữ liệu không hợp lệ',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     // Lấy thông tin xe để biết số ghế
    //     $bus = Bus::find($request->bus_id);
    //     $createdTrips = [];

    //     $start = Carbon::parse($request->start_date);
    //     $end = Carbon::parse($request->end_date);

    //     // Vòng lặp qua từng ngày
    //     while ($start->lte($end)) {
    //         // Kiểm tra ngày trong tuần
    //         if (in_array($start->dayOfWeekIso, $request->repeat_days)) {
    //             // Tạo datetime = ngày + giờ cố định
    //             $departureDateTime = $start->copy()
    //                 ->setTimeFromTimeString($request->departure_time);

    //             // Chỉ tạo nếu thời gian ở tương lai
    //             if ($departureDateTime->greaterThan(now())) {
    //                 $trip = Trip::create([
    //                     'route_id' => $request->route_id,
    //                     'bus_id' => $request->bus_id,
    //                     'departure_time' => $departureDateTime,
    //                     'ticket_price' => $request->ticket_price,
    //                     'available_seats' => $bus->total_seats, // Lấy từ bảng buses
    //                     'status' => 'scheduled',
    //                 ]);

    //                 $createdTrips[] = $trip;
    //             }
    //         }

    //         $start->addDay();
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Đã tạo ' . count($createdTrips) . ' chuyến đi',
    //         'data' => $createdTrips
    //     ], 201);
    // }
    /**
     * Cập nhật chuyến đi
     */
    public function update(Request $request, $id): JsonResponse
    {
        $trip = Trip::find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy chuyến đi'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'bus_id' => 'sometimes|exists:buses,id',
            'departure_time' => 'sometimes|date',
            'ticket_price' => 'sometimes|numeric|min:0',
            'available_seats' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:scheduled,cancelled,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $trip->update($request->only([
            'bus_id',
            'departure_time',
            'ticket_price',
            'available_seats',
            'status'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật chuyến đi thành công',
            'data' => $trip->load(['route', 'bus'])
        ]);
    }

    /**
     * Xóa chuyến đi
     */
    public function destroy($id): JsonResponse
    {
        $trip = Trip::find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy chuyến đi'
            ], 404);
        }

        // Kiểm tra nếu đã có đặt vé thì không cho xóa
        if ($trip->bookings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa chuyến đi đã có đặt vé'
            ], 400);
        }

        $trip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa chuyến đi thành công'
        ]);
    }
}
