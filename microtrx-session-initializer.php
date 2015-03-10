<?php
// Inspired from here: http://silvermapleweb.com/using-the-php-session-in-wordpress/

function microtrxStartSession() {
    if(!session_id()) {
        session_start();
    }
}

function microtrxEndSession() {
    session_destroy ();
}


// Hook into the actions to make sure sessions are enabled for the site and ended when login/logout occurs
add_action('init', 'microtrxStartSession', 1);
add_action('wp_logout', 'microtrxEndSession');
add_action('wp_login', 'microtrxEndSession');

?>
