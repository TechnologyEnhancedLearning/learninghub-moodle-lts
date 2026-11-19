<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php'); 
require_login(); // Ensures full session context, like in core_renderer

header('Content-Type: application/json');

// Function to handle JSON error response and exit.
// This centralizes error handling and ensures consistent output.
function send_json_error($message, $status = 'error', $http_status_code = 500) {
    error_log("theme_nhse: send_json_error - Status: " . $status . ", Message: " . $message);
    http_response_code($http_status_code);
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

try {
    global $USER, $DB;
    $theme_identifier = 'theme_nhse';       

    // Get the .NET application base URL theme property
    $dotnet_base_url = get_config($theme_identifier, 'dotnet_base_url');
    // Ensure the .NET application base URL ends with a slash if it's not empty
    if (!empty($dotnet_base_url) && substr($dotnet_base_url, -1) !== '/') {
        $dotnet_base_url .= '/';
    }

    // Get the API Base URL theme property
    $api_base_url = get_config($theme_identifier, 'api_base_url');
    // Ensure the API base URL ends with a slash if it's not empty
    if (empty($api_base_url)) {        
        send_json_error('API Base URL is not configured in theme settings.', 'error', 400);
    }
    if (substr($api_base_url, -1) !== '/') {
        $api_base_url .= '/';
    }

    // Retrieve the OIDC token
    $accesstoken = null; 
    $token_record = $DB->get_record('auth_oidc_token', ['username' => $USER->username]);
    // IMPORTANT: Only access $token->token if $token is not false (i.e., a record was found)
    if ($token_record) {
        $accesstoken = $token_record->token; // Assuming the actual token is in the 'token' column      
    } else {
        send_json_error(
        'No OIDC token found for user.', // The message for the client and log
        'error',                           // The status field in the JSON response
        500                                // The HTTP status code (e.g., 500 Internal Server Error)
        );
        exit; // Exit if no token is found and you want to indicate an error
    }

} catch (Throwable $e) {    
    send_json_error(
        'An unexpected error occurred: ' . $e->getMessage(),
        'exception_error',
        500
    );
    exit;
}

$searchterm = optional_param('query', '', PARAM_TEXT); 

$api_endpoint_path = 'Search/GetAutoSuggestionResult/';
$url_for_search = $api_base_url . $api_endpoint_path . urlencode($searchterm);


try 
    {
    $curl = new \curl(); 
        // Set Authorization header
    $options = [
        'HTTPHEADER' => [
            'Authorization: Bearer ' . $accesstoken,
            'Accept: application/json'
        ]
    ];
    $response = $curl->get($url_for_search, null, $options);
    $result = json_decode($response, true);    
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $json_error_msg = json_last_error_msg();
        error_log("theme_nhse: JSON decoding error: " . $json_error_msg);       
        send_json_error(
            'API response could not be decoded: ' . $json_error_msg,
            'json_decode_error',
            500 // Internal Server Error as the server received unparseable data
        );        
    }
    
    echo json_encode([
        'status' => 'ok',
        'test_url' => $url_for_search,
        'accesstoken' => $accesstoken,
        'api_raw_response' => $response, // Add the raw string response
        'api_decoded_result' => $result  // Add the decoded array/object response
    ]);
    exit; 


} catch (Exception $e) {       
    error_log("theme_nhse: CURL Exception caught for URL: " . $url_for_search . " Message: " . $e->getMessage()); // Use $url_for_search for clarity
    send_json_error(
        'Failed to connect to API: ' . $e->getMessage(), // Message for client and log
        'curl_error',                                    // Specific status for cURL issues
        500                                              // HTTP 500 Internal Server Error for network/server-side issues
    );
}



