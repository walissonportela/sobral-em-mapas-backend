<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo contato recebido</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #334155;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">

                    <tr>
                        <td style="background-color: #1e40af; padding: 35px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600; letter-spacing: -0.5px;">
                                📍 Sobral em Mapas
                            </h1>
                            <p style="margin: 8px 0 0; color: #bfdbfe; font-size: 15px;">
                                Novo chamado registrado no sistema
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 20px; font-size: 18px; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                                Detalhes da Solicitação
                            </h2>

                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size: 15px; line-height: 1.6;">
                                <tr>
                                    <td width="30%" style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #64748b;">Nome:</td>
                                    <td width="70%" style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; color: #1e293b;">{{ $data['name'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #64748b;">Email:</td>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                        <a href="mailto:{{ $data['email'] }}" style="color: #2563eb; text-decoration: none; font-weight: 500;">{{ $data['email'] }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #64748b;">Telefone:</td>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; color: #1e293b;">{{ $data['phone'] ?? 'Não informado' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #64748b;">Categoria:</td>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
                                        <span style="background-color: #eff6ff; color: #1d4ed8; padding: 4px 12px; border-radius: 9999px; font-size: 13px; font-weight: 600; border: 1px solid #bfdbfe;">
                                            {{ $data['category'] }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; font-weight: 600; color: #64748b;">Assunto:</td>
                                    <td style="padding: 12px 0; color: #1e293b; font-weight: 500;">{{ $data['subject'] }}</td>
                                </tr>
                            </table>

                            <h2 style="margin: 35px 0 15px; font-size: 16px; color: #0f172a;">Mensagem do Usuário:</h2>

                            <div style="background-color: #f8fafc; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 0 8px 8px 0; color: #334155; font-size: 15px; line-height: 1.6; white-space: pre-line; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                                {{ $data['message'] }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #f1f5f9; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 13px; color: #64748b;">
                                Este é um e-mail automático gerado pelo portal <strong>Sobral em Mapas</strong>.
                            </p>
                            <p style="margin: 6px 0 0; font-size: 12px; color: #94a3b8;">
                                Por favor, não responda diretamente a este endereço.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>