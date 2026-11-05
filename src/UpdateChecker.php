<?php
/**
 * GitHub Update Checker — serve plugin updates from GitHub Releases.
 *
 * Drop this class into any plugin, then:
 *
 *     $checker = new Azanumer\UpdateChecker(
 *         __FILE__,
 *         'YourOrg/your-plugin-repo'   // GitHub "owner/repo"
 *     );
 *
 * On each admin update check it asks the GitHub API for the latest release,
 * compares the release tag against your plugin's Version header, and — when
 * the release is newer — tells WordPress an update is available with the
 * release zipball as the download package. WordPress then shows the normal
 * "Update now" flow in Dashboard → Updates and on the Plugins screen.
 *
 * Release tags should be plain versions, optionally prefixed with "v"
 * (v1.2.0 and 1.2.0 are treated the same). For private repos pass a
 * fine-grained personal access token with "Contents: read" as $token.
 *
 * No dependencies. The API response is cached in a transient for 12 hours.
 */

namespace Azanumer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UpdateChecker {

    /** @var string Full path to the plugin's main file. */
    private $plugin_file;

    /** @var string Plugin slug, e.g. "my-plugin/my-plugin.php". */
    private $plugin_slug;

    /** @var string GitHub "owner/repo". */
    private $repo;

    /** @var string Optional token for private repos. */
    private $token;

    /** @var string Transient key for the cached release. */
    private $cache_key;

    public function __construct( $plugin_file, $repo, $token = '' ) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename( $plugin_file );
        $this->repo        = trim( $repo, '/' );
        $this->token       = $token;
        $this->cache_key   = 'ghuc_' . md5( $this->plugin_slug );

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
    }

    /**
     * Current installed version, read from the plugin's Version header.
     */
    public function get_current_version() {
        $data = get_file_data( $this->plugin_file, array( 'Version' => 'Version' ) );
        return isset( $data['Version'] ) ? trim( $data['Version'] ) : '0.0.0';
    }

    /**
     * Latest release from the GitHub API, cached for 12 hours.
     * Returns the decoded release array, or null on any failure.
     */
    public function get_latest_release() {
        $cached = get_site_transient( $this->cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $args = array(
            'headers' => array(
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'WordPress-GitHub-Update-Checker',
            ),
            'timeout' => 15,
        );
        if ( '' !== $this->token ) {
            $args['headers']['Authorization'] = 'Bearer ' . $this->token;
        }

        $response = wp_remote_get( 'https://api.github.com/repos/' . $this->repo . '/releases/latest', $args );

        if ( is_wp_error( $response ) ) {
            return null;
        }
        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return null;
        }

        $release = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $release ) || empty( $release['tag_name'] ) ) {
            return null;
        }

        // Failures are not cached: a transient GitHub hiccup must not hide
        // the next real release check.
        set_site_transient( $this->cache_key, $release, 12 * HOUR_IN_SECONDS );

        return $release;
    }

    /**
     * Strip a leading "v" so v1.2.0 and 1.2.0 compare equal.
     */
    public static function normalize_version( $version ) {
        return ltrim( trim( (string) $version ), 'vV' );
    }

    /**
     * Hooked into the update-plugins transient: add our update when the
     * latest GitHub release is newer than the installed version.
     */
    public function check_for_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new \stdClass();
        }
        if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
            $transient->response = array();
        }

        $release = $this->get_latest_release();
        if ( null === $release ) {
            return $transient;
        }

        $remote_version  = self::normalize_version( $release['tag_name'] );
        $current_version = self::normalize_version( $this->get_current_version() );

        if ( ! version_compare( $remote_version, $current_version, '>' ) ) {
            return $transient;
        }

        $transient->response[ $this->plugin_slug ] = (object) array(
            'slug'        => dirname( $this->plugin_slug ),
            'plugin'      => $this->plugin_slug,
            'new_version' => $remote_version,
            'url'         => isset( $release['html_url'] ) ? $release['html_url'] : 'https://github.com/' . $this->repo,
            // zipball of the release tag — WordPress downloads and installs it.
            'package'     => isset( $release['zipball_url'] ) ? $release['zipball_url'] : '',
        );

        return $transient;
    }

    /**
     * Force a fresh API check on the next update scan (e.g. after a
     * "Check again" click or when debugging).
     */
    public function clear_cache() {
        delete_site_transient( $this->cache_key );
    }
}
