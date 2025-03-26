# 1of1Spoofer

![1of1Spoofer Logo](assets/images/favicon.png)

1of1Spoofer is an advanced email spoofing testing tool designed for educational purposes and authorized penetration testing. It offers a modern web interface and comprehensive features to test email security controls.

## ⚠️ Disclaimer

This tool is provided **for educational purposes and authorized penetration testing only**. Misuse of this tool may violate laws, regulations, and/or organizational policies. The authors and contributors assume no liability for any misuse or damage caused by this tool.

## Features

- **Modern UI:** Sleek Bootstrap 5-based responsive interface
- **Multiple SMTP Profiles:** Save and load multiple SMTP configurations
- **Domain Security Analysis:** Check SPF, DMARC, and MX records
- **Custom Email Headers:** Full control over email headers and content
- **Email Templating:** Pre-made templates for common phishing scenarios
- **Conversation Threading:** Simulate realistic email threads
- **Detailed Error Logging:** Comprehensive logging for troubleshooting
- **Dark/Light Mode:** Choose your preferred theme

## Installation

1. Clone this repository to your web server:

```
git clone https://github.com/yourusername/1of1Spoofer.git
```

2. Configure your web server to serve the application (Apache, Nginx, etc.)

3. Create a `config.php` file based on the example and update your settings:

```
cp config.example.php config.php
```

4. Ensure the required PHP extensions are installed:

   - PHP 7.4 or higher
   - php-imap
   - php-mbstring
   - php-openssl
   - php-curl

5. Set appropriate permissions for the uploads, logs, and cache directories

## Usage

1. Access the application through your web browser

2. Configure your SMTP settings in the Settings menu

3. Use the Domain Security Analyzer to check if a domain is protected against spoofing

4. Fill out the Email Spoofing Form with your test details

5. Send the test email and analyze the results

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- [PHPMailer](https://github.com/PHPMailer/PHPMailer) for email handling
- [Bootstrap 5](https://getbootstrap.com/) for the UI framework
- [jQuery](https://jquery.com/) for JavaScript simplification
- [Summernote](https://summernote.org/) for the rich text editor

## Contact

For educational purposes only. Please use responsibly.
