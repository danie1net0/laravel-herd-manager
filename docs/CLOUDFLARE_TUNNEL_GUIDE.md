# Cloudflare Tunnel - Complete Guide

This guide covers both authenticated and non-authenticated methods to create Cloudflare tunnels for exposing your local sites to the internet.

---

## 📋 Table of Contents

1. [Quick Tunnel (Non-Authenticated)](#quick-tunnel-non-authenticated)
2. [Named Tunnel (Authenticated)](#named-tunnel-authenticated)
3. [Configuration File](#configuration-file)
4. [Useful Commands](#useful-commands)
5. [Practical Examples](#practical-examples)
6. [Troubleshooting](#troubleshooting)

---

## 🚀 Quick Tunnel (Non-Authenticated)

The fastest way to expose a local site without any Cloudflare account setup.

### Advantages
- ✅ No login required
- ✅ No configuration needed
- ✅ Instant setup (1 command)
- ✅ Perfect for testing

### Disadvantages
- ❌ Random URL (changes every time)
- ❌ No uptime guarantee
- ❌ Not for production use
- ❌ Can be terminated by Cloudflare at any time

### Usage

**Basic syntax:**
```bash
cloudflared tunnel --url <local-url>
```

**Examples:**
```bash
# Expose localhost:3000
cloudflared tunnel --url http://localhost:3000

# Expose localhost:80
cloudflared tunnel --url http://localhost:80

# Expose Herd site (empresta-legal.test)
cloudflared tunnel --url http://empresta-legal.test

# Expose with specific port
cloudflared tunnel --url http://localhost:8000
```

**Output:**
```
+--------------------------------------------------------------------------------------------+
|  Your quick Tunnel has been created! Visit it at (it may take some time to be reachable): |
|  https://random-name-here.trycloudflare.com                                                |
+--------------------------------------------------------------------------------------------+
```

The tunnel will stay active until you press `Ctrl+C`.

---

## 🔐 Named Tunnel (Authenticated)

Create permanent tunnels with custom domains. Requires a Cloudflare account and domain.

### Advantages
- ✅ Custom subdomain (e.g., `api.yourdomain.com`)
- ✅ Permanent tunnel ID
- ✅ Production-ready
- ✅ Multiple routes support
- ✅ SSL/TLS included

### Disadvantages
- ❌ Requires Cloudflare account
- ❌ Need a domain in Cloudflare
- ❌ More setup steps

---

### Step 1: Login to Cloudflare

```bash
cloudflared tunnel login
```

This will:
1. Open your browser
2. Ask you to authorize access
3. Save certificate to `~/.cloudflared/cert.pem`

**Verify login:**
```bash
ls ~/.cloudflared/
# Should show: cert.pem
```

---

### Step 2: Create a Named Tunnel

```bash
cloudflared tunnel create <tunnel-name>
```

**Example:**
```bash
cloudflared tunnel create my-herd-sites
```

**Output:**
```
Tunnel credentials written to /Users/yourusername/.cloudflared/UUID.json
Created tunnel my-herd-sites with id UUID
```

**Verify tunnel creation:**
```bash
cloudflared tunnel list
```

---

### Step 3: Route DNS to Your Tunnel

You need a domain managed by Cloudflare for this step.

**📌 O que é Hostname?**

Hostname é o domínio ou subdomínio público que você quer usar para acessar seu site local através do túnel.

**Exemplos de hostname:**
- `app.seudominio.com` - para uma aplicação
- `api.seudominio.com` - para uma API
- `empresta.seudominio.com` - para o site empresta-legal
- `dev.seudominio.com` - para ambiente de desenvolvimento

**Fluxo:**
```
Internet → empresta.seudominio.com → Cloudflare Tunnel → http://empresta-legal.test (local)
```

**Requisitos:**
- ✅ Você precisa ter um domínio registrado (Namecheap, GoDaddy, etc)
- ✅ O domínio precisa estar configurado no Cloudflare (DNS nos nameservers do Cloudflare)
- ❌ Se não tiver domínio, use o "Túnel Rápido" que gera URL automática

---

```bash
cloudflared tunnel route dns <tunnel-name> <hostname>
```

**Examples:**
```bash
# Create subdomain: empresta.yourdomain.com
cloudflared tunnel route dns my-herd-sites empresta.yourdomain.com

# Create subdomain: app.yourdomain.com
cloudflared tunnel route dns my-herd-sites app.yourdomain.com
```

**Output:**
```
Created CNAME record for empresta.yourdomain.com which will route to tunnel my-herd-sites
```

---

### Step 4: Run the Tunnel

**Method A: Direct command (single service)**
```bash
cloudflared tunnel --url http://localhost:80 run <tunnel-name>
```

**Method B: With configuration file (multiple services)**

Create `~/.cloudflared/config.yml`:

```yaml
tunnel: my-herd-sites
credentials-file: /Users/yourusername/.cloudflared/UUID.json

ingress:
  # Route empresta.yourdomain.com to empresta-legal.test
  - hostname: empresta.yourdomain.com
    service: http://empresta-legal.test

  # Route api.yourdomain.com to localhost:8000
  - hostname: api.yourdomain.com
    service: http://localhost:8000

  # Route app.yourdomain.com to localhost:3000
  - hostname: app.yourdomain.com
    service: http://localhost:3000

  # Catch-all rule (required, must be last)
  - service: http_status:404
```

Then run:
```bash
cloudflared tunnel run my-herd-sites
```

---

### Step 5: Run as Background Service (Optional)

**macOS/Linux:**
```bash
# Install as service
sudo cloudflared service install

# Start service
sudo cloudflared service start

# Stop service
sudo cloudflared service stop

# Check status
sudo cloudflared service status
```

**Or use nohup:**
```bash
nohup cloudflared tunnel run my-herd-sites > /dev/null 2>&1 &
```

---

## ⚙️ Configuration File

Full example of `~/.cloudflared/config.yml`:

```yaml
# Tunnel ID (required)
tunnel: my-herd-sites

# Path to credentials file (required)
credentials-file: /Users/yourusername/.cloudflared/UUID.json

# Ingress rules (required)
ingress:
  # First site
  - hostname: site1.yourdomain.com
    service: http://site1.test
    originRequest:
      noTLSVerify: true

  # Second site with custom headers
  - hostname: site2.yourdomain.com
    service: http://localhost:8001
    originRequest:
      httpHostHeader: site2.test

  # WebSocket support
  - hostname: ws.yourdomain.com
    service: http://localhost:3000
    originRequest:
      noTLSVerify: true

  # Catch-all (required, must be last)
  - service: http_status:404
```

**Advanced options:**

```yaml
ingress:
  - hostname: myapp.yourdomain.com
    service: http://localhost:3000
    originRequest:
      # Don't verify TLS (for self-signed certs)
      noTLSVerify: true

      # Connection timeout
      connectTimeout: 30s

      # Keep alive
      keepAliveConnections: 100

      # Custom HTTP Host header
      httpHostHeader: myapp.test

      # Disable chunked encoding
      disableChunkedEncoding: false
```

---

## 🛠 Useful Commands

### Tunnel Management

```bash
# List all tunnels
cloudflared tunnel list

# Get tunnel details
cloudflared tunnel info <tunnel-name>

# Delete a tunnel
cloudflared tunnel delete <tunnel-name>

# Clean up inactive tunnels
cloudflared tunnel cleanup <tunnel-name>
```

### DNS Management

```bash
# List DNS routes (not available directly, check Cloudflare dashboard)
# Visit: https://dash.cloudflare.com/

# Delete DNS route (do it from dashboard or via API)
```

### Service Management

```bash
# Install service (macOS/Linux)
sudo cloudflared service install

# Uninstall service
sudo cloudflared service uninstall

# Start/Stop/Restart
sudo cloudflared service start
sudo cloudflared service stop
sudo cloudflared service restart
```

### Logs and Debugging

```bash
# Run with verbose logging
cloudflared tunnel --loglevel debug run <tunnel-name>

# Check service logs (macOS)
tail -f /Library/Logs/cloudflared.log

# Check if tunnel is running
ps aux | grep cloudflared
```

---

## 💡 Practical Examples

### Example 1: Expose Single Herd Site (Quick)

```bash
# No auth required - instant testing
cloudflared tunnel --url http://empresta-legal.test
```

Access at: `https://random-name.trycloudflare.com`

---

### Example 2: Expose Multiple Herd Sites (Permanent)

**1. Login:**
```bash
cloudflared tunnel login
```

**2. Create tunnel:**
```bash
cloudflared tunnel create herd-sites
```

**3. Setup DNS:**
```bash
cloudflared tunnel route dns herd-sites empresta.yourdomain.com
cloudflared tunnel route dns herd-sites api.yourdomain.com
```

**4. Create config file (`~/.cloudflared/config.yml`):**
```yaml
tunnel: herd-sites
credentials-file: /Users/daniel/.cloudflared/UUID.json

ingress:
  - hostname: empresta.yourdomain.com
    service: http://empresta-legal.test
  - hostname: api.yourdomain.com
    service: http://api-site.test
  - service: http_status:404
```

**5. Run tunnel:**
```bash
cloudflared tunnel run herd-sites
```

Access at:
- `https://empresta.yourdomain.com`
- `https://api.yourdomain.com`

---

### Example 3: Expose with Local Network Port

If you exposed a site on network port 8000 (from Herd Manager):

```bash
# Quick tunnel
cloudflared tunnel --url http://localhost:8000

# Named tunnel
cloudflared tunnel route dns my-tunnel public.yourdomain.com
```

Config:
```yaml
ingress:
  - hostname: public.yourdomain.com
    service: http://localhost:8000
  - service: http_status:404
```

---

### Example 4: Run Multiple Tunnels

You can run multiple tunnels simultaneously:

**Terminal 1:**
```bash
cloudflared tunnel --url http://site1.test
```

**Terminal 2:**
```bash
cloudflared tunnel --url http://site2.test
```

Or with one named tunnel and multiple routes (recommended).

---

## 🔧 Troubleshooting

### Issue: "Cannot determine default origin certificate path"

**Solution:**
```bash
# Login again
cloudflared tunnel login

# Verify cert exists
ls ~/.cloudflared/cert.pem
```

---

### Issue: "Tunnel already exists"

**Solution:**
```bash
# List existing tunnels
cloudflared tunnel list

# Delete old tunnel
cloudflared tunnel delete <tunnel-name>

# Or use existing tunnel
cloudflared tunnel run <existing-tunnel-name>
```

---

### Issue: "Connection refused" or "502 Bad Gateway"

**Causes:**
- Local service not running
- Wrong port number
- Service only listening on 127.0.0.1

**Solutions:**
```bash
# Check if service is running
curl http://localhost:80
curl http://empresta-legal.test

# Verify Herd is running
herd status

# Check nginx logs
tail -f ~/Library/Application\ Support/Herd/logs/nginx-error.log
```

---

### Issue: DNS not propagating

**Solution:**
```bash
# Wait a few minutes (can take up to 5 minutes)

# Check DNS
dig empresta.yourdomain.com

# Verify in Cloudflare dashboard:
# https://dash.cloudflare.com/ -> Your Domain -> DNS Records
```

---

### Issue: "Tunnel credentials not found"

**Solution:**
```bash
# List tunnels to get UUID
cloudflared tunnel list

# Check credentials file exists
ls ~/.cloudflared/*.json

# Update config.yml with correct path
credentials-file: /Users/yourusername/.cloudflared/UUID.json
```

---

### Issue: Kill stuck tunnel process

```bash
# Find process
ps aux | grep cloudflared

# Kill by PID
kill -9 <PID>

# Or kill all
pkill -9 cloudflared
```

---

## 📚 Additional Resources

- **Official Docs:** https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/
- **Dashboard:** https://dash.cloudflare.com/
- **GitHub:** https://github.com/cloudflare/cloudflared
- **Community:** https://community.cloudflare.com/

---

## 🎯 Quick Reference

| Task | Command |
|------|---------|
| Quick tunnel | `cloudflared tunnel --url http://localhost:80` |
| Login | `cloudflared tunnel login` |
| Create tunnel | `cloudflared tunnel create <name>` |
| List tunnels | `cloudflared tunnel list` |
| Route DNS | `cloudflared tunnel route dns <name> <hostname>` |
| Run tunnel | `cloudflared tunnel run <name>` |
| Delete tunnel | `cloudflared tunnel delete <name>` |
| Install service | `sudo cloudflared service install` |
| Check version | `cloudflared --version` |

---

**Created:** December 2025
**Author:** Herd Network Manager Project
