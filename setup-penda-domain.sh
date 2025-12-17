#!/bin/bash

# Penda Digital Domain Setup Script
# This script helps set up penda.digital and subdomains on your server

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  Penda Digital Domain Setup${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}❌ Please run as root or with sudo${NC}"
    exit 1
fi

# Get server IP
SERVER_IP=$(curl -s ifconfig.me)
echo -e "${YELLOW}📍 Detected server IP: ${SERVER_IP}${NC}"
echo ""

# Step 1: Copy Nginx configs
echo -e "${YELLOW}📋 Step 1: Setting up Nginx configurations...${NC}"

if [ ! -f "/var/www/addy/nginx-penda.conf" ]; then
    echo -e "${RED}❌ nginx-penda.conf not found in /var/www/addy/${NC}"
    echo "Please ensure you've pulled the latest code."
    exit 1
fi

# Copy configs
cp /var/www/addy/nginx-penda.conf /etc/nginx/sites-available/penda.digital
cp /var/www/addy/nginx-penda-auth.conf /etc/nginx/sites-available/auth.penda.digital
cp /var/www/addy/nginx-penda-account.conf /etc/nginx/sites-available/account.penda.digital

echo -e "${GREEN}✅ Configuration files copied${NC}"

# Enable sites
if [ ! -L "/etc/nginx/sites-enabled/penda.digital" ]; then
    ln -s /etc/nginx/sites-available/penda.digital /etc/nginx/sites-enabled/
fi

if [ ! -L "/etc/nginx/sites-enabled/auth.penda.digital" ]; then
    ln -s /etc/nginx/sites-available/auth.penda.digital /etc/nginx/sites-enabled/
fi

if [ ! -L "/etc/nginx/sites-enabled/account.penda.digital" ]; then
    ln -s /etc/nginx/sites-available/account.penda.digital /etc/nginx/sites-enabled/
fi

echo -e "${GREEN}✅ Sites enabled${NC}"

# Step 2: Create placeholder directories
echo ""
echo -e "${YELLOW}📋 Step 2: Creating placeholder directories...${NC}"

mkdir -p /var/www/penda/public
mkdir -p /var/www/penda-sso/public
mkdir -p /var/www/penda-account/public

# Create placeholder files
cat > /var/www/penda/public/index.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Penda Digital</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Penda Digital</h1>
    <p>Coming Soon</p>
</body>
</html>
EOF

cat > /var/www/penda-sso/public/index.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Penda SSO</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Penda SSO</h1>
    <p>Authentication Service - Coming Soon</p>
</body>
</html>
EOF

cat > /var/www/penda-account/public/index.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Penda Account</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>Penda Account Portal</h1>
    <p>User Profile & Subscription Management - Coming Soon</p>
</body>
</html>
EOF

# Set permissions
chown -R www-data:www-data /var/www/penda*
chmod -R 755 /var/www/penda*

echo -e "${GREEN}✅ Directories and placeholder files created${NC}"

# Step 3: Test Nginx configuration
echo ""
echo -e "${YELLOW}📋 Step 3: Testing Nginx configuration...${NC}"

if nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    systemctl reload nginx
    echo -e "${GREEN}✅ Nginx reloaded${NC}"
else
    echo -e "${RED}❌ Nginx configuration test failed${NC}"
    exit 1
fi

# Step 4: Check Certbot installation
echo ""
echo -e "${YELLOW}📋 Step 4: Checking Certbot installation...${NC}"

if ! command -v certbot &> /dev/null; then
    echo -e "${YELLOW}⚠️  Certbot not found. Installing...${NC}"
    apt update
    apt install -y certbot python3-certbot-nginx
    echo -e "${GREEN}✅ Certbot installed${NC}"
else
    echo -e "${GREEN}✅ Certbot is installed${NC}"
fi

# Step 5: Display DNS instructions
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  DNS Configuration Required${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}Add these DNS records at your domain registrar:${NC}"
echo ""
echo -e "${GREEN}Type    Name      Value${NC}"
echo "A       @         ${SERVER_IP}"
echo "A       www       ${SERVER_IP}"
echo "A       auth      ${SERVER_IP}"
echo "A       account   ${SERVER_IP}"
echo ""
echo -e "${YELLOW}After DNS propagates (usually 1-2 hours), run:${NC}"
echo ""
echo "sudo certbot --nginx -d penda.digital -d www.penda.digital"
echo "sudo certbot --nginx -d auth.penda.digital"
echo "sudo certbot --nginx -d account.penda.digital"
echo ""

# Step 6: Check firewall
echo ""
echo -e "${YELLOW}📋 Step 5: Checking firewall...${NC}"

if command -v ufw &> /dev/null; then
    if ufw status | grep -q "Status: active"; then
        echo -e "${YELLOW}⚠️  UFW is active. Ensuring ports 80 and 443 are open...${NC}"
        ufw allow 80/tcp
        ufw allow 443/tcp
        echo -e "${GREEN}✅ Firewall rules updated${NC}"
    else
        echo -e "${GREEN}✅ UFW is not active${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  UFW not found. Please ensure ports 80 and 443 are open in your firewall${NC}"
fi

# Summary
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Setup Complete!${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Configure DNS records (see above)"
echo "2. Wait for DNS propagation (check with: dig penda.digital)"
echo "3. Run Certbot commands to get SSL certificates"
echo "4. Test domains in browser"
echo ""
echo -e "${YELLOW}Test DNS propagation:${NC}"
echo "dig penda.digital +short"
echo "dig auth.penda.digital +short"
echo "dig account.penda.digital +short"
echo ""
echo -e "${YELLOW}View setup guide:${NC}"
echo "cat /var/www/addy/PENDA_DOMAIN_SETUP.md"
echo ""


