# Debugging Notes for 1of1Spoofer

This document contains notes and information to help troubleshoot common issues with 1of1Spoofer.

## Common Issues

### 1. Emails Not Sending

#### Symptoms:

- "Email sent successfully" message appears, but no email is received
- Error messages related to SMTP or mail() function
- Timeouts during email sending

#### Troubleshooting Steps:

1. **Check SMTP Configuration**

   - Verify host, port, encryption type, username, and password
   - Test SMTP connection using the built-in test function
   - Try different encryption settings (TLS/SSL/None)

2. **Review Server Logs**

   ```bash
   tail -f logs/smtp.log
   ```

3. **Check PHP mail() Configuration** (if using mail method)

   - Verify sendmail_path in php.ini
   - Check if mail() function is enabled

4. **Firewall/Network Issues**

   - Check if outgoing SMTP connections are allowed (usually port 25, 465, or 587)
   - Verify your hosting provider doesn't block mail functions

5. **Review PHP Error Logs**
   ```bash
   tail -f logs/app.log
   ```

### 2. 500 Internal Server Error

#### Symptoms:

- Blank white screen
- "500 Internal Server Error" message

#### Troubleshooting Steps:

1. **Check PHP Error Logs**

   ```bash
   tail -f logs/app.log
   ```

2. **Verify PHP Version and Extensions**

   ```bash
   php -v
   php -m
   ```

3. **Check File Permissions**

   ```bash
   chmod -R 755 /path/to/1of1spoofer
   chmod -R 777 /path/to/1of1spoofer/logs
   chmod -R 777 /path/to/1of1spoofer/uploads
   ```

4. **Verify Configuration File**
   - Make sure config.php exists and has correct syntax
   - Try restoring from config.example.php

### 3. Domain Analysis Issues

#### Symptoms:

- Domain analysis returns no results
- Error messages during domain lookup

#### Troubleshooting Steps:

1. **Check DNS Resolution**

   ```bash
   nslookup -type=TXT example.com
   dig TXT example.com
   ```

2. **Verify Required PHP Extensions**

   - Make sure php-dns extensions are installed

3. **Network Connectivity**
   - Ensure the server can make outbound DNS queries

### 4. SMTP Authentication Failures

#### Symptoms:

- "SMTP Error: Could not authenticate" messages
- "SMTP Error: 5.7.0 Authentication failure" messages

#### Troubleshooting Steps:

1. **Verify Credentials**

   - Double-check username and password
   - Consider using app password for Gmail/other services with 2FA

2. **Check Security Settings**

   - Some providers require "Less secure app access" to be enabled
   - Try different encryption settings (TLS/SSL)

3. **Debug Output**
   - Set SMTP debug level to 2 or 4 for detailed logs
   - Review logs/smtp.log file for detailed error messages

### 5. Browser Issues

#### Symptoms:

- JavaScript errors in console
- Form submission not working
- UI display problems

#### Troubleshooting Steps:

1. **Clear Browser Cache**

   - Try hard refresh (Ctrl+F5 or Cmd+Shift+R)
   - Clear browser cookies and cache

2. **Check Browser Console**

   - Press F12 to open developer tools
   - Look for JavaScript errors

3. **Try Different Browser**
   - Test in Chrome, Firefox, etc. to isolate browser-specific issues

## System Requirements

1of1Spoofer requires:

- PHP 7.4+ (PHP 8.0+ recommended)
- Required PHP extensions:
  - curl
  - json
  - mbstring
  - xml
  - fileinfo
  - openssl
- Apache2 or Nginx web server
- mod_rewrite enabled (Apache)
- Composer (PHP dependency manager)

## Log File Locations

- SMTP logs: `logs/smtp.log`
- Application logs: `logs/app.log`
- Debug logs: `logs/debug.log`

## Getting Help

If you continue experiencing issues, please:

1. Gather relevant log files
2. Note your PHP version and environment details
3. Create a detailed issue report in the project repository
4. Include steps to reproduce the problem
