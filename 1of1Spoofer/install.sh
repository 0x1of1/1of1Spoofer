#!/bin/bash

# 1of1Spoofer Installation Script
# This script automates the installation of 1of1Spoofer on a fresh Linux server

# Color definitions
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Print banner
echo -e "${BLUE}"
echo "╔════════════════════════════════════════╗"
echo "║ 1of1Spoofer - Email Spoofing Tool Install ║"
echo "╚════════════════════════════════════════╝"
echo -e "${NC}"

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run as root${NC}"
   exit 1
fi

# Confirm installation
echo -e "${YELLOW}This script will install 1of1Spoofer and its dependencies.${NC}"
echo "This includes: Apache, PHP, Composer, and required PHP extensions."
echo ""
read -p "Continue with installation? (y/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    echo "Installation cancelled."
    exit 1
fi

# Function to show progress
progress() {
    echo -e "${BLUE}[*] $1${NC}"
}

# Function to show success
success() {
    echo -e "${GREEN}[✓] $1${NC}"
}

# Function to show error
error() {
    echo -e "${RED}[✗] $1${NC}"
    exit 1
}

# Update system
progress "Updating system packages..."
apt-get update && apt-get upgrade -y || error "Failed to update system packages"
success "System packages updated"

# Install dependencies
progress "Installing dependencies..."
apt-get install -y apache2 php php-cli php-common php-mbstring php-gd php-intl php-xml php-mysql php-curl php-zip unzip git || error "Failed to install dependencies"
success "Dependencies installed"

# Enable Apache modules
progress "Configuring Apache..."
a2enmod rewrite headers
systemctl restart apache2
success "Apache configured"

# Install Composer
progress "Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer || error "Failed to install Composer"
success "Composer installed"

# Clone or download 1of1Spoofer
progress "Downloading 1of1Spoofer..."
cd /var/www/html
rm -rf index.html
git clone https://github.com/yourusername/1of1Spoofer.git . || error "Failed to download 1of1Spoofer"
success "1of1Spoofer downloaded"

# Set permissions
progress "Setting file permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
mkdir -p /var/www/html/logs
mkdir -p /var/www/html/uploads
chmod -R 777 /var/www/html/logs
chmod -R 777 /var/www/html/uploads
success "File permissions set"

# Install dependencies with Composer
progress "Installing PHP dependencies with Composer..."
composer install --no-dev || error "Failed to install PHP dependencies"
success "PHP dependencies installed"

# Copy sample configuration
progress "Setting up configuration..."
if [ ! -f /var/www/html/config.php ]; then
    cp /var/www/html/config.example.php /var/www/html/config.php
    success "Configuration file created"
else
    success "Configuration file already exists"
fi

# Configure Apache virtual host
progress "Configuring Apache virtual host..."
cat > /etc/apache2/sites-available/000-default.conf << 'EOF'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF
systemctl restart apache2
success "Apache virtual host configured"

# Restart Apache
progress "Restarting Apache..."
systemctl restart apache2 || error "Failed to restart Apache"
success "Apache restarted"

# Print success message
echo ""
echo -e "${GREEN}1of1Spoofer has been successfully installed!${NC}"
echo "You can access it at: http://$(hostname -I | awk '{print $1}')"
echo ""
echo -e "${YELLOW}Important:${NC}"
echo "1. Edit the configuration file at: /var/www/html/config.php"
echo "2. Make sure to secure your server and only use for authorized testing"
echo ""
echo "Thank you for installing 1of1Spoofer!" 