<?php
// Get current SMTP settings
$smtpSettings = getSmtpSettings();
// Get all saved profiles
$smtpProfiles = getSmtpProfiles();
?>

<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-bookmark me-2"></i>SMTP Profiles</h5>
        <button type="button" class="btn btn-sm btn-light" id="openNewProfileModalBtn">
            <i class="bi bi-plus-circle"></i> New Profile
        </button>
    </div>
    <div class="card-body">
        <p class="mb-3">Save and manage multiple SMTP configurations for easy switching between different providers.</p>
        
        <?php if (empty($smtpProfiles)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                You don't have any saved SMTP profiles yet. Create your first profile to get started.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Profile Name</th>
                            <th>Server</th>
                            <th>Username</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($smtpProfiles as $profileName => $profile): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($profileName); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($profile['host'] ?? ''); ?>:<?php echo htmlspecialchars($profile['port'] ?? ''); ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars(strtoupper($profile['security'] ?? 'none')); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($profile['username'] ?? ''); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-primary load-profile-btn" data-profile="<?php echo htmlspecialchars($profileName); ?>">
                                            <i class="bi bi-arrow-down-circle"></i> Load
                                        </button>
                                        <button type="button" class="btn btn-danger delete-profile-btn" data-profile="<?php echo htmlspecialchars($profileName); ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info mt-3">
            <i class="bi bi-lightbulb me-2"></i>
            <strong>Tip:</strong> Use profiles to quickly switch between different SMTP providers without retyping credentials.
        </div>
    </div>
</div>

<!-- New Profile Modal -->
<div class="modal" id="newProfileModal" tabindex="-1" aria-labelledby="newProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="newProfileModalLabel">Save Current Settings as Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="saveProfileForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="1234567890abcdef1234567890abcdef">
                    <input type="hidden" name="form_name" value="save_smtp_profile">
                    <input type="hidden" name="action" value="save_smtp_profile">
                    
                    <div class="mb-3">
                        <label for="profile_name" class="form-label">Profile Name:</label>
                        <input type="text" class="form-control" id="profile_name" name="profile_name" required placeholder="e.g., Gmail, Office365, SendGrid">
                        <div class="form-text">Choose a descriptive name to identify this SMTP configuration</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sender_email" class="form-label">Default Sender Email (Optional):</label>
                        <input type="email" class="form-control" id="sender_email" name="sender_email" placeholder="your@email.com">
                        <div class="form-text">If specified, this email will be used as the default sender when using this profile</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            This will save your current SMTP settings (shown in the SMTP Configuration section) as a named profile.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal for Deletion -->
<div class="modal fade" id="deleteProfileModal" tabindex="-1" aria-labelledby="deleteProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteProfileModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the profile "<span id="profileToDelete"></span>"?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteProfile">Delete Profile</button>
            </div>
        </div>
    </div>
</div>

<!-- Add direct JavaScript handler for the modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('SMTP profiles script loading...');
    
    // Debug Bootstrap availability
    if (typeof bootstrap !== 'undefined') {
        console.log('Bootstrap is available in SMTP profiles template');
    } else {
        console.error('ERROR: Bootstrap is NOT available in SMTP profiles template!');
    }
    
    // Direct handler for the new profile button
    const openNewProfileModalBtn = document.getElementById('openNewProfileModalBtn');
    if (openNewProfileModalBtn) {
        console.log('New Profile button found, adding click handler');
        openNewProfileModalBtn.addEventListener('click', function() {
            console.log('New Profile button clicked');
            
            const modalElement = document.getElementById('newProfileModal');
            if (!modalElement) {
                console.error('Modal element not found!');
                alert('Error: Modal element not found');
                return;
            }
            
            try {
                if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
                    console.log('Creating new bootstrap.Modal instance');
                    const newProfileModal = new bootstrap.Modal(modalElement);
                    console.log('Showing modal');
                    newProfileModal.show();
                } else {
                    // Fallback to jQuery if bootstrap is not available
                    console.log('Bootstrap not available, trying jQuery fallback');
                    if (typeof $ !== 'undefined') {
                        console.log('Using jQuery to show modal');
                        $(modalElement).modal('show');
                    } else {
                        console.error('Neither Bootstrap nor jQuery is available');
                        alert('Error: Could not open modal - Bootstrap and jQuery unavailable');
                    }
                }
            } catch (error) {
                console.error('Error showing modal:', error);
                alert('Error showing modal: ' + error.message);
                
                // As a last resort, try making the modal visible with plain JS
                try {
                    console.log('Attempting plain JS fallback for modal display');
                    modalElement.style.display = 'block';
                    modalElement.classList.add('show');
                    document.body.classList.add('modal-open');
                    
                    // Create backdrop
                    let backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(backdrop);
                } catch (fallbackError) {
                    console.error('Fallback display also failed:', fallbackError);
                }
            }
        });
    } else {
        console.error('New Profile button not found!');
    }
    
    // Existing form handler code...
    const saveProfileForm = document.getElementById('saveProfileForm');
    if (saveProfileForm) {
        saveProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Save Profile Form submitted directly');
            
            const profileName = document.getElementById('profile_name').value.trim();
            const senderEmail = document.getElementById('sender_email').value.trim();
            
            if (!profileName) {
                alert('Please enter a profile name');
                return;
            }
            
            const submitBtn = saveProfileForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'save_smtp_profile');
            formData.append('csrf_token', '1234567890abcdef1234567890abcdef');
            formData.append('form_name', 'save_smtp_profile');
            formData.append('profile_name', profileName);
            
            if (senderEmail) {
                formData.append('sender_email', senderEmail);
            }
            
            // Send AJAX request
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                console.log('Save profile response:', result);
                
                if (result.status === 'success') {
                    // Try to close the modal
                    if (typeof bootstrap !== 'undefined') {
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
            })
            .catch(error => {
                console.error('Error saving profile:', error);
                alert('Error saving profile: ' + error);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Profile';
            });
        });
    } else {
        console.error('Save Profile Form not found!');
    }
});
</script>

<!-- Ensure Bootstrap is loaded -->
<script>
// Check if Bootstrap is already loaded
if (typeof bootstrap === 'undefined') {
    console.log('Bootstrap not detected, loading directly in SMTP profiles template');
    
    // Create and append Bootstrap script
    const bootstrapScript = document.createElement('script');
    bootstrapScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
    bootstrapScript.onload = function() {
        console.log('Bootstrap loaded successfully from SMTP profiles template');
        
        // Try to initialize modal functionality after loading
        try {
            if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
                console.log('Bootstrap Modal is now available');
                
                // Re-add click handler
                const openNewProfileModalBtn = document.getElementById('openNewProfileModalBtn');
                if (openNewProfileModalBtn) {
                    console.log('Re-adding click event after Bootstrap load');
                    openNewProfileModalBtn.addEventListener('click', function() {
                        const modalElement = document.getElementById('newProfileModal');
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    });
                }
            }
        } catch (error) {
            console.error('Error initializing Bootstrap after load:', error);
        }
    };
    bootstrapScript.onerror = function() {
        console.error('Failed to load Bootstrap script in SMTP profiles template');
    };
    
    document.body.appendChild(bootstrapScript);
} else {
    console.log('Bootstrap already loaded in SMTP profiles template');
}
</script> 