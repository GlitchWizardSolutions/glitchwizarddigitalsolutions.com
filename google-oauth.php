<?php /* EDITED 12/08/24  Created Admin login OAuth for myself.*/
include_once 'assets/includes/public-config.php';

// If the captured code param exists and is valid
if (isset($_GET['code']) && !empty($_GET['code'])) {
    // Execute cURL request to retrieve the access token
    $params = [
        'code' => $_GET['code'],
        'client_id' => google_oauth_client_id,
        'client_secret' => google_oauth_client_secret,
        'redirect_uri' => google_oauth_redirect_uri,
        'grant_type' => 'authorization_code'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response, true);
    // Make sure access token is valid
    if (isset($response['access_token']) && !empty($response['access_token'])) {
        // Execute cURL request to retrieve the user info associated with the Google account
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
        $response = curl_exec($ch);
        curl_close($ch);
        $profile = json_decode($response, true);
        // Make sure the profile data exists
        if (isset($profile['email'])) {
            // Check if account exists in database
            $stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ?');
            $stmt->execute([ $profile['email'] ]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            // Get the current date
            $date = date('Y-m-d\TH:i:s');
            // If the account exists...
            if ($account) {
                // Account exists! Bind the SQL data
                $username       = $account['username'];
                $role           = $account['role'];
                $id             = $account['id'];
                $access_level   = $account['access_level'];
                $role           = $account['role'];
                $id             = $account['id'];
                $email          = $account['email'];
                $full_name      = $account['full_name'];
                $document_path  = $account['document_path'];
            }
            // Authenticate the user
            session_regenerate_id();
            $_SESSION['loggedin']       = TRUE;
            $_SESSION['name']           = $username;
            $_SESSION['id']             = $id;
            $_SESSION['role']           = $role;
            $_SESSION['access_level']   = $access_level;
            $_SESSION['email']          = $email;
            $_SESSION['full_name']      = $full_name;
            $_SESSION['document_path']  = $document_path;
            
            // Update last seen date
			$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
			$stmt->execute([ $date, $id ]);
            // Redirect to home page
            header('Location: admin/index.php');
            exit;
        } else {
            exit('Could not retrieve profile information! Please try again later!');
        }
    } else {
        exit('Invalid access token! Please try again later!');
    }
} else {
    // Define params and redirect to Google Authentication page
    $params = [
        'response_type' => 'code',
        'client_id'     => google_oauth_client_id,
        'redirect_uri'  => google_oauth_redirect_uri,
        'scope'         => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
        'access_type'   => 'offline',
        'prompt'        => 'consent'
    ];
    header('Location: https://accounts.google.com/o/oauth2/auth?' . http_build_query($params));
    exit;
}
?>