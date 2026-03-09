# LocafyFest - Documentação do Back-end

## Visão Geral

O LocafyFest é um sistema de aluguel de produtos desenvolvido em Laravel 8.75, estruturado com arquitetura modular para facilitar manutenção e escalabilidade. O sistema oferece uma API RESTful completa para gerenciamento de usuários, produtos, pedidos e todas as funcionalidades relacionadas.

## Arquitetura do Projeto

### Estrutura Modular

O projeto foi organizado em módulos independentes, cada um contendo:

```
app/Modules/
├── User/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   ├── Queries/
│   ├── Requests/
│   └── Policies/
├── Product/
├── Order/
├── Address/
├── Notification/
├── Favorite/
├── Review/
└── SystemSetting/
```

### Padrões Implementados

- **Repository Pattern**: Através das classes Query
- **Service Layer**: Lógica de negócio centralizada
- **Policy-based Authorization**: Controle de acesso granular
- **Request Validation**: Validação robusta de dados
- **Modular Architecture**: Separação clara de responsabilidades

## Módulos do Sistema

### 1. User (Usuários)
- **Tipos**: Client, Admin, Manager
- **Funcionalidades**: CRUD, autenticação, upload de foto, alteração de senha
- **Relacionamentos**: Endereços, Pedidos, Notificações, Favoritos

### 2. Product (Produtos)
- **Funcionalidades**: CRUD, variações, imagens, categorias, favoritos
- **Características**: Preços, estoque, disponibilidade, especificações
- **Relacionamentos**: Categorias, Variações, Imagens, Pedidos, Avaliações

### 3. Order (Pedidos)
- **Status**: Pending, Confirmed, Preparing, Delivered, In Use, Returned, Cancelled
- **Funcionalidades**: CRUD, pagamentos, entrega, devolução, relatórios
- **Relacionamentos**: Cliente, Itens, Endereços, Avaliações

### 4. Address (Endereços)
- **Tipos**: Home, Work, Other
- **Funcionalidades**: CRUD, busca por CEP, coordenadas, endereço padrão
- **Relacionamentos**: Usuário, Pedidos (entrega/retirada)

### 5. Notification (Notificações)
- **Tipos**: System, Order, Product, Promotion, Reminder
- **Funcionalidades**: CRUD, envio em massa, expiração, leitura
- **Relacionamentos**: Usuário

## Banco de Dados

### Principais Tabelas

1. **users**: Usuários do sistema
2. **addresses**: Endereços dos usuários
3. **product_categories**: Categorias de produtos
4. **products**: Produtos disponíveis
5. **product_variations**: Variações dos produtos
6. **product_images**: Imagens dos produtos
7. **orders**: Pedidos de aluguel
8. **order_items**: Itens dos pedidos
9. **favorites**: Produtos favoritos
10. **reviews**: Avaliações dos produtos
11. **notifications**: Notificações do sistema
12. **system_settings**: Configurações do sistema

### Relacionamentos Principais

- User → hasMany → Address, Order, Favorite, Review, Notification
- Product → belongsTo → ProductCategory
- Product → hasMany → ProductVariation, ProductImage, OrderItem, Favorite, Review
- Order → belongsTo → User (client), Address (delivery/pickup)
- Order → hasMany → OrderItem, Review

## API Endpoints

### Rotas Públicas (v1/)

```
GET /products - Listar produtos
GET /products/featured - Produtos em destaque
GET /products/popular - Produtos populares
GET /products/{id} - Detalhes do produto
GET /products/{id}/related - Produtos relacionados
POST /products/{id}/check-availability - Verificar disponibilidade
GET /categories - Listar categorias
GET /addresses/search-cep/{cep} - Buscar endereço por CEP
GET /settings/public - Configurações públicas
```

### Rotas Autenticadas (v1/)

```
GET /user - Dados do usuário autenticado
GET|POST /users - Listar/Criar usuários
GET|PUT|DELETE /users/{id} - CRUD de usuário específico
GET|POST /addresses - Listar/Criar endereços
GET|POST /orders - Listar/Criar pedidos
GET|POST /favorites - Listar/Criar favoritos
GET|POST /reviews - Listar/Criar avaliações
GET|POST /notifications - Listar/Criar notificações
```

### Rotas Administrativas (v1/admin/)

```
GET /dashboard - Dashboard administrativo
GET /reports/* - Relatórios diversos
GET /logs - Logs do sistema
POST /backup/* - Backup e restore
```

## Autenticação e Autorização

### Sistema de Autenticação
- **Laravel Sanctum**: Para autenticação de API
- **Tokens**: Geração e gerenciamento de tokens de acesso

### Níveis de Acesso
1. **Client**: Acesso básico (próprios dados, pedidos, favoritos)
2. **Manager**: Gerenciamento de clientes e produtos
3. **Admin**: Acesso total ao sistema

### Policies Implementadas
- UserPolicy: Controle de acesso a usuários
- ProductPolicy: Controle de acesso a produtos
- OrderPolicy: Controle de acesso a pedidos
- AddressPolicy: Controle de acesso a endereços
- NotificationPolicy: Controle de acesso a notificações

## Validações

### Request Classes
Cada operação possui validações específicas:

- **CreateUserRequest**: Validação para criação de usuários
- **UpdateUserRequest**: Validação para atualização de usuários
- **CreateProductRequest**: Validação para criação de produtos
- **CreateOrderRequest**: Validação para criação de pedidos
- **ProcessPaymentRequest**: Validação para processamento de pagamentos

### Regras de Negócio
- Produtos devem ter estoque disponível
- Pedidos respeitam limites mínimos/máximos de dias
- Endereços são validados por CEP
- Pagamentos seguem métodos específicos

## Services e Queries

### Services (Lógica de Negócio)
- **UserService**: Gerenciamento de usuários
- **ProductService**: Gerenciamento de produtos
- **OrderService**: Gerenciamento de pedidos
- **AddressService**: Gerenciamento de endereços
- **NotificationService**: Gerenciamento de notificações

### Queries (Consultas Complexas)
- **UserQuery**: Consultas avançadas de usuários
- **ProductQuery**: Consultas avançadas de produtos
- **OrderQuery**: Consultas avançadas de pedidos
- **AddressQuery**: Consultas avançadas de endereços
- **NotificationQuery**: Consultas avançadas de notificações

## Configuração e Deploy

### Requisitos
- PHP 8.0+
- Laravel 8.75
- MySQL 8.0+
- Composer
- Node.js (para assets)

### Instalação

1. **Clone o repositório**
```bash
git clone <repository-url>
cd locafyfest
```

2. **Instale dependências**
```bash
composer install
npm install
```

3. **Configure ambiente**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure banco de dados**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=locafyfest
DB_USERNAME=root
DB_PASSWORD=
```

5. **Execute migrations**
```bash
php artisan migrate
php artisan db:seed
```

6. **Configure storage**
```bash
php artisan storage:link
```

### Configurações Importantes

#### CORS
Configure CORS no arquivo `config/cors.php` para permitir requisições do frontend.

#### Sanctum
Configure Sanctum no arquivo `config/sanctum.php` para autenticação de API.

#### File Storage
Configure storage no arquivo `config/filesystems.php` para upload de arquivos.

## Funcionalidades Principais

### Gestão de Usuários
- Registro e autenticação
- Perfis com foto
- Diferentes níveis de acesso
- Histórico de atividades

### Gestão de Produtos
- Catálogo completo
- Variações e especificações
- Múltiplas imagens
- Sistema de favoritos
- Avaliações e comentários

### Gestão de Pedidos
- Processo completo de aluguel
- Cálculo automático de preços
- Gestão de entregas
- Controle de devoluções
- Relatórios financeiros

### Sistema de Notificações
- Notificações em tempo real
- Lembretes automáticos
- Comunicação em massa
- Histórico completo

## Segurança

### Medidas Implementadas
- Validação de entrada rigorosa
- Autorização baseada em policies
- Sanitização de dados
- Rate limiting
- CSRF protection
- SQL injection prevention

### Boas Práticas
- Senhas hasheadas
- Tokens seguros
- Logs de auditoria
- Backup automático
- Monitoramento de acesso

## Monitoramento e Logs

### Logs do Sistema
- Logs de aplicação
- Logs de erro
- Logs de acesso
- Logs de auditoria

### Métricas
- Performance de queries
- Uso de recursos
- Estatísticas de usuários
- Relatórios de vendas

## Manutenção

### Backup
- Backup automático do banco
- Backup de arquivos
- Restore point-in-time

### Atualizações
- Versionamento semântico
- Migrations automáticas
- Deploy zero-downtime

## Suporte e Contato

Para dúvidas técnicas ou suporte:
- Documentação: Este arquivo
- Issues: GitHub Issues
- Email: suporte@locafyfest.com

---

**Versão**: 1.0.0  
**Data**: Janeiro 2024  
**Autor**: Equipe LocafyFest

