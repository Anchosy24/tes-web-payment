<?php
require __DIR__ . '/../bootstrap/app.php';

echo "<h2>Debug Database Config</h2>";

// Load config
$config = require __DIR__ . '/../config/database.php';

echo "<h3>Raw Config:</h3>";
echo "<pre>";
print_r($config);
echo "</pre>";

echo "<h3>Environment Variables:</h3>";
echo "<pre>";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'NOT SET') . "\n";
echo "DB_PASS: " . ($_ENV['DB_PASS'] ?? 'NOT SET') . "\n";
echo "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'NOT SET') . "\n";
echo "</pre>";

echo "<h3>Medoo Config yang akan digunakan:</h3>";
$medooConfig = [
    'database_type' => 'mysql',
    'database_name' => $config['dbname'],
    'server'        => $config['host'],
    'username'      => $config['username'],
    'password'      => $config['password'],
    'port'          => (int)$config['port'],
    'charset'       => $config['charset'],
];
echo "<pre>";
print_r($medooConfig);
echo "</pre>";

// Test Medoo connection
try {
    $db = new \Medoo\Medoo($medooConfig);
    echo "<h3 style='color: green;'>✓ Medoo Connection SUCCESS!</h3>";
    
    // Test query
    $result = $db->query('SELECT * FROM users WHERE id = 1')->fetchAll();
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch (\Exception $e) {
    echo "<h3 style='color: red;'>✗ Medoo Connection FAILED!</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}