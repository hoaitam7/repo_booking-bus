<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Gemini;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChatController extends Controller
{

    public function chat(Request $request)
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        try {
            $userInput = $request->input('message');
            $client = Gemini::client(env('GEMINI_API_KEY'));
            $model = $client->generativeModel('models/gemini-2.0-flash');

            // 1. Lấy dữ liệu tổng quát từ các bảng quan trọng
            // Chúng ta lấy các tuyến đường, giá cả và thông tin xe
            $routesData = DB::table('routes')
                ->join('trips', 'routes.id', '=', 'trips.route_id')
                ->select(
                    'routes.from_city',
                    'routes.to_city',
                    'routes.price',
                    'routes.duration',
                    'trips.departure_time',
                    'trips.status'
                )
                ->where('trips.status', 'scheduled')
                ->where('trips.departure_time', '>=', $now) // Lọc từ thời điểm hiện tại trở đi
                ->orderBy('trips.departure_time', 'asc')    // Sắp xếp chuyến gần nhất lên đầu
                ->limit(20) // Tăng lên 20 chuyến để AI có nhiều lựa chọn hơn
                ->get();

            $busesData = DB::table('buses')->select('bus_name', 'bus_type', 'utilities')->get();

            // 2. Xây dựng một "Siêu ngữ cảnh" (Super Context)
            $systemContext = "Bạn là trợ lý thông minh của hãng xe BUS VIP.
        Dưới đây là dữ liệu thực tế từ hệ thống của chúng tôi:
        - Danh sách chuyến xe: " . $routesData->toJson() . "
        - Thông tin các loại xe và tiện ích: " . $busesData->toJson() . "

        Nhiệm vụ của bạn:
        1. Dựa vào dữ liệu trên để trả lời câu hỏi của khách hàng.
        2. Nếu khách hỏi về chuyến đi, hãy tìm trong danh sách chuyến xe.
        3. Nếu khách hỏi về tiện ích (wifi, nước uống...), hãy nhìn vào danh sách xe.
        4. Luôn trả lời lịch sự, ngắn gọn bằng tiếng Việt.
        5. Nếu dữ liệu trên không có thông tin khách cần, hãy nói kiểu : Hiện tại tôi chưa có thông tin cụ thể về yêu cầu này, Anh/Chị vui lòng liên hệ hotline 0923 138 498 nhé!";

            // 3. Gửi toàn bộ cho AI
            $finalPrompt = $systemContext . "\n\nKhách hàng hỏi: " . $userInput . "Hãy trả lời khách bằng tiếng Việt một cách tự nhiên.
                LƯU Ý: Không sử dụng dấu sao (*), không dùng định dạng Markdown.
                Hãy xuống dòng bằng phím Enter bình thường để dễ đọc.";

            $result = $model->generateContent($finalPrompt);

            return response()->json([
                'reply' => $result->text()
            ]);
        } catch (\Exception $e) {
            Log::error("Chat Error: " . $e->getMessage());
            return response()->json(['reply' => "Dạ, hệ thống bận một chút, vui lòng chờ ít phút tôi sẽ quay lại ngay!"], 200);
        }
    }
}
