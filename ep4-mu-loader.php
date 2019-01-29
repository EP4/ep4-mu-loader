<?php
/**
 * EP4 Must-Use Plugins Autoloader
 *
 * This must-use plugin loads all plugins inside subdirectories of the /mu-plugins directory. Simply
 * drop this file at the root of the mu-plugins folder, and it'll take care of the rest!
 *
 * @link                http://ep4.com
 * @since               1.0.0
 * @package             EP4_MU_Loader
 *
 * @wordpress-plugin
 * Plugin Name:         EP4 Must-Use Plugins Autoloader
 * Plugin URI:          https://wpcaptain.com
 * Description:         This must-use plugin loads all plugins inside subdirectories of the /mu-plugins directory.
 * Version:             1.0
 * Author:              Dave Lavoie, EP4
 * Author URI:          https://ep4.com
 * License:             GPL-3.0+
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @ep4-compatibility-checker
 * Requires At Least:   4.7
 * Tested Up To:
 * PHP:                 5.2.4
 * MySQL:
 * Required Plugins:
 * Required Constants:
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this plugin. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 */

defined( 'ABSPATH' ) || die( 'Whaddya think ye doing?' );

/**
 * Must-Use Plugins Autoloader
 *
 * This must-use plugin loads all plugins inside subdirectories of the /mu-plugins directory.
 *
 * @since      1.0.0
 * @package    EP4_MU_Loader
 * @author     Dave Lavoie <dave.lavoie@ep4.com>
 */
class EP4_MU_Loader {
	/**
	 * Headers we're looking for when searching for main plugin files.
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @var      array    $headers        Headers we're looking for when searching for main plugin files.
	 */
	private $headers = array(
		'Name'        => 'Plugin Name',
		'PluginURI'   => 'Plugin URI',
		'Description' => 'Description',
		'Version'     => 'Version',
		'Author'      => 'Author',
		'AuthorURI'   => 'Author URI',
		'TextDomain'  => 'Text Domain',
		'DomainPath'  => 'Domain Path',
		'slug'        => 'slug',
	);

	/**
	 * List of Must-Use plugins that have been found in the WPMU_PLUGIN_DIR.
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @var      array    $mu_plugins     List of Must-Use plugins that have been found in the WPMU_PLUGIN_DIR.
	 */
	private $mu_plugins;

	/**
	 * Position of the pointer in the plugin loop.
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @var      array    $pointer        Position of the pointer in the plugin loop.
	 */
	private $pointer = 0;

	/**
	 * Reference to the current object for use in global context.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @static
	 *
	 * @see      self::this()
	 *
	 * @var      object   $this           Reference to the current object.
	 */
	private static $this = null;

	/**
	 * Create a single instance of the current class when called for the first time, and return the reference to it afterwards.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @static
	 *
	 * @uses     $this->__construct()
	 *
	 * @return   object  self::$this  Reference to the current class instance.
	 */
	public static function this() {
		return ! empty( self::$this ) ? self::$this : self::$this = new self();
	}

	/**
	 * Create the loader instance and prepare hooks. Can only be self constructed.
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @used-by  self::this() in global context.
	 */
	private function __construct() {
		add_filter( 'show_advanced_plugins', array( $this, 'add_mu_plugin_rows' ),           10, 2 );
		add_filter( 'plugin_row_meta',       array( $this, 'edit_mu_plugin_row_meta' ),      10, 4 );
		add_filter( 'plugins_api_result',    array( $this, 'hide_mu_plugins_install_link' ), 10, 3 );
	}

	/**
	 * Check the must-use plugins directory and retrieve all plugin files with plugin data.
	 *
	 * This method only supports plugin files in the base plugins directory
	 * (wp-content/mu-plugins) and in one directory above the plugins directory
	 * (wp-content/mu-plugins/my-plugin).
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 * @uses     get_file_data()
	 * @used-by  $this->have_mu_plugins()
	 * @used-by  $this->the_mu_plugin()
	 * @used-by  $this->add_mu_plugin_rows()
	 *
	 * @return   $this->mu_plugins  Array of mu plugins where the key is the plugin file path and the value is an array of the plugin data.
	 */
	private function get_mu_plugins() {
		if ( isset( $this->mu_plugins ) ) {
			return $this->mu_plugins; // We already have a list of MU plugins to return.
		}

		$this->mu_plugins = array();
		$plugin_dir       = @ opendir( WPMU_PLUGIN_DIR ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! empty( $plugin_dir ) ) {
			while ( ( $file = readdir( $plugin_dir ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
				if ( substr( $file, 0, 1 ) === '.' ) {
					continue;
				}
				if ( is_dir( path_join( WPMU_PLUGIN_DIR, $file ) ) ) {
					$plugins_subdir = @ opendir( path_join( WPMU_PLUGIN_DIR, $file ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
					if ( $plugins_subdir ) {
						while ( ( $subfile = readdir( $plugins_subdir ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
							if ( substr( $subfile, 0, 1 ) === '.' ) {
								continue;
							}
							if ( substr( $subfile, -4 ) === '.php' ) {
								$plugin_file = path_join( path_join( WPMU_PLUGIN_DIR, $file ), $subfile );
								if ( ! is_readable( $plugin_file ) ) {
									continue; // Not readable. Go to next file.
								}
								$plugin_data = get_file_data( $plugin_file, $this->headers );
								if ( ! empty( $plugin_data['Name'] ) ) {
									$this->mu_plugins[ $plugin_file ] = $plugin_data;
								}
							}
						}
						closedir( $plugins_subdir );
					}
				}
			}
			closedir( $plugin_dir );
		}
		return $this->mu_plugins;
	}

	/**
	 * Check if there are still MU plugins that must be included or not, based on the pointer position.
	 *
	 * @since    1.0.0
	 * @access   public
	 *
	 * @uses     $this->get_mu_plugins()
	 * @used-by  self::this() in global context.
	 *
	 * @return   bool     TRUE if there are MU plugins to load from subdirectories, FALSE otherwise.
	 */
	public function have_mu_plugins() {
		$mu_plugins = $this->get_mu_plugins();
		if ( empty( $mu_plugins ) ) {
			return false;
		}

		$mu_plugins_keys = array_keys( $mu_plugins );
		$mu_plugin_file  = ! empty( $mu_plugins_keys[ $this->pointer ] ) ? $mu_plugins_keys[ $this->pointer ] : '';
		return ( $this->pointer < count( $mu_plugins ) && file_exists( $mu_plugin_file ) );
	}

	/**
	 * Iterate the pointer index in the loop and return the current plugin file.
	 *
	 * @since    1.0.0
	 * @access   public
	 *
	 * @uses     $this->get_mu_plugins()
	 * @uses     wp_register_plugin_realpath()
	 * @used-by  self::this() in global context.
	 *
	 * @return   string   Must-Use Plugin path ready to be included.
	 */
	public function the_mu_plugin() {
		$mu_plugins = $this->get_mu_plugins();
		if ( empty( $mu_plugins ) ) {
			return '';
		}

		$mu_plugins_keys = array_keys( $mu_plugins );
		$mu_plugin_file  = $mu_plugins_keys[ $this->pointer ];
		$this->pointer   = $this->pointer + 1;

		$this->mu_plugins[ $mu_plugin_file ]['autoload'] = true;
		wp_register_plugin_realpath( $mu_plugin_file );

		return $mu_plugin_file;
	}

	/**
	 * Allows to exclude specific MU plugins so they aren't autoloaded.
	 *
	 * If the $excluded_plugins array contains PHP files, they will be excluded. Otherwise, it'll
	 * loop through all MU plugins available for autoloading, and look for the directory name in their
	 * path, or the presence of a keyword in the path.
	 *
	 * @since    1.0.0
	 * @access   public
	 *
	 * @uses     $this->get_mu_plugins()
	 * @used-by  self::this() in global context.
	 *
	 * @param    array|string $excluded_plugins Either a string representing a plugin file, a plugin directory, or a keyword to search for, or an array of strings.
	 */
	public function exclude_mu_plugins( $excluded_plugins = array() ) {
		// Prepare vars.
		$excluded_plugins = ! is_array( $excluded_plugins ) ? (array) $excluded_plugins : $excluded_plugins;
		$excluded_files   = array();
		$mu_plugins       = $this->get_mu_plugins();

		// Loop through the list of excluded plugins, and exclude them if they exists.
		foreach ( $excluded_plugins as $k => $plugin ) {
			$excluded_plugin_path = path_join( WPMU_PLUGIN_DIR, $plugin );
			// Loop through the list of autoloaded MU plugins and unset those that are meant to be excluded.
			foreach ( array_keys( $mu_plugins ) as $mu_plugin_path ) {
				if ( strpos( $mu_plugin_path, $excluded_plugin_path ) !== false ) {
					unset( $mu_plugins[ $mu_plugin_path ] );
				}
			}
		}

		// Update the $mu_plugins property.
		$this->mu_plugins = $mu_plugins;
	}

	/**
	 * Use the 'show_advanced_plugins' filter to add loaded MU plugins to the table for display.
	 *
	 * We're making use of the 'show_advanced_filter' as if it was an action hook, hence we don't
	 * change the value of the $show parameter.
	 *
	 * @since    1.0.0
	 * @access   public
	 *
	 * @uses     $this->get_mu_plugins()
	 *
	 * @hook     action show_advanced_plugins
	 *
	 * @param    bool   $show Whether to show the advanced plugins for the specified plugin type. Default true.
	 * @param    string $type The plugin type. Accepts 'mustuse', 'dropins'.
	 * @return   bool   $show Original value.
	 */
	public function add_mu_plugin_rows( $show, $type ) {
		// The filter for the 'dropins' type runs after the one used for the 'mustuse' type, allowing us to override
		// the $plugins['mustuse'] global so we can insert the loaded MU plugins to the list.
		if ( 'dropins' === $type ) {
			global $plugins, $orderby;
			$mu_plugins = $this->get_mu_plugins();

			// Change sorting when browsing must-use plugins screen. Combined with setting the value for $mu_plugin_data['Order']
			// below, it'll allow us to nest the loaded plugins just under the row of the loader itself in the MU plugins table.
			if ( ! empty( $_REQUEST['plugin_status'] ) && 'mustuse' === $_REQUEST['plugin_status'] ) { // WPCS: CSRF ok.
				$orderby  = 'Order'; // Override global ok.
				$position = 10000; // Set the order of the loader row to 10000.
				$mu_total = count( $plugins['mustuse'] );
				$basename = plugin_basename( __FILE__ );

				$plugins['mustuse'] = wp_list_sort( $plugins['mustuse'], 'Name', 'ASC', true ); // Override $plugins global.
				foreach ( $plugins['mustuse'] as $plugin_path => $plugin_data ) {
					$plugins['mustuse'][ $plugin_path ]['Order'] = ( $plugin_path === $basename ) ? $position + $mu_total : $position; // Override $plugins global.
					$position++;
				}

				// Add the MU plugins loaded by the autoloader below the row of loader.
				$mu_plugins = wp_list_sort( $mu_plugins, 'Name', 'ASC', true );
				foreach ( $mu_plugins as $mu_plugin_path => $mu_plugin_data ) {
					$position++;
					$mu_plugin_data['slug']   = empty( $mu_plugin_data['slug'] ) ? dirname( plugin_basename( $mu_plugin_path ) ) : $mu_plugin_data['slug'];
					$mu_plugin_data['Order']  = $position;
					$mu_plugin_data['update'] = true; // Will trigger the 'update' CSS class, for styling.

					$plugins['mustuse'][ plugin_basename( $mu_plugin_path ) ] = $mu_plugin_data; // Override $plugins global.
				}
			} else {
				$plugins['mustuse'] += $mu_plugins; // Override $plugins global.
			}
		}

		return $show;
	}

	/**
	 * Edit the MU plugin rows in order to fix the slug and add a parameter to the URL.
	 *
	 * Insert the CSS styles, and add a parameter to the "View details" link for disabling the install button.
	 *
	 * @since    1.0.0
	 * @access   public
	 *
	 * @used-by  $this->__construct()
	 *
	 * @hook     filter plugin_row_meta
	 *
	 * @param    array  $plugin_meta An array of the plugin's metadata, including the version, author, author URI, and plugin URI.
	 * @param    string $plugin_file Path to the plugin file, relative to the plugins directory.
	 * @param    array  $plugin_data An array of plugin data.
	 * @param    string $status      Status of the plugin. Defaults are 'All', 'Active', 'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use', 'Drop-ins', 'Search'.
	 * @return   array  $plugin_meta An array of the plugin's metadata.
	 */
	public function edit_mu_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( 'mustuse' === $status ) {
			// If the current row is the autoloader.
			if ( plugin_basename( __FILE__ ) === $plugin_file ) {
				global $totals;
				$totals['upgrade'] = ! empty( $totals['upgrade'] ) ? $totals['upgrade'] : 1; // Override $totals['upgrade'] global.
				// Insert CSS styles.
				echo '<style>tr.update>[class]{box-shadow:inset 0 -1px 0 rgba(0,0,0,.1)}.update .plugin-title>strong:before{content:"\f322\00a0";font:400 1.2em/0 dashicons;vertical-align:sub;}</style>';
			} elseif ( ! empty( $plugin_data['autoload'] ) ) { // Else if the current row is a plugin loaded by the autoloader.
				// Add a paramater to the querystring for disabling install & update actions for MU plugins.
				foreach ( $plugin_meta as $meta_key => $meta_value ) {
					if ( false !== strpos( $meta_value, 'plugin-install.php' ) ) {
						$plugin_meta[ $meta_key ] = str_replace( 'plugin=', 'disable_actions=true&plugin=', $meta_value );
						break;
					}
				}
			}
		}

		return $plugin_meta;
	}

	/**
	 * Hide the install & update links by filtering the Plugin Installation API response results.
	 *
	 * @since    1.0.0
	 * @access   public
	 *
	 * @used-by  $this->__construct()
	 *
	 * @hook     filter   plugins_api_result
	 *
	 * @param    object|WP_Error $res    Response object or WP_Error.
	 * @param    string          $action The type of information being requested from the Plugin Installation API.
	 * @param    object          $args   Plugin API arguments.
	 * @return   object|WP_Error $res    Filtered response object or WP_Error.
	 */
	public function hide_mu_plugins_install_link( $res, $action, $args ) {
		if ( ! empty( $_REQUEST['disable_actions'] ) && ! empty( $res->download_link ) ) { // WPCS: CSRF ok.
			$res->download_link = false;
		}
		return $res;
	}

}

/**
 * Run the Loader from outside of the class itself in order to prevent any issues.
 *
 * The include_once statement could run from inside the class, but doing so will probably cause sneaky issues as all plugins
 * are usually loaded from the global scope. Better be safe than sorry, and include the plugins in the global scope.
 *
 * To exclude specific folders or plugins from being loaded by the loader, add the following line just 
 * before the while loop. For example, for websites hosted on WPEngine servers, one must add the following:
 * EP4_MU_Loader::this()->exclude_mu_plugins( array( 'wpengine-common', 'force-strong-passwords' ) );
 *
 * @see          https://wordpress.org/support/topic/fatal-error-when-the-plugin-is-loaded-from-outside-of-the-global-scope/
 * @since        1.0.0
 */
if ( EP4_MU_Loader::this()->have_mu_plugins() ) {
	while ( EP4_MU_Loader::this()->have_mu_plugins() ) {
		include_once EP4_MU_Loader::this()->the_mu_plugin();
	}
}
