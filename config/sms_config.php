<?php
function sendSMSAlert($message) {
    $username = "your_username";  // Africa's Talking
    $apiKey = "your_api_key";
    
    $url = "https://api.africastalking.com/version1/messaging";
    $data = array(
        'username' => $username,
        'to' => "+256xxxxxxxxx",  // Mugwe Fish Pond manager
        'message' => $message
    );
    
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n".
                        "apiKey: $apiKey\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        )
    );
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result;
}
?>