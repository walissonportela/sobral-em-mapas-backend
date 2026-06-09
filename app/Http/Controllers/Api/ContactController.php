<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        // 1. valida os dados que vêm do React
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'category' => 'required|string',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        // 2. DEBUG
        logger()->info('CONTACT RECEBIDO', $data);

        // 3. ENVIA O EMAIL (Comentado para poupar limite do Mailtrap)
        /*
        Mail::to('seuemail@gmail.com')
            ->send(new ContactMail($data));
        */

        // 4. Resposta para o React
        return response()->json([
            'success' => true,
            'message' => 'Email enviado com sucesso',
        ]);
    }
}