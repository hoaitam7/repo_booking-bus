<?php
// app/Http/Controllers/BookingController.php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\Route;
use App\Models\PickupPoint;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    /**
     * Đặt vé
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required|exists:trips,id',
            'pickup_point_id' => 'required|exists:pickup_points,id',
            'seat_numbers' => 'required|string|max:255',
            'passenger_name' => 'required|string|max:255',
            'passenger_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:cash,banking,momo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors()
            ], 422);
        }

        // Kiểm tra chuyến xe có tồn tại và còn chỗ không
        $trip = Trip::find($request->trip_id);
        if (!$trip || $trip->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => 'Chuyến xe không khả dụng'
            ], 400);
        }

        // Kiểm tra số ghế còn trống
        $requestedSeats = explode(',', $request->seat_numbers);
        $seatCount = count($requestedSeats); // ← THÊM DÒNG NÀY

        if ($seatCount > $trip->available_seats) {
            return response()->json([
                'success' => false,
                'message' => 'Số ghế bạn chọn vượt quá số ghế còn trống'
            ], 400);
        }

        // Kiểm tra điểm đón có thuộc tuyến của chuyến xe không
        $pickupPoint = PickupPoint::find($request->pickup_point_id);
        if (!$pickupPoint || $pickupPoint->route_id !== $trip->route_id) {
            return response()->json([
                'success' => false,
                'message' => 'Điểm đón không hợp lệ cho chuyến xe này'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Tính tổng tiền
            $totalAmount = $trip->ticket_price * $seatCount;

            // Tạo booking
            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'trip_id' => $request->trip_id,
                'pickup_point_id' => $request->pickup_point_id,
                'seat_numbers' => $request->seat_numbers,
                'passenger_name' => $request->passenger_name,
                'passenger_phone' => $request->passenger_phone,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'status' => 'confirmed',
                'payment_status' => $request->payment_method === 'cash' ? 'pending' : 'paid',
            ]);

            // Cập nhật số ghế trống
            $trip->available_seats -= $seatCount;
            $trip->save();

            // Tạo invoice đơn giản (chỉ các trường cơ bản)
            $invoiceData = [
                'booking_id' => $booking->id,
                'invoice_number' => 'INV' . date('YmdHis') . rand(100, 999),
                'total_amount' => $totalAmount,
                'status' => $request->payment_method === 'cash' ? 'pending' : 'paid',
            ];

            // Chỉ thêm issue_date và due_date nếu cột tồn tại
            $invoice = Invoice::create($invoiceData);

            DB::commit();

            // Load relationships
            $booking->load(['trip.route', 'trip.bus', 'pickupPoint', 'invoice']);

            return response()->json([
                'success' => true,
                'message' => 'Đặt vé thành công',
                'data' => [
                    'booking' => $booking,
                    'invoice' => $invoice
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Booking error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt vé: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách ghế trống của chuyến xe (xử lý linh hoạt số ghế)
     */
    public function getAvailableSeats($tripId): JsonResponse
    {
        $trip = Trip::with(['bus'])->find($tripId);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Chuyến xe không tồn tại'
            ], 404);
        }

        // Tạo danh sách ghế linh hoạt theo số ghế thực tế
        $allSeats = [];
        $totalSeats = $trip->bus->total_seats;

        $rows = range('A', 'Z');
        $seatsPerRow = 4; // Mỗi hàng 4 ghế (2 tầng: 2 ghế tầng 1, 2 ghế tầng 2)

        $seatCount = 0;
        foreach ($rows as $row) {
            for ($seatNum = 1; $seatNum <= $seatsPerRow; $seatNum++) {
                if ($seatCount >= $totalSeats) break 2;

                $allSeats[] = $row . $seatNum;
                $seatCount++;
            }
        }

        // Lấy ghế đã đặt
        $bookedSeats = Booking::where('trip_id', $tripId)
            ->whereIn('status', ['confirmed', 'pending'])
            ->pluck('seat_numbers')
            ->flatMap(function ($seats) {
                return explode(',', $seats);
            })
            ->toArray();

        $availableSeats = array_diff($allSeats, $bookedSeats);

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $tripId,
                'bus_name' => $trip->bus->bus_name,
                'bus_type' => $trip->bus->bus_type,
                'total_seats' => $trip->bus->total_seats,
                'available_seats' => array_values($availableSeats),
                'booked_seats' => array_values($bookedSeats),
                'all_seats' => $allSeats,
                'seat_layout' => $this->generateFlexibleSeatLayout($allSeats, $bookedSeats, $trip->bus->bus_type)
            ]
        ]);
    }

    /**
     * Tạo layout ghế linh hoạt theo loại xe
     */
    private function generateFlexibleSeatLayout($allSeats, $bookedSeats, $busType)
    {
        $layout = [];
        $currentRow = '';
        $rowSeats = [];

        foreach ($allSeats as $seat) {
            $row = $seat[0]; // Lấy ký tự đầu (A, B, C...)
            $number = (int) substr($seat, 1); // Lấy số (1, 2, 3...)

            if ($row !== $currentRow) {
                if (!empty($rowSeats)) {
                    $layout[] = [
                        'row' => $currentRow,
                        'seats' => $rowSeats
                    ];
                }
                $currentRow = $row;
                $rowSeats = [];
            }

            // Xác định loại ghế theo vị trí
            $seatType = $this->getSeatType($number, $busType);

            $rowSeats[] = [
                'seat_number' => $seat,
                'is_available' => !in_array($seat, $bookedSeats),
                'seat_type' => $seatType,
                'floor' => $number <= 2 ? 'lower' : 'upper' // 1-2: tầng dưới, 3-4: tầng trên
            ];
        }

        if (!empty($rowSeats)) {
            $layout[] = [
                'row' => $currentRow,
                'seats' => $rowSeats
            ];
        }

        return $layout;
    }

    /**
     * Xác định loại ghế
     */
    private function getSeatType($seatNumber, $busType)
    {
        if ($busType === 'sleeper') {
            // Xe giường nằm
            return $seatNumber <= 2 ? 'lower_berth' : 'upper_berth';
        } else {
            // Xe ghế ngồi
            return 'standard';
        }
    }
    // app/Http/Controllers/BookingController.php

    /**
     * Lấy danh sách điểm đón của tuyến xe
     */
    public function getPickupPoints($routeId): JsonResponse
    {
        // Kiểm tra tuyến đường có tồn tại không
        $route = Route::find($routeId);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Tuyến đường không tồn tại'
            ], 404);
        }

        // Lấy danh sách điểm đón
        $pickupPoints = PickupPoint::where('route_id', $routeId)
            ->orderBy('name')
            ->get(['id', 'route_id', 'name', 'address']);

        if ($pickupPoints->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy điểm đón cho tuyến đường này'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'route_info' => [
                    'id' => $route->id,
                    'from_city' => $route->from_city,
                    'to_city' => $route->to_city
                ],
                'pickup_points' => $pickupPoints
            ]
        ]);
    }
}
