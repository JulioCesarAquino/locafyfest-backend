# LocafyFest API Reference

## Base URL
```
https://api.locafyfest.com/api/v1
```

## Autenticação

### Bearer Token
```http
Authorization: Bearer {token}
```

### Obter Token
```http
POST /auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

## Códigos de Status

- `200` - OK
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Formato de Resposta

### Sucesso
```json
{
  "success": true,
  "data": {},
  "message": "Operação realizada com sucesso"
}
```

### Erro
```json
{
  "success": false,
  "message": "Mensagem de erro",
  "error": "Código do erro",
  "errors": {} // Detalhes de validação (quando aplicável)
}
```

## Endpoints

### Usuários

#### Listar Usuários
```http
GET /users
Authorization: Bearer {token}
```

**Query Parameters:**
- `name` (string): Filtrar por nome
- `email` (string): Filtrar por email
- `user_type` (string): client|admin|manager
- `is_active` (boolean): Filtrar por status
- `per_page` (integer): Itens por página (padrão: 15)

**Resposta:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "João Silva",
        "email": "joao@example.com",
        "user_type": "client",
        "is_active": true,
        "created_at": "2024-01-01T00:00:00.000000Z"
      }
    ],
    "total": 100
  }
}
```

#### Criar Usuário
```http
POST /users
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "user_type": "client",
  "phone": "(11) 99999-9999",
  "cpf": "123.456.789-00"
}
```

#### Obter Usuário
```http
GET /users/{id}
Authorization: Bearer {token}
```

#### Atualizar Usuário
```http
PUT /users/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "João Silva Santos",
  "phone": "(11) 88888-8888"
}
```

#### Deletar Usuário
```http
DELETE /users/{id}
Authorization: Bearer {token}
```

### Produtos

#### Listar Produtos
```http
GET /products
```

**Query Parameters:**
- `search` (string): Buscar por nome/descrição
- `category_id` (integer): Filtrar por categoria
- `min_price` (decimal): Preço mínimo
- `max_price` (decimal): Preço máximo
- `featured` (boolean): Apenas produtos em destaque
- `in_stock` (boolean): Apenas produtos em estoque
- `per_page` (integer): Itens por página

**Resposta:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Vestido de Festa",
        "description": "Vestido elegante para festas",
        "price": 150.00,
        "quantity_available": 5,
        "is_available": true,
        "is_featured": true,
        "category": {
          "id": 1,
          "name": "Vestidos"
        },
        "primary_image": {
          "url": "https://example.com/image.jpg"
        }
      }
    ]
  }
}
```

#### Obter Produto
```http
GET /products/{id}
```

#### Criar Produto
```http
POST /products
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Vestido de Festa",
  "description": "Vestido elegante para festas",
  "short_description": "Vestido elegante",
  "category_id": 1,
  "price": 150.00,
  "quantity_available": 5,
  "minimum_rental_days": 1,
  "maximum_rental_days": 7,
  "deposit_amount": 50.00,
  "specifications": {
    "material": "Seda",
    "cor": "Azul"
  }
}
```

#### Verificar Disponibilidade
```http
POST /products/{id}/check-availability
Content-Type: application/json

{
  "start_date": "2024-02-01",
  "end_date": "2024-02-03",
  "quantity": 1,
  "variation_id": 1
}
```

### Pedidos

#### Listar Pedidos
```http
GET /orders
Authorization: Bearer {token}
```

**Query Parameters:**
- `order_number` (string): Número do pedido
- `client_id` (integer): ID do cliente
- `status` (string): Status do pedido
- `payment_status` (string): Status do pagamento
- `created_from` (date): Data inicial
- `created_to` (date): Data final

#### Criar Pedido
```http
POST /orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "client_id": 1,
  "rental_start_date": "2024-02-01",
  "rental_end_date": "2024-02-03",
  "delivery_address_id": 1,
  "pickup_address_id": 1,
  "notes": "Observações do pedido",
  "items": [
    {
      "product_id": 1,
      "product_variation_id": 1,
      "quantity": 1,
      "notes": "Observações do item"
    }
  ]
}
```

#### Confirmar Pedido
```http
POST /orders/{id}/confirm
Authorization: Bearer {token}
```

#### Cancelar Pedido
```http
POST /orders/{id}/cancel
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Motivo do cancelamento"
}
```

#### Processar Pagamento
```http
POST /orders/{id}/process-payment
Authorization: Bearer {token}
Content-Type: application/json

{
  "method": "credit_card",
  "amount": 200.00,
  "card_number": "1234 5678 9012 3456",
  "card_holder_name": "João Silva",
  "card_expiry_month": 12,
  "card_expiry_year": 2025,
  "card_cvv": "123",
  "installments": 1
}
```

### Endereços

#### Listar Endereços
```http
GET /addresses
Authorization: Bearer {token}
```

#### Criar Endereço
```http
POST /addresses
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "home",
  "street": "Rua das Flores, 123",
  "number": "123",
  "complement": "Apto 45",
  "neighborhood": "Centro",
  "city": "São Paulo",
  "state": "SP",
  "zip_code": "01234-567",
  "is_default": true
}
```

#### Buscar por CEP
```http
GET /addresses/search-cep/{cep}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "zip_code": "01234-567",
    "street": "Rua das Flores",
    "neighborhood": "Centro",
    "city": "São Paulo",
    "state": "SP"
  }
}
```

### Favoritos

#### Listar Favoritos
```http
GET /favorites/my-favorites
Authorization: Bearer {token}
```

#### Adicionar aos Favoritos
```http
POST /favorites
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 1
}
```

#### Remover dos Favoritos
```http
DELETE /favorites/{id}
Authorization: Bearer {token}
```

### Avaliações

#### Listar Avaliações do Produto
```http
GET /products/{id}/reviews
```

#### Criar Avaliação
```http
POST /reviews
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 1,
  "order_id": 1,
  "rating": 5,
  "comment": "Produto excelente!"
}
```

#### Aprovar Avaliação
```http
POST /reviews/{id}/approve
Authorization: Bearer {token}
```

### Notificações

#### Listar Notificações
```http
GET /notifications/my-notifications
Authorization: Bearer {token}
```

#### Contar Não Lidas
```http
GET /notifications/unread-count
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "count": 5
  }
}
```

#### Marcar como Lida
```http
POST /notifications/{id}/mark-read
Authorization: Bearer {token}
```

#### Marcar Todas como Lidas
```http
POST /notifications/mark-all-read
Authorization: Bearer {token}
```

### Categorias

#### Listar Categorias
```http
GET /categories
```

#### Criar Categoria
```http
POST /categories
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Vestidos",
  "description": "Vestidos para todas as ocasiões",
  "is_active": true
}
```

## Webhooks

### Pagamento Processado
```http
POST /webhooks/payment-gateway
Content-Type: application/json

{
  "event": "payment.processed",
  "order_id": 123,
  "transaction_id": "txn_123456",
  "status": "paid",
  "amount": 200.00
}
```

### Entrega Realizada
```http
POST /webhooks/delivery-service
Content-Type: application/json

{
  "event": "delivery.completed",
  "order_id": 123,
  "delivery_date": "2024-02-01T14:30:00Z",
  "recipient": "João Silva"
}
```

## Rate Limiting

- **Limite**: 60 requisições por minuto por IP
- **Headers de Resposta**:
  - `X-RateLimit-Limit`: Limite total
  - `X-RateLimit-Remaining`: Requisições restantes
  - `X-RateLimit-Reset`: Timestamp do reset

## Paginação

### Parâmetros
- `page` (integer): Página atual
- `per_page` (integer): Itens por página (máximo: 100)

### Resposta
```json
{
  "current_page": 1,
  "data": [],
  "first_page_url": "https://api.example.com/users?page=1",
  "from": 1,
  "last_page": 10,
  "last_page_url": "https://api.example.com/users?page=10",
  "next_page_url": "https://api.example.com/users?page=2",
  "path": "https://api.example.com/users",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 150
}
```

## Filtros e Ordenação

### Filtros Comuns
- `search`: Busca textual
- `created_from`: Data inicial (YYYY-MM-DD)
- `created_to`: Data final (YYYY-MM-DD)
- `is_active`: Status ativo (true/false)

### Ordenação
- `sort_by`: Campo para ordenação
- `sort_order`: Direção (asc/desc)

Exemplo:
```
GET /products?sort_by=name&sort_order=asc
```

## Códigos de Erro

### 400 - Bad Request
```json
{
  "success": false,
  "message": "Dados inválidos",
  "error": "INVALID_DATA"
}
```

### 401 - Unauthorized
```json
{
  "success": false,
  "message": "Token inválido ou expirado",
  "error": "UNAUTHORIZED"
}
```

### 403 - Forbidden
```json
{
  "success": false,
  "message": "Acesso negado",
  "error": "FORBIDDEN"
}
```

### 422 - Validation Error
```json
{
  "success": false,
  "message": "Dados de validação inválidos",
  "error": "VALIDATION_ERROR",
  "errors": {
    "email": ["O campo email é obrigatório"],
    "password": ["A senha deve ter pelo menos 8 caracteres"]
  }
}
```

## Exemplos de Uso

### Fluxo Completo de Pedido

1. **Buscar produtos**
```http
GET /products?category_id=1&in_stock=true
```

2. **Verificar disponibilidade**
```http
POST /products/1/check-availability
{
  "start_date": "2024-02-01",
  "end_date": "2024-02-03",
  "quantity": 1
}
```

3. **Criar pedido**
```http
POST /orders
{
  "rental_start_date": "2024-02-01",
  "rental_end_date": "2024-02-03",
  "items": [{"product_id": 1, "quantity": 1}]
}
```

4. **Processar pagamento**
```http
POST /orders/123/process-payment
{
  "method": "credit_card",
  "amount": 200.00
}
```

### Gerenciamento de Favoritos

1. **Adicionar aos favoritos**
```http
POST /favorites
{"product_id": 1}
```

2. **Listar favoritos**
```http
GET /favorites/my-favorites
```

3. **Remover dos favoritos**
```http
DELETE /favorites/1
```

---

**Versão da API**: v1  
**Última atualização**: Janeiro 2024

