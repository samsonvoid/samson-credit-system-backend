<?php
$pdo = new PDO('mysql:host=db;dbname=credit_system', 'root', 'root');
$hash = password_hash('demo123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->execute([$hash, 'admin@credit-system.com']);
echo "Done: $hash";