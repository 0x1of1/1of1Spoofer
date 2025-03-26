# 1of1Spoofer

1of1Spoofer is an advanced email spoofing tool designed for educational purposes and authorized penetration testing. This tool helps security professionals understand email spoofing vulnerabilities and test domain security configurations.

## Features

- **Email Spoofing**: Test email security by sending spoofed emails
- **Domain Security Analysis**: Check domains for SPF, DMARC, and DKIM configurations
- **SMTP Profile Management**: Save and manage multiple SMTP configurations
- **Raw Email Mode**: Edit and send modified raw email files

## Installation

### Prerequisites

- PHP 7.4+ with required extensions
- Web server (Apache/Nginx)
- Composer (PHP dependency manager)

### Quick Install

1. Clone the repository:

   ```
   git clone https://github.com/0x1of1/1of1Spoofer.git
   ```

2. Install dependencies:

   ```
   cd 1of1Spoofer
   composer install
   ```

3. Configure your SMTP settings in `config.php`

4. Ensure the `logs` and `uploads` directories are writable

## Usage

1. Access the tool via your web browser
2. Configure your SMTP settings
3. Use the email form to create and send test emails
4. Analyze domains for security vulnerabilities

For detailed instructions, see the documentation files

## Security Disclaimer

**WARNING**: This tool is for educational purposes and authorized penetration testing only. Unauthorized email spoofing may violate laws and regulations. Always obtain proper authorization before testing.

## Documentation

- Quick Start Guide
- Installation Guide
- Domain Analysis Guide
- Debugging Notes

## License

This project is available for educational purposes only. See the LICENSE file for details.
