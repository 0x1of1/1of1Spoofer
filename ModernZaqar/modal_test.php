<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modal Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }

        .debug-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Bootstrap Modal Test</h1>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal">
            Open Test Modal
        </button>

        <button type="button" class="btn btn-success" id="jsOpenModalBtn">
            JavaScript Open Modal
        </button>

        <div class="debug-info">
            <h3>Debug Information</h3>
            <pre id="bootstrapVersion">Checking Bootstrap version...</pre>
            <pre id="jqueryVersion">Checking jQuery version...</pre>
            <pre id="modalDebug">No modal interactions yet</pre>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check Bootstrap version
            let bootstrapVersion = "Not detected";
            if (typeof bootstrap !== 'undefined') {
                bootstrapVersion = "Bootstrap is available";
            } else {
                bootstrapVersion = "ERROR: Bootstrap is NOT available!";
            }
            document.getElementById('bootstrapVersion').textContent = bootstrapVersion;

            // Check jQuery version
            let jqueryVersion = "Not detected";
            if (typeof jQuery !== 'undefined') {
                jqueryVersion = "jQuery version: " + jQuery.fn.jquery;
            } else {
                jqueryVersion = "ERROR: jQuery is NOT available!";
            }
            document.getElementById('jqueryVersion').textContent = jqueryVersion;

            // Log modal events
            const testModal = document.getElementById('testModal');
            if (testModal) {
                const modalDebug = document.getElementById('modalDebug');

                testModal.addEventListener('show.bs.modal', function() {
                    modalDebug.textContent += "\nModal is about to be shown";
                    console.log('Modal is about to be shown');
                });

                testModal.addEventListener('shown.bs.modal', function() {
                    modalDebug.textContent += "\nModal has been shown";
                    console.log('Modal has been shown');
                });

                testModal.addEventListener('hide.bs.modal', function() {
                    modalDebug.textContent += "\nModal is about to be hidden";
                    console.log('Modal is about to be hidden');
                });

                testModal.addEventListener('hidden.bs.modal', function() {
                    modalDebug.textContent += "\nModal has been hidden";
                    console.log('Modal has been hidden');
                });

                // JavaScript open button
                document.getElementById('jsOpenModalBtn').addEventListener('click', function() {
                    modalDebug.textContent += "\nTrying to open modal via JavaScript";
                    console.log('Trying to open modal via JavaScript');

                    try {
                        const modal = new bootstrap.Modal(testModal);
                        modal.show();
                        modalDebug.textContent += "\nSuccessfully called modal.show()";
                    } catch (error) {
                        modalDebug.textContent += "\nERROR: " + error.message;
                        console.error('Error opening modal:', error);
                    }
                });
            }
        });
    </script>
</body>

</html>