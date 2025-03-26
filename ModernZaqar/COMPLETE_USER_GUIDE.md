# 1of1spoofer - Complete User Guide

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Main Interface Overview](#main-interface-overview)
4. [Email Spoofing Features](#email-spoofing-features)
   - [Basic Email Fields](#basic-email-fields)
   - [Advanced Email Options](#advanced-email-options)
   - [Message Composition](#message-composition)
   - [Email Attachments](#email-attachments)
   - [Email Templates](#email-templates)
   - [Sending Methods](#sending-methods)
5. [Email Threading and Reply Chains](#email-threading-and-reply-chains)
   - [Starting a Thread](#starting-a-thread)
   - [Continuing a Thread](#continuing-a-thread)
   - [Finding Message IDs](#finding-message-ids)
6. [Domain Security Analysis](#domain-security-analysis)
   - [Checking Domain Security](#checking-domain-security)
   - [Interpreting Results](#interpreting-results)
   - [Security Records Explained](#security-records-explained)
7. [SMTP Configuration](#smtp-configuration)
   - [Setting Up SMTP](#setting-up-smtp)
   - [Testing SMTP Connection](#testing-smtp-connection)
   - [SMTP Debug Options](#smtp-debug-options)
8. [Troubleshooting](#troubleshooting)
   - [Common Errors](#common-errors)
   - [Debugging Tips](#debugging-tips)
   - [Logs and Diagnostics](#logs-and-diagnostics)
9. [Best Practices](#best-practices)
10. [Legal and Ethical Considerations](#legal-and-ethical-considerations)

## Introduction

1of1spoofer is an advanced email spoofing tool designed for educational purposes and authorized penetration testing. The application allows security professionals to test email security configurations, analyze domain vulnerabilities, and simulate phishing campaigns in controlled environments.

This tool should only be used:

- During authorized penetration testing engagements
- For security awareness training
- To test your own organization's email security controls

## Getting Started

### Accessing the Application

After installation, access the tool via your web browser:

```
http://your-server-address/
```

### Interface Overview

The application features a dark-themed, responsive interface with several key sections:

- Navigation menu (top)
- Email spoofing form (left)
- Domain security analyzer (right)
- Templates section (right/bottom)

### Theme Options

You can toggle between dark and light modes using the theme button in the top navigation bar.

## Main Interface Overview

### Navigation Menu

- **About**: Information about the application
- **Help**: Documentation and usage guides
- **Settings**: SMTP configuration options
- **Theme Toggle**: Switch between dark/light modes

### Main Sections

- **Email Spoofing Form**: The primary tool for composing and sending spoofed emails
- **Domain Security Analyzer**: Tool to check if domains are protected against spoofing
- **Email Templates**: Pre-made templates for common scenarios

## Email Spoofing Features

### Basic Email Fields

#### From Name

The display name that appears in the recipient's email client. This can be any text to impersonate a person or organization.

#### From Email

The email address shown as the sender. This is what appears to be the originating address of the email.

#### To Email

The recipient's email address. Multiple recipients can be separated with commas.

#### Subject

The subject line of the email. For replies, it's recommended to use "Re: Original Subject" format.

### Advanced Email Options

#### Reply-To

Sets a different address where replies will be sent. Essential for receiving responses to your spoofed emails.

#### CC (Carbon Copy)

Additional recipients who will receive the email with their address visible to all recipients. Separate multiple addresses with commas.

#### BCC (Blind Carbon Copy)

Recipients who receive the email without other recipients knowing. Separate multiple addresses with commas.

#### References

Used for email threading. Contains Message-IDs of all previous emails in a conversation thread. For replying to existing emails.

#### In-Reply-To

Contains the Message-ID of the specific email being replied to. Works with References to maintain proper email threading.

### Message Composition

The tool includes a rich text editor with formatting options:

- Text formatting (bold, italic, underline)
- Lists (ordered and unordered)
- Links and tables
- HTML view for advanced users

### Email Attachments

You can attach files to your spoofed emails:

- Click the "Browse" button to select files
- Multiple files can be selected at once
- File size limitations apply based on server configuration
- Allowed file types are shown below the attachment field

### Email Templates

Pre-made templates for common scenarios:

- **Password Reset**: Template mimicking password reset requests
- **Account Verification**: Email verification template for new accounts
- **Invoice Notification**: Payment request with invoice details
- **Security Alert**: Template for security incident notifications

To use a template:

1. Click "Load Template" or select from the templates panel
2. Choose the desired template
3. Customize the content as needed
4. Complete the remaining email fields

### Sending Methods

The tool supports multiple sending methods:

#### PHP mail()

Uses the server's default mail function. Limited spoofing capabilities.

#### SMTP Server

Connects to a specified SMTP server:

- Requires valid SMTP credentials
- Offers better delivery rates and spoofing capabilities
- Allows for detailed error messages and debugging

#### API (Future Feature)

Integration with email API services.

## Email Threading and Reply Chains

### Starting a Thread

When you send an initial spoofed email:

1. The system automatically generates a unique Message-ID
2. This ID is displayed in the success message after sending
3. Save this ID for future replies in the thread

### Continuing a Thread

To reply in a thread with a spoofed identity:

1. Fill in the same "From Email" as your previous spoofed message
2. Set the "Subject" with "Re:" prefix (e.g., "Re: Original Subject")
3. In the "References" field, paste the Message-ID of the original email
4. In the "In-Reply-To" field, paste the Message-ID of the immediate previous email
5. Set your actual email as the "Reply-To" address to receive responses

### Finding Message IDs

To get the Message-ID from received emails:

- **Gmail**: Open the email → Click "More" (three dots) → "Show original" → Find "Message-ID:" in the headers
- **Outlook**: Open the email → "File" → "Properties" → Find "Message-ID" in the Internet headers
- **Apple Mail**: Open the email → "View" → "Message" → "All Headers" → Find "Message-ID"

For emails sent from the tool:

- The Message-ID is displayed in a copyable field in the success message after sending
- Click to select the entire ID for easy copying

## Domain Security Analysis

### Checking Domain Security

The Domain Security Analyzer allows you to check if a domain is protected against email spoofing:

1. Enter a domain name in the analyzer field (e.g., example.com)
2. Click "Analyze" to retrieve the domain's security records
3. Alternatively, click "Check Domain" on the email form to analyze the domain in the From Email field

### Interpreting Results

The analyzer provides several key metrics:

#### SPF Record Status

- **Protected**: Domain has restrictive SPF with "-all" suffix
- **Neutral**: Domain has SPF with "~all" or "?all" suffix
- **Permissive**: Domain has SPF with "+all" or lacks SPF entirely
- **Missing**: No SPF record found

#### DMARC Policy Status

- **Reject**: Strongest protection, spoofed emails rejected
- **Quarantine**: Medium protection, spoofed emails sent to spam
- **None**: Monitoring only, no enforcement
- **Missing**: No DMARC record found

#### MX Records

Shows whether the domain has properly configured mail servers.

#### Vulnerability Score

A 0-10 score indicating overall security:

- 8-10: Highly protected
- 5-7: Moderately protected
- 0-4: Vulnerable to spoofing

#### Spoofability Assessment

A clear indication of whether the domain can likely be spoofed.

### Security Records Explained

Click "Technical Details" to view:

- The full SPF record text
- The full DMARC record text
- Complete list of MX records

This information helps understand why a domain is vulnerable or protected.

## SMTP Configuration

### Setting Up SMTP

To configure SMTP settings:

1. Click "Settings" in the top navigation
2. Enter your SMTP server details:
   - **Host**: SMTP server address (e.g., smtp.gmail.com)
   - **Port**: Server port (typically 587 for TLS, 465 for SSL)
   - **Security**: Choose TLS, SSL, or None
   - **Username**: Your SMTP account username
   - **Password**: Your SMTP account password

### Testing SMTP Connection

Before sending emails:

1. Enter your SMTP settings
2. Click "Test Connection"
3. View the test results in the alert box
4. Fix any connection issues before attempting to send emails

### SMTP Debug Options

For troubleshooting SMTP issues:

1. Set the Debug Level setting:

   - **Off (0)**: No debugging
   - **Client (1)**: Client messages only
   - **Server (2)**: Client and server messages
   - **Connection (3)**: Connection details included
   - **Verbose (4)**: Maximum debugging information

2. Check the "Verify SSL Certificate" option if your SMTP server requires certificate validation

## Troubleshooting

### Common Errors

#### "To email is required"

- Ensure the To field is filled with a valid email address
- Check the field's HTML name attribute matches what the server expects

#### "Client host rejected: Access denied"

- The SMTP server requires authentication
- Verify username and password are correct
- Check if the SMTP server restricts IP addresses

#### "Could not authenticate"

- SMTP credentials are incorrect
- Try testing the connection before sending

#### "Attachment not received"

- Check file size limits
- Ensure the file type is allowed
- Verify the attachment field name is correct

#### Text encoding issues

- The tool uses UTF-8 and base64 encoding
- Complex characters should display correctly

### Debugging Tips

1. Enable SMTP debugging (level 3 or 4)
2. Check the browser console for JavaScript errors
3. Look for error messages in the result modal
4. Verify network requests in the browser developer tools

### Logs and Diagnostics

Log files are stored in the `logs` directory:

- `smtp_debug.log`: SMTP connection details
- `email_sent.log`: Successful email deliveries
- `email_error.log`: Failed email attempts
- `php_errors.log`: PHP runtime errors

## Best Practices

### For Successful Spoofing Tests

1. **Always analyze domains first** before attempting to spoof
2. **Start with easily spoofable domains** (no SPF/DMARC)
3. **Use a reliable SMTP server** with good reputation
4. **Set Reply-To to your address** to receive responses
5. **Test with your own email first** before targeting others
6. **Use email templates** for more convincing messages
7. **Save Message-IDs** for threaded conversations

### Security Considerations

1. **Use HTTPS** when accessing the tool
2. **Regularly update** the application
3. **Restrict access** to authorized personnel only
4. **Use strong SMTP credentials**
5. **Monitor logs** for unauthorized usage

## Legal and Ethical Considerations

### Educational Use Only

This tool is provided for **EDUCATIONAL PURPOSES ONLY**. Email spoofing can be illegal if used without explicit permission from the target organization.

### Authorization Requirements

Always ensure you have:

- Written permission to conduct email security tests
- Proper scope documentation
- Approval from the organization's security team
- Compliance with relevant laws and regulations

### Reporting Vulnerabilities

If you discover email security vulnerabilities:

1. Document the findings clearly
2. Report to the organization's security team
3. Provide recommendations for remediation
4. Follow responsible disclosure practices

---

_This documentation is provided as part of the 1of1spoofer email security testing tool. Last updated: March 20, 2025._
