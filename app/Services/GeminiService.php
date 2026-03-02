<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
    }

    public function generateProductDescription($productName, $category)
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API Key is missing.');
            return "Maaf, API Key Gemini belum dikonfigurasi.";
        }

        $prompt = "Buatkan deskripsi produk yang menarik, SEO-friendly, dan persuasif untuk produk '$productName' dengan kategori '$category'. Gunakan bahasa Indonesia yang santai namun profesional. Maksimal 150 kata. Outputnya hanya teks deskripsi saja, tanpa intro.";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Extract text from Gemini response structure
                // candidates[0].content.parts[0].text
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Gagal mengambil deskripsi dari respon AI.';
            } else {
                Log::error('Gemini API Error: ' . $response->body());
                return "Maaf, terjadi kesalahan saat menghubungi AI: " . $response->status();
            }
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            return "Terjadi kesalahan internal pada layanan AI.";
        }
    }
}
