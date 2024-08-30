<?php
session_start();

// Include Google Client Library for PHP autoload file
require_once 'vendor/autoload.php';

// Create a new Google client object
$google_client = new Google_Client();

// Set the OAuth 2.0 Client ID
$google_client->setClientId('123086864034-2ag3bnphlqbvevsu6bjs90a6qnqjto03.apps.googleusercontent.com');

// Set the OAuth 2.0 Client Secret key
$google_client->setClientSecret('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');

// Set the OAuth 2.0 Redirect URI
$google_client->setRedirectUri('http://localhost:89/gym_management/login_with_google.php');

// Define the scopes to request email and profile
$google_client->addScope('email');
$google_client->addScope('profile');

// Check if the "code" parameter exists in the URL (this happens after Google redirects back)
if (isset($_GET["code"])) {
    // Attempt to exchange the authorization code for an access token
    $token = $google_client->fetchAccessTokenWithAuthCode($_GET["code"]);

    // Check for any error in token fetching
    if (!isset($token["error"])) {
        // Set the access token for the client
        $google_client->setAccessToken($token['access_token']);

        // Get the user's profile information from Google
        $google_service = new Google_Service_Oauth2($google_client);
        $data = $google_service->userinfo->get();

        // Store the user's information in session variables
        $_SESSION['user_first_name'] = $data['given_name'];
        $_SESSION['user_last_name'] = $data['family_name'];
        $_SESSION['user_email_address'] = $data['email'];
        $_SESSION['user_image'] = $data['picture'];

        // Redirect to a protected page
        header('Location: index.php');
        exit();
    }
}

// If the user is logged in, display their profile information
if (isset($_SESSION['user_email_address'])) {
    echo '<img src="' . $_SESSION["user_image"] . '" alt="Profile Image" />';
    echo '<h3><b>Name:</b> ' . $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] . '</h3>';
    echo '<h3><b>Email:</b> ' . $_SESSION['user_email_address'] . '</h3>';
    echo '<br><a href="logout.php">Logout</a>';
} else {
    // Generate the Google login URL and display a login button
    $login_button = '<a href="' . $google_client->createAuthUrl() . '"><img src="images/google-login-button.png" alt="Login with Google" /></a>';
    echo '<div align="center">' . $login_button . '</div>';
}
?>
