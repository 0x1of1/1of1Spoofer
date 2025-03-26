<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1of1Spoofer - Email Spoofing Tool</title>
    <meta name="description" content="Educational Email Spoofing Tool for Penetration Testing and Security Awareness">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Add other head elements here -->
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">1of1Spoofer</a>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage === 'main') ? 'active' : ''; ?>" href="index.php">
                                <i class="fas fa-envelope"></i> Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage === 'raw_email') ? 'active' : ''; ?>" href="index.php?page=raw_email">
                                <i class="fas fa-code"></i> Raw Email Mode
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage === 'domain') ? 'active' : ''; ?>" href="index.php?page=domain">
                                <i class="fas fa-globe"></i> Domain Lookup
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage === 'smtp') ? 'active' : ''; ?>" href="index.php?page=smtp">
                                <i class="fas fa-server"></i> SMTP
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container mt-4">
        <!-- Main content will be inserted here -->
    </main>
</body>
</html> 