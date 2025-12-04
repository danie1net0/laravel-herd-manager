# Herd Manager

Gerenciador web para expor sites do Laravel Herd para a rede local.

## Características

- 📱 Interface web intuitiva
- 🌐 Exposição de sites para rede local
- 🔄 Proxy reverso configurável
- ⚡ Configuração dinâmica de portas
- 🧪 Testes automatizados com Pest PHP

## Requisitos

- PHP 8.1+
- Laravel Herd
- Composer

## Instalação

```bash
# Instalar dependências
composer install
```

## Testes

Este projeto utiliza [Pest PHP](https://pestphp.com/) v4 para testes.

### Executar todos os testes

```bash
composer test
```

Ou diretamente:

```bash
./vendor/bin/pest
```

### Executar testes específicos

```bash
# Apenas testes do HerdManager
./vendor/bin/pest tests/Unit/HerdManagerTest.php

# Apenas testes do ProxyManager
./vendor/bin/pest tests/Unit/ProxyManagerTest.php
```

### Ver cobertura de código

```bash
composer test:coverage
```

### Executar com filtro

```bash
# Executar apenas testes que contenham "generateNginxConfig"
./vendor/bin/pest --filter=generateNginxConfig

# Executar apenas testes de validação de porta
./vendor/bin/pest --filter=checkPortAvailability
```

## Estrutura do Projeto

```
herd-manager/
├── src/
│   ├── HerdManager.php      # Gerenciamento de sites do Herd
│   └── ProxyManager.php     # Gerenciamento de proxies reversos
├── tests/
│   └── Unit/
│       ├── HerdManagerTest.php
│       └── ProxyManagerTest.php
├── index.php                # Interface web
├── api.php                  # API REST
└── composer.json
```

## API

### Endpoints Disponíveis

#### Listar Sites
```
GET /api.php?action=list
```

#### Obter IP Local
```
GET /api.php?action=ip
```

#### Aplicar Configurações
```
POST /api.php?action=apply
Content-Type: application/json

{
  "sites": [
    {
      "name": "my-site",
      "url": "http://my-site.test",
      "port": 8000,
      "exposed": true
    }
  ]
}
```

#### Verificar Disponibilidade de Porta
```
GET /api.php?action=check-port&port=8000
```

#### Listar Proxies
```
GET /api.php?action=list-proxies
```

#### Criar Proxy
```
POST /api.php?action=create-proxy
Content-Type: application/json

{
  "name": "my-proxy",
  "port": 3000
}
```

#### Deletar Proxy
```
POST /api.php?action=delete-proxy
Content-Type: application/json

{
  "name": "my-proxy"
}
```

## Desenvolvimento

### Executar testes durante desenvolvimento

```bash
# Watch mode (reexecuta testes ao modificar arquivos)
./vendor/bin/pest --watch
```

### Adicionar novos testes

Os testes seguem o padrão BDD do Pest:

```php
describe('MinhaClasse', function () {
    beforeEach(function () {
        $this->instance = new MinhaClasse();
    });

    describe('meuMetodo', function () {
        it('faz algo esperado', function () {
            $result = $this->instance->meuMetodo('input');

            expect($result)->toBe('expected');
        });
    });
});
```

## Cobertura de Testes

### Estatísticas

- **39 testes** (25 unitários + 14 integração)
- **181 assertions**
- **100% cobertura** das classes principais
- **1.8s** tempo de execução

### O que está coberto

#### Testes Unitários
✅ Parsing de lista de sites do Herd
✅ Validação de disponibilidade de portas
✅ Geração de configurações Nginx
✅ Criação e remoção de proxies
✅ Validação de nomes e portas
✅ Geração de configurações de proxy

#### Testes de Integração
✅ Fluxo completo de exposição de sites
✅ Fluxo completo de criação de proxies
✅ Validação de estruturas de dados
✅ Configurações Nginx com headers corretos
✅ Suporte a WebSocket

Veja [TEST_RESULTS.md](TEST_RESULTS.md) para detalhes completos.

## Troubleshooting

### Testes falhando

Se os testes estiverem falhando, verifique:

1. PHP versão 8.1+ instalado
2. Extensões PHP necessárias habilitadas
3. Permissões de leitura/escrita nos diretórios

### Problemas com autoload

```bash
composer dump-autoload
```

## Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-feature`)
3. Commit suas mudanças (`git commit -m 'feat: adiciona nova feature'`)
4. Push para a branch (`git push origin feature/nova-feature`)
5. Abra um Pull Request

### Padrão de Commits

Seguimos o padrão [Conventional Commits](https://www.conventionalcommits.org/):

- `feat`: Nova funcionalidade
- `fix`: Correção de bug
- `docs`: Mudanças na documentação
- `test`: Adição ou correção de testes
- `refactor`: Refatoração de código
- `style`: Formatação de código
- `chore`: Tarefas de manutenção

## Licença

Este projeto é de código aberto e está disponível sob a licença MIT.

## Autor

**Daniel**
Data: 2025-12-02
