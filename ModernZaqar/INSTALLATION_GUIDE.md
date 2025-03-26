# 1of1Spoofer: Installation & Usage Guide

This document provides detailed instructions for setting up and using the 1of1Spoofer email spoofing tool on a Linux VPS server.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Setup](#server-setup)
3. [Software Installation](#software-installation)
4. [1of1Spoofer Installation](#1of1spoofer-installation)
5. [Configuration](#configuration)
6. [Usage](#usage)
7. [Security Considerations](#security-considerations)
8. [Troubleshooting](#troubleshooting)
9. [Maintenance](#maintenance)

## Prerequisites

- A VPS running Ubuntu 20.04+ or Debian 11+
- Root access to the server
- A domain name (optional but recommended)

## Server Setup

### Update System

First, update your system:

```bash
apt update
apt upgrade -y
```

### Set Hostname

Set a hostname for your server:

```bash
hostnamectl set-hostname spoofer-server
echo "127.0.0.1 spoofer-server" >> /etc/hosts
```

### Secure SSH (Optional but Recommended)

Edit SSH configuration:

```bash
nano /etc/ssh/sshd_config
```

Make the following changes:

- Change default port: `Port 2222`
- Disable root login: `PermitRootLogin no`
- Allow only key-based authentication: `PasswordAuthentication no`

Restart SSH:

```bash
systemctl restart sshd
```

## Software Installation

### Install Required Packages

```bash
apt install -y apache2 php php-cli php-fpm php-json php-common php-mysql php-zip php-gd php-mbstring php-curl php-xml php-pear php-bcmath git unzip
```

### Configure Apache

Enable required modules:

```bash
a2enmod rewrite headers
systemctl restart apache2
```

### Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

## 1of1Spoofer Installation

### Create Web Directory

```bash
mkdir -p /var/www/html/1of1spoofer
```

### Deploy 1of1Spoofer

Clone the repository:

```bash
git clone https://github.com/yourusername/1of1Spoofer.git /var/www/html/1of1spoofer
cd /var/www/html/1of1spoofer
```

Install dependencies:

```bash
composer install --no-dev
```

Set proper permissions:

```bash
chmod -R 755 /var/www/html/1of1spoofer
chmod -R 777 /var/www/html/1of1spoofer/logs
chmod -R 777 /var/www/html/1of1spoofer/uploads
chown -R www-data:www-data /var/www/html/1of1spoofer
```

### Configure Apache Virtual Host

Create a new virtual host:

```bash
nano /etc/apache2/sites-available/1of1spoofer.conf
```

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/1of1spoofer

    <Directory /var/www/html/1of1spoofer>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/1of1spoofer-error.log
    CustomLog ${APACHE_LOG_DIR}/1of1spoofer-access.log combined
</VirtualHost>
```

Enable the site:

```bash
a2ensite 1of1spoofer.conf
systemctl reload apache2
```

## Configuration

### Basic Configuration

Copy the example configuration:

```bash
cp /var/www/html/1of1spoofer/config.example.php /var/www/html/1of1spoofer/config.php
nano /var/www/html/1of1spoofer/config.php
```

### SMTP Configuration

1of1Spoofer supports three email sending methods. Choose the one that best fits your needs:

#### 1. Direct mail() function

```php
'send_method' => 'mail',
```

This method uses the PHP mail() function. It's simple but often blocked by spam filters.

#### 2. SMTP Server

```php
'send_method' => 'smtp',
'smtp' => [
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'your_username',
    'password' => 'your_password',
    'encryption' => 'tls',
    'debug' => 0,
    'verify_ssl' => true
],
```

This method uses a standard SMTP server for sending emails. Better deliverability than mail().

#### 3. Direct Socket Connection

```php
'send_method' => 'socket',
'socket' => [
    'timeout' => 30,
    'debug' => false
],
```

This method establishes a direct socket connection to the recipient's mail server. Most likely to bypass spam filters but requires proper configuration of DNS and reverse DNS.

### Security Keys

Generate random keys for security:

```php
'app_key' => 'GENERATE_A_RANDOM_32_CHARACTER_STRING',
'csrf_salt' => 'GENERATE_ANOTHER_RANDOM_STRING',
```

You can generate a random string with:

```bash
openssl rand -hex 16
```

## Usage

### Accessing the Interface

Open your web browser and navigate to your server's IP address or domain name:

```
http://YOUR_SERVER_IP/1of1spoofer/
```

or if you configured a virtual host:

```
http://your-domain.com/
```

### Login

Use the default credentials:

- Username: admin
- Password: admin

**Important:** Change the default password immediately after your first login!

### Spoofing an Email

1. On the dashboard, click "New Email" or navigate to the "Spoof Email" tab
2. Fill in the required fields:
   - From Name: The name to display to recipients
   - From Email: The email address you want to spoof
   - To Email: Your test recipient email
   - Subject: Test subject
   - Message: Test message
3. Optional: Use the "Domain Security Analyzer" to check if the domain is spoofable
4. Click "Send Email"

### Domain Security Analysis

Before sending spoofed emails, check if the domain has protective measures:

1. Click on "Domain Security Analyzer"
2. Enter the domain name you want to check
3. Click "Analyze"

The tool will check for:

- SPF Records
- DMARC Policies
- DKIM Records
- MX Records

### Understanding Results

The domain analysis will give you one of these results:

- **High Risk**: Domain has no SPF or DMARC records. Emails can likely be spoofed.
- **Medium Risk**: Domain has SPF but no DMARC or has weak DMARC policy (p=none).
- **Low Risk**: Domain has SPF and DMARC with a strict policy (p=reject or p=quarantine).

## Security Considerations

### Securing Your Installation

1. Use HTTPS:

   ```bash
   apt install -y certbot python3-certbot-apache
   certbot --apache -d your-domain.com
   ```

2. Enable basic authentication:

   ```bash
   apt install -y apache2-utils
   htpasswd -c /etc/apache2/.htpasswd username
   ```

   Then edit your virtual host:

   ```
   <Directory /var/www/html/1of1spoofer>
       AuthType Basic
       AuthName "Restricted Access"
       AuthUserFile /etc/apache2/.htpasswd
       Require valid-user
   </Directory>
   ```

3. Configure a firewall:
   ```bash
   apt install -y ufw
   ufw allow ssh
   ufw allow http
   ufw allow https
   ufw enable
   ```

### Legal Considerations

This tool is for **EDUCATIONAL PURPOSES ONLY**. Always:

- Get explicit written permission before testing
- Document all testing activities
- Never use spoofed emails for fraud or harassment
- Understand the relevant laws in your jurisdiction

## Troubleshooting

### Common Issues

#### 500 Internal Server Error

Check Apache error log:

```bash
cat /var/log/apache2/1of1spoofer-error.log
```

Common causes:

- PHP syntax errors
- File permissions issues
- PHP extensions missing

#### Emails Not Sending

Check application log:

```bash
cat /var/www/html/1of1spoofer/logs/app.log
```

Common causes:

- SMTP configuration errors
- Server blocking outgoing SMTP
- PHP mail() function is disabled

#### Domain Analysis Not Working

Ensure the server can make DNS queries:

```bash
apt install -y dnsutils
dig TXT example.com
```

### Debugging

Enable debug mode in config.php:

```php
'debug' => true,
'log_level' => 'debug',
```

## Maintenance

### Keeping 1of1Spoofer Updated

```bash
cd /var/www/html/1of1spoofer
git pull
composer install --no-dev
chown -R www-data:www-data /var/www/html/1of1spoofer
```

### Backup

Regularly back up your configuration:

```bash
cp /var/www/html/1of1spoofer/config.php /root/1of1spoofer-config-backup.php
```

### Logs

Monitor and clean logs periodically:

```bash
find /var/www/html/1of1spoofer/logs -type f -name "*.log" -size +10M -delete
```

## Conclusion

By following this guide, you should now have a fully functional 1of1Spoofer installation. Remember to use this tool responsibly and only for legitimate educational or testing purposes.
