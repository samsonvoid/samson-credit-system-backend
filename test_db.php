<?php
try {
    $dsn = 'mysql:host=127.0.0.1;port=3306;dbname=credit_system';
    $user = 'root';
    $pass = '';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => true,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected to MySQL!\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users count: " . $result->count . "\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    
    // Try with different auth
    echo "\nTrying with mysql_native_password...\n";
    try {
        $dsn2 = 'mysql:host=127.0.0.1;port=3306;dbname=credit_system;auth=MySQL41';
        $pdo2 = new PDO($dsn2, $user, $pass);
        echo "Connected with MySQL41 auth!\n";
    } catch(PDOException $e2) {
        echo "MySQL41 also failed: " . $e2->getMessage() . "\n";
    }
}