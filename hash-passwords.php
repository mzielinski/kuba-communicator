<?php
/**
 * Password Hashing Utility
 * Run this script to hash passwords in credentials.json
 * Usage: php hash-passwords.php
 */

$credentialsFile = __DIR__ . '/data/credentials.json';

// Load current credentials
if (!file_exists($credentialsFile)) {
    echo "❌ Error: credentials.json not found\n";
    exit(1);
}

$content = file_get_contents($credentialsFile);
$data = json_decode($content, true);

if (!isset($data['users'])) {
    echo "❌ Error: No users found in credentials.json\n";
    exit(1);
}

echo "🔐 Password Hashing Utility\n";
echo "================================\n\n";

// Process each user
$updated = false;
foreach ($data['users'] as &$user) {
    $username = $user['username'];
    $password = $user['password'];

    // Check if password is already hashed (hashed passwords start with $2y$)
    if (strpos($password, '$2y$') === 0 || strpos($password, '$2a$') === 0) {
        echo "✓ User '$username': Password already hashed\n";
    } else {
        // Hash the plain text password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $user['password'] = $hashedPassword;
        echo "✓ User '$username': Password hashed successfully\n";
        echo "  Hash: " . substr($hashedPassword, 0, 20) . "...\n";
        $updated = true;
    }
}

// Save updated credentials if any were hashed
if ($updated) {
    $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($credentialsFile, $newContent)) {
        echo "\n✅ credentials.json updated successfully!\n";
        echo "🔒 All passwords are now securely hashed.\n";
    } else {
        echo "\n❌ Error: Could not write to credentials.json\n";
        exit(1);
    }
} else {
    echo "\n✓ No updates needed - all passwords are already hashed.\n";
}

echo "\n================================\n";
echo "✅ Done!\n";
?>

