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

// Pull in the DB intiializer for installation and upgrade scenarios
include 'microtrx-db-initializer.php';

// Pull in the options page for admin settings
include 'microtrx-options.php';

// Pull in the post meta page for editing posts
include 'microtrx-post-meta.php';

// Pull in the post filter for inserting paywall
include 'microtrx-post-filter.php';

?>
