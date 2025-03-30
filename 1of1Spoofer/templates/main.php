<?php
// Get current SMTP settings for debugging
$currentSmtpSettings = getSmtpSettings();
error_log("Current SMTP settings: " . json_encode($currentSmtpSettings));

// Output settings to browser console for debugging
echo "<!-- SMTP Settings Debug: " . htmlspecialchars(json_encode($currentSmtpSettings), ENT_QUOTES) . " -->";
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1of1spoofer - Email Spoofing Testing Tool</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <?php if (config('ui.rich_text_editor', true)): ?>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-envelope-fill me-2"></i>
                1of1spoofer
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="thread_builder.php">
                            <i class="bi bi-chat-quote me-1"></i> Thread Builder
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">
                            <i class="bi bi-info-circle me-1"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#helpModal">
                            <i class="bi bi-question-circle me-1"></i> Help
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="settingsBtn" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="bi bi-gear-fill me-1"></i>Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="themeToggleBtn">
                            <i class="bi bi-moon-fill me-1"></i>
                            <span id="themeToggleText">Dark Mode</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Educational Use Only:</strong> This tool is provided for educational purposes and authorized penetration testing only. Misuse may be illegal.
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Email Spoofing Form -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-envelope me-2"></i>Email Spoofing Form</h5>
                    </div>
                    <div class="card-body">
                        <form id="emailForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $email_csrf_token; ?>">
                            <input type="hidden" name="form_name" value="email_form">
                            <input type="hidden" name="action" value="send_email">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fromName" class="form-label">From Name:</label>
                                    <input type="text" class="form-control" id="fromName" name="from_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="fromEmail" class="form-label">From Email:</label>
                                    <input type="email" class="form-control" id="fromEmail" name="from_email" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="toEmail" class="form-label">To Email:</label>
                                    <input type="email" class="form-control" id="toEmail" name="to" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="replyTo" class="form-label">Reply-To:</label>
                                    <div class="input-group">
                                        <select class="form-select" id="replyToSelect" aria-label="Reply-To options">
                                            <option value="hz9999@proton.me" selected>hz9999@proton.me</option>
                                            <option value="custom">Use custom email...</option>
                                        </select>
                                        <input type="email" class="form-control d-none" id="replyTo" name="reply_to" placeholder="Custom reply-to email">
                                    </div>
                                    <div class="form-text">Email address for recipients to reply to</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="cc" class="form-label">CC: (optional, separate with commas)</label>
                                    <input type="text" class="form-control" id="cc" name="cc">
                                </div>
                                <div class="col-md-6">
                                    <label for="bcc" class="form-label">BCC: (optional, separate with commas)</label>
                                    <input type="text" class="form-control" id="bcc" name="bcc">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject:</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                                <div class="form-text">For replies, use "Re: Original Subject"</div>
                            </div>
                            
                            <!-- Message input -->
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <div class="reply-tools mb-2 d-none">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="formatReplyBtn" data-bs-toggle="modal" data-bs-target="#replyHelperModal">
                                        <i class="fas fa-reply"></i> Format as Reply with Conversation History
                                    </button>
                                    <div class="form-text mt-1">For proper threading, include previous conversation content in replies.</div>
                                </div>
                                <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                            </div>
                            
                            <!-- Signature input -->
                            <div class="mb-3">
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <label for="signature" class="form-label mb-0">Email Signature</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enableSignature" name="enableSignature">
                                        <label class="form-check-label" for="enableSignature">Include signature</label>
                                    </div>
                                </div>
                                
                                <!-- Signature Container -->
                                <div id="signatureContainer" class="mb-2">
                                    <textarea class="form-control" id="signature" name="signature" rows="4"></textarea>
                                    <div class="form-text mt-1">
                                        Create a professional signature with formatting, images, and links. Your signature will be added at the end of your email.
                                    </div>
                                    <div class="d-flex justify-content-end mt-2">
                                        <button type="button" id="saveSignature" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="bi bi-save"></i> Save Signature
                                        </button>
                                        <button type="button" id="loadSignature" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-arrow-counterclockwise"></i> Load Saved
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Previous email content modal -->
                            <div class="modal fade" id="replyHelperModal" tabindex="-1" aria-labelledby="replyHelperModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="replyHelperModalLabel">Format Reply with Conversation History</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="previousEmailContent" class="form-label">Paste the content of the previous email</label>
                                                <textarea class="form-control" id="previousEmailContent" rows="10" placeholder="Paste the original email content here..."></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="previousSender" class="form-label">Previous Email Sender</label>
                                                <input type="text" class="form-control" id="previousSender" placeholder="e.g. John Smith">
                                            </div>
                                            <div class="mb-3">
                                                <label for="previousDate" class="form-label">Previous Email Date</label>
                                                <input type="text" class="form-control" id="previousDate" placeholder="e.g. Mon, 1 May 2023 10:30:45">
                                            </div>
                                            <div class="mb-3">
                                                <label for="yourReplyContent" class="form-label">Your Reply</label>
                                                <textarea class="form-control" id="yourReplyContent" rows="4" placeholder="Write your reply here..."></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-primary" id="generateFormattedReply">Generate Formatted Reply</button>
                                            </div>
                                            <div class="mb-3">
                                                <label for="formattedReplyPreview" class="form-label">Formatted Reply Preview</label>
                                                <textarea class="form-control" id="formattedReplyPreview" rows="10" readonly></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="button" class="btn btn-primary" id="useFormattedReply" data-bs-dismiss="modal">Use This Reply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="attachments" class="form-label">Attachment(s): (optional)</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                                <div class="form-text">
                                    Max size: <?php echo format_file_size(config('uploads.max_size', 5 * 1024 * 1024)); ?>.
                                    Allowed types: <?php echo implode(', ', array_keys(config('uploads.allowed_types', []))); ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="sendMethod" class="form-label">Send Method:</label>
                                <div class="form-control bg-light">SMTP Server (Only available method)</div>
                                <div id="smtpSection" class="mt-3 p-3 border rounded">
                                    <h6>Current SMTP Configuration:</h6>
                                    <p class="mb-1">Host: <?php echo htmlspecialchars($currentSmtpSettings['host'] ?? 'Not configured'); ?></p>
                                    <p class="mb-1">Port: <?php echo htmlspecialchars($currentSmtpSettings['port'] ?? '587'); ?></p>
                                    <p class="mb-0">Security: <?php echo htmlspecialchars($currentSmtpSettings['security'] ?? 'tls'); ?></p>
                                    
                                    <?php 
                                    // Get SMTP profiles for dropdown
                                    $smtpProfiles = getSmtpProfiles();
                                    ?>
                                    
                                    <div class="mt-3">
                                        <label for="smtpProfile" class="form-label">SMTP Profile:</label>
                                        <select class="form-select" id="smtpProfile">
                                            <option value="">-- Select a profile --</option>
                                            <?php foreach ($smtpProfiles as $profileName => $profile): ?>
                                                <option value="<?php echo htmlspecialchars($profileName); ?>" 
                                                        data-sender="<?php echo htmlspecialchars($profile['sender_email'] ?? ''); ?>">
                                                    <?php echo htmlspecialchars($profileName); ?> 
                                                    (<?php echo htmlspecialchars($profile['host']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Select a profile to quickly load SMTP settings and default sender email.</div>
                                    </div>
                                    
                                    <p class="form-text mt-3">To change SMTP settings, use the Settings menu.</p>
                                </div>
                            </div>
                            
                            <?php if (config('security.require_ethical_agreement', true)): ?>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="ethicalAgreement" name="ethical_agreement" value="yes" required>
                                <label class="form-check-label" for="ethicalAgreement">
                                    I confirm that I am using this tool for educational purposes or authorized penetration testing only.
                                </label>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary me-2" id="templateBtn">
                                    <i class="bi bi-file-earmark-text me-1"></i> Load Template
                                </button>
                                <button type="button" class="btn btn-success" id="analyzeBtn">
                                    <i class="bi bi-shield-check me-1"></i> Check Domain
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-1"></i> Send Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Security Tools & Results -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Domain Security Analyzer</h5>
                    </div>
                    <div class="card-body">
                        <form id="analyzeForm" class="mb-3">
                            <input type="hidden" name="csrf_token" value="<?php echo $analyze_csrf_token; ?>">
                            <input type="hidden" name="form_name" value="analyze">
                            <input type="hidden" name="action" value="domain_analysis">
                            <div class="input-group">
                                <input type="text" class="form-control" id="domainToCheck" name="domain" placeholder="Enter domain to analyze (e.g., example.com)">
                                <button class="btn btn-primary" type="submit">Analyze</button>
                            </div>
                        </form>
                        
                        <div id="analyzeResults" class="mt-3 d-none">
                            <h5>Results for <span id="resultsDomain"></span></h5>
                            <div class="mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>SPF Record:</span>
                                    <span id="spfStatus" class="badge"></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>DMARC Policy:</span>
                                    <span id="dmarcStatus" class="badge"></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>MX Records:</span>
                                    <span id="mxStatus" class="badge"></span>
                                </div>
                                <div class="progress mb-2">
                                    <div id="vulnerabilityProgress" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Vulnerability Score:</span>
                                    <strong id="vulnerabilityScore">0/10</strong>
                                </div>
                                <div class="alert mt-3" id="spoofableStatus"></div>
                                <div class="accordion mt-3" id="securityDetailsAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#recordDetails">
                                                Technical Details
                                            </button>
                                        </h2>
                                        <div id="recordDetails" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                <h6>SPF Record:</h6>
                                                <pre id="spfRecord" class="bg-light p-2 rounded small">None found</pre>
                                                
                                                <h6>DMARC Record:</h6>
                                                <pre id="dmarcRecord" class="bg-light p-2 rounded small">None found</pre>
                                                
                                                <h6>MX Records:</h6>
                                                <pre id="mxRecords" class="bg-light p-2 rounded small">None found</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Email Templates</h5>
                    </div>
                    <div class="card-body">
                        <p>Load a pre-made email template:</p>
                        <div class="list-group">
                            <button type="button" class="list-group-item list-group-item-action template-item" data-template="password_reset">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Password Reset</h6>
                                    <small><i class="bi bi-key"></i></small>
                                </div>
                                <small>Standard password reset email template.</small>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action template-item" data-template="account_verification">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Account Verification</h6>
                                    <small><i class="bi bi-check-circle"></i></small>
                                </div>
                                <small>Email verification template for new accounts.</small>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action template-item" data-template="invoice">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Invoice Notification</h6>
                                    <small><i class="bi bi-receipt"></i></small>
                                </div>
                                <small>Invoice notification with payment link.</small>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action template-item" data-template="security_alert">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Security Alert</h6>
                                    <small><i class="bi bi-exclamation-triangle"></i></small>
                                </div>
                                <small>Alert for suspicious account activity.</small>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Message-ID Extractor Tool -->
                <div class="card mb-3 border-left-info">
                    <div class="card-body">
                        <h5 class="card-title">
                            <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#messageIdExtractorCollapse" aria-expanded="false">
                                <i class="fas fa-tools"></i> Message-ID Extractor Tool <i class="fas fa-chevron-down small"></i>
                            </button>
                        </h5>
                        <div class="collapse" id="messageIdExtractorCollapse">
                            <p class="card-text">Paste email headers to extract the Message-ID for replies.</p>
                            <div class="mb-3">
                                <label for="emailHeaderContent" class="form-label">Email Headers</label>
                                <textarea class="form-control" id="emailHeaderContent" rows="6" placeholder="Paste the full email headers here..."></textarea>
                            </div>
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" id="extractMessageIdBtn">Extract Message-ID</button>
                            </div>
                            <div class="mb-3">
                                <label for="extractedMessageId" class="form-label">Extracted Message-ID</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="extractedMessageId" readonly>
                                    <button class="btn btn-outline-secondary" type="button" id="copyExtractedMessageId">Copy</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Result Modal -->
    <div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header" id="resultModalHeader">
                    <h5 class="modal-title" id="resultModalTitle">Result</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="resultModalContent">
                    <!-- Content will be inserted here via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Email Templates</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <button type="button" class="list-group-item list-group-item-action template-modal-item" data-template="password_reset">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Password Reset</h6>
                                <small><i class="bi bi-key"></i></small>
                            </div>
                            <small>Standard password reset email template.</small>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action template-modal-item" data-template="account_verification">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Account Verification</h6>
                                <small><i class="bi bi-check-circle"></i></small>
                            </div>
                            <small>Email verification template for new accounts.</small>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action template-modal-item" data-template="invoice">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Invoice Notification</h6>
                                <small><i class="bi bi-receipt"></i></small>
                            </div>
                            <small>Invoice notification with payment link.</small>
                        </button>
                        <button type="button" class="list-group-item list-group-item-action template-modal-item" data-template="security_alert">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Security Alert</h6>
                                <small><i class="bi bi-exclamation-triangle"></i></small>
                            </div>
                            <small>Alert for suspicious account activity.</small>
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- About Modal -->
    <div class="modal fade" id="aboutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">About 1of1spoofer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="bi bi-envelope-fill text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="text-center"><?php echo config('app_name', '1of1spoofer'); ?> v<?php echo config('version', '2.0.0'); ?></h4>
                    <p class="text-center">Advanced Email Spoofing Tool for Educational Purposes</p>
                    <hr>
                    <p>1of1spoofer is an EmailSpoofer with modern libraries, improved security, and additional features including:</p>
                    <ul>
                        <li>Modern PHP 8.x Support</li>
                        <li>Enhanced Security & Improved UI/UX</li>
                        <li>DKIM/SPF/DMARC Analysis</li>
                        <li>Multiple Email Sending Methods</li>
                        <li>Comprehensive Logging</li>
                    </ul>
                    <div class="alert alert-warning">
                        <strong>Educational Use Only:</strong> This tool is provided for educational purposes and authorized penetration testing only.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Help & Documentation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <nav>
                        <div class="nav nav-tabs mb-3" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-usage-tab" data-bs-toggle="tab" data-bs-target="#nav-usage" type="button" role="tab">Usage Guide</button>
                            <button class="nav-link" id="nav-security-tab" data-bs-toggle="tab" data-bs-target="#nav-security" type="button" role="tab">Security Info</button>
                            <button class="nav-link" id="nav-faq-tab" data-bs-toggle="tab" data-bs-target="#nav-faq" type="button" role="tab">FAQ</button>
                        </div>
                    </nav>
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-usage" role="tabpanel" aria-labelledby="nav-usage-tab">
                            <h5>Using the Email Spoofing Tool</h5>
                            <p>To send a spoofed email:</p>
                            <ol>
                                <li>Fill in the <strong>From Name</strong> and <strong>From Email</strong> with the sender details you want to impersonate.</li>
                                <li>Enter the <strong>To Email</strong> address where you want to send the message.</li>
                                <li>Add a <strong>Subject</strong> and <strong>Message</strong> for your email.</li>
                                <li>Optionally add an <strong>Attachment</strong> file.</li>
                                <li>Select a <strong>Send Method</strong> (PHP mail(), SMTP, or API).</li>
                                <li>Check the ethical usage agreement box.</li>
                                <li>Click <strong>Send Email</strong> to send the message.</li>
                            </ol>
                            <div class="alert alert-info">
                                <strong>Tip:</strong> Use the <strong>Domain Security Analyzer</strong> to check if a domain can be spoofed before attempting to use it.
                            </div>
                        </div>
                        <div class="tab-pane fade" id="nav-security" role="tabpanel" aria-labelledby="nav-security-tab">
                            <h5>Email Security Information</h5>
                            <h6>SPF (Sender Policy Framework)</h6>
                            <p>SPF records specify which mail servers are authorized to send email on behalf of a domain. When an SPF record includes:</p>
                            <ul>
                                <li><strong>-all</strong>: Unauthorized servers are rejected (most secure)</li>
                                <li><strong>~all</strong>: Unauthorized servers are soft-failed (marked as suspicious)</li>
                                <li><strong>?all</strong>: Neutral (no policy)</li>
                                <li><strong>+all</strong>: All servers are allowed (least secure)</li>
                            </ul>
                            <h6>DMARC (Domain-based Message Authentication, Reporting & Conformance)</h6>
                            <p>DMARC tells receiving servers what to do when SPF or DKIM checks fail:</p>
                            <ul>
                                <li><strong>p=reject</strong>: Reject unauthorized emails</li>
                                <li><strong>p=quarantine</strong>: Send to spam folder</li>
                                <li><strong>p=none</strong>: Take no action (monitoring only)</li>
                            </ul>
                        </div>
                        <div class="tab-pane fade" id="nav-faq" role="tabpanel" aria-labelledby="nav-faq-tab">
                            <h5>Frequently Asked Questions</h5>
                            <div class="accordion" id="faqAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqOne">
                                            Why isn't my spoofed email delivered?
                                        </button>
                                    </h2>
                                    <div id="faqOne" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            The domain you're trying to spoof likely has proper SPF, DKIM and/or DMARC records that prevent spoofing. Use the Domain Security Analyzer to check if a domain has such protections.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqTwo">
                                            How can I configure SMTP settings?
                                        </button>
                                    </h2>
                                    <div id="faqTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Edit the <code>config.php</code> file and look for the <code>smtp</code> section. Update the host, port, username, password, and other settings as needed.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqThree">
                                            Is this tool legal to use?
                                        </button>
                                    </h2>
                                    <div id="faqThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            This tool is for educational purposes only. Using it without explicit permission from the target organization may be illegal. Only use during authorized penetration testing or security awareness training within your own organization.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SMTP Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-gear-fill me-2"></i>SMTP Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-none">Token: <?php echo htmlspecialchars($smtp_csrf_token ?? ''); ?></div>
                    
                    <?php $smtpSettings = getSmtpSettings(); ?>
                    <form id="smtpSettingsForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($smtp_csrf_token ?? ''); ?>">
                        <input type="hidden" name="form_name" value="smtp_settings">
                        <input type="hidden" name="action" value="save_smtp_settings">
                        
                        <div class="mb-3">
                            <label for="smtp_host" class="form-label">SMTP Server</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($smtpSettings['host'] ?? ''); ?>">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="smtp_port" class="form-label">Port:</label>
                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo intval($smtpSettings['port'] ?? 587); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="smtp_security" class="form-label">Security:</label>
                                <select class="form-select" id="smtp_security" name="smtp_security">
                                    <option value="tls" <?php echo ($smtpSettings['security'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($smtpSettings['security'] ?? 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="" <?php echo empty($smtpSettings['security'] ?? 'tls') ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="smtp_username" class="form-label">Username:</label>
                                <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($smtpSettings['username'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="smtp_password" class="form-label">Password:</label>
                                <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($smtpSettings['password'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="smtp_debug" class="form-label">Debug Level:</label>
                                <select class="form-select" id="smtp_debug" name="smtp_debug">
                                    <option value="0" <?php echo ($smtpSettings['debug'] ?? 0) == 0 ? 'selected' : ''; ?>>Off</option>
                                    <option value="1" <?php echo ($smtpSettings['debug'] ?? 0) == 1 ? 'selected' : ''; ?>>Client</option>
                                    <option value="2" <?php echo ($smtpSettings['debug'] ?? 0) == 2 ? 'selected' : ''; ?>>Server</option>
                                    <option value="3" <?php echo ($smtpSettings['debug'] ?? 0) == 3 ? 'selected' : ''; ?>>Connection</option>
                                    <option value="4" <?php echo ($smtpSettings['debug'] ?? 0) == 4 ? 'selected' : ''; ?>>Verbose</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input type="checkbox" class="form-check-input" id="smtp_verify_ssl" name="smtp_verify_ssl" value="1" <?php echo ($smtpSettings['verify_ssl'] ?? false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="smtp_verify_ssl">Verify SSL Certificate</label>
                                </div>
                            </div>
                        </div>
                        
                        <div id="settingsSmtpTestResult" class="alert d-none mb-3"></div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" id="test-smtp-btn" class="btn btn-secondary">
                                <i class="bi bi-lightning-charge me-1"></i> Test Connection
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <!-- SMTP Profiles Section -->
                    <?php include 'templates/smtp_profiles.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-3 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">
                        <i class="bi bi-envelope-fill me-2"></i> <?php echo config('app_name', '1of1spoofer'); ?> v<?php echo config('version', '2.0.0'); ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-1"></i> Educational Purposes Only
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php if (config('ui.rich_text_editor', true)): ?>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <?php endif; ?>
    <script src="js/modal-fallback.js"></script>
    <script src="js/scripts.js"></script>

    <!-- Additional debugging for SMTP settings -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php 
        $smtpSettings = getSmtpSettings();
        $fallbackSmtpSettings = getFallbackSmtpSettings();
        // Remove passwords for security
        if (isset($smtpSettings['password'])) { $smtpSettings['password'] = '***'; }
        if (isset($fallbackSmtpSettings['password'])) { $fallbackSmtpSettings['password'] = '***'; }
        ?>
        
        console.log('Current SMTP Settings:', <?php echo json_encode($smtpSettings); ?>);
        console.log('Current Fallback SMTP Settings:', <?php echo json_encode($fallbackSmtpSettings); ?>);
        console.log('Fallback SMTP Enabled:', <?php echo json_encode(isFallbackSmtpEnabled()); ?>);
    });

    // Function to directly open the New Profile modal
    function openNewProfileModal() {
        console.log('Opening New Profile Modal via direct function call');
        if (typeof bootstrap !== 'undefined') {
            const newProfileModal = new bootstrap.Modal(document.getElementById('newProfileModal'));
            newProfileModal.show();
        } else {
            console.error('Bootstrap is not available');
            alert('Error: Could not open the modal. Please check if Bootstrap is loaded properly.');
        }
    }
    </script>
</body>
</html> 