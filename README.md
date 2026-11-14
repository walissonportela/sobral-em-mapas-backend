
# 🗺️ Sobral em Mapas — Back-end

API REST da plataforma **Sobral em Mapas**, desenvolvida para o gerenciamento de informações geoespaciais do município de Sobral, Ceará.

Este repositório contém o back-end da aplicação, desenvolvido como parte do Trabalho de Conclusão de Curso (TCC) intitulado **"Sobral em Mapas: Engenharia de Software Aplicada ao Desenvolvimento de uma Plataforma WebGIS para Gestão de Dados Geoespaciais"**.

## 📌 Sobre o projeto

O back-end do Sobral em Mapas é responsável pelo gerenciamento dos dados administrativos e geoespaciais utilizados pela plataforma.

A aplicação disponibiliza uma API REST responsável pela comunicação com o front-end, autenticação de usuários, gerenciamento de categorias, subcategorias, camadas cartográficas e arquivos GeoJSON.

A arquitetura permite utilizar arquivos GeoJSON armazenados no servidor e integrar serviços geoespaciais externos por meio de WMS.

## 🚀 Tecnologias utilizadas

- PHP 8.2 no ambiente Docker
- Laravel 8
- MySQL 8
- Laravel Sanctum
- Docker e Docker Compose
- Apache
- Composer
- API REST
- GeoJSON
- WMS / GeoServer

## ✨ Funcionalidades

- Autenticação de usuários utilizando Laravel Sanctum.
- Controle de permissões por perfis.
- Cadastro e gerenciamento de usuários.
- Aprovação de solicitações de cadastro.
- Gerenciamento de categorias e subcategorias.
- Cadastro, edição e exclusão de camadas geográficas.
- Gerenciamento de serviços WMS.
- Upload e armazenamento de arquivos GeoJSON.
- Disponibilização de camadas geográficas por API.
- Integração com serviços geoespaciais externos.
- Gerenciamento de dados administrativos no MySQL.
- Disponibilização de estatísticas para o painel administrativo.

## 📋 Pré-requisitos

Para executar o projeto pelo método recomendado, instale:

- [Git](https://git-scm.com/)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)

O Docker permite executar a aplicação sem instalar PHP, MySQL e Apache diretamente no computador.

Certifique-se de que as portas `8080`, `8081` e `3306` estejam disponíveis.

## ⚙️ Instalação e execução com Docker

### 1. Clonar o repositório

Abra um terminal e execute:

```bash
git clone https://github.com/walissonportela/sobral-em-mapas-backend.git
```

Entre na pasta:

```bash
cd sobral-em-mapas-backend
```

### 2. Configurar o ambiente

Na raiz do projeto, crie um arquivo chamado `.env`.

Adicione a configuração inicial:

```env
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

**Importante:**

- Altere o e-mail e a senha do superadministrador para valores próprios.
- A senha deve possuir pelo menos 12 caracteres.
- `DB_HOST=db` corresponde ao nome do serviço MySQL no Docker Compose.
- As configurações de banco correspondem aos valores definidos no `docker-compose.yml`.
- O arquivo `.env` contém dados sensíveis e não deve ser enviado ao GitHub.
- `APP_DEBUG=true` deve ser utilizado apenas em desenvolvimento.

### 3. Iniciar o Docker

Abra o Docker Desktop e certifique-se de que ele esteja em execução.

No terminal, dentro da pasta do back-end, execute:

```bash
docker compose up -d --build
```

Esse comando constrói e inicializa os contêineres.

O ambiente é composto por três serviços:

| Serviço | Endereço |
|---------|----------|
| Laravel / Apache | http://localhost:8080 |
| MySQL | localhost:3306 |
| phpMyAdmin | http://localhost:8081 |

Na primeira execução, a construção das imagens poderá demorar um pouco mais.

### 4. Instalar as dependências do Laravel

Após iniciar os contêineres, execute:

```bash
docker compose exec app composer install --no-interaction
```

Esse comando garante que as dependências PHP estejam disponíveis no diretório montado pelo Docker.

### 5. Gerar a chave da aplicação

Execute:

```bash
docker compose exec app php artisan key:generate
```

O Laravel atualizará automaticamente a variável `APP_KEY` no arquivo `.env`.

### 6. Executar as migrações

Para criar as tabelas do banco de dados, execute:

```bash
docker compose exec app php artisan migrate
```

### 7. Executar os seeders

Para cadastrar os dados iniciais, execute:

```bash
docker compose exec app php artisan db:seed
```

Os seeders configuram:

- Perfil Superadministrador.
- Perfil Administrador.
- Perfil Visitante.
- Usuário inicial Superadministrador.
- Categorias temáticas iniciais.
- Subcategorias iniciais.

O superadministrador será criado utilizando o e-mail e a senha informados no `.env`.

**Observação:** os seeders não cadastram automaticamente as camadas geográficas utilizadas na demonstração do TCC.

Os arquivos GeoJSON e os respectivos cadastros de camadas devem ser adicionados separadamente.

### 8. Verificar a API

Depois de concluir a instalação, acesse:

http://localhost:8080/api/map-data

O endpoint deve retornar uma resposta JSON contendo os dados públicos disponibilizados pelo sistema.

A instalação inicial pode apresentar categorias sem camadas associadas, até que os dados cartográficos sejam cadastrados.

## 🗄️ Banco de dados

O projeto utiliza MySQL para armazenar as informações administrativas.

Entre as principais entidades estão:

- **User:** usuários cadastrados.
- **Profile:** perfis e permissões.
- **Category:** categorias temáticas.
- **Subcategory:** subcategorias.
- **Layer:** metadados das camadas geográficas.
- **WMS:** informações de serviços geoespaciais externos.

Os relacionamentos permitem organizar as camadas de maneira hierárquica, facilitando sua consulta e administração.

### Acessando o phpMyAdmin

Abra:

http://localhost:8081

Utilize as credenciais configuradas no Docker Compose:

```text
Servidor: db
Usuário: user
Senha: password
Banco: laravel
```

Essas são credenciais de exemplo para desenvolvimento local e não devem ser reutilizadas em produção.

## 🌍 Gerenciamento de dados geoespaciais

O sistema permite trabalhar com duas fontes principais de informações geográficas.

### 1. Serviços WMS

Permite consultar camadas provenientes de servidores geoespaciais, incluindo serviços disponibilizados por GeoServer.

A disponibilidade dessas camadas depende do funcionamento dos serviços externos.

### 2. Arquivos GeoJSON

Permite armazenar e disponibilizar arquivos GeoJSON diretamente pelo back-end.

Os arquivos enviados são armazenados em:

```text
storage/app/geodata
```

O upload aceita arquivos `.geojson` do tipo `FeatureCollection`, respeitando a validação e o limite de 10 MB por arquivo definidos no controlador.

O cadastro de um arquivo não implica sua publicação automática. É necessário associá-lo a uma camada e configurar sua disponibilidade.

Uma camada GeoJSON publicada pode ser disponibilizada por meio de:

```text
/api/geodata/{filename}
```

Essa abordagem permite utilizar camadas geográficas mesmo quando determinados serviços WMS externos estão indisponíveis.

## 🔗 Principais endpoints

### Rotas públicas

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/map-data` | Consulta de categorias e camadas públicas |
| POST | `/api/login` | Autenticação |
| POST | `/api/register` | Cadastro de visitante |
| GET | `/api/geodata/{filename}` | Acesso a GeoJSON publicado |
| GET | `/api/proxy-wms` | Comunicação com serviços WMS |

### Rotas autenticadas

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/me` | Dados do usuário autenticado |
| POST | `/api/logout` | Encerramento da autenticação |
| GET | `/api/admin/dashboard/stats` | Estatísticas administrativas |
| GET | `/api/admin/categories` | Listagem de categorias |
| GET | `/api/admin/layers` | Listagem administrativa de camadas |
| GET | `/api/admin/geojson-files` | Listagem de arquivos GeoJSON |
| POST | `/api/admin/geojson-files` | Upload de arquivos GeoJSON |

As rotas administrativas utilizam autenticação e verificação de permissões.

A relação completa de endpoints encontra-se em:

```text
routes/api.php
```

## 🔐 Controle de acesso

O sistema utiliza Laravel Sanctum para autenticação baseada em tokens.

Os usuários são organizados em três perfis.

**Superadministrador:** possui acesso completo às funcionalidades administrativas e ao gerenciamento de usuários.

**Administrador:** possui acesso ao gerenciamento de dados cartográficos e a operações administrativas autorizadas, com restrições sobre contas de outros administradores.

**Visitante:** possui acesso às funcionalidades públicas do mapa.

Os cadastros podem exigir aprovação antes da liberação do acesso.

## 📁 Estrutura do projeto

```text
app/
├── Http/
│   └── Controllers/    # Controllers e endpoints
└── Infrastructure/     # Modelos e componentes da aplicação

bootstrap/              # Inicialização do Laravel
config/                 # Configurações
database/
├── migrations/         # Estrutura das tabelas
└── seeders/            # Dados iniciais

docker/                 # Configurações do Apache
public/                 # Entrada pública da aplicação
routes/
└── api.php             # Rotas REST

storage/
├── app/
│   └── geodata/        # Arquivos GeoJSON
└── logs/               # Logs da aplicação

Dockerfile
docker-compose.yml
composer.json
```

## 🛠️ Comandos úteis do Docker

### Iniciar os contêineres

```bash
docker compose up -d
```

### Verificar os contêineres

```bash
docker compose ps
```

### Acompanhar os logs da aplicação

```bash
docker compose logs -f app
```

### Executar migrações pendentes

```bash
docker compose exec app php artisan migrate
```

### Limpar o cache de configuração

```bash
docker compose exec app php artisan config:clear
```

### Listar as rotas cadastradas

```bash
docker compose exec app php artisan route:list
```

### Parar o ambiente

```bash
docker compose down
```

Esse último comando remove os contêineres sem excluir o volume persistente do MySQL.

**Atenção:** evite utilizar `docker compose down -v` se desejar preservar os dados do banco, pois a opção `-v` também remove os volumes nomeados.

## ❗ Possíveis problemas

### Erro de conexão com o MySQL

Verifique:

- Se o Docker está funcionando.
- Se o contêiner do MySQL está iniciado.
- Se as credenciais do `.env` correspondem às do Docker Compose.
- Se `DB_HOST` está configurado como `db`.

### Erro de APP_KEY

Execute:

```bash
docker compose exec app php artisan key:generate
```

### Erro ao executar os seeders

Verifique se `SUPERADMIN_EMAIL` contém um e-mail válido e se `SUPERADMIN_PASSWORD` possui pelo menos 12 caracteres.

O seeder inicial não altera automaticamente a senha de um usuário que já existe no banco.

### Erro de dependências

Execute novamente:

```bash
docker compose exec app composer install --no-interaction
```

### Erro no upload de GeoJSON

Verifique se o diretório abaixo existe e possui permissão de escrita:

```text
storage/app/geodata
```

### Camadas não aparecem no mapa

Confirme se:

- As tabelas foram criadas.
- Existem camadas cadastradas.
- As camadas estão ativas e públicas.
- Os arquivos GeoJSON correspondentes estão disponíveis.
- O front-end está configurado para acessar a API correta.

## 🌐 Implantação

O back-end foi preparado para implantação utilizando Docker, incluindo configurações de execução na plataforma Railway.

Em produção, é necessário configurar adequadamente:

- Variáveis de ambiente.
- Credenciais do banco de dados.
- Chave de criptografia do Laravel.
- Endereço público da aplicação.
- Origem permitida do front-end.
- Armazenamento persistente dos arquivos GeoJSON.
- Permissões dos diretórios de armazenamento.

Não reutilize as credenciais de desenvolvimento em servidores públicos e mantenha `APP_DEBUG=false` em produção.

## 📚 Repositórios

**Back-end:**

https://github.com/walissonportela/sobral-em-mapas-backend

**Front-end:**

https://github.com/walissonportela/sobral-em-mapas-frontend

## 👨‍💻 Autor

**Walisson Portela**

Projeto acadêmico desenvolvido como Trabalho de Conclusão de Curso (TCC), com foco na aplicação de conceitos de Engenharia de Software ao desenvolvimento de uma plataforma WebGIS para gestão de dados geoespaciais.
