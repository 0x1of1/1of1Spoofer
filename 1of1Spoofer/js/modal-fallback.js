/**
 * Modal Fallback
 * A lightweight fallback for Bootstrap modals when they're not working properly
 */
(function () {
    console.log('Modal fallback script loaded');

    // Initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function () {
        initFallbackModals();
    });

    function initFallbackModals() {
        console.log('Initializing fallback modal system');

        // Find all modal triggers
        document.querySelectorAll('[data-fallback-modal]').forEach(function (trigger) {
            const modalId = trigger.getAttribute('data-fallback-modal');

            if (!modalId) {
                console.error('Modal trigger missing target modal ID');
                return;
            }

            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                openFallbackModal(modalId);
            });

            console.log('Added fallback click handler for modal: ' + modalId);
        });

        // Add specific handler for the New Profile button
        const newProfileBtn = document.getElementById('openNewProfileModalBtn');
        if (newProfileBtn) {
            console.log('Found New Profile button, adding fallback handler');
            newProfileBtn.addEventListener('click', function (e) {
                // Only use fallback if bootstrap isn't working
                if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
                    e.preventDefault();
                    openFallbackModal('newProfileModal');
                }
            });
        }

        // Initialize close buttons
        document.querySelectorAll('[data-fallback-dismiss="modal"]').forEach(function (closeBtn) {
            closeBtn.addEventListener('click', function () {
                const modal = closeBtn.closest('.modal');
                if (modal) {
                    closeFallbackModal(modal.id);
                }
            });
        });

        // Close when clicking backdrop
        document.querySelectorAll('.modal').forEach(function (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeFallbackModal(modal.id);
                }
            });
        });
    }

    function openFallbackModal(modalId) {
        console.log('Opening fallback modal: ' + modalId);
        const modal = document.getElementById(modalId);

        if (!modal) {
            console.error('Modal not found: ' + modalId);
            return;
        }

        // Create backdrop if it doesn't exist
        let backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade';
            document.body.appendChild(backdrop);

            // Force reflow
            backdrop.offsetHeight;

            // Add show class
            backdrop.classList.add('show');
        }

        // Add necessary classes
        document.body.classList.add('modal-open');
        modal.style.display = 'block';

        // Force reflow
        modal.offsetHeight;

        // Add show class
        modal.classList.add('show');

        console.log('Fallback modal opened: ' + modalId);
    }

    function closeFallbackModal(modalId) {
        console.log('Closing fallback modal: ' + modalId);
        const modal = document.getElementById(modalId);

        if (!modal) {
            console.error('Modal not found: ' + modalId);
            return;
        }

        // Remove show class
        modal.classList.remove('show');

        // Wait for transition
        setTimeout(function () {
            modal.style.display = 'none';

            // Remove backdrop and body class if no other modals are open
            const openModals = document.querySelectorAll('.modal.show');
            if (openModals.length === 0) {
                document.body.classList.remove('modal-open');

                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.classList.remove('show');

                    // Wait for backdrop transition
                    setTimeout(function () {
                        if (backdrop.parentNode) {
                            backdrop.parentNode.removeChild(backdrop);
                        }
                    }, 150);
                }
            }
        }, 300);

        console.log('Fallback modal closed: ' + modalId);
    }

    // Export to global scope
    window.FallbackModal = {
        open: openFallbackModal,
        close: closeFallbackModal,
        init: initFallbackModals
    };
})(); 