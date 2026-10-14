<?php
/**
 * Removes the plugin's only option when the plugin is deleted.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'wpu_dela_settings' );
