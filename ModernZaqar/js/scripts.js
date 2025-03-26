/**
 * 1of1Spoofer - Front-end Scripts
 * 
 * This file contains the JavaScript code for the 1of1Spoofer application.
 * Handles form submissions, AJAX requests, and UI interactions.
 */

$(document).ready(function () {
    // Initialize the rich text editor if enabled
    if ($("#message").length) {
        try {
            $("#message").summernote({
                placeholder: 'Enter your email message here...',
                height: 200,
                toolbar: [
                    ['style', ['style', 'bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'table', 'hr']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                dialogsInBody: true,
                dialogsFade: true,
                callbacks: {
                    onInit: function () {
                        // Fix dark mode for the editor
                        $('.note-editable').css('background-color', '#2a2a2a');
                        $('.note-editable').css('color', '#f5f5f5');
                        $('.note-codable').css('background-color', '#2a2a2a');
                        $('.note-codable').css('color', '#f5f5f5');
                    }
                }
            });
        } catch (e) {
            // Rich text editor initialization failed, fallback to normal textarea
            console.log("Rich text editor failed to initialize.");
            // Ensure the normal textarea has the right colors
            $("#message").addClass("bg-dark text-light");
        }
    } else {
        // Ensure textarea has dark mode styling if not using rich text editor
        $("#message").addClass("bg-dark text-light");
    }

    // Open settings modal
    $("#settingsBtn").on("click", function () {
        console.log("Settings button clicked");
        try {
            var settingsModal = new bootstrap.Modal(document.getElementById('settingsModal'));
            settingsModal.show();
        } catch (error) {
            console.error("Error showing modal:", error);
        }
    });

    // Toggle SMTP settings form
    $('#smtp-settings-toggle').on('click', function (e) {
        e.preventDefault();
        $('#smtp-settings-form').slideToggle();
    });

    // Handle SMTP test connection
    $('#test-smtp-btn').on('click', function () {
        const $form = $(this).closest('form');
        const $testBtn = $(this);
        // Determine which result box to use based on the form ID
        const $resultBox = $form.attr('id') === 'smtpSettingsForm' ? $('#settingsSmtpTestResult') : $('#smtpTestResult');

        console.log('Testing SMTP connection for form: ' + $form.attr('id'));

        // Show loading
        $testBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing...');
        $resultBox.removeClass('d-none alert-success alert-danger').addClass('alert-info').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing SMTP connection...');

        // Get form data
        const formData = new FormData($form[0]);
        formData.set('action', 'test_smtp_connection');

        // Always use the hardcoded token for consistency
        console.log('Setting hardcoded CSRF token in request');
        formData.set('csrf_token', '1234567890abcdef1234567890abcdef');

        // Log the form data being sent
        console.log("SMTP test data:");
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Send AJAX request
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                console.log('Test connection response:', response);

                // Parse response if needed
                let result = response;
                if (typeof response === 'string') {
                    try {
                        result = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse test response:', e);
                        $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html('<i class="fas fa-times-circle"></i> Invalid server response. Check console for details.');
                        return;
                    }
                }

                // Show result
                if (result.status === 'success') {
                    $resultBox.removeClass('alert-info alert-danger').addClass('alert-success').html(`
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Connection Successful!</strong>
                        <p class="mb-0 mt-2">${result.message}</p>
                    `);
                } else {
                    $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html(`
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <strong>Connection Failed!</strong>
                        <p class="mb-0 mt-2">${result.message}</p>
                    `);
                }
            },
            error: function (xhr, status, error) {
                console.error('SMTP test error:', status, error);

                // Try to extract more meaningful error message
                let errorMessage = error;

                try {
                    // Try to parse JSON response
                    if (xhr.responseText) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    }
                } catch (e) {
                    // If we can't parse JSON, try to extract error from HTML response
                    if (xhr.responseText && xhr.responseText.includes('<')) {
                        // Looks like HTML response, extract text between <body> tags if possible
                        const bodyMatch = /<body[^>]*>([\s\S]*?)<\/body>/i.exec(xhr.responseText);
                        if (bodyMatch && bodyMatch[1]) {
                            // Remove HTML tags to get plain text error
                            errorMessage = bodyMatch[1].replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                        } else {
                            // Just take the first 100 characters
                            errorMessage = xhr.responseText.substring(0, 100) + '...';
                        }
                    }
                }

                $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html(`
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Test Failed!</strong>
                    <p class="mb-0 mt-2">Error: ${errorMessage}</p>
                    <div class="mt-2">
                        <small class="text-muted">Status: ${status} (${xhr.status})</small>
                    </div>
                `);
            },
            complete: function () {
                // Reset button state
                $testBtn.prop('disabled', false).html('<i class="bi bi-lightning-charge me-1"></i> Test Connection');
            }
        });
    });

    // Handle SMTP form submission
    $('#smtpForm').on('submit', function (e) {
        e.preventDefault();
        console.log("SMTP form submitted");

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $resultBox = $('#smtpTestResult');

        // Show form data being sent
        const formData = new FormData($form[0]);
        console.log("SMTP form data:");
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Show loading
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $resultBox.removeClass('d-none alert-success alert-danger').addClass('alert-info').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving SMTP settings...');

        // Send AJAX request
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            success: function (response) {
                console.log("SMTP save response:", response);
                // Parse response if needed
                let result = response;
                if (typeof response === 'string') {
                    try {
                        result = JSON.parse(response);
                        console.log("Parsed JSON result:", result);
                    } catch (e) {
                        console.error('Failed to parse response:', e);
                        console.log("Raw response:", response);
                        $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html('<i class="fas fa-times-circle"></i> Invalid server response. Check console for details.');
                        return;
                    }
                }

                // Show result
                if (result.status === 'success') {
                    $resultBox.removeClass('alert-info alert-danger').addClass('alert-success').html(`
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Success!</strong>
                        <p class="mb-0 mt-2">${result.message}</p>
                    `);
                } else {
                    $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html(`
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <strong>Error!</strong>
                        <p class="mb-0 mt-2">${result.message}</p>
                    `);
                }
            },
            error: function (xhr, status, error) {
                console.error('SMTP save error:', status, error);
                $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html(`
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Error!</strong>
                    <p class="mb-0 mt-2">Failed to save settings: ${error}</p>
                `);
            },
            complete: function () {
                // Reset button state
                $submitBtn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Save Settings');
            }
        });
    });

    // Handle Fallback SMTP form submission
    $('#fallbackSmtpForm').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $resultBox = $('#fallbackSmtpTestResult');

        // Show loading
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $resultBox.removeClass('d-none alert-success alert-danger').addClass('alert-info').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving fallback SMTP settings...');

        // Send AJAX request
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            success: function (response) {
                // Parse response if needed
                let result = response;
                if (typeof response === 'string') {
                    try {
                        result = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse response:', e);
                        $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html('<i class="fas fa-times-circle"></i> Invalid server response. Check console for details.');
                        return;
                    }
                }

                // Show result
                if (result.status === 'success') {
                    $resultBox.removeClass('alert-info alert-danger').addClass('alert-success').html(`
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Success!</strong>
                        <p class="mb-0 mt-2">${result.message}</p>
                    `);
                } else {
                    $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html(`
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <strong>Error!</strong>
                        <p class="mb-0 mt-2">${result.message}</p>
                    `);
                }
            },
            error: function (xhr, status, error) {
                console.error('Fallback SMTP save error:', status, error);
                $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html(`
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Error!</strong>
                    <p class="mb-0 mt-2">Failed to save fallback settings: ${error}</p>
                `);
            },
            complete: function () {
                // Reset button state
                $submitBtn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Save Fallback Settings');
            }
        });
    });

    // Test fallback SMTP connection
    $('#test-fallback-smtp-btn').on('click', function () {
        const $btn = $(this);
        const $form = $('#fallbackSmtpForm');
        const $resultBox = $('#fallbackSmtpTestResult');

        // Show loading
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing...');
        $resultBox.removeClass('d-none alert-success alert-danger').addClass('alert-info').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing fallback SMTP connection...');

        // Prepare form data
        const formData = new FormData($form[0]);
        formData.set('action', 'test_fallback_smtp_connection');

        // Send AJAX request
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                // Parse response if needed
                let result = response;
                if (typeof response === 'string') {
                    try {
                        result = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse response:', e);
                        $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html('<i class="fas fa-times-circle"></i> Invalid server response. Check console for details.');
                        return;
                    }
                }

                // Show result
                if (result.status === 'success') {
                    $resultBox.removeClass('alert-info alert-danger').addClass('alert-success').html(`
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Connection Successful!</strong>
                        <p class="mb-0 mt-2">${result.message}</p>
                    `);
                } else {
                    $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html(`
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <strong>Connection Failed!</strong>
                        <p class="mb-0 mt-2">${result.message}</p>
                    `);
                }
            },
            error: function (xhr, status, error) {
                console.error('SMTP test error:', status, error);
                $resultBox.removeClass('alert-info alert-success').addClass('alert-danger').html(`
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Error!</strong>
                    <p class="mb-0 mt-2">Failed to test connection: ${error}</p>
                `);
            },
            complete: function () {
                // Reset button state
                $btn.prop('disabled', false).html('<i class="bi bi-lightning-charge me-1"></i> Test Fallback Connection');
            }
        });
    });

    // Toggle dark/light mode
    $("#themeToggleBtn").on("click", function () {
        const htmlElement = document.documentElement;
        const currentTheme = htmlElement.getAttribute("data-bs-theme");
        const newTheme = currentTheme === "dark" ? "light" : "dark";

        htmlElement.setAttribute("data-bs-theme", newTheme);

        // Store preference in local storage
        localStorage.setItem("theme", newTheme);

        // Update button text and icon
        if (newTheme === "dark") {
            $("#themeToggleText").text("Light Mode");
            $("#themeToggleBtn i").removeClass("bi-moon-fill").addClass("bi-sun-fill");

            // Update editor theme
            $('.note-editable').css('background-color', '#2a2a2a');
            $('.note-editable').css('color', '#f5f5f5');
        } else {
            $("#themeToggleText").text("Dark Mode");
            $("#themeToggleBtn i").removeClass("bi-sun-fill").addClass("bi-moon-fill");

            // Update editor theme
            $('.note-editable').css('background-color', '#ffffff');
            $('.note-editable').css('color', '#000000');
        }
    });

    // Load theme preference from local storage
    if (localStorage.getItem("theme") === "dark") {
        document.documentElement.setAttribute("data-bs-theme", "dark");
        $("#themeToggleText").text("Light Mode");
        $("#themeToggleBtn i").removeClass("bi-moon-fill").addClass("bi-sun-fill");
    }

    // Handle email form submission
    $('#emailForm').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $resultModal = $('#resultModal');
        const $resultContent = $('#resultModalContent');

        // Get message from Summernote or fallback to textarea
        let message;
        if ($('#message').next('.note-editor').length) {
            message = $('#message').summernote('code');
        } else {
            message = $('#message').val();
        }

        // Disable submit button and show loading indicator
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');

        // Prepare form data
        const formData = new FormData($form[0]);
        formData.set('message', message);

        // Display all form data being sent for debugging
        console.log('Sending form data:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Send AJAX request
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 60000, // 60 second timeout
            success: function (response) {
                console.log('Server response:', response);

                // Show result in modal
                if (response.status === 'success') {
                    let successContent = `
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Success</h5>
                            <p>${response.message}</p>
                        </div>
                    `;

                    // Add Message-ID info if available for reply tracking
                    if (response.messageId) {
                        successContent += `
                            <div class="alert alert-info">
                                <h6>For Future Replies:</h6>
                                <p>Copy this Message-ID to use in the "References" and "In-Reply-To" fields:</p>
                                <input type="text" class="form-control mb-2" value="${response.messageId}" readonly onclick="this.select()">
                                <small class="text-muted">Click to select. This ID helps maintain proper email threading.</small>
                            </div>
                        `;
                    }

                    $resultContent.html(successContent);

                    // Reset form on success
                    $form[0].reset();
                    if ($('#message').next('.note-editor').length) {
                        $('#message').summernote('code', '');
                    }
                } else {
                    $resultContent.html(`
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-circle"></i> Error</h5>
                            <p>${response.message}</p>
                        </div>
                    `);
                }

                // Show modal with result
                $resultModal.modal('show');
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                console.log('Response status:', xhr.status);
                console.log('Response text:', xhr.responseText?.substring(0, 1000));

                let errorDetails = '';

                // Try to extract error message from HTML response if it's not valid JSON
                try {
                    // Try to parse as JSON first
                    const errorObj = JSON.parse(xhr.responseText);
                    errorDetails = errorObj.message || error;
                } catch (e) {
                    // If not JSON, try to extract error from HTML
                    const htmlMatch = xhr.responseText?.match(/<b>([^<]+)<\/b> on line/);
                    if (htmlMatch && htmlMatch[1]) {
                        errorDetails = "PHP Error: " + htmlMatch[1];
                    } else {
                        // If all else fails, provide generic error with status
                        errorDetails = error + " (Status: " + xhr.status + ")";
                    }
                }

                // Show error in modal
                $resultContent.html(`
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Error</h5>
                        <p>Failed to send email: ${errorDetails}</p>
                        <p>Status: ${status}</p>
                        <div class="mt-3">
                            <h6>Troubleshooting steps:</h6>
                            <ol>
                                <li>Check your SMTP configuration settings</li>
                                <li>Verify the recipient email address is valid</li>
                                <li>Check server logs for more information</li>
                                <li>Try with a smaller attachment or no attachment</li>
                            </ol>
                        </div>
                    </div>
                `);

                // Show modal with error
                $resultModal.modal('show');
            },
            complete: function () {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).html('Send Email');
            }
        });
    });

    // Handle domain security analysis form submission
    $("#analyzeForm").on("submit", function (e) {
        e.preventDefault();

        const domain = $("#domainToCheck").val().trim();
        if (!domain) {
            return;
        }

        // Show loading indicators
        $("#analyzeResults").removeClass("d-none");
        $("#resultsDomain").text(domain);
        $("#spfStatus, #dmarcStatus, #mxStatus").html('<span class="spinner-border spinner-border-sm" role="status"></span>');
        $("#spfRecord, #dmarcRecord, #mxRecords").text("Loading...");
        $("#vulnerabilityScore").text("--/10");
        $("#vulnerabilityProgress").css("width", "0%").removeClass("bg-success bg-warning bg-danger");
        $("#spoofableStatus").removeClass("alert-success alert-warning alert-danger").addClass("alert-info").html('<span class="spinner-border spinner-border-sm me-2" role="status"></span> Analyzing domain security...');

        // Get the CSRF token
        const csrfToken = $("#analyzeForm input[name='csrf_token']").val();
        console.log("Using CSRF token:", csrfToken); // Debug log

        // Send AJAX request
        $.ajax({
            url: "index.php",
            type: "POST",
            data: {
                action: "domain_analysis",
                form_name: "analyze", // Make sure form_name is set correctly
                domain: domain,
                csrf_token: csrfToken
            },
            success: function (response) {
                try {
                    console.log("Response received:", response); // Debug log
                    const result = typeof response === 'string' ? JSON.parse(response) : response;

                    if (result.status === 'success') {
                        // Update SPF status
                        if (result.spf.exists) {
                            if (result.spf.policy === 'restrictive') {
                                $("#spfStatus").html('<span class="badge bg-success">Protected</span>');
                            } else if (result.spf.policy === 'neutral') {
                                $("#spfStatus").html('<span class="badge bg-warning">Neutral</span>');
                            } else {
                                $("#spfStatus").html('<span class="badge bg-danger">Permissive</span>');
                            }
                            $("#spfRecord").text(result.spf.record || "None found");
                        } else {
                            $("#spfStatus").html('<span class="badge bg-danger">Missing</span>');
                            $("#spfRecord").text("No SPF record found");
                        }

                        // Update DMARC status
                        if (result.dmarc.exists) {
                            if (result.dmarc.policy_value === 'reject') {
                                $("#dmarcStatus").html('<span class="badge bg-success">Reject</span>');
                            } else if (result.dmarc.policy_value === 'quarantine') {
                                $("#dmarcStatus").html('<span class="badge bg-warning">Quarantine</span>');
                            } else {
                                $("#dmarcStatus").html('<span class="badge bg-danger">None</span>');
                            }
                            $("#dmarcRecord").text(result.dmarc.record || "None found");
                        } else {
                            $("#dmarcStatus").html('<span class="badge bg-danger">Missing</span>');
                            $("#dmarcRecord").text("No DMARC record found");
                        }

                        // Update MX status
                        if (result.mx.exists) {
                            $("#mxStatus").html('<span class="badge bg-success">Found</span>');
                            $("#mxRecords").text(result.mx.records.join("\n") || "None found");
                        } else {
                            $("#mxStatus").html('<span class="badge bg-danger">Missing</span>');
                            $("#mxRecords").text("No MX records found");
                        }

                        // Update vulnerability score
                        const score = result.vulnerability_score;
                        $("#vulnerabilityScore").text(score + "/10");

                        const percentage = (score / 10) * 100;
                        $("#vulnerabilityProgress").css("width", percentage + "%");

                        if (score >= 7) {
                            $("#vulnerabilityProgress").removeClass("bg-warning bg-danger").addClass("bg-success");
                        } else if (score >= 4) {
                            $("#vulnerabilityProgress").removeClass("bg-success bg-danger").addClass("bg-warning");
                        } else {
                            $("#vulnerabilityProgress").removeClass("bg-success bg-warning").addClass("bg-danger");
                        }

                        // Update spoofable status
                        if (result.spoofable) {
                            $("#spoofableStatus").removeClass("alert-info alert-success alert-warning").addClass("alert-danger")
                                .html('<i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Potentially Spoofable:</strong> This domain may be vulnerable to email spoofing.');
                        } else {
                            $("#spoofableStatus").removeClass("alert-info alert-danger alert-warning").addClass("alert-success")
                                .html('<i class="bi bi-shield-fill-check me-2"></i><strong>Protected:</strong> This domain has proper email security protections in place.');
                        }
                    } else {
                        // Error in analysis
                        $("#spfStatus, #dmarcStatus, #mxStatus").html('<span class="badge bg-secondary">Error</span>');
                        $("#spfRecord, #dmarcRecord, #mxRecords").text("Analysis failed");
                        $("#vulnerabilityScore").text("--/10");
                        $("#vulnerabilityProgress").css("width", "0%");
                        $("#spoofableStatus").removeClass("alert-success alert-info alert-danger").addClass("alert-warning")
                            .html('<i class="bi bi-exclamation-triangle-fill me-2"></i>' + (result.message || "An error occurred during analysis."));
                    }
                } catch (e) {
                    // JSON parsing error
                    console.error("JSON parsing error:", e, response); // Debug log
                    $("#spfStatus, #dmarcStatus, #mxStatus").html('<span class="badge bg-secondary">Error</span>');
                    $("#spfRecord, #dmarcRecord, #mxRecords").text("Failed to parse response");
                    $("#vulnerabilityScore").text("--/10");
                    $("#vulnerabilityProgress").css("width", "0%");
                    $("#spoofableStatus").removeClass("alert-success alert-info alert-danger").addClass("alert-warning")
                        .html('<i class="bi bi-exclamation-triangle-fill me-2"></i>Failed to process server response.');
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX error:", status, error, xhr.responseText); // Debug log
                $("#spfStatus, #dmarcStatus, #mxStatus").html('<span class="badge bg-secondary">Error</span>');
                $("#spfRecord, #dmarcRecord, #mxRecords").text("Request failed");
                $("#vulnerabilityScore").text("--/10");
                $("#vulnerabilityProgress").css("width", "0%");
                $("#spoofableStatus").removeClass("alert-success alert-info alert-danger").addClass("alert-warning")
                    .html('<i class="bi bi-exclamation-triangle-fill me-2"></i>Request failed: ' + error);
            }
        });
    });

    // Handle "Check Domain" button click
    $("#analyzeBtn").on("click", function () {
        const fromEmail = $("#fromEmail").val().trim();
        if (fromEmail) {
            // Extract domain from email
            const domain = fromEmail.split('@')[1];
            if (domain) {
                $("#domainToCheck").val(domain);
                $("#analyzeForm").submit();
            }
        }
    });

    // Handle template selection button
    $("#templateBtn").on("click", function () {
        $("#templateModal").modal("show");
    });

    // Handle template selection in sidebar
    $(".template-item, .template-modal-item").on("click", function () {
        const templateId = $(this).data("template");
        loadEmailTemplate(templateId);

        // Close the modal if it's open
        $("#templateModal").modal("hide");
    });

    // Function to load an email template
    function loadEmailTemplate(templateId) {
        if (!templateId) return;

        $.ajax({
            url: "index.php",
            type: "POST",
            data: {
                action: "get_email_template",
                template_id: templateId,
                csrf_token: $("#emailForm input[name='csrf_token']").val()
            },
            success: function (response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;

                    if (result.success && result.template) {
                        // Set the subject
                        $("#subject").val(result.template.subject);

                        // Set the message content (considering rich text editor)
                        if ($("#message").summernote) {
                            $("#message").summernote('code', result.template.message);
                        } else {
                            $("#message").val(result.template.message);
                        }
                    }
                } catch (e) {
                    // JSON parsing error
                    console.error("Failed to load template:", e);
                }
            },
            error: function (xhr, status, error) {
                console.error("Template request failed:", error);
            }
        });
    }

    // Utility function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Direct click handler as backup for settings button
    document.getElementById('settingsBtn').addEventListener('click', function () {
        console.log("Direct click handler fired");
        var settingsModal = new bootstrap.Modal(document.getElementById('settingsModal'));
        settingsModal.show();
    });

    // Function to show alert messages
    function showAlert(type, message) {
        const alertElement = $('<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>');

        // Add alert to page
        $('#alert-container').append(alertElement);

        // Auto-dismiss after 5 seconds
        setTimeout(function () {
            alertElement.alert('close');
        }, 5000);
    }

    // Handle file input change - show file names
    $('#attachment').on('change', function () {
        const fileNames = Array.from(this.files).map(file => file.name).join(', ');
        $('#attachment-label').text(fileNames || 'Choose file(s)');
    });

    // Add validation for Message-IDs in the References and In-Reply-To fields
    function validateMessageId(id) {
        // Basic format check for a Message-ID: should be enclosed in angle brackets and contain an @ symbol
        if (!id) return true; // Empty is valid

        id = id.trim();

        // Add angle brackets if missing
        if (id && !id.startsWith('<')) {
            id = '<' + id;
        }

        if (id && !id.endsWith('>')) {
            id = id + '>';
        }

        // Should contain @ symbol
        if (!id.includes('@')) {
            return false;
        }

        return true;
    }

    // Format Message-IDs on blur to ensure proper format
    $('#references, #in_reply_to').on('blur', function () {
        let val = $(this).val().trim();
        console.log('Formatting Message-ID: ' + val);

        if (val) {
            // Add angle brackets if missing
            if (!val.startsWith('<')) {
                val = '<' + val;
            }
            if (!val.endsWith('>')) {
                val = val + '>';
            }
            $(this).val(val);
            console.log('Formatted Message-ID: ' + val);
        }
    });

    // Additional validation before form submission
    $('#emailForm').on('submit', function (e) {
        // Format Message-IDs if present
        let references = $('#references').val().trim();
        let inReplyTo = $('#in_reply_to').val().trim();

        console.log('Form submission - References: ' + references);
        console.log('Form submission - In-Reply-To: ' + inReplyTo);

        // Auto-copy References to In-Reply-To if In-Reply-To is empty but References is not
        if (!inReplyTo && references) {
            $('#in_reply_to').val(references);
            console.log('Copied References to empty In-Reply-To: ' + references);
        }

        // Format both fields
        if (references) {
            if (!references.startsWith('<')) references = '<' + references;
            if (!references.endsWith('>')) references += '>';
            $('#references').val(references);
        }

        if (inReplyTo) {
            if (!inReplyTo.startsWith('<')) inReplyTo = '<' + inReplyTo;
            if (!inReplyTo.endsWith('>')) inReplyTo += '>';
            $('#in_reply_to').val(inReplyTo);
        }

        console.log('Final References: ' + $('#references').val());
        console.log('Final In-Reply-To: ' + $('#in_reply_to').val());
    });

    // Show/hide reply tools when subject starts with Re:
    $('#subject').on('input', function () {
        let subject = $(this).val();

        if (subject.toLowerCase().startsWith('re:')) {
            $('.threading-fields').addClass('border-warning').removeClass('border-left-info');
            $('#threadingInfo').removeClass('d-none');
            $('.reply-tools').removeClass('d-none');
            console.log('Reply mode activated - showing threading fields and reply tools');
        } else {
            $('.threading-fields').removeClass('border-warning').addClass('border-left-info');
            $('#threadingInfo').addClass('d-none');
            $('.reply-tools').addClass('d-none');
            console.log('Regular email mode - hiding threading fields and reply tools');
        }
    });

    // Reply helper functions
    $('#generateFormattedReply').on('click', function () {
        const previousContent = $('#previousEmailContent').val().trim();
        const previousSender = $('#previousSender').val().trim();
        const previousDate = $('#previousDate').val().trim();
        const yourReply = $('#yourReplyContent').val().trim();

        if (!previousContent) {
            alert('Please paste the previous email content');
            return;
        }

        // Format the previous content with > at the beginning of each line
        const quotedContent = previousContent.split('\n').map(line => '> ' + line).join('\n');

        // Create the formatted reply with proper threading format
        let formattedReply = yourReply + '\n\n';

        if (previousSender && previousDate) {
            formattedReply += `On ${previousDate}, ${previousSender} wrote:\n`;
        } else if (previousSender) {
            formattedReply += `On a previous date, ${previousSender} wrote:\n`;
        } else if (previousDate) {
            formattedReply += `On ${previousDate}, the previous sender wrote:\n`;
        } else {
            formattedReply += 'In a previous message:\n';
        }

        formattedReply += quotedContent;

        // Show the preview
        $('#formattedReplyPreview').val(formattedReply);
        console.log('Generated formatted reply with quoted content');
    });

    // Use the formatted reply
    $('#useFormattedReply').on('click', function () {
        const formattedReply = $('#formattedReplyPreview').val();
        if (formattedReply) {
            $('#message').val(formattedReply);
            console.log('Applied formatted reply to message field');
        }
    });

    // Helper to extract and format Message-ID from an email header
    $('#extractMessageIdBtn').on('click', function () {
        const headerContent = $('#emailHeaderContent').val().trim();
        if (!headerContent) {
            alert('Please paste the email headers first');
            return;
        }

        // Try to extract Message-ID using regex
        const messageIdMatch = headerContent.match(/Message-ID:\s*(<[^>]+>)/i);
        if (messageIdMatch && messageIdMatch[1]) {
            const messageId = messageIdMatch[1].trim();
            $('#extractedMessageId').val(messageId);
            console.log('Extracted Message-ID: ' + messageId);

            // Ask if user wants to use this for References and In-Reply-To
            if (confirm('Do you want to use this Message-ID for References and In-Reply-To fields?')) {
                $('#references').val(messageId);
                $('#in_reply_to').val(messageId);
                console.log('Applied Message-ID to References and In-Reply-To fields');
            }
        } else {
            alert('No Message-ID found in the headers. Look for a line that starts with "Message-ID:" or "Message-Id:"');
        }
    });

    // Copy extracted Message-ID to clipboard
    $('#copyExtractedMessageId').on('click', function () {
        const messageId = $('#extractedMessageId').val();
        if (messageId) {
            // Create a temporary input element to copy from
            const tempInput = document.createElement('input');
            tempInput.value = messageId;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            // Show a temporary "Copied!" feedback
            const $btn = $(this);
            const originalText = $btn.text();
            $btn.text('Copied!');
            setTimeout(function () {
                $btn.text(originalText);
            }, 2000);

            console.log('Copied Message-ID to clipboard: ' + messageId);
        }
    });

    // Raw Email Form submission
    $('#rawEmailForm').on('submit', function (e) {
        e.preventDefault();

        // Check if file is selected
        if (!$('#emlFile').val()) {
            alert('Please select an .eml file to upload');
            return false;
        }

        const form = $(this)[0];
        const formData = new FormData(form);

        // Show loading indicator
        $('#loadingModal').modal('show');

        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                $('#loadingModal').modal('hide');

                if (response.success) {
                    // Success
                    const messageId = response.message_id || '';

                    let successMessage = 'Email sent successfully!';
                    if (messageId) {
                        successMessage += '<br><br>Message-ID: <code>' + messageId + '</code>';
                    }

                    $('#resultModalLabel').text('Success');
                    $('#resultModalBody').html('<div class="alert alert-success">' + successMessage + '</div>');
                    $('#resultModal').modal('show');

                    // Reset form
                    form.reset();
                    $('#emailFields').addClass('d-none');
                } else {
                    // Error
                    $('#resultModalLabel').text('Error');
                    $('#resultModalBody').html('<div class="alert alert-danger">' + response.message + '</div>');
                    $('#resultModal').modal('show');
                }
            },
            error: function (xhr, status, error) {
                $('#loadingModal').modal('hide');

                console.error('Error:', xhr, status, error);

                // Try to parse the error response
                let errorMessage = 'An error occurred while sending the email.';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse && errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                } catch (e) {
                    if (xhr.responseText) {
                        errorMessage = xhr.responseText;
                    }
                }

                $('#resultModalLabel').text('Error');
                $('#resultModalBody').html('<div class="alert alert-danger">' + errorMessage + '</div>');
                $('#resultModal').modal('show');
            }
        });
    });

    // Handle exporting templates
    $('#exportTemplatesBtn').on('click', function () {
        window.location.href = 'index.php?action=export_templates&csrf_token=' + $('input[name="csrf_token"]').val();
    });

    // Modal template panel handling
    $('.template-panel-btn').on('click', function (e) {
        e.preventDefault();
        const panel = $(this).data('panel');
        $('.template-panel').addClass('d-none');
        $('#' + panel).removeClass('d-none');
    });

    // Handle user analysis when using raw email mode
    $('#analyzeBtn').on('click', function () {
        const emailRaw = $('#emailRaw').val();
        if (!emailRaw) {
            alert('Please paste a raw email to analyze.');
            return;
        }

        // Show loading state
        $('#analyzeResult').html('<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Analyzing email...</p></div>');

        // Send to server for analysis
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: {
                action: 'analyze_raw_email',
                email_raw: emailRaw,
                csrf_token: $('input[name="csrf_token"]').val()
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#analyzeResult').html(response.html);
                } else {
                    $('#analyzeResult').html('<div class="alert alert-danger">' +
                        '<i class="bi bi-exclamation-circle-fill me-2"></i>' +
                        'Error analyzing email: ' + (response.message || 'Unknown error') + '</div>');
                }
            },
            error: function (xhr, status, error) {
                let errorMessage = 'Server error: ' + error;

                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                    } catch (e) {
                        // If JSON parsing fails, try to extract error message from HTML
                        const htmlMatch = xhr.responseText.match(/<b>([^<]+)<\/b> on line/);
                        if (htmlMatch && htmlMatch[1]) {
                            errorMessage = "PHP Error: " + htmlMatch[1];
                        }
                    }
                }

                $('#resultModalLabel').text('Error');
                $('#resultModalBody').html('<div class="alert alert-danger">' + errorMessage + '</div>');
                $('#resultModal').modal('show');
            }
        });
    });

    // Handle SMTP Settings Modal form submission
    $('#smtpSettingsForm').on('submit', function (e) {
        e.preventDefault();
        console.log("SMTP settings modal form submitted");

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $resultBox = $('#settingsSmtpTestResult');

        // Show form data being sent
        const formData = new FormData($form[0]);
        console.log("SMTP settings form data:");
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Show loading
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $resultBox.removeClass('d-none alert-success alert-danger').addClass('alert-info').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving SMTP settings...');

        // Send AJAX request
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                console.log("SMTP settings save response:", response);

                // Parse response if needed
                let result = response;
                if (typeof response === 'string') {
                    try {
                        result = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse response:', e);
                    }
                }

                // Show result
                if (result.status === 'success') {
                    $resultBox.removeClass('d-none alert-info alert-danger').addClass('alert-success').html(`
                        <i class="bi bi-check-circle-fill me-2"></i> ${result.message}
                    `);

                    // Reload the page to reflect updated settings
                    setTimeout(function () {
                        window.location.reload();
                    }, 1500);
                } else {
                    $resultBox.removeClass('d-none alert-info alert-success').addClass('alert-danger').html(`
                        <i class="bi bi-exclamation-circle-fill me-2"></i> ${result.message}
                    `);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error saving SMTP settings:', status, error);
                $resultBox.removeClass('d-none alert-info alert-success').addClass('alert-danger').html(`
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Error: ${error}
                `);
            },
            complete: function () {
                $submitBtn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Save Settings');
            }
        });
    });

    // Handle SMTP Profile Save Form
    $('#saveProfileForm').on('submit', function (e) {
        e.preventDefault();
        console.log("Save SMTP profile form submitted");

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const profileName = $('#profile_name').val().trim();

        if (!profileName) {
            alert('Please enter a profile name');
            return;
        }

        // Show loading
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        // Create form data
        const formData = new FormData();
        formData.append('action', 'save_smtp_profile');
        formData.append('csrf_token', '1234567890abcdef1234567890abcdef');
        formData.append('profile_name', profileName);
        formData.append('form_name', 'save_smtp_profile');

        // Add sender email if provided
        const senderEmail = $('#sender_email').val().trim();
        if (senderEmail) {
            formData.append('sender_email', senderEmail);
        }

        // Log data being sent
        console.log("Save SMTP profile data:");
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Send AJAX request
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                console.log("Save profile response:", response);

                // Parse response if needed
                let result = response;
                if (typeof response === 'string') {
                    try {
                        result = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse save profile response:', e);
                    }
                }

                if (result.status === 'success') {
                    // Close the modal
                    if (bootstrap && bootstrap.Modal) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('newProfileModal'));
                        if (modal) modal.hide();
                    }

                    // Show success message
                    alert(result.message);

                    // Reload the page to reflect new profile
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to save profile'));
                }
            },
            error: function (xhr, status, error) {
                console.error('Error saving profile:', status, error);
                alert('Error saving profile: ' + error);
            },
            complete: function () {
                $submitBtn.prop('disabled', false).html('Save Profile');
            }
        });
    });

    // Handle Load Profile Button Click
    $(document).on('click', '.load-profile-btn', function () {
        const profileName = $(this).data('profile');
        console.log("Loading SMTP profile:", profileName);

        if (!profileName) {
            alert('Invalid profile selected');
            return;
        }

        if (!confirm('Load profile "' + profileName + '"? This will replace your current SMTP settings.')) {
            return;
        }

        // Create form data
        const formData = new FormData();
        formData.set('action', 'load_smtp_profile');
        formData.set('profile_name', profileName);
        formData.set('csrf_token', '1234567890abcdef1234567890abcdef');

        // Show loading
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

        // Send AJAX request
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                console.log("Load profile response:", response);

                // Parse response if needed
                let result = response;
                if (typeof response === 'string') {
                    try {
                        result = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse load profile response:', e);
                    }
                }

                if (result.status === 'success') {
                    alert(result.message);
                    // Reload the page to reflect loaded profile
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to load profile'));
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading profile:', status, error);
                alert('Error loading profile: ' + error);
            },
            complete: function () {
                $('.load-profile-btn').prop('disabled', false).html('<i class="bi bi-arrow-down-circle"></i> Load');
            }
        });
    });

    // Handle Delete Profile Button Click
    $(document).on('click', '.delete-profile-btn', function () {
        const profileName = $(this).data('profile');
        console.log("Opening delete confirmation for profile:", profileName);

        if (!profileName) {
            alert('Invalid profile selected');
            return;
        }

        // Set profile name in confirmation modal
        $('#profileToDelete').text(profileName);

        // Store profile name for the confirm button
        $('#confirmDeleteProfile').data('profile', profileName);

        // Show confirmation modal
        if (bootstrap && bootstrap.Modal) {
            const modal = new bootstrap.Modal(document.getElementById('deleteProfileModal'));
            modal.show();
        } else {
            // Fallback if bootstrap modal isn't available
            if (confirm('Are you sure you want to delete the profile "' + profileName + '"? This action cannot be undone.')) {
                deleteProfile(profileName);
            }
        }
    });

    // Handle Confirm Delete Button Click
    $('#confirmDeleteProfile').on('click', function () {
        const profileName = $(this).data('profile');
        console.log("Confirming deletion of profile:", profileName);

        if (!profileName) {
            alert('Invalid profile selected');
            return;
        }

        deleteProfile(profileName);
    });

    // Function to delete a profile
    function deleteProfile(profileName) {
        // Create form data
        const formData = new FormData();
        formData.set('action', 'delete_smtp_profile');
        formData.set('profile_name', profileName);
        formData.set('csrf_token', '1234567890abcdef1234567890abcdef');

        // Show loading
        $('#confirmDeleteProfile').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...');

        // Send AJAX request
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                console.log("Delete profile response:", response);

                // Parse response if needed
                let result = response;
                if (typeof response === 'string') {
                    try {
                        result = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse delete profile response:', e);
                    }
                }

                // Hide the modal
                if (bootstrap && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteProfileModal'));
                    if (modal) modal.hide();
                }

                if (result.status === 'success') {
                    alert(result.message);
                    // Reload the page to reflect deleted profile
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to delete profile'));
                }
            },
            error: function (xhr, status, error) {
                console.error('Error deleting profile:', status, error);
                alert('Error deleting profile: ' + error);
            },
            complete: function () {
                $('#confirmDeleteProfile').prop('disabled', false).html('Delete Profile');
            }
        });
    }

    // Initialize tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Handle SMTP Profile selection in email form
    $('#smtpProfile').on('change', function () {
        const selectedOption = $(this).find('option:selected');
        const senderEmail = selectedOption.data('sender');
        const profileName = selectedOption.val();

        console.log('SMTP Profile selected:', profileName);
        console.log('Default sender email:', senderEmail);

        if (profileName && senderEmail) {
            // Auto-fill sender email if available
            $('#fromEmail').val(senderEmail);
            console.log('Auto-filled sender email:', senderEmail);

            // Notify user
            alert('Selected profile "' + profileName + '". Sender email has been set to the profile default: ' + senderEmail);
        } else if (profileName) {
            // Just notify about selection without changing email
            alert('Selected profile "' + profileName + '". No default sender email is configured for this profile.');
        }

        if (profileName) {
            // Load the profile via AJAX
            const formData = new FormData();
            formData.set('action', 'load_smtp_profile');
            formData.set('profile_name', profileName);
            formData.set('csrf_token', '1234567890abcdef1234567890abcdef');

            // Show loading indicator
            const originalText = $('#smtpSection h6').text();
            $('#smtpSection h6').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading profile...');

            $.ajax({
                url: 'index.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    console.log("Load profile response:", response);

                    // Just refresh the page to show updated settings
                    window.location.reload();
                },
                error: function (xhr, status, error) {
                    console.error('Error loading profile:', status, error);
                    alert('Error loading profile: ' + error);
                    // Restore original heading
                    $('#smtpSection h6').text(originalText);
                }
            });
        }
    });

    // Add direct handler for new profile button
    $(document).on('click', 'button[data-bs-target="#newProfileModal"]', function (e) {
        console.log("New Profile button clicked");
        if (bootstrap && bootstrap.Modal) {
            const newProfileModal = new bootstrap.Modal(document.getElementById('newProfileModal'));
            newProfileModal.show();
        } else {
            console.error("Bootstrap Modal is not available - Check if Bootstrap JS is loaded");
            alert("Error: Could not open the modal. Please check console for details.");
        }
    });
});

// Format date as RFC 2822
function formatDateRFC2822() {
    // Implementation of formatDateRFC2822 function
} 