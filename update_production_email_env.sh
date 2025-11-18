#!/bin/bash
# Script to update email configuration on production server

echo "Updating email configuration on production server..."

ssh addy-production << 'EOF'
cd /var/www/addy

# Backup current .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update email settings
sed -i 's/^MAIL_MAILER=.*/MAIL_MAILER=smtp/' .env
sed -i 's/^MAIL_HOST=.*/MAIL_HOST=penda.digital/' .env
sed -i 's/^MAIL_PORT=.*/MAIL_PORT=465/' .env
sed -i 's/^MAIL_USERNAME=.*/MAIL_USERNAME=info@penda.digital/' .env
sed -i 's/^MAIL_PASSWORD=.*/MAIL_PASSWORD=3fAbv5#56g5/' .env
sed -i 's/^MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=ssl/' .env
sed -i 's/^MAIL_FROM_ADDRESS=.*/MAIL_FROM_ADDRESS=info@penda.digital/' .env
sed -i 's/^MAIL_FROM_NAME=.*/MAIL_FROM_NAME="Addy Business"/' .env

# Add if they don't exist
grep -q "^MAIL_MAILER=" .env || echo "MAIL_MAILER=smtp" >> .env
grep -q "^MAIL_HOST=" .env || echo "MAIL_HOST=penda.digital" >> .env
grep -q "^MAIL_PORT=" .env || echo "MAIL_PORT=465" >> .env
grep -q "^MAIL_USERNAME=" .env || echo "MAIL_USERNAME=info@penda.digital" >> .env
grep -q "^MAIL_PASSWORD=" .env || echo "MAIL_PASSWORD=3fAbv5#56g5" >> .env
grep -q "^MAIL_ENCRYPTION=" .env || echo "MAIL_ENCRYPTION=ssl" >> .env
grep -q "^MAIL_FROM_ADDRESS=" .env || echo "MAIL_FROM_ADDRESS=info@penda.digital" >> .env
grep -q "^MAIL_FROM_NAME=" .env || echo "MAIL_FROM_NAME=\"Addy Business\"" >> .env

echo "Email configuration updated!"
echo "Clearing config cache..."
php artisan config:clear
php artisan cache:clear

echo "Done!"
EOF

echo "Production email configuration updated successfully!"

