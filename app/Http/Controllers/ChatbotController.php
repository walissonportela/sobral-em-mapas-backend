<?php

namespace App\Http\Controllers;

use App\Application\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    public function sendMessage(Request $request)
{
    Log::info('Método sendMessage do ChatbotController foi chamado.');

    $message = $request->input('message');
    $sender = $request->input('sender');

    // Evita registrar conteúdo enviado pelo usuário.

    try {
        $responses = $this->chatbotService->sendMessage($message, $sender);

        return response()->json($responses);
    } catch (\Exception $e) {
        Log::error('Erro no método sendMessage:', ['error' => $e->getMessage(), 'sender' => $sender]);
        return response()->json(['error' => 'Algo deu errado.'], 500);
    }
}

}
