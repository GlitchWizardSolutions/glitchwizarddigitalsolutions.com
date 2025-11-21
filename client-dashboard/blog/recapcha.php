<?php
$sitekey = $settings["gcaptcha_sitekey"];
$secret =  $settings["gcaptcha_secretkey"];  
$token = $_POST['g-recaptcha-response'];
$remoteIp = $_SERVER['REMOTE_ADDR'];
$gcaptcha_projectid = $settings["gcaptcha_projectid"];
// Step 1: Verify the token with Google
$url = 'https://recaptchaenterprise.googleapis.com/v1/projects/' . $gcaptcha_projectid . '/assessments?key=' . $sitekey;

// Data for the API request (for Enterprise)
$data = [
    'event' => [
        'token' => $token,
        'siteKey' => $sitekey,
        'expectedAction' => 'submit',
        'userIpAddress' => $remoteIp
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// Step 2: Check if it's valid
if (!empty($result['tokenProperties']['valid']) && $result['tokenProperties']['valid'] === true) {
    $score = $result['riskAnalysis']['score'] ?? 0;
    $threshold = 0.5; // Adjust as needed

    if ($score >= $threshold) {
        echo "Comment accepted! (Score: $score)";
        // Process and store comment in DB or file
    } else {
        echo "We couldn’t verify you're a human. Please try again. (Score: $score)";
    }
} else {
    echo "reCAPTCHA verification failed.";
}
?>