<?php
/**
 * Plugin Name: MicroTrx Bitcoin Paywall
 * Plugin URI: http://www.microTrx.com
 * Description: A paywall to allow users to charge Bitcoin to see posts.
 * Version: 0.1.1
 * Author: James Poole
 * Author URI: http://www.microTrx.com
 * License: GPL2
 */

 global $wpdb;

 global $microtrx_db_version;
 $microtrx_db_version = '1.0';

 global $microtrx_table_name ;
 $microtrx_table_name = $wpdb->prefix . 'microtrxrequests';

// Pull in the DB intiializer for installation and upgrade scenarios
include 'microtrx-db-initializer.php';

// Pull in the options page for admin settings
include 'microtrx-options.php';

// Pull in the session initializer so we can get the session ID
include 'microtrx-session-initializer.php';

// Pull in the post meta page for editing posts
include 'microtrx-post-meta.php';

// Pull in the post filter for inserting paywall
include 'microtrx-post-filter.php';

?>
