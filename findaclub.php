<?php 
/*
 * Plugin Name:       Core Site Plugin | findaclub.com.au
 * Plugin URI:        https://findaclub.com.au
 * Description:       Custom Taxonomy, Post Types, Functions, etc.
 * Version:           1.0.0
 * Requires at least: 6.9.0
 * Requires PHP:      8.2.3
 * Author:            Samuel Huth
 * Author URI:        https://samhuth.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 */

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'actions.php';
require_once plugin_dir_path( __FILE__ ) . 'activate.php';
require_once plugin_dir_path( __FILE__ ) . 'admin.php';
require_once plugin_dir_path( __FILE__ ) . 'filters.php';
require_once plugin_dir_path( __FILE__ ) . 'shortcodes.php';

function findaclub_activation_function(){
    // findaclub_register_club_taxonomies();
    // findaclub_register_custom_post_types();
}

function findaclub_deactivation_function(){
    echo 'DEACTIVATED';
}

register_activation_hook(
	__FILE__,
	'findaclub_activation_function'
);

register_deactivation_hook(
	__FILE__,
	'findaclub_deactivation_function'
);



