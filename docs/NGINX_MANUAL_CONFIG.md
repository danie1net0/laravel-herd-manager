# 🐘 Configuração Manual do Nginx no Laravel Herd

Este tutorial explica como expor manualmente seus sites do Laravel Herd na rede local, editando as configurações do Nginx diretamente.

## 📋 Índice

1. [Entendendo a Estrutura](#entendendo-a-estrutura)
2. [Passo a Passo](#passo-a-passo)
3. [Exemplo Completo](#exemplo-completo)
4. [Verificação e Testes](#verificação-e-testes)
5. [Troubleshooting](#troubleshooting)

---

## Entendendo a Estrutura

### Locais Importantes

```bash
# Diretório de configurações do Herd
~/Library/Application Support/Herd/config/nginx/

# Arquivo principal do Nginx
~/Library/Application Support/Herd/config/nginx/nginx.conf

# Seus arquivos de configuração personalizados
~/Library/Application Support/Herd/config/nginx/*-local.conf
```

### Como o Herd Funciona

O Laravel Herd usa Nginx para servir seus sites localmente. Por padrão:

- Sites são acessíveis apenas em `127.0.0.1` (localhost)
- Porta padrão: `80` para HTTP
- Domínios: `*.test` (exemplo: `meu-projeto.test`)

Para expor na rede local, você precisa:

1. Criar um servidor Nginx adicional que escuta em `0.0.0.0` (todas as interfaces)
2. Configurar um proxy reverso que encaminha para o servidor local do Herd
3. Incluir essa configuração no arquivo principal do Nginx

---

## Passo a Passo

### 1. Obter Informações Necessárias

Primeiro, identifique os sites disponíveis:

```bash
# Ver sites "parked" (baseados em diretórios)
herd parked

# Ver sites "linked" (links simbólicos)
herd links
```

Exemplo de saída:
```
┌──────────────────┬──────────┬─────────────────────────────┬──────────────────────┐
│ Site             │ SSL      │ URL                         │ Path                 │
├──────────────────┼──────────┼─────────────────────────────┼──────────────────────┤
│ meu-projeto      │ secured  │ https://meu-projeto.test    │ ~/Herd/meu-projeto   │
└──────────────────┴──────────┴─────────────────────────────┴──────────────────────┘
```

### 2. Obter seu IP Local

```bash
# Para interface Wi-Fi (en0)
ipconfig getifaddr en0

# Para interface Ethernet (en1)
ipconfig getifaddr en1
```

Exemplo de resultado: `192.168.0.13`

### 3. Escolher uma Porta Disponível

Escolha uma porta entre `8000-9999` que não esteja em uso:

```bash
# Verificar se a porta está disponível
lsof -i :8000
```

Se não houver saída, a porta está livre.

### 4. Criar Arquivo de Configuração

Navegue até o diretório de configurações:

```bash
cd ~/Library/Application\ Support/Herd/config/nginx/
```

Crie um arquivo de configuração com o padrão `nome-do-site-local.conf`:

```bash
# Exemplo para o site "meu-projeto"
nano meu-projeto-local.conf
```

### 5. Adicionar Configuração do Servidor

Cole a seguinte configuração no arquivo:

```nginx
server {
    listen 0.0.0.0:8000;
    server_name _;

    location / {
        proxy_set_header Host meu-projeto.test;
        proxy_set_header X-Forwarded-Host $http_host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_pass http://127.0.0.1:80;
    }
}
```

**Substitua:**
- `8000` pela porta que você escolheu
- `meu-projeto.test` pelo domínio do seu site no Herd

Salve o arquivo (`Ctrl+O`, `Enter`, `Ctrl+X` no nano).

### 6. Incluir no Arquivo Principal

Edite o arquivo principal do Nginx:

```bash
nano ~/Library/Application\ Support/Herd/config/nginx/nginx.conf
```

Procure pela linha que contém `include herd.conf;` (geralmente dentro do bloco `http {}`):

```nginx
http {
    # ... outras configurações ...

    include herd.conf;
    include meu-projeto-local.conf;  # Adicione esta linha

    # ... resto das configurações ...
}
```

Salve o arquivo.

### 7. Testar a Configuração

Antes de aplicar, verifique se não há erros de sintaxe:

```bash
# Testar configuração do Nginx
nginx -t -c ~/Library/Application\ Support/Herd/config/nginx/nginx.conf
```

Se houver erros, corrija-os antes de prosseguir.

### 8. Reiniciar o Nginx

```bash
# Reiniciar o Nginx do Herd
herd restart nginx
```

Aguarde alguns segundos para o Nginx reiniciar completamente.

---

## Exemplo Completo

### Cenário

Você quer expor 3 sites na rede local:

1. `meu-blog.test` → Porta `8000`
2. `api-laravel.test` → Porta `8001`
3. `dashboard.test` → Porta `8002`

### Arquivos a Criar

**1. `meu-blog-local.conf`**
```nginx
server {
    listen 0.0.0.0:8000;
    server_name _;

    location / {
        proxy_set_header Host meu-blog.test;
        proxy_set_header X-Forwarded-Host $http_host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_pass http://127.0.0.1:80;
    }
}
```

**2. `api-laravel-local.conf`**
```nginx
server {
    listen 0.0.0.0:8001;
    server_name _;

    location / {
        proxy_set_header Host api-laravel.test;
        proxy_set_header X-Forwarded-Host $http_host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_pass http://127.0.0.1:80;
    }
}
```

**3. `dashboard-local.conf`**
```nginx
server {
    listen 0.0.0.0:8002;
    server_name _;

    location / {
        proxy_set_header Host dashboard.test;
        proxy_set_header X-Forwarded-Host $http_host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_pass http://127.0.0.1:80;
    }
}
```

### Arquivo `nginx.conf` Modificado

```nginx
http {
    # ... configurações anteriores ...

    include herd.conf;
    include meu-blog-local.conf;
    include api-laravel-local.conf;
    include dashboard-local.conf;

    # ... resto das configurações ...
}
```

### Comandos para Executar

```bash
# 1. Navegar até o diretório
cd ~/Library/Application\ Support/Herd/config/nginx/

# 2. Criar os arquivos
nano meu-blog-local.conf    # Cole a configuração e salve
nano api-laravel-local.conf # Cole a configuração e salve
nano dashboard-local.conf   # Cole a configuração e salve

# 3. Editar nginx.conf
nano nginx.conf             # Adicione as linhas include

# 4. Testar
nginx -t -c ~/Library/Application\ Support/Herd/config/nginx/nginx.conf

# 5. Reiniciar
herd restart nginx
```

---

## Verificação e Testes

### 1. Verificar se as Portas Estão Escutando

```bash
# Listar todas as portas que o Nginx está escutando
lsof -i -P | grep nginx
```

Você deve ver algo como:
```
nginx   12345  user   6u  IPv4  0x...  TCP *:8000 (LISTEN)
nginx   12345  user   7u  IPv4  0x...  TCP *:8001 (LISTEN)
nginx   12345  user   8u  IPv4  0x...  TCP *:8002 (LISTEN)
```

### 2. Testar Localmente

```bash
# Testar se a porta responde
curl -I http://127.0.0.1:8000

# Ou use o navegador
open http://127.0.0.1:8000
```

### 3. Testar na Rede Local

De outro dispositivo na mesma rede (celular, tablet, outro computador):

```
http://192.168.0.13:8000  # Seu IP local + porta
```

### 4. Verificar Logs em Caso de Erro

```bash
# Ver logs do Nginx
tail -f ~/Library/Application\ Support/Herd/logs/nginx_error.log

# Ver logs de acesso
tail -f ~/Library/Application\ Support/Herd/logs/nginx_access.log
```

---

## Troubleshooting

### Problema: "Connection Refused"

**Causa:** A porta não está aberta ou o Nginx não reiniciou corretamente.

**Solução:**
```bash
# 1. Verificar se o Nginx está rodando
herd status

# 2. Reiniciar novamente
herd restart nginx

# 3. Verificar se não há erro de sintaxe
nginx -t -c ~/Library/Application\ Support/Herd/config/nginx/nginx.conf
```

### Problema: "404 Not Found"

**Causa:** O `proxy_set_header Host` está apontando para o domínio errado.

**Solução:**
- Verifique se o valor em `proxy_set_header Host` corresponde exatamente ao domínio do seu site no Herd
- Liste seus sites: `herd parked` e `herd links`

### Problema: Porta em Uso

**Causa:** Outra aplicação está usando a porta.

**Solução:**
```bash
# 1. Descobrir qual processo está usando a porta
lsof -i :8000

# 2. Matar o processo (se necessário)
kill -9 [PID]

# 3. Ou escolher outra porta
```

### Problema: Firewall Bloqueando

**Causa:** O firewall do macOS está bloqueando conexões externas.

**Solução:**
```bash
# 1. Abrir Preferências do Sistema > Segurança > Firewall

# 2. Permitir conexões de entrada para nginx

# Ou via terminal (requer sudo)
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --add /usr/local/bin/nginx
sudo /usr/libexec/ApplicationFirewall/socketfilterfw --unblock /usr/local/bin/nginx
```

### Problema: Site Não Carrega CSS/JS

**Causa:** Headers de proxy não estão configurados corretamente.

**Solução:**
- Verifique se todos os `proxy_set_header` estão presentes
- Certifique-se de que não há erros de digitação

### Problema: Configuração Não Persiste

**Causa:** O Herd pode sobrescrever `nginx.conf` em atualizações.

**Solução:**
- Sempre mantenha backup dos seus arquivos `-local.conf`
- Após atualizar o Herd, verifique se as linhas `include` ainda estão em `nginx.conf`

---

## Desabilitando uma Configuração

### Método 1: Remover Include

Edite `nginx.conf` e comente ou remova a linha:

```nginx
# include meu-projeto-local.conf;  # Comentado
```

### Método 2: Deletar o Arquivo

```bash
rm ~/Library/Application\ Support/Herd/config/nginx/meu-projeto-local.conf
```

Depois, remova a linha `include` do `nginx.conf`.

### Reiniciar

```bash
herd restart nginx
```

---

## Dicas de Segurança

### 1. Limitar Acesso por IP

Se você quer permitir apenas alguns IPs:

```nginx
server {
    listen 0.0.0.0:8000;
    server_name _;

    # Permitir apenas IPs específicos
    allow 192.168.0.0/24;  # Toda a rede local
    allow 10.0.0.5;        # IP específico
    deny all;              # Negar todos os outros

    location / {
        proxy_set_header Host meu-projeto.test;
        proxy_set_header X-Forwarded-Host $http_host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_pass http://127.0.0.1:80;
    }
}
```

### 2. Autenticação Básica

Para adicionar senha:

```bash
# 1. Instalar htpasswd (se não tiver)
brew install httpd

# 2. Criar arquivo de senhas
htpasswd -c ~/.htpasswd usuario
```

```nginx
server {
    listen 0.0.0.0:8000;
    server_name _;

    auth_basic "Área Restrita";
    auth_basic_user_file /Users/seu-usuario/.htpasswd;

    location / {
        proxy_set_header Host meu-projeto.test;
        proxy_set_header X-Forwarded-Host $http_host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_pass http://127.0.0.1:80;
    }
}
```

---

## Automatização com Scripts

### Script para Criar Configuração

Crie um arquivo `create-network-site.sh`:

```bash
#!/bin/bash

SITE_NAME=$1
PORT=$2
DOMAIN="${SITE_NAME}.test"
CONFIG_DIR="$HOME/Library/Application Support/Herd/config/nginx"
CONFIG_FILE="${CONFIG_DIR}/${SITE_NAME}-local.conf"

if [ -z "$SITE_NAME" ] || [ -z "$PORT" ]; then
    echo "Uso: $0 <nome-do-site> <porta>"
    echo "Exemplo: $0 meu-projeto 8000"
    exit 1
fi

cat > "$CONFIG_FILE" <<EOF
server {
    listen 0.0.0.0:${PORT};
    server_name _;

    location / {
        proxy_set_header Host ${DOMAIN};
        proxy_set_header X-Forwarded-Host \$http_host;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Port \$server_port;
        proxy_pass http://127.0.0.1:80;
    }
}
EOF

echo "Configuração criada em: $CONFIG_FILE"
echo ""
echo "Agora adicione ao nginx.conf:"
echo "    include ${SITE_NAME}-local.conf;"
echo ""
echo "E reinicie o nginx:"
echo "    herd restart nginx"
```

Tornar executável e usar:

```bash
chmod +x create-network-site.sh
./create-network-site.sh meu-projeto 8000
```

---

## Conclusão

Agora você sabe como:

✅ Criar configurações manuais do Nginx
✅ Expor sites na rede local
✅ Testar e verificar configurações
✅ Solucionar problemas comuns
✅ Adicionar segurança básica

O Herd Manager automatiza todo esse processo, mas entender os fundamentos te permite personalizar e debugar quando necessário!
