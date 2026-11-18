<?php

namespace App\Infrastructure\Adapters;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotAdapter
{
    protected $baseUrl;

    public function __construct()
    {
        // Defina a URL do chatbot
        $this->baseUrl = 'https://3ae60893eeb5.ngrok-free.app/webhooks/rest/webhook';
    }

    public function sendMessageToChatbot($message, $sender)
    {
        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->timeout(8)
                ->post($this->baseUrl, [
                'message' => $message,
                'sender' => $sender,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Erro ao enviar mensagem para o chatbot no adaptador: ', ['error' => $e->getMessage()]);
            throw new \Exception('Falha na comunicação com o chatbot.');
        }
    }
}
