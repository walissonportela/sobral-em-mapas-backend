# Revisão de segurança — Sobral em Mapas

Esta branch contém correções de baixo impacto destinadas à revisão antes de uma implantação.

## Modificações aplicadas

- Remove as rotas administrativas legadas **sem autenticação** de `routes/web.php`. A API administrativa existente em `routes/api.php` continua exigindo `auth:sanctum` e `role:Superadministrador,Administrador`.
- O endpoint público `/api/coord` retorna somente camadas ativas e publicadas, cujas categorias e subcategorias estejam ativas.
- Aplica limites mais restritos por IP aos endpoints públicos de contato, chatbot, estatísticas e WMS, mantendo as rotas.
- Remove chaves secretas escritas como fallback no código. A aplicação passa a depender de `APP_KEY` definida **no ambiente**, como já recomendado para o Laravel. O segredo de deploy passa a utilizar `DEPLOY_SECRET` em vez de um nome literal.
- Bloqueia acesso HTTP a logs, backups, arquivos de configuração e artefatos internos em `public/.htaccess`.
- Remove logs, caches e views gerados por engano que estavam rastreados no Git e amplia `.gitignore`.
- Evita registrar mensagens de chatbot e dados de contato completos nos logs.

## Antes de considerar o merge

1. **Conferir no Railway `APP_KEY`**, garantindo que existe uma chave válida e que será mantida. Não redefinir a chave existente sem avaliar o impacto em criptografia/sessões. **Não colocar o valor no GitHub, issue ou pull request.**
2. Conferir `APP_ENV=production`, `APP_DEBUG=false` e `DEBUGBAR_ENABLED=false` no Railway.
3. Verificar HTTPS para frontend e API e configurar `FRONTEND_URL` estritamente para o domínio real.
4. Confirmar se algum recurso ainda depende das antigas rotas web `/admin/*`. A interface React deve utilizar `/api/admin/*`.
5. Executar `php -l` nos arquivos PHP alterados, `php artisan route:list` e os testes automatizados no ambiente local.
6. Validar login, expiração/revogação de token, usuários pendentes, perfis Visitante/Administrador/Superadministrador, criação/edição/exclusão de categorias/camadas/usuários.
7. Confirmar carregamento dos mapas GeoJSON, pré-visualização de upload, pesquisa e estatísticas.
8. Verificar acesso não autenticado às rotas administrativas (esperado 401/403) e às camadas não publicadas (esperado 404).
9. Testar os novos limites de requisição sem disparar tráfego excessivo nem testes intrusivos em produção.
10. Verificar se o `/public/laravel.log` ou outros logs estiveram expostos anteriormente; remover da hospedagem e tratar o histórico Git como potencialmente comprometido. Se houver credenciais expostas, fazer rotação em Railway/Vercel/serviços pertinentes. Apenas remover o arquivo do commit mais recente **não apaga o histórico do repositório**.

## Pendências intencionais (não aplicar sem avaliação de compatibilidade)

- **SSRF no proxy WMS**: `GeoServerClient` aceita URLs dos links WMS previamente cadastrados. Para reduzir o risco sem quebrar serviços, implementar allowlist de hosts de destino, bloqueio de IPs privados, validação de DNS, prevenção de redirects e validação da operação OGC. Considerar desativar WMS completamente após confirmar que nenhuma camada o utiliza.
- **Sanctum e frontend**: avaliar migração planejada de Bearer token em `localStorage` para cookies HttpOnly/Secure com proteção CSRF e domínios configurados. Não trocar apenas o frontend, pois isso quebra o login.
- **Dependências**: atualizar Laravel 8 e dependências em branch separada com testes de regressão.
- **CSP e demais headers**: planejar política considerando Leaflet, provedores de tiles, Vercel, GeoJSON e estilos inline; aplicar CSP estrita sem esses testes pode quebrar o mapa.
- **Rate limit distribuído**: o middleware `throttle` depende do cache configurado; em múltiplas réplicas deve-se usar armazenamento compartilhado.
- **Credenciais de desenvolvimento**: proteger portas MySQL/phpMyAdmin e senhas locais no Docker Compose; não usar essas credenciais em ambientes públicos.

## Estado

Mudanças limitadas a esta branch. **Não efetuar merge nem deploy antes da validação manual.**
