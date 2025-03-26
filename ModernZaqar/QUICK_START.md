# 1of1Spoofer Quick Start Guide

This guide will help you get 1of1Spoofer up and running as quickly as possible.

## Prerequisites

- A web server with PHP 7.4+ installed
- Composer (PHP dependency manager)
- Basic understanding of SMTP and email protocols

## Installation Options

Choose one of the following installation methods:

### A. Automated Installation (Linux/Ubuntu)

1. Download the install script:

   ```
   wget https://raw.githubusercontent.com/yourusername/1of1Spoofer/main/install.sh
   ```

2. Make it executable:

   ```
   chmod +x install.sh
   ```

3. Run the installer as root:

   ```
   sudo ./install.sh
   ```

4. Follow the on-screen instructions.

### B. Deploy 1of1Spoofer

1. Clone the repository:

   ```
   git clone https://github.com/yourusername/1of1Spoofer.git
   ```

2. Navigate to the project directory:

   ```
   cd 1of1Spoofer
   ```

3. Install dependencies:

   ```
   composer install
   ```

4. Set directory permissions:

   ```
   chmod -R 755 .
   chmod -R 777 logs uploads
   ```

5. Create a configuration file:

   ```
   cp config.example.php config.php
   ```

6. Edit `config.php` with your settings:

   ```
   nano config.php
   ```

7. Point your web server to the project directory.

## SMTP Configuration

For the tool to send emails properly, you need to configure SMTP settings:

1. Edit `config.php`
2. Set the SMTP host, port, security type, username, and password
3. Make sure the SMTP account is properly authorized

Example configuration:

```php
'smtp' => [
    'host' => 'smtp.example.com',
    'port' => 587,
    'security' => 'tls',
    'username' => 'your-username@example.com',
    'password' => 'your-password',
    'debug' => 0,
    'verify_ssl' => true
],
```

## Access 1of1Spoofer

Once installed, access the tool via your web browser:

```
http://your-server-ip/
```

You should see the 1of1Spoofer interface. Log in with the default credentials:

- Username: admin
- Password: password

(Make sure to change these immediately after login)

## First Test

1. Navigate to the Email Spoofing section
2. Enter the sender information (name and email)
3. Enter the recipient email address (use your own for testing)
4. Enter a subject and message
5. Click "Send Email"
6. Check your inbox to verify the email was delivered

## Security Disclaimer

1of1Spoofer is provided for **EDUCATIONAL PURPOSES ONLY**. Email spoofing without explicit permission is illegal in most jurisdictions. Only use this tool in authorized testing environments or with explicit permission from all parties involved.

## Troubleshooting

If you encounter issues:

1. Check the logs directory for error messages
2. Verify your SMTP configuration is correct
3. Make sure your SMTP provider allows the type of emails you're sending
4. Consult the full documentation for more detailed information

## Next Steps

For more detailed information, refer to:

- [Installation Guide](INSTALLATION_GUIDE.md)
- [Domain Analysis Guide](DOMAIN_ANALYSIS_GUIDE.md)

## ⚠️ Legal Warning

1of1Spoofer is provided for **EDUCATIONAL PURPOSES ONLY**. Email spoofing without explicit permission is illegal in most jurisdictions. Only use this tool in authorized testing environments or with explicit permission from all parties involved.

---

_For more help, visit the issues section of the repository._
