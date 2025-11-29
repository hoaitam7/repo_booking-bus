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
}
