<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Bus;

class BusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $buses = Bus::orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách xe buýt thành công',
                'data' => $buses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bus_name' => 'required|string|max:255',
            'license_plate' => 'required|string|max:255|unique:buses,license_plate',
            'bus_type' => 'nullable|string|max:255',
            'total_seats' => 'required|integer|min:1|max:100',
            'utilities' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $bus = Bus::create([
                'bus_name' => $request->bus_name,
                'license_plate' => $request->license_plate,
                'bus_type' => $request->bus_type,
                'total_seats' => $request->total_seats,
                'utilities' => $request->utilities,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thêm xe buýt thành công',
                'data' => $bus
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thêm xe buýt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $bus = Bus::find($id);

            if (!$bus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy xe buýt'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin xe buýt thành công',
                'data' => $bus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $bus = Bus::find($id);

            if (!$bus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy xe buýt'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'bus_name' => 'sometimes|required|string|max:255',
                'license_plate' => 'sometimes|required|string|max:255|unique:buses,license_plate,' . $id,
                'bus_type' => 'nullable|string|max:255',
                'total_seats' => 'sometimes|required|integer|min:1|max:100',
                'utilities' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi xác thực',
                    'errors' => $validator->errors()
                ], 422);
            }

            $bus->update($request->only(['bus_name', 'license_plate', 'bus_type', 'total_seats', 'utilities']));

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật xe buýt thành công',
                'data' => $bus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật xe buýt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $bus = Bus::find($id);

            if (!$bus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy xe buýt'
                ], 404);
            }

            $bus->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa xe buýt thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa xe buýt',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
