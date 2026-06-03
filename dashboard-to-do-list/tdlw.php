<?php
/*
	Plugin Name: Dashboard To-Do List
	Description: Dashboard To-Do list widget with option to show as a floating list on your website.
	Version: 2.0.0
	Author: AR Web Design
	Author URI: https://arwebdesign.co.uk
	License: GPL2
	Text Domain: dashboard-to-do-list
	Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

function ardtdw_version() {
	static $ver;
	if ( ! $ver ) {
		$data = get_plugin_data( __FILE__ );
		$ver  = $data['Version'];
	}
	return $ver;
}

// Text domain
function dashboard_to_do_list_load_plugin_textdomain() {
	load_plugin_textdomain( 'dashboard-to-do-list', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'dashboard_to_do_list_load_plugin_textdomain' );

// Frontend scripts 
function ardtdw_widget_scripts() {
	wp_enqueue_style(  'ardtdw_widget_css', plugins_url( '/public/assets/todo-widget.css', __FILE__ ), array(), ardtdw_version() );
	wp_enqueue_script( 'ardtdw_widget_js',  plugins_url( '/public/assets/todo-widget.js',  __FILE__ ), array(), ardtdw_version(), true );
}
add_action( 'wp_enqueue_scripts', 'ardtdw_widget_scripts' );

// Admin scripts 
function ardtdw_widget_scripts_admin() {
	wp_enqueue_style(  'wp-color-picker' );
	wp_enqueue_style(  'ardtdw_widget_admincss', plugins_url( '/admin/assets/widgets.css', __FILE__ ), array(), ardtdw_version() );
	wp_enqueue_script( 'ardtdw_widget_adminjs',  plugins_url( '/admin/assets/widgets.js',  __FILE__ ), array( 'jquery', 'wp-color-picker' ), ardtdw_version(), true );
	wp_localize_script( 'ardtdw_widget_adminjs', 'ardtdwAdmin', array(
		'nonce' => wp_create_nonce( 'ardtdw_update_list' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'ardtdw_widget_scripts_admin' );

// Plugin link
function ardtdw_plugin_link($links) {
  $settings_link = '<a href="index.php">' . esc_html__('Edit List', 'arwd') . '</a>';
  array_unshift($links, $settings_link);
  return $links;
	}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ardtdw_plugin_link');

// Load modules 
include_once 'public/todo-widget-html.php';
require_once 'admin/todo-widget.php';
