<?php
// Simple test file to check if PHP is working
echo "<h1>PHP Test Page</h1>";
echo "<p>PHP version: " . phpversion() . "</p>";
echo "<h2>Server Information</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";

echo "<h2>Environment Variables</h2>";
echo "<pre>";
print_r($_ENV);
echo "</pre>";

echo "<h2>PHP Information</h2>";
phpinfo();
