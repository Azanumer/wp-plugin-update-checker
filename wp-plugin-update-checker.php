<?php
/**
 * Plugin Name: GitHub Update Checker
 * Plugin URI: https://github.com/Azanumer/wp-plugin-update-checker
 * Description: Lets any WordPress plugin serve its updates from GitHub Releases — drop in src/UpdateChecker.php, point it at your repo, and WordPress shows the normal "Update now" flow. This plugin checks itself for updates as a live demo.
 * Version: 1.0.0
 * Author: Azan Umer
 * Author URI: https://azanumer.cv
 * License: MIT
 * Text Domain: wp-plugin-update-checker
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/src/UpdateChecker.php';

/**
 * This plugin updates itself from its own GitHub repo — the same two lines
 * any other plugin needs. Replace the repo string with your own.
 */
new Azanumer\UpdateChecker( __FILE__, 'Azanumer/wp-plugin-update-checker' );
