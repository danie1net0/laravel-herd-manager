# Cloudflare Tunnel: Como Expor Seu Ambiente Local Para a Internet (Guia Completo)

## Resumo da ópera

Você precisa expor seu ambiente de desenvolvimento local para:
- Testar sua API em um dispositivo móvel real
- Receber webhooks de APIs externas (Stripe, PagSeguro, etc.)
- Demonstrar uma feature para um cliente antes do deploy
- Compartilhar seu trabalho com a equipe sem subir para staging

Este guia mostra duas abordagens: **túnel rápido** (30 segundos, zero configuração) e **túnel nomeado** (produção, com domínio customizado).

**Vantagens sobre ngrok e similares:**
- ✅ Sem limite de requisições (free tier)
- ✅ HTTPS automático
- ✅ Suporte a múltiplos sites simultâneos
- ✅ Edge global da Cloudflare
- ✅ Proteção DDoS nativa

---

## O Problema

Você está desenvolvendo localmente. Seu Laravel está rodando perfeitamente em `http://meusite.test` ou `http://localhost:8000`.

Aí surge um dos seguintes cenários:

1. **Mobile precisa testar a API**: O desenvolvedor mobile está no mesmo escritório, mas a rede WiFi é segmentada e ele não consegue acessar seu IP local.

2. **Webhook de produção**: Você está integrando com a API do Stripe e precisa receber o webhook de confirmação de pagamento. A API externa não consegue chamar `localhost`.

3. **Demonstração para cliente**: O cliente quer ver a feature funcionando antes de você fazer deploy para staging.

4. **Testes em dispositivos reais**: Você precisa testar notificações push ou comportamento específico de iOS/Android.

### Soluções tradicionais (e seus problemas)

**Port forwarding no roteador**
- Não funciona em redes corporativas
- Requer acesso administrativo ao roteador
- Expõe seu IP público
- Configuração manual de firewall

**Servidor temporário (VPS, cloud)**
- Custo (mesmo que mínimo)
- Tempo de setup (15-30 minutos)
- Deploy manual a cada mudança
- Gerenciamento de infraestrutura

**ngrok**
- Limite de 40 requisições/minuto (free tier)
- URL customizada apenas no plano pago
- Sessões limitadas

**localtunnel**
- Instabilidade frequente
- Túneis caem sem aviso
- Não recomendado para demonstrações

---

## A Solução: Cloudflare Tunnel

Cloudflare Tunnel cria uma conexão persistente e segura entre sua máquina local e a edge network da Cloudflare.

### Como funciona

```
┌──────────────┐        ┌─────────────────────┐        ┌──────────────┐
│   Internet   │───────▶│  Cloudflare Edge    │───────▶│ Sua Máquina  │
│              │        │  (servidor público) │        │ (localhost)  │
└──────────────┘        └─────────────────────┘        └──────────────┘
                                  ▲                            │
                                  │                            │
                                  └────── Túnel persistente ───┘
```

**Fluxo de uma requisição:**

1. Usuário acessa `https://api.seudominio.com`
2. DNS resolve para o edge server da Cloudflare
3. Cloudflare identifica o túnel associado
4. Requisição é encaminhada via túnel para sua máquina
5. Seu `localhost:8000` processa e responde
6. Cloudflare retorna a resposta ao usuário

**Principais vantagens técnicas:**

| Característica | Cloudflare Tunnel | ngrok (free) | localtunnel |
|----------------|-------------------|--------------|-------------|
| Limite de requisições | ❌ Sem limite | ⚠️ 40/min | ❌ Sem limite |
| URL customizada | ✅ Com DNS próprio | ❌ Apenas pago | ⚠️ Aleatória |
| HTTPS | ✅ Automático | ✅ Automático | ✅ Automático |
| Múltiplos sites | ✅ Sim | ❌ Não | ❌ Não |
| Estabilidade | ✅ Alta | ✅ Alta | ⚠️ Baixa |
| WebSocket | ✅ Suportado | ✅ Suportado | ✅ Suportado |
| Autenticação | ⚠️ Opcional | ✅ Obrigatória | ❌ Não requer |

---

## Abordagem 1: Túnel Rápido (Quick Tunnel)

Ideal para testes rápidos, debugging de webhooks e demonstrações temporárias.

### Características

- **Tempo de setup:** 30 segundos
- **Autenticação:** Não requerida
- **URL:** Aleatória (ex: `https://random-slug-abc.trycloudflare.com`)
- **Persistência:** Enquanto o processo estiver rodando
- **Caso de uso:** Testes, webhooks, demos temporárias

### Passo a passo

#### 1. Instalar cloudflared

```bash
# macOS (Homebrew)
brew install cloudflared

# Linux (Debian/Ubuntu)
wget https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared-linux-amd64.deb

# Verificar instalação
cloudflared --version
```

#### 2. Iniciar o túnel

```bash
# Sintaxe básica
cloudflared tunnel --url <URL_LOCAL>

# Exemplos práticos

# Expor site do Laravel Herd
cloudflared tunnel --url http://meusite.test

# Expor localhost em porta específica
cloudflared tunnel --url http://localhost:8000

# Expor API Node.js
cloudflared tunnel --url http://localhost:3000
```

#### 3. Obter a URL pública

O terminal exibirá:

```
2025-12-02T10:30:45Z INF Starting tunnel
2025-12-02T10:30:46Z INF +--------------------------------------------------------------------------------------------+
2025-12-02T10:30:46Z INF |  Your quick Tunnel has been created! Visit it at (it may take some time to be reachable): |
2025-12-02T10:30:46Z INF |  https://purple-rain-4d7f.trycloudflare.com                                                |
2025-12-02T10:30:46Z INF +--------------------------------------------------------------------------------------------+
```

Copie a URL gerada e compartilhe.

### Exemplo real: Webhook do Stripe

Cenário: Você está implementando pagamentos com Stripe e precisa receber o webhook de confirmação.

**Código da rota (Laravel):**

```php
// routes/web.php
Route::post('/webhook/stripe', function (Request $request) {
    Log::info('Stripe webhook received', [
        'payload' => $request->all()
    ]);

    // Processar evento
    return response()->json(['status' => 'received']);
});
```

**Expor o ambiente local:**

```bash
cloudflared tunnel --url http://meusite.test
```

**Configurar webhook no Stripe:**

1. Acesse o Dashboard do Stripe
2. Vá em Developers → Webhooks
3. Cole a URL: `https://purple-rain-4d7f.trycloudflare.com/webhook/stripe`
4. Selecione os eventos que deseja receber

**Testar:**

1. Faça um pagamento de teste no Stripe
2. O webhook será enviado para sua máquina local
3. Verifique os logs: `tail -f storage/logs/laravel.log`

Veja que você conseguiu testar a integração completa sem fazer deploy.

### Finalizando o túnel

Para parar o túnel, simplesmente pressione `Ctrl+C` no terminal.

A URL pública para de funcionar imediatamente.

---

## Abordagem 2: Túnel Nomeado (Named Tunnel)

Ideal para ambientes de desenvolvimento compartilhados e demonstrações permanentes.

### Características

- **Tempo de setup:** 5 minutos (primeira vez)
- **Autenticação:** Conta Cloudflare obrigatória
- **URL:** Customizada (ex: `https://api.seudominio.com`)
- **Persistência:** Permanente (pode rodar como serviço)
- **Caso de uso:** Ambiente de dev compartilhado, staging pessoal

### Requisitos

✅ Conta Cloudflare (gratuita)
✅ Domínio configurado no Cloudflare DNS

**Nota:** Se seu domínio está em outro provedor (GoDaddy, Registro.br), você só precisa apontar os nameservers para a Cloudflare.

### Passo a passo completo

#### Passo 1: Autenticar no Cloudflare

```bash
cloudflared tunnel login
```

**O que acontece:**
1. Seu navegador abre automaticamente
2. Você seleciona o domínio que deseja usar
3. Autoriza o acesso
4. Um certificado é salvo em `~/.cloudflared/cert.pem`

**Verificar autenticação:**

```bash
ls -la ~/.cloudflared/cert.pem
```

Se o arquivo existe, você está autenticado.

#### Passo 2: Criar um túnel nomeado

```bash
cloudflared tunnel create meu-ambiente-dev
```

**Saída esperada:**

```
Tunnel credentials written to /Users/daniel/.cloudflared/a1b2c3d4-uuid.json
Created tunnel meu-ambiente-dev with id a1b2c3d4-e5f6-7890-abcd-ef1234567890
```

Veja que dois arquivos importantes foram criados:
- **Credentials file:** Contém as credenciais do túnel (UUID único)
- **Tunnel ID:** Identificador do seu túnel

**Listar túneis existentes:**

```bash
cloudflared tunnel list
```

#### Passo 3: Configurar rota DNS

Agora você precisa dizer ao Cloudflare qual domínio apontará para este túnel.

```bash
cloudflared tunnel route dns meu-ambiente-dev api.seudominio.com
```

**O que esse comando faz:**
1. Cria um registro CNAME no DNS do Cloudflare
2. Aponta `api.seudominio.com` para o túnel `meu-ambiente-dev`
3. Configura automaticamente SSL/TLS

**Verificar propagação DNS:**

```bash
dig api.seudominio.com +short
```

Você deve ver IPs da Cloudflare (ex: `172.67.x.x` ou `104.21.x.x`).

#### Passo 4: Criar arquivo de configuração

O arquivo `config.yml` define quais hostnames apontam para quais serviços locais.

**Criar o arquivo:**

```bash
nano ~/.cloudflared/config.yml
```

**Configuração básica (um site):**

```yaml
tunnel: meu-ambiente-dev
credentials-file: /Users/daniel/.cloudflared/a1b2c3d4-uuid.json

ingress:
  - hostname: api.seudominio.com
    service: http://localhost:8000
  - service: http_status:404
```

**Explicação de cada campo:**

| Campo | Descrição |
|-------|-----------|
| `tunnel` | Nome do túnel criado no Passo 2 |
| `credentials-file` | Caminho completo para o arquivo JSON das credenciais |
| `ingress` | Lista de regras de roteamento |
| `hostname` | Domínio público que você quer expor |
| `service` | URL local que será acessada |
| `http_status:404` | Regra catch-all obrigatória (deve ser a última) |

**Configuração avançada (múltiplos sites):**

```yaml
tunnel: meu-ambiente-dev
credentials-file: /Users/daniel/.cloudflared/a1b2c3d4-uuid.json

ingress:
  # API principal
  - hostname: api.seudominio.com
    service: http://localhost:8000
    originRequest:
      noTLSVerify: true

  # Frontend em Vue.js
  - hostname: app.seudominio.com
    service: http://localhost:3000

  # Admin Laravel
  - hostname: admin.seudominio.com
    service: http://admin.test
    originRequest:
      httpHostHeader: admin.test

  # WebSocket para chat real-time
  - hostname: ws.seudominio.com
    service: http://localhost:6001
    originRequest:
      noTLSVerify: true

  # Catch-all obrigatório
  - service: http_status:404
```

**Opções avançadas de `originRequest`:**

| Opção | Descrição | Caso de uso |
|-------|-----------|-------------|
| `noTLSVerify` | Desabilita verificação SSL | Certificados auto-assinados locais |
| `httpHostHeader` | Define header Host customizado | Sites do Laravel Herd |
| `connectTimeout` | Timeout de conexão (padrão: 30s) | Aplicações lentas |
| `keepAliveConnections` | Conexões persistentes | Alta performance |
| `disableChunkedEncoding` | Desabilita chunked encoding | Compatibilidade com proxies |

#### Passo 5: Rodar o túnel

```bash
cloudflared tunnel run meu-ambiente-dev
```

**Saída esperada:**

```
2025-12-02T10:45:30Z INF Starting tunnel tunnelID=a1b2c3d4-uuid
2025-12-02T10:45:30Z INF Version 2025.11.1
2025-12-02T10:45:32Z INF Registered tunnel connection connIndex=0 location=GRU
2025-12-02T10:45:33Z INF Registered tunnel connection connIndex=1 location=GRU
2025-12-02T10:45:34Z INF Registered tunnel connection connIndex=2 location=GRU
2025-12-02T10:45:35Z INF Registered tunnel connection connIndex=3 location=SJP
```

Veja que o túnel estabeleceu 4 conexões com diferentes edge servers da Cloudflare.

Agora você pode acessar `https://api.seudominio.com` e será redirecionado para seu `localhost:8000`.

#### Passo 6 (Opcional): Rodar como serviço em background

**Opção A: Usando nohup (temporário)**

```bash
nohup cloudflared tunnel run meu-ambiente-dev > /tmp/tunnel.log 2>&1 &
```

**Opção B: Instalar como serviço do sistema (permanente)**

```bash
# Instalar serviço
sudo cloudflared service install

# Iniciar serviço
sudo cloudflared service start

# Verificar status
sudo cloudflared service status

# Parar serviço
sudo cloudflared service stop

# Reiniciar serviço
sudo cloudflared service restart
```

**Vantagens de rodar como serviço:**
- Inicia automaticamente ao ligar o computador
- Logs centralizados
- Gerenciamento via systemd/launchd
- Não depende de sessão de terminal

---

## Exemplo Prático Completo: Múltiplos Ambientes

Vamos configurar um ambiente de desenvolvimento completo com 3 serviços:

1. **API Laravel** (backend)
2. **SPA Vue.js** (frontend)
3. **Admin Laravel Nova** (painel administrativo)

### Estrutura local

```
- API Laravel rodando em: http://localhost:8000
- Vue.js Dev Server em: http://localhost:3000
- Admin Laravel em: http://admin.test (Laravel Herd)
```

### Configuração do túnel

**1. Criar rotas DNS:**

```bash
cloudflared tunnel route dns prod-env api.meuapp.com
cloudflared tunnel route dns prod-env app.meuapp.com
cloudflared tunnel route dns prod-env admin.meuapp.com
```

**2. Arquivo `~/.cloudflared/config.yml`:**

```yaml
tunnel: prod-env
credentials-file: /Users/daniel/.cloudflared/abc123-uuid.json

ingress:
  # API Backend (Laravel)
  - hostname: api.meuapp.com
    service: http://localhost:8000
    originRequest:
      connectTimeout: 60s
      noTLSVerify: true

  # SPA Frontend (Vue.js)
  - hostname: app.meuapp.com
    service: http://localhost:3000
    originRequest:
      disableChunkedEncoding: true

  # Admin Panel (Laravel Nova no Herd)
  - hostname: admin.meuapp.com
    service: http://admin.test
    originRequest:
      httpHostHeader: admin.test
      noTLSVerify: true

  - service: http_status:404
```

**3. Rodar o túnel:**

```bash
cloudflared tunnel run prod-env
```

**4. Testar cada endpoint:**

```bash
# API
curl https://api.meuapp.com/api/health

# Frontend
curl -I https://app.meuapp.com

# Admin
curl -I https://admin.meuapp.com/nova
```

Veja que agora você tem três domínios públicos apontando para três serviços locais diferentes, todos gerenciados por um único túnel.

---

## Troubleshooting: Problemas Comuns

### Erro 1: "DNS não resolve"

**Sintoma:**

```
$ curl https://api.seudominio.com
curl: (6) Could not resolve host: api.seudominio.com
```

**Causa:** Você criou o túnel mas esqueceu de configurar a rota DNS.

**Solução:**

```bash
# Configurar rota DNS
cloudflared tunnel route dns meu-tunel api.seudominio.com

# Aguardar propagação (1-2 minutos)
# Verificar DNS
dig api.seudominio.com +short
```

**Saída esperada do dig:**

```
172.67.158.22
104.21.8.227
```

Se você vê esses IPs da Cloudflare, o DNS está correto.

---

### Erro 2: "502 Bad Gateway"

**Sintoma:**

```
502 Bad Gateway
cloudflare
```

**Causa:** O túnel está funcionando, mas seu serviço local não está rodando ou não está acessível.

**Diagnóstico:**

```bash
# 1. Verificar se o serviço local responde
curl http://localhost:8000

# 2. Se for Laravel Herd, verificar status
herd status

# 3. Verificar se a porta está correta
lsof -i :8000

# 4. Checar logs do nginx (se usar Herd)
tail -f ~/Library/Application\ Support/Herd/logs/nginx-error.log
```

**Solução:**

Certifique-se que seu serviço local está rodando antes de tentar acessar via túnel.

```bash
# Iniciar servidor Laravel
php artisan serve --port=8000

# Ou usar Herd
herd start
```

---

### Erro 3: "Tunnel credentials not found"

**Sintoma:**

```
Error: cannot open tunnel credentials file: no such file or directory
```

**Causa:** O caminho do `credentials-file` no `config.yml` está incorreto.

**Solução:**

```bash
# 1. Listar túneis para obter o UUID correto
cloudflared tunnel list

# Exemplo de saída:
# ID                                   NAME        CREATED
# a1b2c3d4-e5f6-7890-abcd-ef1234567890 meu-tunel   2025-12-02

# 2. Verificar se o arquivo de credenciais existe
ls ~/.cloudflared/*.json

# 3. Atualizar config.yml com o caminho correto
nano ~/.cloudflared/config.yml

# Corrigir linha:
credentials-file: /Users/daniel/.cloudflared/a1b2c3d4-e5f6-7890-abcd-ef1234567890.json
```

---

### Erro 4: "Connection refused" ao rodar túnel

**Sintoma:**

```
Error: cannot establish connection to edge: connection refused
```

**Causa:** Problemas de rede ou firewall bloqueando a conexão com a Cloudflare.

**Diagnóstico:**

```bash
# Testar conectividade com edge da Cloudflare
curl -I https://cloudflare.com

# Verificar se não há proxy ou VPN interferindo
env | grep -i proxy
```

**Solução:**

1. Desabilitar VPN temporariamente
2. Verificar firewall corporativo
3. Tentar em outra rede

---

### Erro 5: Túnel rodando mas nada acontece

**Checklist de diagnóstico:**

- [ ] O arquivo `~/.cloudflared/config.yml` existe?
- [ ] O nome do túnel no `config.yml` está correto?
- [ ] As rotas DNS foram criadas?
- [ ] O serviço local está acessível?
- [ ] O caminho do `credentials-file` está correto?

**Comando de diagnóstico completo:**

```bash
# 1. Verificar túneis criados
cloudflared tunnel list

# 2. Ver detalhes do túnel
cloudflared tunnel info meu-tunel

# 3. Testar configuração
cloudflared tunnel ingress validate

# 4. Verificar qual regra casa com um hostname
cloudflared tunnel ingress rule https://api.seudominio.com
```

---

## Gerenciamento de Túneis

### Comandos úteis

```bash
# Listar todos os túneis
cloudflared tunnel list

# Ver informações detalhadas de um túnel
cloudflared tunnel info meu-tunel

# Validar arquivo de configuração
cloudflared tunnel ingress validate

# Testar regra de ingress
cloudflared tunnel ingress rule https://api.seudominio.com

# Deletar túnel
cloudflared tunnel delete meu-tunel

# Limpar túneis inativos
cloudflared tunnel cleanup meu-tunel
```

### Gerenciar processos

```bash
# Ver túneis rodando
ps aux | grep cloudflared

# Parar túnel específico
pkill -f "cloudflared tunnel run meu-tunel"

# Parar todos os túneis
pkill cloudflared

# Forçar parada
pkill -9 cloudflared
```

### Logs e debugging

```bash
# Rodar com logs verbosos
cloudflared tunnel --loglevel debug run meu-tunel

# Ver logs do serviço (macOS)
tail -f /Library/Logs/cloudflared.log

# Ver logs do serviço (Linux)
journalctl -u cloudflared -f

# Salvar logs em arquivo
cloudflared tunnel run meu-tunel 2>&1 | tee tunnel.log
```

---

## Boas Práticas

### 1. Segurança

**Nunca exponha informações sensíveis:**

```yaml
# ❌ Ruim: Expor ambiente de desenvolvimento com dados reais
ingress:
  - hostname: api.seudominio.com
    service: http://localhost:8000  # Database com dados de produção

# ✅ Bom: Usar apenas em ambiente isolado
ingress:
  - hostname: api-dev.seudominio.com
    service: http://localhost:8000  # Database de desenvolvimento
```

**Use variáveis de ambiente específicas:**

```bash
# .env.tunnel
APP_ENV=tunnel
APP_DEBUG=false
DB_DATABASE=development
```

### 2. Organização

**Nomeie túneis de forma descritiva:**

```bash
# ❌ Ruim
cloudflared tunnel create tunnel1

# ✅ Bom
cloudflared tunnel create projeto-api-dev
cloudflared tunnel create staging-pessoal
cloudflared tunnel create demo-cliente-xpto
```

**Mantenha configurações versionadas:**

```bash
# Criar repositório para configs
mkdir ~/tunnel-configs
cd ~/tunnel-configs
git init

# Salvar configs (sem credenciais)
cp ~/.cloudflared/config.yml ./config.yml.example

# .gitignore
echo "*.json" >> .gitignore
echo "cert.pem" >> .gitignore
```

### 3. Performance

**Configure timeouts apropriados:**

```yaml
ingress:
  - hostname: api-lenta.com
    service: http://localhost:8000
    originRequest:
      connectTimeout: 120s      # Para operações longas
      keepAliveConnections: 100 # Reusar conexões
```

**Use compression quando possível:**

```yaml
ingress:
  - hostname: api.com
    service: http://localhost:8000
    originRequest:
      disableChunkedEncoding: false  # Permite streaming
```

---

## Assistente de Linha de Comando

Para facilitar o gerenciamento, criei um script interativo que automatiza todas as operações.

### Funcionalidades

- Túnel rápido (sem autenticação)
- Login no Cloudflare
- Criar túnel nomeado
- Rotear DNS automaticamente
- Gerar `config.yml` interativamente
- Iniciar/parar túneis em background
- Verificar status de todos os túneis
- Deletar túneis

### Instalação

```bash
# 1. Baixar o script
curl -o tunnel https://raw.githubusercontent.com/seuuser/scripts/main/cloudflare-tunnel-assistant.sh

# 2. Tornar executável
chmod +x tunnel

# 3. Mover para PATH global
sudo mv tunnel /usr/local/bin/

# 4. Executar
tunnel
```

### Interface

```
╔════════════════════════════════════════════════╗
║     Assistente de Túneis Cloudflare           ║
╚════════════════════════════════════════════════╝

✓ cloudflared encontrado (versão 2025.11.1)

Escolha uma opção:

  1) Túnel Rápido (sem autenticação)
  2) Login no Cloudflare
  3) Listar túneis
  4) Criar túnel nomeado
  5) Rotear DNS
  6) Criar arquivo de configuração
  7) Executar túnel (foreground)
  8) Iniciar túnel (background)
  9) Parar túnel
 10) Status dos túneis
 11) Deletar túnel
  0) Sair

Opção:
```

---

## Como Remover Completamente

Se você quiser desfazer tudo e voltar ao estado inicial:

### 1. Parar todos os túneis

```bash
# Processos em foreground (Ctrl+C no terminal)

# Processos em background
pkill cloudflared

# Serviço instalado
sudo cloudflared service stop
sudo cloudflared service uninstall
```

### 2. Deletar túneis

```bash
# Listar túneis existentes
cloudflared tunnel list

# Deletar cada túnel
cloudflared tunnel delete meu-tunel
cloudflared tunnel delete outro-tunel
```

### 3. Remover configurações DNS

**Opção A: Via dashboard**
1. Acesse https://dash.cloudflare.com
2. Selecione seu domínio
3. Vá em DNS
4. Delete os registros CNAME criados

**Opção B: Via CLI** (não há comando direto, use o dashboard)

### 4. Limpar arquivos locais

```bash
# Remover configurações
rm ~/.cloudflared/config.yml

# Remover credenciais
rm ~/.cloudflared/*.json

# Remover certificado
rm ~/.cloudflared/cert.pem

# Opcional: Remover diretório completo
rm -rf ~/.cloudflared
```

### 5. Desinstalar cloudflared

```bash
# macOS
brew uninstall cloudflared

# Linux (Debian/Ubuntu)
sudo dpkg -r cloudflared

# Verificar remoção
which cloudflared  # Não deve retornar nada
```

---

## Checklist de Implementação

### Túnel Rápido (Quick)

- [ ] Instalar cloudflared
- [ ] Verificar instalação: `cloudflared --version`
- [ ] Rodar túnel: `cloudflared tunnel --url http://localhost:3000`
- [ ] Copiar URL gerada
- [ ] Testar em navegador/dispositivo móvel
- [ ] Parar túnel: `Ctrl+C`

### Túnel Nomeado (Production)

- [ ] Criar conta Cloudflare (se não tiver)
- [ ] Configurar domínio no Cloudflare DNS
- [ ] Fazer login: `cloudflared tunnel login`
- [ ] Criar túnel: `cloudflared tunnel create nome-do-tunel`
- [ ] Anotar UUID gerado
- [ ] Configurar DNS: `cloudflared tunnel route dns nome-do-tunel subdominio.com`
- [ ] Criar `~/.cloudflared/config.yml`
- [ ] Validar config: `cloudflared tunnel ingress validate`
- [ ] Testar regra: `cloudflared tunnel ingress rule https://subdominio.com`
- [ ] Rodar túnel: `cloudflared tunnel run nome-do-tunel`
- [ ] Testar acesso público: `curl -I https://subdominio.com`
- [ ] (Opcional) Instalar como serviço: `sudo cloudflared service install`

### Troubleshooting

- [ ] DNS não resolve? → Verificar com `dig subdominio.com`
- [ ] 502 Bad Gateway? → Testar serviço local: `curl http://localhost:porta`
- [ ] Credentials not found? → Verificar caminho no `config.yml`
- [ ] Túnel não conecta? → Verificar firewall/proxy
- [ ] Serviço não inicia? → Checar logs: `journalctl -u cloudflared`

---

## Conclusão

Cloudflare Tunnel resolve de forma elegante um problema recorrente no desenvolvimento: expor ambientes locais de forma segura e rápida.

**Você aprendeu:**
- Como funciona a arquitetura de túneis
- Duas abordagens (rápida e permanente)
- Configuração de múltiplos serviços
- Troubleshooting de problemas comuns
- Boas práticas de segurança e organização

**Próximos passos:**

1. Escolha a abordagem adequada para seu caso de uso
2. Implemente seguindo o checklist
3. Documente as URLs e configurações da sua equipe
4. Considere automatizar com o assistente de linha de comando

**Refatorações e melhorias são sempre bem-vindas.** Se encontrou algum erro ou tem sugestões, abra uma issue ou pull request.

---

**Desenvolvido por:** Daniel
**Data:** 2025-12-02

**Recursos adicionais:**
- [Documentação oficial Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/)
- [Repositório cloudflared no GitHub](https://github.com/cloudflare/cloudflared)
- [Cloudflare Dashboard](https://dash.cloudflare.com/)
