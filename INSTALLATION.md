# LocafyFest - Guia de Instalação

## Requisitos do Sistema

### Requisitos Mínimos
- **PHP**: 8.0 ou superior
- **Laravel**: 8.75
- **MySQL**: 8.0 ou superior
- **Composer**: 2.0 ou superior
- **Node.js**: 16.0 ou superior
- **NPM**: 8.0 ou superior

### Extensões PHP Necessárias
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- PDO_MySQL
- Tokenizer
- XML
- GD (para manipulação de imagens)
- Zip

## Instalação

### 1. Clone o Repositório

```bash
git clone https://github.com/seu-usuario/locafyfest.git
cd locafyfest
```

### 2. Instale as Dependências

#### Dependências PHP
```bash
composer install
```

#### Dependências JavaScript
```bash
npm install
```

### 3. Configuração do Ambiente

#### Copie o arquivo de ambiente
```bash
cp .env.example .env
```

#### Gere a chave da aplicação
```bash
php artisan key:generate
```

#### Configure o arquivo .env
```env
APP_NAME=LocafyFest
APP_ENV=local
APP_KEY=base64:sua-chave-gerada
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=locafyfest
DB_USERNAME=root
DB_PASSWORD=sua-senha

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DRIVER=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@locafyfest.com
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

# Configurações específicas do LocafyFest
LOCAFYFEST_DEFAULT_DELIVERY_FEE=10.00
LOCAFYFEST_MAX_RENTAL_DAYS=30
LOCAFYFEST_MIN_RENTAL_DAYS=1
```

### 4. Configuração do Banco de Dados

#### Crie o banco de dados
```sql
CREATE DATABASE locafyfest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### Execute as migrations
```bash
php artisan migrate
```

#### Execute os seeders (opcional)
```bash
php artisan db:seed
```

### 5. Configuração de Storage

#### Crie o link simbólico para storage
```bash
php artisan storage:link
```

#### Configure permissões (Linux/Mac)
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 6. Configuração do Laravel Sanctum

#### Publique a configuração do Sanctum
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

#### Execute a migration do Sanctum
```bash
php artisan migrate
```

### 7. Configuração de CORS

#### Configure CORS no arquivo config/cors.php
```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Em produção, especifique os domínios
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

### 8. Compilação de Assets (Opcional)

#### Para desenvolvimento
```bash
npm run dev
```

#### Para produção
```bash
npm run production
```

## Configuração para Produção

### 1. Configurações de Ambiente

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com

# Use um driver de cache mais robusto
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Configure email real
MAIL_MAILER=smtp
MAIL_HOST=seu-smtp.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@dominio.com
MAIL_PASSWORD=sua-senha
MAIL_ENCRYPTION=tls
```

### 2. Otimizações

#### Cache de configuração
```bash
php artisan config:cache
```

#### Cache de rotas
```bash
php artisan route:cache
```

#### Cache de views
```bash
php artisan view:cache
```

#### Otimização do autoloader
```bash
composer install --optimize-autoloader --no-dev
```

### 3. Configuração do Servidor Web

#### Nginx
```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /var/www/locafyfest/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.html index.htm index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache
```apache
<VirtualHost *:80>
    ServerName seu-dominio.com
    DocumentRoot /var/www/locafyfest/public

    <Directory /var/www/locafyfest/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/locafyfest_error.log
    CustomLog ${APACHE_LOG_DIR}/locafyfest_access.log combined
</VirtualHost>
```

## Configuração de Queue (Opcional)

### 1. Configure o driver de queue
```env
QUEUE_CONNECTION=database
```

### 2. Crie a tabela de jobs
```bash
php artisan queue:table
php artisan migrate
```

### 3. Execute o worker
```bash
php artisan queue:work
```

### 4. Configure supervisor (produção)
```ini
[program:locafyfest-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/locafyfest/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/locafyfest/storage/logs/worker.log
```

## Configuração de Backup

### 1. Instale o pacote de backup
```bash
composer require spatie/laravel-backup
```

### 2. Publique a configuração
```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

### 3. Configure o cron job
```bash
# Adicione ao crontab
0 2 * * * cd /var/www/locafyfest && php artisan backup:run
```

## Configuração de Monitoramento

### 1. Configure logs
```env
LOG_CHANNEL=daily
LOG_LEVEL=info
```

### 2. Configure notificações de erro
```php
// No arquivo config/logging.php
'channels' => [
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'LocafyFest',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

## Comandos Úteis

### Desenvolvimento
```bash
# Iniciar servidor de desenvolvimento
php artisan serve

# Limpar caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recriar banco de dados
php artisan migrate:fresh --seed

# Gerar documentação da API
php artisan api:generate
```

### Produção
```bash
# Deploy completo
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up

# Backup
php artisan backup:run

# Verificar status
php artisan health:check
```

## Solução de Problemas

### Erro de Permissões
```bash
sudo chown -R www-data:www-data /var/www/locafyfest
sudo chmod -R 755 /var/www/locafyfest
sudo chmod -R 775 /var/www/locafyfest/storage
sudo chmod -R 775 /var/www/locafyfest/bootstrap/cache
```

### Erro de Chave da Aplicação
```bash
php artisan key:generate
```

### Erro de Storage Link
```bash
php artisan storage:link
```

### Erro de Migrations
```bash
php artisan migrate:status
php artisan migrate:rollback
php artisan migrate
```

### Erro de Composer
```bash
composer dump-autoload
composer install --no-scripts
```

## Verificação da Instalação

### 1. Teste a aplicação
```bash
php artisan serve
```

### 2. Acesse no navegador
```
http://localhost:8000/api/health
```

### 3. Resposta esperada
```json
{
  "status": "ok",
  "timestamp": "2024-01-01T00:00:00.000000Z",
  "version": "1.0.0",
  "environment": "local"
}
```

## Suporte

Para problemas de instalação:
1. Verifique os logs em `storage/logs/laravel.log`
2. Consulte a documentação oficial do Laravel
3. Abra uma issue no repositório do projeto
4. Entre em contato com a equipe de desenvolvimento

---

**Versão**: 1.0.0  
**Última atualização**: Janeiro 2024

