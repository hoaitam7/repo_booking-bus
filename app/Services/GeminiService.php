<?php

namespace App\Services;

use Gemini;

class GeminiService
{
    public function askGemini(string $question)
    {
        // Khởi tạo client dùng API Key trong .env
        $client = Gemini::client(env('GEMINI_API_KEY'));

        // Gọi model Flash (nhanh nhất để làm chatbot)
        $result = $client->generativeModel('models/gemini-flash-latest')->generateContent($question);

        return $result->text();
    }
}
