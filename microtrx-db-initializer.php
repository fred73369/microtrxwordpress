<?php
// Code inspired from here: http://codex.wordpress.org/Creating_Tables_with_Plugins

define('WP_DEBUG_LOG', true);

global $microtrx_db_version;
$microtrx_db_version = '1.0';

// Function to install/update db table
function microtrx_install_db() {

  global $wpdb;
  global $microtrx_db_version;

  $table_name = $wpdb->prefix . 'microtrxrequests';

  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
    session varchar(40) NOT NULL,
    post mediumint(9) NOT NULL,
    address varchar(35) NOT NULL,
    paid bool DEFAULT 0 NOT NULL,
    PRIMARY KEY  (session,post)
  ) $charset_collate;";

  // Whoever wrote this piece of shit code dbDelta() should be fired
  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );

  add_option( 'microtrx_db_version', $microtrx_db_version );

}

// Check to see if the DB needs to be updated
function microtrx_update_db_check() {
    global $microtrx_db_version;
    if ( get_site_option( 'microtrx_db_version' ) != $microtrx_db_version ) {
      microtrx_install_db();
    }
}

// Register the functions
register_activation_hook( 'microtrx-bitcoin-paywall/microtrx-bitcoin-paywall.php', 'microtrx_install_db' );
add_action( 'plugins_loaded', 'microtrx_update_db_check' );


?>
