<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://sitesbyyogi.com
 * @since      1.0.0
 *
 * @package    Nfinite_Dash
 * @subpackage Nfinite_Dash/admin
 */

class Nfinite_Dash_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Prevent duplicate dashboard rendering.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var bool $rendered Tracks if dashboard has already been displayed.
	 */
	private static $rendered = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name  The name of this plugin.
	 * @param    string    $version      The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// ✅ Hook into WordPress Admin Menu
		add_action('admin_menu', array($this, 'add_admin_menu'));

		// ✅ Enqueue Admin Styles & Scripts
		add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

	/**
	 * ✅ Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'css/nfinite-dash-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * ✅ Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'js/nfinite-dash-admin.js',
			array('jquery'),
			$this->version,
			true
		);
	}

	/**
	 * ✅ Add Custom Admin Menu for Nfinite Dashboard
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			__('Nfinite Dashboard', 'nfinite-dash'),
			__('Nfinite Dashboard', 'nfinite-dash'),
			'manage_options',
			'nfinite-dash',
			array($this, 'render_admin_dashboard'),
			'dashicons-dashboard',
			2
		);
	}

	/**
	 * ✅ Render the Admin Dashboard Page (Prevents Double Rendering)
	 *
	 * @since    1.0.0
	 */
	public function render_admin_dashboard() {
		// Prevent duplicate rendering
		if (self::$rendered) {
			return;
		}
		self::$rendered = true;

		// ✅ Include the Admin Dashboard Display File
		$display_file = plugin_dir_path(__FILE__) . 'partials/nfinite-dash-admin-display.php';
		if (file_exists($display_file)) {
			include $display_file;
		} else {
			echo '<div class="error"><p>Dashboard display file missing.</p></div>';
		}
	}
}
