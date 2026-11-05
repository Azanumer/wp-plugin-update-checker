<?php
/**
 * Uninstall: drop the cached release transient.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_site_transient( 'ghuc_' . md5( 'wp-plugin-update-checker/wp-plugin-update-checker.php' ) );
