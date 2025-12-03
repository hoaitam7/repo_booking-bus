<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PickupPointController extends Controller
{
    /**
     * Lấy danh sách tất cả điểm đón
     */
    public function index(): JsonResponse
    {
        $pickupPoints = PickupPoint::with('route')->get();

        return response()->json([
            'success' => true,
            'data' => $pickupPoints
        ]);
    }

    /**
     * Lấy danh sách điểm đón theo route_id
     */
    public function getByRoute($routeId): JsonResponse
    {
        $route = Route::find($routeId);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tuyến đường'
            ], 404);
        }

        $pickupPoints = PickupPoint::where('route_id', $routeId)->get();

        return response()->json([
            'success' => true,
            'data' => $pickupPoints
        ]);
    }

    /**
     * Lấy thông tin chi tiết 1 điểm đón
     */
    public function show($id): JsonResponse
    {
        $pickupPoint = PickupPoint::with('route')->find($id);

        if (!$pickupPoint) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy điểm đón'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pickupPoint
        ]);
    }

    /**
     * Tạo điểm đón mới
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'route_id' => 'required|exists:routes,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $pickupPoint = PickupPoint::create([
            'route_id' => $request->route_id,
            'name' => $request->name,
            'address' => $request->address,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo điểm đón thành công',
            'data' => $pickupPoint->load('route')
        ], 201);
    }

    /**
     * Cập nhật điểm đón
     */
    public function update(Request $request, $id): JsonResponse
    {
        $pickupPoint = PickupPoint::find($id);

        if (!$pickupPoint) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy điểm đón'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'route_id' => 'sometimes|required|exists:routes,id',
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $pickupPoint->update([
            'route_id' => $request->has('route_id') ? $request->route_id : $pickupPoint->route_id,
            'name' => $request->has('name') ? $request->name : $pickupPoint->name,
            'address' => $request->has('address') ? $request->address : $pickupPoint->address,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật điểm đón thành công',
            'data' => $pickupPoint->load('route')
        ]);
    }

    /**
     * Xóa điểm đón
     */
    public function destroy($id): JsonResponse
    {
        $pickupPoint = PickupPoint::find($id);

        if (!$pickupPoint) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy điểm đón'
            ], 404);
        }

        $pickupPoint->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa điểm đón thành công'
        ]);
    }
}
