<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Pagination
        $users = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Danh sách users'
        ]);
    }
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'role' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'role' => $request->role ?? 'user',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo user thành công',
            'data' => [
                'user' => $user
            ]
        ], 201);
    }
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user
            ]
        ]);
    }
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User không tồn tại'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'role' => 'sometimes|in:admin,user,moderator',
            'password' => 'sometimes|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        if ($request->has('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật user thành công',
            'data' => [
                'user' => $user->only(['id', 'full_name', 'email', 'phone', 'address', 'role'])
            ]
        ]);
    }

    public function destroy($id): JsonResponse
    {
        // Tìm user theo ID
        $user = User::find($id);

        // Kiểm tra user có tồn tại không
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User không tồn tại'
            ], 404);
        }

        // Prevent self-deletion
        if (auth()->id() === (int)$id) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa tài khoản của chính mình'
            ], 403);
        }

        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'Xóa user thành công'
        ]);
    }
}
