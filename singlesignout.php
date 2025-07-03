<?php
define('NO_MOODLE_COOKIES', true);
require(__DIR__ . '/config.php');
require_once($CFG->libdir . '/filelib.php');

// Accept logout_token (no signature verification)
$logout_token = $_POST['logout_token'] ?? null;
if (!$logout_token) {
    http_response_code(400);
    exit("Missing logout_token");
}

// Decode JWT payload (2nd part)
$jwt_parts = explode('.', $logout_token);
if (count($jwt_parts) !== 3) {
    http_response_code(400);
    exit("Invalid JWT");
}

$payload_json = base64_decode(strtr($jwt_parts[1], '-_', '+/'));
$payload = json_decode($payload_json, true);
$sub = $payload['sub'] ?? null;

if (!$sub) {
    http_response_code(400);
    exit("Missing sub claim");
}

// Lookup user by sub (assumed to match idnumber or username)
$user = $DB->get_record('user', ['auth' => 'oidc', 'username' => $sub]);
if (!$user) {
    http_response_code(404);
    exit("User not found");
}

// Kill the user’s sessions
\core\session\manager::kill_user_sessions($user->id);

http_response_code(200);
echo "User logged out";
