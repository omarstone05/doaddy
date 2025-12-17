# Penda Digital Domain Setup Guide

This guide will help you configure `penda.digital` and its subdomains for the SSO system.

## 🌐 Domain Structure

| Subdomain | Purpose | Application Path |
|-----------|---------|------------------|
| `penda.digital` | Main landing/marketing page | `/var/www/penda/public` |
| `auth.penda.digital` | SSO authentication service | `/var/www/penda-sso/public` |
| `account.penda.digital` | User account portal | `/var/www/penda-account/public` |
| `api.penda.digital` | API gateway (optional) | `/var/www/penda-api/public` |

---

## 📋 Step 1: DNS Configuration

### A. Get Your Server IP Address

First, identify your server's IP address:

```bash
# If you're on the server
curl ifconfig.me

# Or check your server details
# Based on your deployment, it's likely: 72.61.105.187
```

### B. Configure DNS Records

Go to your domain registrar (where you purchased `penda.digital`) and add these DNS records:

#### Required DNS Records

```
Type    Name                    Value              TTL
A       @                       72.61.105.187      3600
A       www                     72.61.105.187      3600
A       auth                    72.61.105.187      3600
A       account                 72.61.105.187      3600
A       api                     72.61.105.187      3600
```

**Note**: Replace `72.61.105.187` with your actual server IP address.

#### Alternative: CNAME Records (if using a subdomain)

If you want to use CNAME records instead:

```
Type    Name                    Value              TTL
A       @                       72.61.105.187      3600
CNAME   www                     penda.digital       3600
CNAME   auth                    penda.digital       3600
CNAME   account                 penda.digital       3600
CNAME   api                     penda.digital       3600
```

### C. Verify DNS Propagation

After adding DNS records, verify they're working:

```bash
# Check main domain
dig penda.digital +short

# Check subdomains
dig auth.penda.digital +short
dig account.penda.digital +short

# Or use online tools:
# - https://dnschecker.org
# - https://www.whatsmydns.net
```

**DNS propagation can take 5 minutes to 48 hours**, but usually completes within 1-2 hours.

---

## 📋 Step 2: Server Configuration

### A. Copy Nginx Configuration Files

SSH into your server and copy the Nginx configuration files:

```bash
ssh addy-production  # or your SSH alias

# Copy main domain config
sudo cp /var/www/addy/nginx-penda.conf /etc/nginx/sites-available/penda.digital

# Copy auth subdomain config
sudo cp /var/www/addy/nginx-penda-auth.conf /etc/nginx/sites-available/auth.penda.digital

# Copy account subdomain config
sudo cp /var/www/addy/nginx-penda-account.conf /etc/nginx/sites-available/account.penda.digital

# Enable the sites
sudo ln -s /etc/nginx/sites-available/penda.digital /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/auth.penda.digital /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/account.penda.digital /etc/nginx/sites-enabled/
```

### B. Test Nginx Configuration

```bash
# Test configuration syntax
sudo nginx -t

# If successful, reload Nginx
sudo systemctl reload nginx
```

### C. Create Directory Structure (Temporary)

Until the SSO applications are deployed, create placeholder directories:

```bash
# Create directories
sudo mkdir -p /var/www/penda/public
sudo mkdir -p /var/www/penda-sso/public
sudo mkdir -p /var/www/penda-account/public

# Create placeholder index files
echo "<h1>Penda Digital - Coming Soon</h1>" | sudo tee /var/www/penda/public/index.html
echo "<h1>Penda SSO - Coming Soon</h1>" | sudo tee /var/www/penda-sso/public/index.html
echo "<h1>Penda Account Portal - Coming Soon</h1>" | sudo tee /var/www/penda-account/public/index.html

# Set permissions
sudo chown -R www-data:www-data /var/www/penda*
sudo chmod -R 755 /var/www/penda*
```

---

## 📋 Step 3: SSL Certificate Setup

### A. Install Certbot (if not already installed)

```bash
sudo apt update
sudo apt install certbot python3-certbot-nginx -y
```

### B. Obtain SSL Certificates

**Option 1: Individual Certificates (Recommended for now)**

```bash
# Main domain
sudo certbot --nginx -d penda.digital -d www.penda.digital

# Auth subdomain
sudo certbot --nginx -d auth.penda.digital

# Account subdomain
sudo certbot --nginx -d account.penda.digital
```

**Option 2: Wildcard Certificate (More efficient, requires DNS challenge)**

```bash
# This requires adding a TXT record to DNS for verification
sudo certbot certonly --manual --preferred-challenges dns \
  -d "*.penda.digital" -d penda.digital
```

### C. Verify SSL Certificates

```bash
# Check certificate status
sudo certbot certificates

# Test auto-renewal
sudo certbot renew --dry-run
```

### D. Auto-Renewal Setup

Certbot automatically sets up a cron job, but verify it exists:

```bash
# Check if renewal cron job exists
sudo systemctl status certbot.timer

# Or check crontab
sudo crontab -l | grep certbot
```

---

## 📋 Step 4: Update Nginx Configs with SSL

After Certbot runs, it will automatically update your Nginx configs. However, you can manually verify:

```bash
# Check main domain config
sudo cat /etc/nginx/sites-available/penda.digital | grep ssl_certificate

# Should show:
# ssl_certificate /etc/letsencrypt/live/penda.digital/fullchain.pem;
# ssl_certificate_key /etc/letsencrypt/live/penda.digital/privkey.pem;
```

---

## 📋 Step 5: Firewall Configuration

Ensure your firewall allows HTTP (80) and HTTPS (443):

```bash
# If using UFW
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw status

# If using iptables
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
```

---

## 📋 Step 6: Test Everything

### A. Test HTTP to HTTPS Redirect

```bash
# Should redirect to HTTPS
curl -I http://penda.digital
curl -I http://auth.penda.digital
curl -I http://account.penda.digital
```

### B. Test HTTPS

```bash
# Test SSL connection
openssl s_client -connect penda.digital:443 -servername penda.digital

# Test in browser
# Visit: https://penda.digital
# Visit: https://auth.penda.digital
# Visit: https://account.penda.digital
```

### C. Test SSL Rating

Visit these sites to check your SSL configuration:
- https://www.ssllabs.com/ssltest/analyze.html?d=penda.digital
- https://securityheaders.com/?q=https://penda.digital

---

## 📋 Step 7: Environment Configuration

When you deploy the SSO applications, update their `.env` files:

### For SSO Service (auth.penda.digital)

```env
APP_NAME="Penda SSO"
APP_ENV=production
APP_URL=https://auth.penda.digital
APP_DEBUG=false

# OAuth Configuration
OAUTH_CLIENT_ID=your-client-id
OAUTH_CLIENT_SECRET=your-client-secret

# Allowed Redirect URIs
OAUTH_REDIRECT_URIS=https://doaddy.com/auth/sso/callback,https://projjo.com/auth/sso/callback
```

### For Account Portal (account.penda.digital)

```env
APP_NAME="Penda Account"
APP_ENV=production
APP_URL=https://account.penda.digital
APP_DEBUG=false

# SSO Service URL
PENDA_SSO_URL=https://auth.penda.digital
```

---

## 🔧 Troubleshooting

### DNS Not Resolving

```bash
# Check DNS records
dig penda.digital
nslookup penda.digital

# Clear local DNS cache (on your local machine)
# macOS:
sudo dscacheutil -flushcache
# Linux:
sudo systemd-resolve --flush-caches
```

### Nginx Not Starting

```bash
# Check Nginx error log
sudo tail -f /var/log/nginx/error.log

# Test configuration
sudo nginx -t

# Check if ports are in use
sudo netstat -tulpn | grep :80
sudo netstat -tulpn | grep :443
```

### SSL Certificate Issues

```bash
# Check certificate expiration
sudo certbot certificates

# Renew certificate manually
sudo certbot renew

# Check certificate files
sudo ls -la /etc/letsencrypt/live/penda.digital/
```

### 502 Bad Gateway

```bash
# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Check PHP-FPM error log
sudo tail -f /var/log/php8.4-fpm.log

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

---

## 📝 Quick Reference Commands

```bash
# Reload Nginx after config changes
sudo systemctl reload nginx

# Restart Nginx
sudo systemctl restart nginx

# Check Nginx status
sudo systemctl status nginx

# View Nginx access logs
sudo tail -f /var/log/nginx/penda-access.log

# View Nginx error logs
sudo tail -f /var/log/nginx/penda-error.log

# Test SSL certificate
sudo certbot certificates

# Renew SSL certificates
sudo certbot renew
```

---

## ✅ Verification Checklist

- [ ] DNS records added and propagated
- [ ] Nginx configuration files copied and enabled
- [ ] Nginx configuration tested (`nginx -t`)
- [ ] Nginx reloaded successfully
- [ ] SSL certificates obtained via Certbot
- [ ] HTTP redirects to HTTPS
- [ ] HTTPS works for all domains
- [ ] SSL rating is A or A+
- [ ] Firewall allows ports 80 and 443
- [ ] Auto-renewal for SSL certificates configured

---

**Last Updated**: December 16, 2024  
**Status**: Ready for DNS configuration


