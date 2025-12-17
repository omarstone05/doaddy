# Penda Digital Domain - Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Configure DNS

Go to your domain registrar and add these A records:

```
Type: A
Name: @
Value: YOUR_SERVER_IP

Type: A  
Name: www
Value: YOUR_SERVER_IP

Type: A
Name: auth
Value: YOUR_SERVER_IP

Type: A
Name: account
Value: YOUR_SERVER_IP
```

**To find your server IP:**
```bash
ssh addy-production
curl ifconfig.me
```

### Step 2: Run Setup Script on Server

```bash
ssh addy-production
cd /var/www/addy
sudo bash setup-penda-domain.sh
```

This script will:
- ✅ Copy Nginx configuration files
- ✅ Create placeholder directories
- ✅ Enable sites
- ✅ Test Nginx configuration
- ✅ Check/install Certbot
- ✅ Display DNS instructions

### Step 3: Wait for DNS Propagation

Check if DNS has propagated:

```bash
dig penda.digital +short
dig auth.penda.digital +short
```

**Usually takes 1-2 hours**, but can be as fast as 5 minutes.

### Step 4: Get SSL Certificates

Once DNS is working, get SSL certificates:

```bash
# Main domain
sudo certbot --nginx -d penda.digital -d www.penda.digital

# Auth subdomain
sudo certbot --nginx -d auth.penda.digital

# Account subdomain
sudo certbot --nginx -d account.penda.digital
```

### Step 5: Verify Everything Works

```bash
# Test in browser:
# - https://penda.digital
# - https://auth.penda.digital
# - https://account.penda.digital

# Or test with curl:
curl -I https://penda.digital
curl -I https://auth.penda.digital
curl -I https://account.penda.digital
```

---

## 📋 What Gets Created

| Domain | Purpose | Path |
|--------|---------|------|
| `penda.digital` | Main site | `/var/www/penda/public` |
| `auth.penda.digital` | SSO service | `/var/www/penda-sso/public` |
| `account.penda.digital` | Account portal | `/var/www/penda-account/public` |

---

## 🔧 Troubleshooting

### DNS Not Working?

```bash
# Check DNS propagation
dig penda.digital
nslookup penda.digital

# Clear local DNS cache (macOS)
sudo dscacheutil -flushcache
```

### Nginx Errors?

```bash
# Check Nginx status
sudo systemctl status nginx

# Test configuration
sudo nginx -t

# View error logs
sudo tail -f /var/log/nginx/error.log
```

### SSL Certificate Issues?

```bash
# Check certificates
sudo certbot certificates

# Renew manually
sudo certbot renew

# Test auto-renewal
sudo certbot renew --dry-run
```

---

## 📞 Need Help?

See the full guide: `PENDA_DOMAIN_SETUP.md`

---

**Last Updated**: December 16, 2024


