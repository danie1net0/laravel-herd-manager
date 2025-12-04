# Manual de Configuração: Proxies para Sites Dinâmicos

Este manual explica como configurar proxies reversos no Laravel Herd para acessar servidores de desenvolvimento (React, Angular, Vue, Node.js, etc.) usando domínios `.test` em vez de `localhost:porta`.

## Índice

1. [Visão Geral](#visão-geral)
2. [Pré-requisitos](#pré-requisitos)
3. [Configuração Manual](#configuração-manual)
4. [Exemplos Práticos](#exemplos-práticos)
5. [Solução de Problemas](#solução-de-problemas)
6. [Boas Práticas](#boas-práticas)

---

## Visão Geral

### O que são Proxies para Sites Dinâmicos?

Frameworks JavaScript modernos (React, Vue, Angular) e servidores Node.js rodam seus próprios servidores de desenvolvimento em portas específicas (geralmente 3000, 4200, 8080, etc.). Por padrão, você acessa esses servidores via:

```
http://localhost:3000
http://localhost:4200
http://localhost:8080
```

Com proxies reversos no Herd, você pode acessar esses mesmos servidores usando domínios `.test` amigáveis:

```
http://meu-app-react.test
http://meu-projeto-angular.test
http://api-node.test
```

### Como Funciona?

O Herd usa nginx como servidor web. Quando você cria um proxy:

1. Um arquivo de configuração nginx é criado em `~/Library/Application Support/Herd/config/valet/Nginx/`
2. O nginx intercepta requisições para o domínio `.test`
3. As requisições são encaminhadas (proxy) para o servidor local na porta especificada
4. O servidor de desenvolvimento responde normalmente
5. O nginx retorna a resposta para o navegador

```
Navegador → suas.test:80 → Nginx → localhost:3000 → React Dev Server
```

---

## Pré-requisitos

- Laravel Herd instalado e funcionando
- Servidor de desenvolvimento rodando em uma porta específica
- Acesso ao terminal

---

## Configuração Manual

### Passo 1: Identificar a Porta do Servidor

Primeiro, descubra em qual porta seu servidor de desenvolvimento está rodando:

**React (Create React App / Vite):**
```bash
# Geralmente porta 3000 ou 5173
npm start
# ou
npm run dev
```

**Angular:**
```bash
# Geralmente porta 4200
ng serve
```

**Vue:**
```bash
# Geralmente porta 8080 ou 5173
npm run serve
# ou
npm run dev
```

**Node.js / Express:**
```bash
# Verifique no código qual porta está configurada
# Geralmente 3000, 3001, 8000, etc
node server.js
```

### Passo 2: Escolher um Nome de Domínio

Escolha um nome para seu domínio local. Regras:
- Apenas letras minúsculas, números e hífens
- Sem espaços ou caracteres especiais
- Será usado como `nome.test`

Exemplos:
- `meu-app` → `meu-app.test`
- `api-backend` → `api-backend.test`
- `dashboard` → `dashboard.test`

### Passo 3: Criar o Arquivo de Configuração

Crie um arquivo em `~/Library/Application Support/Herd/config/valet/Nginx/` com o nome `seu-dominio.test`:

```bash
# Exemplo para criar proxy "meu-app" na porta 3000
nano ~/Library/Application\ Support/Herd/config/valet/Nginx/meu-app.test
```

Cole a seguinte configuração:

```nginx
server {
    listen 127.0.0.1:80;
    server_name meu-app.test www.meu-app.test *.meu-app.test;
    charset utf-8;
    client_max_body_size 1024M;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }
}
```

**Personalize:**
- Substitua `meu-app.test` pelo seu domínio
- Substitua `3000` pela porta do seu servidor

### Passo 4: Reiniciar o Nginx

```bash
herd restart nginx
```

### Passo 5: Testar

Com seu servidor de desenvolvimento rodando, acesse no navegador:

```
http://meu-app.test
```

---

## Exemplos Práticos

### React (Create React App)

**Servidor rodando:**
```bash
cd ~/projetos/meu-app-react
npm start
# Server running on http://localhost:3000
```

**Configuração (`react-app.test`):**
```nginx
server {
    listen 127.0.0.1:80;
    server_name react-app.test www.react-app.test *.react-app.test;
    charset utf-8;
    client_max_body_size 1024M;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }
}
```

**Acessar:** `http://react-app.test`

### Angular

**Servidor rodando:**
```bash
cd ~/projetos/meu-app-angular
ng serve
# Server running on http://localhost:4200
```

**Configuração (`angular-app.test`):**
```nginx
server {
    listen 127.0.0.1:80;
    server_name angular-app.test www.angular-app.test *.angular-app.test;
    charset utf-8;
    client_max_body_size 1024M;

    location / {
        proxy_pass http://127.0.0.1:4200;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }
}
```

**Acessar:** `http://angular-app.test`

### Vue.js (Vite)

**Servidor rodando:**
```bash
cd ~/projetos/meu-app-vue
npm run dev
# Server running on http://localhost:5173
```

**Configuração (`vue-app.test`):**
```nginx
server {
    listen 127.0.0.1:80;
    server_name vue-app.test www.vue-app.test *.vue-app.test;
    charset utf-8;
    client_max_body_size 1024M;

    location / {
        proxy_pass http://127.0.0.1:5173;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }
}
```

**Acessar:** `http://vue-app.test`

### Node.js / Express API

**Servidor rodando:**
```bash
cd ~/projetos/minha-api
node server.js
# Server running on http://localhost:8000
```

**Configuração (`api.test`):**
```nginx
server {
    listen 127.0.0.1:80;
    server_name api.test www.api.test *.api.test;
    charset utf-8;
    client_max_body_size 1024M;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }
}
```

**Acessar:** `http://api.test`

### Next.js

**Servidor rodando:**
```bash
cd ~/projetos/meu-app-next
npm run dev
# Server running on http://localhost:3000
```

**Configuração (`nextjs-app.test`):**
```nginx
server {
    listen 127.0.0.1:80;
    server_name nextjs-app.test www.nextjs-app.test *.nextjs-app.test;
    charset utf-8;
    client_max_body_size 1024M;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }
}
```

**Acessar:** `http://nextjs-app.test`

---

## Solução de Problemas

### Problema: "404 Not Found" ou página do Herd

**Possíveis causas:**

1. **Servidor de desenvolvimento não está rodando**
   ```bash
   # Verifique se o servidor está ativo
   lsof -i :3000
   # Deve mostrar o processo rodando na porta
   ```

2. **Porta incorreta na configuração**
   ```bash
   # Verifique qual porta seu servidor está usando
   # E confirme que está igual no arquivo de configuração
   ```

3. **Nginx não foi reiniciado**
   ```bash
   herd restart nginx
   ```

4. **Erro no arquivo de configuração**
   ```bash
   # Teste a configuração do nginx
   nginx -t
   ```

### Problema: "502 Bad Gateway"

Significa que o nginx não consegue se conectar ao servidor de desenvolvimento.

**Soluções:**

1. Confirme que o servidor está rodando:
   ```bash
   lsof -i :3000
   ```

2. Verifique se a porta está correta no arquivo de configuração

3. Reinicie o servidor de desenvolvimento

### Problema: Hot Reload / HMR não funciona

Para alguns frameworks, você precisa configurar o HMR para aceitar requisições do domínio `.test`.

**React (Vite):**

Adicione no `vite.config.js`:
```javascript
export default {
  server: {
    hmr: {
      host: 'react-app.test',
    },
  },
}
```

**Next.js:**

O Next.js geralmente funciona sem configuração adicional.

**Angular:**

No `angular.json`, adicione:
```json
{
  "serve": {
    "options": {
      "host": "0.0.0.0",
      "disableHostCheck": true
    }
  }
}
```

### Problema: CORS errors

Se você estiver fazendo requisições de um domínio `.test` para outro, pode encontrar erros de CORS.

**Solução:** Configure CORS no seu servidor backend para aceitar requisições do domínio `.test`:

```javascript
// Node.js / Express
const cors = require('cors');
app.use(cors({
  origin: 'http://frontend.test'
}));
```

### Problema: WebSocket não conecta

Verifique se sua configuração inclui os headers de WebSocket:

```nginx
proxy_set_header Upgrade $http_upgrade;
proxy_set_header Connection 'upgrade';
```

Esses headers são essenciais para Hot Reload e WebSockets.

---

## Boas Práticas

### 1. Nomenclatura Consistente

Use nomes descritivos e consistentes:

```
✅ Bom:
- meu-app-react.test
- api-backend.test
- dashboard-admin.test

❌ Evite:
- app.test (muito genérico)
- teste123.test (não descritivo)
- MeuApp.test (use minúsculas)
```

### 2. Organize por Projeto

Para projetos com frontend e backend, use nomes relacionados:

```
projeto-frontend.test  → porta 3000 (React)
projeto-api.test       → porta 8000 (Node.js)
```

### 3. Documente suas Configurações

Mantenha uma lista dos proxies ativos:

```bash
# Liste arquivos de configuração
ls -la ~/Library/Application\ Support/Herd/config/valet/Nginx/
```

### 4. Remova Proxies Não Utilizados

Quando um projeto não estiver mais ativo:

```bash
# Remova o arquivo de configuração
rm ~/Library/Application\ Support/Herd/config/valet/Nginx/projeto-antigo.test

# Reinicie o nginx
herd restart nginx
```

### 5. Timeout para APIs Lentas

Se sua API demora muito para responder, aumente o timeout:

```nginx
location / {
    proxy_pass http://127.0.0.1:8000;
    proxy_read_timeout 300;  # 5 minutos
    # ... outros headers
}
```

### 6. Upload de Arquivos Grandes

Para permitir upload de arquivos grandes:

```nginx
server {
    listen 127.0.0.1:80;
    server_name upload-app.test;
    client_max_body_size 2048M;  # 2GB

    location / {
        proxy_pass http://127.0.0.1:3000;
        # ... outros headers
    }
}
```

### 7. Logs para Debugging

Para facilitar o debug, você pode adicionar logs:

```nginx
server {
    listen 127.0.0.1:80;
    server_name debug-app.test;

    access_log /Users/seu-usuario/logs/debug-app-access.log;
    error_log /Users/seu-usuario/logs/debug-app-error.log;

    location / {
        proxy_pass http://127.0.0.1:3000;
        # ... outros headers
    }
}
```

---

## Comandos Úteis

### Listar Proxies Ativos

```bash
ls -la ~/Library/Application\ Support/Herd/config/valet/Nginx/
```

### Verificar se uma Porta Está em Uso

```bash
lsof -i :3000
```

### Matar Processo em uma Porta

```bash
lsof -ti :3000 | xargs kill
```

### Testar Configuração do Nginx

```bash
nginx -t
```

### Verificar Status do Nginx

```bash
herd status
```

### Reiniciar Nginx

```bash
herd restart nginx
```

### Ver Logs do Nginx

```bash
tail -f ~/Library/Application\ Support/Herd/Log/nginx-error.log
```

---

## Arquitetura Técnica

### Por Que Isso Funciona?

O Herd gerencia o arquivo `/etc/hosts` e adiciona automaticamente entradas para `*.test`:

```
127.0.0.1 *.test
```

Isso faz com que todos os domínios `.test` apontem para o localhost.

O nginx do Herd inclui automaticamente configurações de:
```
~/Library/Application Support/Herd/config/valet/Nginx/*
```

Quando você acessa `meu-app.test`:
1. DNS resolve para 127.0.0.1
2. Nginx escuta na porta 80
3. Nginx verifica `server_name` e encontra sua configuração
4. Nginx faz proxy_pass para a porta do servidor
5. Servidor responde
6. Nginx retorna ao navegador

### Ordem de Inclusão dos Configs

O Herd carrega configurações nesta ordem:

1. Configs do Herd Pro (`/Applications/Herd.app/Contents/Resources/config/pro/nginx/*.conf`)
2. **Configs personalizados** (`~/Library/Application Support/Herd/config/valet/Nginx/*`)
3. Config principal do Herd (`herd.conf`)
4. Configs de sites isolados (`*-local.conf`)
5. Sites padrão

Por isso seus proxies têm prioridade sobre os sites PHP padrão do Herd.

---

## Automação com Script

Para facilitar a criação de proxies, você pode criar um script:

```bash
#!/bin/bash
# Salve como ~/bin/herd-proxy

NOME=$1
PORTA=$2

if [ -z "$NOME" ] || [ -z "$PORTA" ]; then
    echo "Uso: herd-proxy <nome> <porta>"
    echo "Exemplo: herd-proxy meu-app 3000"
    exit 1
fi

CONFIG_FILE="$HOME/Library/Application Support/Herd/config/valet/Nginx/$NOME.test"

cat > "$CONFIG_FILE" << EOF
server {
    listen 127.0.0.1:80;
    server_name $NOME.test www.$NOME.test *.$NOME.test;
    charset utf-8;
    client_max_body_size 1024M;

    location / {
        proxy_pass http://127.0.0.1:$PORTA;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
        proxy_read_timeout 86400;
    }
}
EOF

herd restart nginx

echo "✅ Proxy criado: http://$NOME.test -> localhost:$PORTA"
```

**Uso:**
```bash
chmod +x ~/bin/herd-proxy
herd-proxy meu-app 3000
```

---

## Conclusão

Proxies reversos no Herd permitem que você:

- Use domínios `.test` amigáveis para seus servidores de desenvolvimento
- Simule ambientes de produção localmente
- Teste integrações entre frontend e backend
- Trabalhe com múltiplos projetos simultaneamente
- Evite conflitos de porta

A configuração é simples e os benefícios são significativos para o fluxo de trabalho de desenvolvimento.

Para mais informações sobre o Laravel Herd, visite: https://herd.laravel.com
