<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    /**
     * Đăng ký user mới
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
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
            'role' => 'user',

        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công',
            'data' => [
                'user' => $user,
            ]
        ], 201);
    }

    /**
     * Đăng nhập
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        //không tồn tại user, tài khoản sai
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng'
            ], 401);
        }

        // Tạo token
        $plainToken = $user->createToken('auth_token')->plainTextToken;
        $token = explode('|', $plainToken)[1] ?? $plainToken;
        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'user' => $user,
                'access_token' => $token
            ]
        ]);
    }
    /**
     * Đổi mật khẩu
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors()
            ], 422);
        }
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng'
            ], 400);
        }
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công'
        ]);
    }
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Email không tồn tại trong hệ thống.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            // Tìm user
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng không tồn tại'
                ], 404);
            }

            // Tạo password mới (8 ký tự, có số và chữ)
            $newPassword = Str::random(8) . rand(10, 99);

            // Cập nhật password trong database
            $user->password = Hash::make($newPassword);
            $user->save();
            // Gửi email chứa password mới
            // Mail::send($view, $data, $callback);
            Mail::send('email', [
                'user' => $user,
                'newPassword' => $newPassword,
            ], function (Message $message) use ($user) {
                $message->to($user->email) //to: là email đích gửi đến user quên mk
                    ->subject('Mật khẩu mới'); //$message->subject('tiêu đề email');
            });

            return response()->json([
                'success' => true,
                'message' => 'Đã gửi mật khẩu mới qua email',
                'data' => [
                    'email' => $user->email,
                    'message' => 'Vui lòng kiểm tra email và đăng nhập bằng mật khẩu mới'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
