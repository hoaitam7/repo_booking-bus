<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RouteController extends Controller
{
    // app/Http/Controllers/RouteController.php
    public function getPopularRoutes(): JsonResponse
    {
        $popularRoutes = Route::with(['trips.bus'])
            ->whereIn('from_city', ['TP HCM', 'Hà Nội']) // Các thành phố lớn
            ->orWhereIn('to_city', ['TP HCM', 'Hà Nội'])
            ->get()
            ->map(function ($route) {
                // Lấy loại xe phổ biến nhất trên tuyến
                $commonBusType = $route->trips->groupBy('bus.bus_type')->sortDesc()->keys()->first();

                return [
                    'id' => $route->id,
                    'from_city' => $route->from_city,
                    'to_city' => $route->to_city,
                    'distance' => $route->distance,
                    'duration' => $route->duration,
                    'price' => $route->price,
                    'bus_type' => $commonBusType,
                    'trip_count' => $route->trips->count()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $popularRoutes
        ]);
    }

    // app/Http/Controllers/RouteController.php
    public function searchRoutes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string|min:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập ít nhất 2 ký tự',
                'errors' => $validator->errors()
            ], 422);
        }

        $keyword = $request->keyword;

        $routes = Route::with(['trips.bus'])
            ->where(function ($query) use ($keyword) {
                $query->where('from_city', 'like', "%{$keyword}%")
                    ->orWhere('to_city', 'like', "%{$keyword}%");
            })
            ->get()
            ->map(function ($route) {
                $commonBusType = $route->trips->groupBy('bus.bus_type')->sortDesc()->keys()->first();

                return [
                    'id' => $route->id,
                    'from_city' => $route->from_city,
                    'to_city' => $route->to_city,
                    'distance' => $route->distance,
                    'duration' => $route->duration,
                    'price' => $route->price,
                    'bus_type' => $commonBusType,
                    'trip_count' => $route->trips->count()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $routes
        ]);
    }

    //CURD Route
    /**
     * Lấy danh sách tất cả các tuyến đường
     */
    public function index(): JsonResponse
    {
        $routes = Route::all();

        return response()->json([
            'success' => true,
            'data' => $routes
        ]);
    }

    /**
     * Lấy thông tin chi tiết 1 tuyến đường theo ID
     */
    public function show($id): JsonResponse
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tuyến đường'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $route
        ]);
    }

    /**
     * Tạo tuyến đường mới
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_city' => 'required|string|max:255',
            'to_city' => 'required|string|max:255',
            'distance' => 'nullable|numeric|min:0',
            'duration' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $route = Route::create([
            'from_city' => $request->from_city,
            'to_city' => $request->to_city,
            'distance' => $request->distance,
            'duration' => $request->duration,
            'price' => $request->price,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo tuyến đường thành công',
            'data' => $route
        ], 201);
    }

    /**
     * Cập nhật tuyến đường theo ID
     */
    public function update(Request $request, $id): JsonResponse
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tuyến đường'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'from_city' => 'sometimes|required|string|max:255',
            'to_city' => 'sometimes|required|string|max:255',
            'distance' => 'nullable|numeric|min:0',
            'duration' => 'nullable|string|max:50',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $route->update([
            'from_city' => $request->has('from_city') ? $request->from_city : $route->from_city,
            'to_city' => $request->has('to_city') ? $request->to_city : $route->to_city,
            'distance' => $request->has('distance') ? $request->distance : $route->distance,
            'duration' => $request->has('duration') ? $request->duration : $route->duration,
            'price' => $request->has('price') ? $request->price : $route->price,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tuyến đường thành công',
            'data' => $route
        ]);
    }

    /**
     * Xóa tuyến đường theo ID
     */
    public function destroy($id): JsonResponse
    {
        $route = Route::find($id);

        if (!$route) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tuyến đường'
            ], 404);
        }

        // Kiểm tra nếu có chuyến đi liên quan
        if ($route->trips && $route->trips->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa tuyến đường vì có chuyến đi liên quan'
            ], 400);
        }

        $route->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa tuyến đường thành công'
        ]);
    }
}
