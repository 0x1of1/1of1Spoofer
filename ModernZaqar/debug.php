<?php
// Include necessary files
require_once 'includes/init.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1of1spoofer - Debug Page</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .debug-section {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }

        .debug-log {
            height: 150px;
            overflow-y: auto;
            background-color: #212529;
            color: #fff;
            padding: 10px;
            font-family: monospace;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container my-4">
        <h1>1of1spoofer Debug Page</h1>
        <p>This page helps diagnose issues with Bootstrap modals and JavaScript.</p>

        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Check the browser console (F12 > Console) for more detailed information.
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Environment Tests</h5>
                    </div>
                    <div class="card-body">
                        <div class="debug-section">
                            <h5>Library Detection</h5>
                            <ul id="libraryTests">
                                <li>Bootstrap: <span id="bootstrapTest">Checking...</span></li>
                                <li>jQuery: <span id="jqueryTest">Checking...</span></li>
                                <li>Bootstrap Modal: <span id="modalTest">Checking...</span></li>
                            </ul>
                        </div>

                        <div class="debug-section">
                            <h5>Console Log</h5>
                            <div id="debugLog" class="debug-log">
                                Debug log will appear here...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Modal Tests</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <button type="button" class="btn btn-primary" id="testBootstrapModal">
                                Test Bootstrap Modal
                            </button>

                            <button type="button" class="btn btn-success" id="testFallbackModal">
                                Test Fallback Modal
                            </button>

                            <button type="button" class="btn btn-secondary" id="testInlineModal">
                                Test Inline Modal
                            </button>
                        </div>

                        <div class="alert" id="modalTestResult">
                            Click a button to test a modal
                        </div>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">New Profile Test</h5>
                    </div>
                    <div class="card-body">
                        <p>Test the New Profile button directly:</p>
                        <button type="button" class="btn btn-primary" id="newProfileTest">
                            Open New Profile Modal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Modal -->
    <div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="testModalLabel">Test Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This is a test modal. If you can see this, modals are working properly.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for inline test -->
    <div class="modal" id="inlineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Inline Modal</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This modal uses inline JavaScript without Bootstrap. If you can see this, the fallback is working.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeInlineModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/modal-fallback.js"></script>

    <script>
        // Debug log function
        function debugLog(message) {
            const log = document.getElementById('debugLog');
            const timestamp = new Date().toLocaleTimeString();
            log.innerHTML += `<div>[${timestamp}] ${message}</div>`;
            log.scrollTop = log.scrollHeight;
            console.log(message);
        }

        // Initialize tests
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('Debug page loaded');

            // Check libraries
            const bootstrapTest = document.getElementById('bootstrapTest');
            const jqueryTest = document.getElementById('jqueryTest');
            const modalTest = document.getElementById('modalTest');

            // Bootstrap test
            if (typeof bootstrap !== 'undefined') {
                bootstrapTest.textContent = 'Available ✓';
                bootstrapTest.className = 'text-success';
                debugLog('Bootstrap is available');
            } else {
                bootstrapTest.textContent = 'Not Available ✗';
                bootstrapTest.className = 'text-danger';
                debugLog('Bootstrap is NOT available');
            }

            // jQuery test
            if (typeof jQuery !== 'undefined') {
                jqueryTest.textContent = `Available ✓ (v${jQuery.fn.jquery})`;
                jqueryTest.className = 'text-success';
                debugLog(`jQuery is available (v${jQuery.fn.jquery})`);
            } else {
                jqueryTest.textContent = 'Not Available ✗';
                jqueryTest.className = 'text-danger';
                debugLog('jQuery is NOT available');
            }

            // Modal test
            if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
                modalTest.textContent = 'Available ✓';
                modalTest.className = 'text-success';
                debugLog('Bootstrap Modal is available');
            } else {
                modalTest.textContent = 'Not Available ✗';
                modalTest.className = 'text-danger';
                debugLog('Bootstrap Modal is NOT available');
            }

            // Bootstrap modal test
            document.getElementById('testBootstrapModal').addEventListener('click', function() {
                debugLog('Testing Bootstrap modal...');
                const result = document.getElementById('modalTestResult');

                try {
                    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
                        const modal = new bootstrap.Modal(document.getElementById('testModal'));
                        modal.show();
                        debugLog('Bootstrap modal opened successfully');
                        result.className = 'alert alert-success';
                        result.textContent = 'Bootstrap modal opened successfully';
                    } else {
                        throw new Error('Bootstrap Modal not available');
                    }
                } catch (error) {
                    debugLog('Error opening Bootstrap modal: ' + error.message);
                    result.className = 'alert alert-danger';
                    result.textContent = 'Error: ' + error.message;
                }
            });

            // Fallback modal test
            document.getElementById('testFallbackModal').addEventListener('click', function() {
                debugLog('Testing fallback modal...');
                const result = document.getElementById('modalTestResult');

                try {
                    if (typeof FallbackModal !== 'undefined') {
                        FallbackModal.open('testModal');
                        debugLog('Fallback modal opened successfully');
                        result.className = 'alert alert-success';
                        result.textContent = 'Fallback modal opened successfully';
                    } else {
                        throw new Error('FallbackModal not available');
                    }
                } catch (error) {
                    debugLog('Error opening fallback modal: ' + error.message);
                    result.className = 'alert alert-danger';
                    result.textContent = 'Error: ' + error.message;
                }
            });

            // Inline modal test
            document.getElementById('testInlineModal').addEventListener('click', function() {
                debugLog('Testing inline modal...');
                showInlineModal();
            });

            // New Profile test
            document.getElementById('newProfileTest').addEventListener('click', function() {
                debugLog('Testing New Profile modal...');
                const result = document.getElementById('modalTestResult');

                try {
                    const newProfileModal = document.getElementById('newProfileModal');

                    if (!newProfileModal) {
                        throw new Error('New Profile modal element not found');
                    }

                    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
                        debugLog('Using Bootstrap Modal for New Profile test');
                        const modal = new bootstrap.Modal(newProfileModal);
                        modal.show();
                    } else if (typeof FallbackModal !== 'undefined') {
                        debugLog('Using FallbackModal for New Profile test');
                        FallbackModal.open('newProfileModal');
                    } else {
                        debugLog('Using inline method for New Profile test');
                        showInlineModalElement(newProfileModal);
                    }

                    debugLog('New Profile modal opened successfully');
                    result.className = 'alert alert-success';
                    result.textContent = 'New Profile modal opened successfully';
                } catch (error) {
                    debugLog('Error opening New Profile modal: ' + error.message);
                    result.className = 'alert alert-danger';
                    result.textContent = 'Error: ' + error.message;
                }
            });
        });

        // Inline modal functions
        function showInlineModal() {
            const modal = document.getElementById('inlineModal');
            showInlineModalElement(modal);
        }

        function showInlineModalElement(modalElement) {
            if (!modalElement) {
                debugLog('Modal element not found');
                return;
            }

            // Create backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);

            // Show modal
            modalElement.style.display = 'block';
            modalElement.className = 'modal fade show';
            document.body.classList.add('modal-open');

            debugLog('Inline modal opened successfully');
            const result = document.getElementById('modalTestResult');
            result.className = 'alert alert-success';
            result.textContent = 'Inline modal opened successfully';
        }

        function closeInlineModal() {
            const modal = document.getElementById('inlineModal');

            if (!modal) {
                debugLog('Modal element not found');
                return;
            }

            // Hide modal
            modal.style.display = 'none';
            modal.className = 'modal fade';
            document.body.classList.remove('modal-open');

            // Remove backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                document.body.removeChild(backdrop);
            }

            debugLog('Inline modal closed successfully');
        }
    </script>
</body>

</html>