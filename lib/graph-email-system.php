<?php
/**
 * Microsoft Graph API Email System
 * Sends emails via Microsoft Graph API (bypasses SMTP port restrictions)
 * Uses HTTPS (port 443) instead of SMTP ports 587/465
 * 
 * Created: December 5, 2025
 */

// Note: We use direct cURL calls instead of the Graph SDK for better compatibility
// The microsoft-graph v2 SDK uses Kiota which is more complex than needed for simple email

/**
 * Send email via Microsoft Graph API
 * 
 * @param string $to_email Recipient email address
 * @param string $to_name Recipient display name
 * @param string $subject Email subject
 * @param string $body_html HTML email body
 * @param string $from_email Sender email address (defaults to mail_from constant)
 * @param string $from_name Sender display name (defaults to mail_name constant)
 * @param string $reply_to_email Reply-To email address (optional)
 * @param string $reply_to_name Reply-To display name (optional)
 * @return bool Success status
 */
function send_email_via_graph(
    $to_email, 
    $to_name, 
    $subject, 
    $body_html, 
    $from_email = null, 
    $from_name = null,
    $reply_to_email = null,
    $reply_to_name = null
) {
    try {
        // Use defaults from config if not provided
        if ($from_email === null) {
            $from_email = defined('mail_from') ? mail_from : 'notifications@glitchwizarddigitalsolutions.com';
        }
        if ($from_name === null) {
            $from_name = defined('mail_name') ? mail_name : 'GlitchWizard Digital Solutions';
        }
        
        // Get OAuth2 access token
        $accessToken = get_graph_access_token();
        
        if (!$accessToken) {
            if (function_exists('critical_log')) {
                critical_log('Graph Email', 'send_email_via_graph', 'Token Failed', 'Could not obtain access token');
            }
            return false;
        }
        
        // Build email message
        $message = [
            'subject' => $subject,
            'body' => [
                'contentType' => 'HTML',
                'content' => $body_html
            ],
            'toRecipients' => [
                [
                    'emailAddress' => [
                        'address' => $to_email,
                        'name' => $to_name
                    ]
                ]
            ]
        ];
        
        // Add Reply-To if provided
        if ($reply_to_email) {
            $message['replyTo'] = [
                [
                    'emailAddress' => [
                        'address' => $reply_to_email,
                        'name' => $reply_to_name ? $reply_to_name : ''
                    ]
                ]
            ];
        }
        
        // Prepare request body
        $requestBody = [
            'message' => $message,
            'saveToSentItems' => true
        ];
        
        // Send email via Graph API using cURL
        $url = "https://graph.microsoft.com/v1.0/users/$from_email/sendMail";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Check for errors
        if ($curlError) {
            if (function_exists('critical_log')) {
                critical_log('Graph Email', 'send_email_via_graph', 'cURL Error', $curlError);
            }
            return false;
        }
        
        if ($httpCode !== 202) { // Graph API returns 202 Accepted for successful email send
            if (function_exists('critical_log')) {
                critical_log('Graph Email', 'send_email_via_graph', 'HTTP Error', 
                    "HTTP $httpCode: $response");
            }
            return false;
        }
        
        // Log success
        if (function_exists('debug_log')) {
            debug_log('Graph Email', 'send_email_via_graph', 'Email Sent', 
                "From: $from_name <$from_email>, To: $to_email, Subject: $subject" . 
                ($reply_to_email ? ", Reply-To: $reply_to_email" : "")
            );
        }
        
        return true;
        
    } catch (\Exception $e) {
        // Log error with full details
        $error_msg = "Graph API Error: " . $e->getMessage();
        $error_details = "From: $from_email, To: $to_email, Subject: $subject";
        
        if (function_exists('critical_log')) {
            critical_log('Graph Email', 'send_email_via_graph', 'Send Failed', 
                $error_msg . " | " . $error_details);
        } else {
            error_log($error_msg . " | " . $error_details);
        }
        
        return false;
    }
}

/**
 * Get Microsoft Graph API access token
 * Uses OAuth2 client credentials flow
 * 
 * @return string|false Access token on success, false on failure
 */
function get_graph_access_token() {
    try {
        // Build token request URL
        $tokenUrl = 'https://login.microsoftonline.com/' . oauth_tenant_id . '/oauth2/v2.0/token';
        
        // Build POST data
        $postData = [
            'client_id' => oauth_client_id,
            'client_secret' => oauth_client_secret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials'
        ];
        
        // Initialize cURL
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Check for cURL errors
        if ($curlError) {
            if (function_exists('critical_log')) {
                critical_log('Graph Email', 'get_graph_access_token', 'cURL Error', $curlError);
            }
            return false;
        }
        
        // Check HTTP response code
        if ($httpCode !== 200) {
            if (function_exists('critical_log')) {
                critical_log('Graph Email', 'get_graph_access_token', 'HTTP Error', 
                    "HTTP $httpCode: $response");
            }
            return false;
        }
        
        // Parse JSON response
        $tokenData = json_decode($response, true);
        
        if (!isset($tokenData['access_token'])) {
            if (function_exists('critical_log')) {
                critical_log('Graph Email', 'get_graph_access_token', 'Invalid Response', 
                    'No access_token in response: ' . $response);
            }
            return false;
        }
        
        return $tokenData['access_token'];
        
    } catch (\Exception $e) {
        if (function_exists('critical_log')) {
            critical_log('Graph Email', 'get_graph_access_token', 'Exception', $e->getMessage());
        }
        return false;
    }
}

/**
 * Helper function to send email with context-specific display name
 * 
 * @param string $context Email context: 'payment', 'support', 'security', 'account', 'project', 'general'
 * @param string $to_email Recipient email
 * @param string $to_name Recipient name
 * @param string $subject Email subject
 * @param string $body_html HTML body
 * @return bool Success status
 */
function send_contextual_email($context, $to_email, $to_name, $subject, $body_html) {
    // Map contexts to display names and reply-to addresses
    $contexts = [
        'payment' => [
            'name' => 'GlitchWizard Payments',
            'reply' => 'payments@glitchwizardsolutions.com'
        ],
        'support' => [
            'name' => 'GlitchWizard Support',
            'reply' => 'support@glitchwizardsolutions.com'
        ],
        'security' => [
            'name' => 'GlitchWizard Security',
            'reply' => 'support@glitchwizardsolutions.com'
        ],
        'account' => [
            'name' => 'GlitchWizard Accounts',
            'reply' => 'support@glitchwizardsolutions.com'
        ],
        'project' => [
            'name' => 'GlitchWizard Projects',
            'reply' => 'webmaster@glitchwizardsolutions.com'
        ],
        'general' => [
            'name' => 'GlitchWizard Digital Solutions',
            'reply' => 'support@glitchwizardsolutions.com'
        ]
    ];
    
    // Get context configuration or default to general
    $config = isset($contexts[$context]) ? $contexts[$context] : $contexts['general'];
    
    return send_email_via_graph(
        $to_email,
        $to_name,
        $subject,
        $body_html,
        mail_from,  // Always from notifications@
        $config['name'],  // Dynamic display name
        $config['reply'],  // Context-specific reply-to
        ''  // Reply-to name not needed
    );
}
