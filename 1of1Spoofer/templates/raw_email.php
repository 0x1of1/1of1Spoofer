<?php
// Prevent direct access
if (!defined('MODERN_ZAQAR')) {
    http_response_code(403);
    exit('No direct script access allowed');
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Raw Email Mode (Advanced Threading)</h6>
                </div>
                <div class="card-body">
                    <p>This mode allows you to upload an existing email file (.eml) and modify specific fields while preserving the original email structure including all threading headers.</p>
                    
                    <form id="rawEmailForm" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="send_raw_email">
                        
                        <div class="mb-3">
                            <label for="emlFile" class="form-label">Upload Email File (.eml)</label>
                            <input type="file" class="form-control" id="emlFile" name="emlFile" accept=".eml" required>
                            <div class="form-text">
                                Upload a complete email file (.eml format) exported from your email client. 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#emlHelpModal">How to get an .eml file?</a>
                            </div>
                        </div>
                        
                        <div id="emailFields" class="d-none">
                            <hr>
                            <h5>Modify Email Fields</h5>
                            <p class="text-muted">Only these fields will be changed. All other headers and structure will be preserved.</p>
                            
                            <div class="mb-3">
                                <label for="rawFromEmail" class="form-label">From Email</label>
                                <input type="email" class="form-control" id="rawFromEmail" name="rawFromEmail" placeholder="New sender email">
                            </div>
                            
                            <div class="mb-3">
                                <label for="rawFromName" class="form-label">From Name</label>
                                <input type="text" class="form-control" id="rawFromName" name="rawFromName" placeholder="New sender name">
                            </div>
                            
                            <div class="mb-3">
                                <label for="rawToEmail" class="form-label">To Email</label>
                                <input type="email" class="form-control" id="rawToEmail" name="rawToEmail" placeholder="New recipient email">
                            </div>
                            
                            <div class="mb-3">
                                <label for="rawSubject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="rawSubject" name="rawSubject" placeholder="New subject (leave empty to keep original)">
                            </div>
                            
                            <div class="mb-3">
                                <label for="rawMessage" class="form-label">Message Body</label>
                                <textarea class="form-control" id="rawMessage" name="rawMessage" rows="10" placeholder="New message body (leave empty to keep original)"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="preserveRecipients" name="preserveRecipients" checked>
                                    <label class="form-check-label" for="preserveRecipients">
                                        Preserve CC/BCC recipients
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="preserveAttachments" name="preserveAttachments" checked>
                                    <label class="form-check-label" for="preserveAttachments">
                                        Preserve attachments
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Modified Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="emlHelpModal" tabindex="-1" aria-labelledby="emlHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emlHelpModalLabel">How to Get an .eml File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Gmail</h6>
                <ol>
                    <li>Open the email you want to use</li>
                    <li>Click the three dots (More) in the top right</li>
                    <li>Select "Show original"</li>
                    <li>Click "Download Original" to save as .eml</li>
                </ol>
                
                <h6>Outlook</h6>
                <ol>
                    <li>Open the email you want to use</li>
                    <li>Click on File > Save As</li>
                    <li>Choose "Outlook Message Format" (.msg) or "Text Only" (.txt) format</li>
                    <li>If saved as .msg, you may need to convert to .eml using online tools</li>
                </ol>
                
                <h6>Apple Mail</h6>
                <ol>
                    <li>Open the email you want to use</li>
                    <li>Go to File > Save As</li>
                    <li>Choose the "Raw Message Source" option</li>
                    <li>Save with the .eml extension</li>
                </ol>
                
                <h6>Thunderbird</h6>
                <ol>
                    <li>Right-click on the email</li>
                    <li>Select "Save As" > "File"</li>
                    <li>It will automatically save as .eml</li>
                </ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Show fields after file is uploaded
        $('#emlFile').on('change', function() {
            if ($(this).val()) {
                $('#emailFields').removeClass('d-none');
            } else {
                $('#emailFields').addClass('d-none');
            }
        });
    });
</script> 