#!/bin/bash

# SSH Setup Script for Production Server
# Server IP: 72.61.105.187

set -e

SERVER_IP="72.61.105.187"
SSH_KEY_PATH="$HOME/.ssh/addy_production"
SSH_CONFIG_PATH="$HOME/.ssh/config"

echo "🔐 Setting up SSH access for production server..."

# Check if key exists
if [ ! -f "$SSH_KEY_PATH" ]; then
    echo "📝 Generating new SSH key pair..."
    ssh-keygen -t ed25519 -C "addy-production-$(date +%Y%m%d)" -f "$SSH_KEY_PATH" -N ""
    echo "✅ SSH key generated successfully!"
else
    echo "✅ SSH key already exists at $SSH_KEY_PATH"
fi

# Display public key
echo ""
echo "📋 Your public key:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
cat "$SSH_KEY_PATH.pub"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Add to SSH config
echo "⚙️  Configuring SSH config..."
if [ ! -f "$SSH_CONFIG_PATH" ]; then
    touch "$SSH_CONFIG_PATH"
    chmod 600 "$SSH_CONFIG_PATH"
fi

# Check if entry already exists
if ! grep -q "Host addy-production" "$SSH_CONFIG_PATH" 2>/dev/null; then
    cat >> "$SSH_CONFIG_PATH" << EOF

# Addy Production Server
Host addy-production
    HostName $SERVER_IP
    User root
    IdentityFile $SSH_KEY_PATH
    StrictHostKeyChecking no
    UserKnownHostsFile /dev/null
EOF
    echo "✅ SSH config updated!"
else
    echo "✅ SSH config entry already exists"
fi

echo ""
echo "📤 Next steps:"
echo "1. Copy your public key to the server:"
echo "   ssh-copy-id -i $SSH_KEY_PATH.pub root@$SERVER_IP"
echo ""
echo "   OR manually add it to the server:"
echo "   ssh root@$SERVER_IP 'mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys' < $SSH_KEY_PATH.pub"
echo ""
echo "2. Test the connection:"
echo "   ssh addy-production"
echo ""
echo "3. If you need to specify a different user, edit ~/.ssh/config"
echo ""

