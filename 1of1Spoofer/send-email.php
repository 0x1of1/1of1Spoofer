<div class="mb-3">
    <label for="attachments" class="form-label">Attachment(s): (optional)</label>
    <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
    <div class="form-text">
        Max size: <?php echo format_file_size(config('uploads.max_size', 5 * 1024 * 1024)); ?>.
        Allowed types: <?php echo implode(', ', array_keys(config('uploads.allowed_types', []))); ?>
    </div>
</div>

<div class="mb-3 form-check">
    <input class="form-check-input" type="checkbox" id="embed_images" name="embed_images" value="yes" checked>
    <label class="form-check-label" for="embed_images">
        Embed images in email body (recommended for displaying images in the recipient's email client)
    </label>
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