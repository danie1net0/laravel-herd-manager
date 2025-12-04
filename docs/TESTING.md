# Guia de Testes - Herd Manager

Este documento explica como os testes estão organizados e como adicionar novos testes.

## Arquitetura de Testes

O projeto utiliza **Pest PHP v4** com estilo BDD (Behavior-Driven Development).

### Estrutura

```
tests/
├── Pest.php              # Configuração global do Pest
├── TestCase.php          # Classe base para testes
└── Unit/
    ├── HerdManagerTest.php
    └── ProxyManagerTest.php
```

## Padrões de Teste

### 1. Estrutura BDD com `describe` e `it`

```php
describe('MinhaClasse', function () {
    describe('meuMetodo', function () {
        it('retorna resultado esperado', function () {
            // Arrange
            $input = 'test';

            // Act
            $result = minhaFuncao($input);

            // Assert
            expect($result)->toBe('expected');
        });
    });
});
```

### 2. Setup com `beforeEach`

```php
describe('HerdManager', function () {
    beforeEach(function () {
        $this->manager = new HerdManager();
    });

    it('pode acessar a instância', function () {
        expect($this->manager)->toBeInstanceOf(HerdManager::class);
    });
});
```

### 3. Expectations

#### Tipos básicos
```php
expect($value)->toBe('expected');
expect($value)->toEqual('expected');
expect($value)->toBeTrue();
expect($value)->toBeFalse();
expect($value)->toBeNull();
expect($value)->toBeEmpty();
```

#### Arrays
```php
expect($array)->toHaveCount(3);
expect($array)->toHaveKey('name');
expect($array)->toContain('value');
expect($array)->toBeArray();
expect($array)->toMatchArray(['key' => 'value']);
```

#### Strings
```php
expect($string)->toContain('substring');
expect($string)->toStartWith('prefix');
expect($string)->toEndWith('suffix');
expect($string)->toMatch('/regex/');
```

#### Tipos
```php
expect($value)->toBeString();
expect($value)->toBeInt();
expect($value)->toBeBool();
expect($value)->toBeFloat();
expect($value)->toBeInstanceOf(MyClass::class);
```

#### Exceptions
```php
expect(fn() => throwError())
    ->toThrow(Exception::class);

expect(fn() => throwError())
    ->toThrow(Exception::class, 'Error message');
```

## Exemplos de Testes por Categoria

### Testes de Validação

```php
describe('createProxy', function () {
    it('valida nome obrigatório', function () {
        expect(fn() => $this->manager->createProxy('', 3000))
            ->toThrow(InvalidArgumentException::class, 'Name and port are required');
    });

    it('valida formato do nome', function () {
        expect(fn() => $this->manager->createProxy('Invalid_Name', 3000))
            ->toThrow(InvalidArgumentException::class);
    });

    it('valida range de porta', function () {
        expect(fn() => $this->manager->createProxy('test', 65536))
            ->toThrow(InvalidArgumentException::class, 'Port must be between 1024 and 65535');
    });
});
```

### Testes de Transformação

```php
describe('parseSitesList', function () {
    it('transforma output do herd em array estruturado', function () {
        $output = [
            '  | my-site |          | http://my-site.test | /path |',
        ];

        $result = $this->manager->parseSitesList($output, 'parked');

        expect($result)->toHaveCount(1);
        expect($result[0])->toMatchArray([
            'name' => 'my-site',
            'url' => 'http://my-site.test',
            'type' => 'parked',
        ]);
    });
});
```

### Testes de Geração

```php
describe('generateNginxConfig', function () {
    it('gera configuração nginx válida', function () {
        $site = [
            'name' => 'test',
            'url' => 'http://test.test',
            'port' => 8000,
        ];

        $config = $this->manager->generateNginxConfig($site);

        expect($config)->toContain('listen 0.0.0.0:8000');
        expect($config)->toContain('server_name _');
        expect($config)->toContain('proxy_pass http://127.0.0.1:80');
    });

    it('remove protocolo da URL', function () {
        $site = [
            'url' => 'https://test.test',
            'port' => 8000,
        ];

        $config = $this->manager->generateNginxConfig($site);

        expect($config)->not->toContain('https://');
        expect($config)->toContain('Host test.test');
    });
});
```

### Testes de Integração de Rede

```php
describe('checkPortAvailability', function () {
    it('detecta porta disponível', function () {
        $availablePort = 9999;

        expect($this->manager->checkPortAvailability($availablePort))
            ->toBeTrue();
    });

    it('detecta porta em uso', function () {
        $usedPort = 80;

        $result = $this->manager->checkPortAvailability($usedPort);

        expect($result)->toBeBool();
    });
});
```

## Comandos Úteis

### Executar testes específicos

```bash
# Por arquivo
./vendor/bin/pest tests/Unit/HerdManagerTest.php

# Por método (filtro)
./vendor/bin/pest --filter="generateNginxConfig"

# Por grupo
./vendor/bin/pest --group=validation
```

### Watch mode

```bash
./vendor/bin/pest --watch
```

### Cobertura de código

```bash
# Com relatório no terminal
./vendor/bin/pest --coverage

# Com relatório HTML
./vendor/bin/pest --coverage --coverage-html=coverage/html
```

### Modo verboso

```bash
./vendor/bin/pest --verbose
```

### Executar em paralelo

```bash
./vendor/bin/pest --parallel
```

## Boas Práticas

### 1. Um conceito por teste

❌ **Ruim:**
```php
it('testa tudo', function () {
    expect($this->manager->method1())->toBe('a');
    expect($this->manager->method2())->toBe('b');
    expect($this->manager->method3())->toBe('c');
});
```

✅ **Bom:**
```php
it('method1 retorna a', function () {
    expect($this->manager->method1())->toBe('a');
});

it('method2 retorna b', function () {
    expect($this->manager->method2())->toBe('b');
});
```

### 2. Nomes descritivos

❌ **Ruim:**
```php
it('testa validação', function () { ... });
```

✅ **Bom:**
```php
it('lança exceção quando nome está vazio', function () { ... });
it('lança exceção quando porta é inválida', function () { ... });
```

### 3. Arrange-Act-Assert

```php
it('calcula total corretamente', function () {
    // Arrange
    $items = [10, 20, 30];

    // Act
    $total = calculateTotal($items);

    // Assert
    expect($total)->toBe(60);
});
```

### 4. Dados de teste realistas

❌ **Ruim:**
```php
$site = ['url' => 'x', 'port' => 1];
```

✅ **Bom:**
```php
$site = [
    'name' => 'empresta-legal',
    'url' => 'http://empresta-legal.test',
    'port' => 8000,
];
```

## Dataset Testing (Pest v4)

Para testar com múltiplos inputs:

```php
it('valida formatos de nome válidos', function (string $name) {
    expect(fn() => $this->manager->createProxy($name, 3000))
        ->not->toThrow(InvalidArgumentException::class);
})->with([
    'lowercase',
    'with-hyphens',
    'with123numbers',
    'mix-123-test',
]);

it('valida formatos de nome inválidos', function (string $name) {
    expect(fn() => $this->manager->createProxy($name, 3000))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'UPPERCASE',
    'with_underscore',
    'with space',
    'special@char',
]);
```

## Mocking (quando necessário)

```php
it('chama método externo corretamente', function () {
    $mock = Mockery::mock(ExternalService::class);
    $mock->shouldReceive('process')
         ->once()
         ->with('input')
         ->andReturn('output');

    $result = processWithService($mock, 'input');

    expect($result)->toBe('output');
});
```

## Debugging

### Ver output durante teste

```php
it('debugs something', function () {
    $value = someFunction();

    dump($value);  // ou var_dump($value)

    expect($value)->toBe('expected');
});
```

### Pausar execução

```php
it('pauses for inspection', function () {
    $value = someFunction();

    ray($value);  // Se usar Laravel Ray

    expect($value)->toBe('expected');
});
```

## Próximos Passos

1. **Feature Tests**: Adicionar testes de integração completos
2. **Mocks**: Mockar filesystem e comandos externos
3. **Fixtures**: Criar fixtures de configuração nginx
4. **Coverage**: Atingir 80%+ de cobertura
5. **CI/CD**: Configurar GitHub Actions

## Recursos

- [Pest Documentation](https://pestphp.com/)
- [Pest Expectations](https://pestphp.com/docs/expectations)
- [Pest Datasets](https://pestphp.com/docs/datasets)
- [PHPUnit Assertions](https://phpunit.de/manual/current/en/assertions.html)

---

**Desenvolvido por:** Daniel
**Data:** 2025-12-02
