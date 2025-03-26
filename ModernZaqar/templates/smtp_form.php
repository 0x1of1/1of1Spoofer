<?php
// Get current SMTP settings
$smtpSettings = getSmtpSettings();
?>

<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-server me-2"></i>SMTP Configuration</h5>
    </div>
    <div class="card-body">
        <form id="smtpForm">
            <input type="hidden" name="csrf_token" value="1234567890abcdef1234567890abcdef">
            <input type="hidden" name="form_name" value="smtp_settings">
            <input type="hidden" name="action" value="save_smtp_settings">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="smtp_host" class="form-label">SMTP Host:</label>
                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" placeholder="e.g., smtp.gmail.com" value="<?php echo htmlspecialchars($smtpSettings['host'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="smtp_port" class="form-label">Port:</label>
                    <select class="form-select" id="smtp_port" name="smtp_port">
                        <option value="25" <?php echo ($smtpSettings['port'] ?? 587) == 25 ? 'selected' : ''; ?>>25 (Default, non-encrypted)</option>
                        <option value="465" <?php echo ($smtpSettings['port'] ?? 587) == 465 ? 'selected' : ''; ?>>465 (SSL)</option>
                        <option value="587" <?php echo ($smtpSettings['port'] ?? 587) == 587 ? 'selected' : ''; ?>>587 (TLS)</option>
                        <option value="2525" <?php echo ($smtpSettings['port'] ?? 587) == 2525 ? 'selected' : ''; ?>>2525 (Alternate)</option>
                    </select>
                </div>
                <div class="col-md-3">
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
                    <input type="text" class="form-control" id="smtp_username" name="smtp_username" placeholder="Your SMTP username" value="<?php echo htmlspecialchars($smtpSettings['username'] ?? ''); ?>">
                    <div class="form-text">Most SMTP servers require authentication</div>
                </div>
                <div class="col-md-6">
                    <label for="smtp_password" class="form-label">Password:</label>
                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="Your SMTP password" value="<?php echo htmlspecialchars($smtpSettings['password'] ?? ''); ?>">
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
                    <div class="form-text">Set higher for more detailed error messages</div>
                </div>
                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input type="checkbox" class="form-check-input" id="smtp_verify_ssl" name="smtp_verify_ssl" value="1" <?php echo ($smtpSettings['verify_ssl'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="smtp_verify_ssl">Verify SSL Certificate</label>
                        <div class="form-text">Disable for self-signed certificates</div>
                    </div>
                </div>
            </div>
            
            <div id="smtpTestResult" class="alert d-none mb-3"></div>
            
            <div class="d-flex justify-content-between">
                <button type="button" id="test-smtp-btn" class="btn btn-secondary">
                    <i class="bi bi-lightning-charge me-1"></i> Test Connection
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save Settings
                </button>
            </div>

            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Important:</strong> Most SMTP servers require authentication to send emails. 
                If you're seeing "Access denied" errors, make sure you've entered your SMTP credentials correctly.
            </div>
        </form>
    </div>
</div>

<!-- Fallback SMTP Configuration -->
<?php
// Get current fallback SMTP settings
$fallbackSmtpSettings = getFallbackSmtpSettings();
$isFallbackEnabled = isFallbackSmtpEnabled();
?>
<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-server me-2"></i>Fallback SMTP Configuration</h5>
    </div>
    <div class="card-body">
        <p class="mb-3">Configure a fallback SMTP server that will be used when the primary server reaches its sending limit or encounters errors.</p>
        
        <form id="fallbackSmtpForm">
            <input type="hidden" name="csrf_token" value="1234567890abcdef1234567890abcdef">
            <input type="hidden" name="form_name" value="fallback_smtp_settings">
            <input type="hidden" name="action" value="save_fallback_smtp_settings">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="fallback_smtp_host" class="form-label">Fallback SMTP Host:</label>
                    <input type="text" class="form-control" id="fallback_smtp_host" name="fallback_smtp_host" placeholder="e.g., smtp.outlook.com" value="<?php echo htmlspecialchars($fallbackSmtpSettings['host'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="fallback_smtp_port" class="form-label">Port:</label>
                    <select class="form-select" id="fallback_smtp_port" name="fallback_smtp_port">
                        <option value="25" <?php echo ($fallbackSmtpSettings['port'] ?? 587) == 25 ? 'selected' : ''; ?>>25 (Default, non-encrypted)</option>
                        <option value="465" <?php echo ($fallbackSmtpSettings['port'] ?? 587) == 465 ? 'selected' : ''; ?>>465 (SSL)</option>
                        <option value="587" <?php echo ($fallbackSmtpSettings['port'] ?? 587) == 587 ? 'selected' : ''; ?>>587 (TLS)</option>
                        <option value="2525" <?php echo ($fallbackSmtpSettings['port'] ?? 587) == 2525 ? 'selected' : ''; ?>>2525 (Alternate)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fallback_smtp_security" class="form-label">Security:</label>
                    <select class="form-select" id="fallback_smtp_security" name="fallback_smtp_security">
                        <option value="tls" <?php echo ($fallbackSmtpSettings['encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo ($fallbackSmtpSettings['encryption'] ?? 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="" <?php echo empty($fallbackSmtpSettings['encryption'] ?? 'tls') ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="fallback_smtp_username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="fallback_smtp_username" name="fallback_smtp_username" placeholder="Your fallback SMTP username" value="<?php echo htmlspecialchars($fallbackSmtpSettings['username'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label for="fallback_smtp_password" class="form-label">Password:</label>
                    <input type="password" class="form-control" id="fallback_smtp_password" name="fallback_smtp_password" placeholder="Your fallback SMTP password" value="<?php echo htmlspecialchars($fallbackSmtpSettings['password'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="enable_fallback" name="enable_fallback" value="1" <?php echo $isFallbackEnabled ? 'checked' : ''; ?>>
                <label class="form-check-label" for="enable_fallback">Enable Fallback SMTP</label>
                <div class="form-text">When enabled, the system will try this server if the primary SMTP server fails</div>
            </div>
            
            <div id="fallbackSmtpTestResult" class="alert d-none mb-3"></div>
            
            <div class="d-flex justify-content-between">
                <button type="button" id="test-fallback-smtp-btn" class="btn btn-secondary">
                    <i class="bi bi-lightning-charge me-1"></i> Test Fallback Connection
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save Fallback Settings
                </button>
            </div>
        </form>
    </div>
</div> 
</div> 