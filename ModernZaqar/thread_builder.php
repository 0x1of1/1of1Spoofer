<?php
// Thread Builder feature - allows manual creation of email conversation threads
require_once 'config.php';
require_once 'includes/utils.php';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thread Builder - 1of1spoofer</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-envelope-fill me-2"></i>
                1of1spoofer
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="thread_builder.php">
                            <i class="bi bi-chat-quote me-1"></i> Thread Builder
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-envelope me-1"></i> Email Spoofing
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

        <div class="container mt-4">
            <h2>Email Thread Builder</h2>
            <p class="lead">Create a conversation thread by manually adding messages</p>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    Thread Preview
                </div>
                <div class="card-body thread-preview" id="threadPreview">
                    <div class="alert alert-info">Your thread will appear here as you add messages</div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    Add Message to Thread
                </div>
                <div class="card-body">
                    <form id="threadMessageForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sender_name" class="form-label">Sender Name</label>
                                <input type="text" class="form-control" id="sender_name" name="sender_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="sender_email" class="form-label">Sender Email</label>
                                <input type="email" class="form-control" id="sender_email" name="sender_email" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="reply_to_email" class="form-label">Reply-To Email (Camouflaged)</label>
                                <input type="email" class="form-control" id="reply_to_email" name="reply_to_email">
                                <div class="form-text">Enter YOUR email here to receive replies. With our enhanced camouflage, the recipient will see the spoofed sender's name and address when they reply, but responses will actually come to you.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="to_email" class="form-label">To Email</label>
                                <input type="email" class="form-control" id="to_email" name="to_email" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="message_date" class="form-label">Date & Time</label>
                                <input type="text" class="form-control" id="message_date" name="message_date"
                                    value="<?php echo date('D, j M Y H:i:s O'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="message_body" class="form-label">Message Body</label>
                            <textarea class="form-control" id="message_body" name="message_body" rows="5" required></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-success" id="addMessageBtn">Add to Thread</button>
                            <button type="button" class="btn btn-warning" id="clearThreadBtn">Clear Thread</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-primary text-white">
                    Final Thread Actions
                </div>
                <div class="card-body">
                    <form id="finalThreadForm" action="index.php" method="post">
                        <input type="hidden" name="thread_data" id="thread_data">
                        <div class="mb-3">
                            <label for="from_email" class="form-label">From Email (for sending)</label>
                            <input type="email" class="form-control" id="from_email" name="from_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="from_name" class="form-label">From Name (for sending)</label>
                            <input type="text" class="form-control" id="from_name" name="from_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="to_send_email" class="form-label">Send To Email</label>
                            <input type="email" class="form-control" id="to_send_email" name="to" required>
                        </div>
                        <div class="mb-3">
                            <label for="reply_to" class="form-label">Reply-To Email (Camouflaged)</label>
                            <input type="email" class="form-control" id="reply_to" name="reply_to">
                            <div class="form-text">Enter YOUR email here to receive replies. With our enhanced camouflage, the recipient will see the spoofed sender's name and address when they reply, but responses will actually come to you.</div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary" name="action" value="send_thread">Send This Thread</button>
                            <button type="button" class="btn btn-secondary" id="exportThreadBtn">Export Thread</button>
                        </div>
                    </form>
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

    <style>
        .thread-preview {
            max-height: 500px;
            overflow-y: auto;
        }

        .email-message {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .email-message.latest {
            background-color: #fff;
            border-color: #007bff;
        }

        .email-header {
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }

        .email-divider {
            margin-top: 15px;
            color: #666;
            font-style: italic;
        }

        .quoted-text {
            color: #666;
            border-left: 3px solid #ccc;
            padding-left: 10px;
            margin: 5px 0;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let threadMessages = [];

            // Add a message to the thread
            $("#addMessageBtn").click(function() {
                const message = {
                    sender_name: $("#sender_name").val(),
                    sender_email: $("#sender_email").val(),
                    reply_to_email: $("#reply_to_email").val(),
                    to_email: $("#to_email").val(),
                    date: $("#message_date").val(),
                    subject: $("#subject").val(),
                    body: $("#message_body").val()
                };

                // Validate
                if (!message.sender_name || !message.sender_email || !message.subject || !message.body) {
                    alert("Please fill in all required fields");
                    return;
                }

                // Add to array and update preview
                threadMessages.push(message);
                updateThreadPreview();

                // Clear form
                $("#message_body").val("");

                // Update hidden field for submission
                $("#thread_data").val(JSON.stringify(threadMessages));
            });

            // Clear the entire thread
            $("#clearThreadBtn").click(function() {
                if (confirm("Are you sure you want to clear the entire thread?")) {
                    threadMessages = [];
                    updateThreadPreview();
                    $("#thread_data").val("");
                }
            });

            // Export the thread
            $("#exportThreadBtn").click(function() {
                if (threadMessages.length === 0) {
                    alert("Thread is empty. Add some messages first.");
                    return;
                }

                // Format the thread into a single text
                let threadText = formatThreadForExport();

                // Create a download file
                const blob = new Blob([threadText], {
                    type: "text/plain"
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = "email_thread.txt";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });

            function updateThreadPreview() {
                if (threadMessages.length === 0) {
                    $("#threadPreview").html('<div class="alert alert-info">Your thread will appear here as you add messages</div>');
                    return;
                }

                let preview = "";

                // Loop through messages in reverse (newest at top)
                for (let i = threadMessages.length - 1; i >= 0; i--) {
                    const msg = threadMessages[i];
                    const isLatest = (i === threadMessages.length - 1);

                    preview += `
                    <div class="email-message ${isLatest ? "latest" : "previous"} mb-4">
                        <div class="email-header">
                            <div><strong>From:</strong> ${escapeHtml(msg.sender_name)} &lt;${escapeHtml(msg.sender_email)}&gt;</div>
                            <div><strong>To:</strong> ${escapeHtml(msg.to_email)}</div>
                            <div><strong>Date:</strong> ${escapeHtml(msg.date)}</div>
                            <div><strong>Subject:</strong> ${escapeHtml(msg.subject)}</div>
                        </div>
                        <div class="email-body mt-2">
                            ${formatEmailBody(msg.body, i)}
                        </div>
                        ${i > 0 ? '<div class="email-divider">--- Original Message ---</div>' : ""}
                    </div>
                `;
                }

                $("#threadPreview").html(preview);
            }

            function formatEmailBody(text, index) {
                // If this isn't the most recent message, add the quote marks
                if (index < threadMessages.length - 1) {
                    const lines = text.split("\n");
                    return lines.map(line => `<div class="quoted-text">&gt; ${escapeHtml(line)}</div>`).join("");
                } else {
                    // For the most recent message, just format with proper line breaks
                    return escapeHtml(text).replace(/\n/g, "<br>");
                }
            }

            function formatThreadForExport() {
                let result = "";

                // Format in chronological order (oldest first)
                for (let i = 0; i < threadMessages.length; i++) {
                    const msg = threadMessages[i];
                    result += `From: ${msg.sender_name} <${msg.sender_email}>\n`;
                    result += `To: ${msg.to_email}\n`;
                    result += `Date: ${msg.date}\n`;
                    result += `Subject: ${msg.subject}\n\n`;

                    // Add quoting for previous messages
                    let body = msg.body;
                    if (i > 0) {
                        // Add quote symbols to previous messages when referenced
                        const lines = body.split("\n");
                        body = lines.map(line => line).join("\n");

                        // Add reference to previous message
                        body += "\n\n";
                        body += `On ${threadMessages[i-1].date}, ${threadMessages[i-1].sender_name} <${threadMessages[i-1].sender_email}> wrote:\n`;

                        const prevLines = threadMessages[i - 1].body.split("\n");
                        body += prevLines.map(line => `> ${line}`).join("\n");
                    }

                    result += body + "\n\n";

                    if (i < threadMessages.length - 1) {
                        result += "---\n\n";
                    }
                }

                return result;
            }

            function escapeHtml(text) {
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
</body>

</html>