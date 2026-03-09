# LocafyFest - Sistema de Aluguel de Produtos

## Estrutura do Projeto

Este projeto segue uma arquitetura modular bem estruturada para facilitar a manutenção e escalabilidade.

### Estrutura de Pastas

```
app/
├── Modules/
│   ├── User/
│   │   ├── Controllers/
│   │   ├── Services/
│   │   ├── Queries/
│   │   ├── Requests/
│   │   ├── Policies/
│   │   └── Models/
│   ├── Address/
│   ├── Product/
│   ├── Order/
│   ├── Favorite/
│   ├── Review/
│   ├── Notification/
│   └── SystemSetting/
database/
├── migrations/
routes/
├── api.php
```

### Módulos do Sistema

1. **User** - Gerenciamento de usuários
2. **Address** - Endereços dos usuários
3. **Product** - Produtos, categorias, variações e imagens
4. **Order** - Pedidos e itens de pedidos
5. **Favorite** - Produtos favoritos dos usuários
6. **Review** - Avaliações dos produtos
7. **Notification** - Sistema de notificações
8. **SystemSetting** - Configurações do sistema

### Padrões Utilizados

- **Controllers**: Responsáveis por receber as requisições HTTP e retornar respostas
- **Services**: Contêm a lógica de negócio da aplicação
- **Queries**: Responsáveis por consultas complexas ao banco de dados
- **Requests**: Validação e formatação dos dados de entrada
- **Policies**: Autorização e controle de acesso
- **Models**: Representação das entidades do banco de dados

### Tecnologias

- Laravel Framework 8.75
- MySQL
- API RESTful
- Autenticação via API Token

