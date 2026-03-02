<?php

namespace App\Services;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $token;
    protected $baseUrl;

    public function __construct()
    {
        $this->token = env('FONNTE_TOKEN', '');
        $this->baseUrl = 'https://api.fonnte.com';
    }

    public function send($target, $message)
    {
        // Allow comma separated targets, but for logging we might want to split
        // For now, handle single target primarily
        
        if (empty($this->token)) {
            Log::warning('FONNTE_TOKEN is not set. WhatsApp message not sent.');
            $this->logMessage($target, $message, 'failed', json_encode(['error' => 'Token not set']));
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->withoutVerifying()
            ->post("{$this->baseUrl}/send", [
                'target' => $target,
                'message' => $message,
            ]);

            $responseData = $response->json();
            // Fonnte returns 'status' => true/false in body usually
            $isSuccess = $response->successful() && ($responseData['status'] ?? false); 
            $status = $isSuccess ? 'sent' : 'failed';

            $this->logMessage($target, $message, $status, json_encode($responseData));

            return $isSuccess;

        } catch (\Exception $e) {
            Log::error('Fonnte Error: ' . $e->getMessage());
            $this->logMessage($target, $message, 'failed', json_encode(['error' => $e->getMessage()]));
            return false;
        }
    }

    protected function logMessage($target, $message, $status, $response)
    {
        NotificationLog::create([
            'recipient_phone' => $target,
            'message' => $message,
            'status' => $status,
            'provider' => 'fonnte',
            'response_data' => $response
        ]);
    }
}
