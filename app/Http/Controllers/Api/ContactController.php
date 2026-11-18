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
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:254',
            'phone' => 'nullable|string|max:30',
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:180',
            'message' => 'required|string|max:4000',
        ]);

        // Não registre dados pessoais enviados pelo formulário nos logs.

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