<?php
/**
 * virtue initial setup and constants
 */
function virtue_setup() {

	// Register wp_nav_menu() menus (http://codex.wordpress.org/Function_Reference/register_nav_menus)
	register_nav_menus(array(
	'primary_navigation'    => __('Primary Navigation', 'virtue'),
	'secondary_navigation'  => __('Secondary Navigation', 'virtue'),
	'mobile_navigation'     => __('Mobile Navigation', 'virtue'),
	'topbar_navigation'     => __('Topbar Navigation', 'virtue'),
	'footer_navigation'     => __('Footer Navigation', 'virtue'),
	));

	add_theme_support( 'title-tag' );
	add_theme_support('post-thumbnails');
	add_image_size( 'widget-thumb', 80, 50, true );
	add_post_type_support( 'attachment', 'page-attributes' );
	add_theme_support( 'automatic-feed-links' );
	add_editor_style('/assets/css/editor-style.css');
}
add_action('after_setup_theme', 'virtue_setup');

function virtue_fav_output(){
	// Keep for fallback, only show if there is no site icon.
	$site_icon_id = get_option( 'site_icon' );
	if ( empty( $site_icon_id ) ) {
		global $virtue;
		if ( isset( $virtue[ 'virtue_custom_favicon' ][ 'url' ] ) && !empty( $virtue[ 'virtue_custom_favicon' ][ 'url' ] ) ) {
			echo '<link rel="shortcut icon" type="image/x-icon" href="'. esc_url( $virtue[ 'virtue_custom_favicon' ][ 'url' ] ).'" />';
		}
	}
}
add_action('wp_head', 'virtue_fav_output', 5);
