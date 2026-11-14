# Sobral em Mapas — Back-end

API REST do **Sobral em Mapas**, plataforma WebGIS para organização, gerenciamento e consulta de informações geoespaciais do município de Sobral (CE). Este repositório integra o TCC *“Sobral em Mapas: Engenharia de Software Aplicada ao Desenvolvimento de uma Plataforma WebGIS para Gestão de Dados Geoespaciais”*.

> A interface web está em [sobral-em-mapas-frontend](https://github.com/walissonportela/sobral-em-mapas-frontend).

## Tecnologias e arquitetura

- **PHP 8.2** no contêiner de desenvolvimento;
- **Laravel 8** — API REST, validação, regras de negócio e persistência;
- **Laravel Sanctum** — autenticação por token;
- **MySQL 8** — usuários, perfis, categorias, subcategorias e metadados das camadas;
- **Docker Compose** — contêineres de aplicação, banco de dados e phpMyAdmin;
- **GeoJSON** armazenado localmente — arquivos de camadas geográficas;
- **WMS/GeoServer** — integração opcional com serviços geoespaciais externos.

Os arquivos GeoJSON enviados pelo painel são armazenados em `storage/app/geodata`. A disponibilidade de camadas WMS externas depende dos respectivos servidores.

## Pré-requisitos

Para a execução recomendada **não é necessário instalar PHP, Composer ou MySQL diretamente na máquina**. Basta ter:

- **Git**;
- **Docker Desktop** (ou Docker Engine) com **Docker Compose**;
- Portas **8080**, **8081** e **3306** disponíveis.

> O arquivo `docker-compose.yml` foi preparado para desenvolvimento local. As credenciais de exemplo do MySQL devem ser alteradas antes de qualquer implantação pública.

## Como executar localmente com Docker

### 1. Clone o repositório

```bash
git clone https://github.com/walissonportela/sobral-em-mapas-backend.git
cd sobral-em-mapas-backend
```

### 2. Crie o arquivo de ambiente

Na raiz do projeto, crie um arquivo chamado **`.env`**. Este repositório não contém um `.env.example` na raiz; portanto, comece com a configuração mínima abaixo:

```dotenv
APP_NAME="Sobral em Mapas"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=user
DB_PASSWORD=password

FRONTEND_URL=http://localhost:5173

SUPERADMIN_EMAIL=admin@exemplo.com
SUPERADMIN_PASSWORD=defina-uma-senha-local-forte

FILESYSTEM_DRIVER=local
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

**Antes de continuar:** substitua o e-mail e a senha de exemplo do superadministrador por dados seus. `SUPERADMIN_PASSWORD` deve conter **pelo menos 12 caracteres**, conforme validação do seeder.

Os dados de banco acima correspondem ao `docker-compose.yml` atual. Dentro do contêiner, `DB_HOST` deve ser **`db`**, e não `localhost`. O arquivo `.env` está no `.gitignore` e **não deve ser enviado ao GitHub**.

### 3. Suba os contêineres

Abra o Docker Desktop e, na pasta do back-end, execute:

```bash
docker compose up -d --build
```

O Docker Compose inicia:

| Serviço | Endereço local |
| --- | --- |
| API Laravel/Apache | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |
| MySQL | `localhost:3306` |

A primeira inicialização pode levar mais tempo por causa da construção da imagem e da inicialização do MySQL.

### 4. Instale as dependências PHP no volume de desenvolvimento

```bash
docker compose exec app composer install --no-interaction
```

**Importante:** o `docker-compose.yml` monta a pasta do repositório em `/var/www/html`. Por isso, mesmo que a imagem tenha instalado as dependências durante o build, o diretório `vendor/` da imagem pode ficar oculto pelo volume; o comando acima garante a instalação no diretório montado.

### 5. Gere a chave da aplicação

```bash
docker compose exec app php artisan key:generate
```

Esse comando preenche `APP_KEY` no `.env`. Não utilize uma chave fixa ou compartilhada entre instalações.

### 6. Execute as migrações e os seeders

```bash
docker compose exec app php artisan migrate --seed
```

O comando cria a estrutura do banco e executa os seeders. O `DatabaseSeeder` atual cadastra:

- Perfis **Superadministrador**, **Administrador** e **Visitante**;
- Uma conta inicial de **Superadministrador** com o e-mail e a senha configurados no `.env`;
- Categorias e subcategorias temáticas iniciais.

**Atenção:** os seeders **não incluem as camadas cartográficas nem os arquivos GeoJSON da demonstração do TCC**. Uma instalação nova terá o catálogo inicial, mas será necessário cadastrar ou importar camadas para reproduzir os mapas apresentados no trabalho.

### 7. Teste a API e o banco

- **Rota pública de dados:** http://localhost:8080/api/map-data
- **phpMyAdmin:** http://localhost:8081

Para entrar no phpMyAdmin, utilize o servidor `db`, usuário `user` e senha `password` (conforme o Compose atual).

O endpoint `/api/map-data` deve retornar JSON com as categorias e camadas públicas ativas. A ausência de camadas após uma instalação limpa não significa necessariamente erro.

### 8. Execute o front-end

Em outro terminal, clone o [repositório do front-end](https://github.com/walissonportela/sobral-em-mapas-frontend) e siga as instruções do README correspondente. Na configuração local do React, use:

```dotenv
VITE_API_URL=http://localhost:8080/api
```

O endereço padrão da interface é **http://localhost:5173**.

## Endpoints principais

| Método e rota | Finalidade |
| --- | --- |
| `GET /api/map-data` | Catálogo público de categorias e camadas |
| `POST /api/login` | Autenticação |
| `POST /api/register` | Cadastro de visitante |
| `GET /api/me` | Dados do usuário autenticado |
| `POST /api/logout` | Encerrar o token atual |
| `GET /api/geodata/{filename}` | GeoJSON de camada pública e ativa |
| `GET /api/proxy-wms` | Intermediação de requisições WMS |
| `GET /api/admin/dashboard/stats` | Estatísticas administrativas |
| `GET/POST /api/admin/geojson-files` | Listagem e upload administrativo de GeoJSON |

As rotas administrativas requerem autenticação Sanctum e perfil autorizado. Consulte `routes/api.php` para a relação completa e atualizada.

## Gerenciamento do Docker

```bash
# Verificar serviços
docker compose ps

# Acompanhar os logs da API
docker compose logs -f app

# Executar um comando Artisan
docker compose exec app php artisan route:list

# Parar e remover os contêineres (preservando o volume do banco)
docker compose down
```

**Cuidado:** `docker compose down -v` remove também os volumes nomeados, inclusive o volume `dbdata` do MySQL. Não execute esse comando se quiser preservar os dados.

## Armazenamento de dados geoespaciais

O painel permite enviar arquivos `.geojson` do tipo `FeatureCollection`, com tamanho máximo de **10 MB por arquivo** conforme o controlador atual. Os arquivos são gravados em **`storage/app/geodata`**.

O upload sozinho não publica uma camada: é necessário cadastrar a camada correspondente, ativá-la e defini-la como pública. Os arquivos servidos pela rota `/api/geodata/{filename}` estão sujeitos a essas verificações.

Os dados administrativos ficam no MySQL, enquanto as geometrias podem vir dos arquivos GeoJSON da aplicação ou de serviços WMS externos. **O banco e os arquivos geoespaciais são recursos distintos**; para reproduzir uma instalação, preserve ambos.

## Problemas comuns

**Erro de conexão com o MySQL:** confira `DB_HOST=db`, os dados do `.env` e se o serviço do MySQL já terminou de iniciar. Em seguida, execute `docker compose ps` e tente novamente a migração.

**Erro de arquivo `vendor/autoload.php`:** execute `docker compose exec app composer install --no-interaction` para instalar as dependências na pasta montada.

**Erro de `APP_KEY`:** execute `docker compose exec app php artisan key:generate` e limpe o cache de configuração, se necessário, com `docker compose exec app php artisan config:clear`.

**Erro no seeder de usuários:** defina `SUPERADMIN_EMAIL` válido e `SUPERADMIN_PASSWORD` com no mínimo 12 caracteres no `.env`. O seeder usa `firstOrCreate`; uma nova execução não redefine automaticamente a senha de uma conta existente.

**Erro de permissão no upload GeoJSON:** verifique se `storage/app/geodata` existe e se o usuário do Apache consegue escrever no diretório.

**O front-end não recebe os dados:** confirme `VITE_API_URL=http://localhost:8080/api`, verifique o endpoint público e a configuração CORS em `config/cors.php`.

**Camadas WMS não aparecem:** o GeoServer ou serviço remoto pode estar indisponível. Isso não impede o uso das camadas GeoJSON locais, quando elas estiverem cadastradas e acessíveis.

## Estrutura principal

```text
app/               # Controllers, regras e modelos da aplicação
config/            # Configurações de banco, CORS e serviços
database/
├── migrations/    # Estrutura das tabelas
└── seeders/       # Perfis, usuário inicial e catálogo temático
docker/            # Configuração do Apache
routes/api.php     # Rotas da API
storage/app/geodata/  # Arquivos GeoJSON enviados
Dockerfile
docker-compose.yml
```

## Repositórios e autoria

- [Back-end — este repositório](https://github.com/walissonportela/sobral-em-mapas-backend)
- [Front-end — interface React](https://github.com/walissonportela/sobral-em-mapas-frontend)

Projeto acadêmico desenvolvido por **Walisson Portela**, no contexto do TCC **Sobral em Mapas**.
