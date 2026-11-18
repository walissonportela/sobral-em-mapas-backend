# Revisão de segurança — backend

Branch: `fix/security`. **Não há deploy automático deste conjunto de alterações.**

## Proteções preparadas
- Rotas web administrativas antigas removidas; gerenciamento fica na API com Sanctum + perfil.
- Consultas públicas de camadas limitadas a publicadas e ativas.
- Rate limiting em login, cadastro, contato, chatbot, estatísticas e WMS.
- Proteção do proxy WMS: lista de hosts por ambiente, remoção de queries arbitrárias, operações GetMap/GetLegendGraphic fixas, limites de parâmetros, sem redirecionamentos e limites de tempo e tamanho.
- GetCapabilities administrativo restringido ao mesmo host autorizado e sem redirecionamentos.
- Tokens Sanctum expiram após 1440 minutos por padrão (ajuste em `SANCTUM_TOKEN_EXPIRATION`).
- `/api/me` recusa contas cuja aprovação foi revogada.
- Validações adicionais de entradas públicas.
- Headers de segurança e `Cache-Control: no-store` nas respostas autenticadas.
- 14 arquivos de logs/caches acidentais removidos do estado atual do Git; registros sensíveis de chatbot/contato reduzidos.
- Portas do MySQL e phpMyAdmin ligadas somente ao loopback no Docker Compose.
- Teste unitário da allowlist WMS e Action de lint PHP.

## Variáveis de ambiente antes do merge/deploy
- `APP_KEY`: **obrigatória**, manter a chave válida já usada no Railway. Não publicar nem gerar outra sem plano de migração.
- `APP_ENV=production`, `APP_DEBUG=false`, `DEBUGBAR_ENABLED=false`.
- `FRONTEND_URL`: URL exata do frontend publicado.
- `WMS_ALLOWED_HOSTS`: hosts WMS confiáveis, separados por vírgulas (padrão: `geoserver.sobral.ce.gov.br`). Não cadastrar IPs privados ou domínios controlados por terceiros.
- `SANCTUM_TOKEN_EXPIRATION=1440`: opcional, duração em minutos.
- `DEPLOY_SECRET`: apenas se houver integração de deploy externa ativa, em ambiente seguro.

## Roteiro de testes antes de mesclar
1. Instalar dependências existentes sem atualizar versões automaticamente.
2. Executar `php artisan route:list` e `php artisan test` em um banco **de testes**, com .env válido.
3. Confirmar ausência de rotas web públicas de escrita em `/admin`; `/api/admin/*` deve retornar 401 sem token e 403 para Visitante.
4. Testar login, registro, sessão, logout, suspensão de aprovação e os perfis administrativos.
5. Testar criação, edição e exclusão de usuários, categorias, subcategorias e camadas.
6. Testar upload de GeoJSON, visualização privada por admin e pública apenas de camada publicada.
7. Testar busca, Estatísticas, contato e chatbot; limite de 12 mensagens/min por IP pode afetar testes intensivos.
8. Se WMS ainda for usado, testar URLs de servidores permitidos e verificar resposta 422 para URL interna/externa não autorizada.
9. Verificar no Railway a localização e o acesso a volumes GeoJSON persistentes.
10. Conferir cabeçalhos de segurança, CORS e erros em ambiente HTTPS.

## Riscos e pendências que NÃO podem ser eliminados apenas com este PR
- **Histórico Git:** logs e a chave que antes estava no código continuam nos commits antigos; verificar presença de segredos e rotacionar os comprometidos. Remover arquivos no commit atual não limpa o histórico.
- **DNS rebinding:** a allowlist restringe o hostname, mas não fixa a conexão a um IP público resolvido; implantações mais sensíveis devem restringir o tráfego de saída na rede e/ou usar um proxy de saída dedicado.
- **Framework:** Laravel 8 e dependências antigas requerem atualização coordenada, com migrações e testes de regressão. Não atualizar sem testes reais.
- **Tokens SPA:** Bearer tokens continuam em `localStorage` por compatibilidade. Migrar para cookies HttpOnly, Secure e CSRF exige alteração coordenada de Laravel, CORS e React.
- **Chaves em produção:** `APP_KEY` e outras variáveis de Railway/Vercel não são acessíveis por este PR.
- **Produção:** análise estática não substitui testes de autorização e deploy em ambiente de homologação.
