<?php

// Block direct access to the main plugin file.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Ascend Premium
function kadence_import_ascend_files() {
  return array(
    array(
      'import_file_name'           => 'Base Demo',
      'categories'                 => array(),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend/base/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend/base/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend/base/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend/base/preview-image-min.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Gallery Demo',
      'categories'                 => array(),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend/gallery/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend/gallery/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend/gallery/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend/gallery/preview-image.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Shop Demo',
      'categories'                 => array(),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend/shop/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend/shop/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend/shop/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend/shop/preview-image-min.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Blogger Demo',
      'categories'                 => array(),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend/blogger/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend/blogger/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend/blogger/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend/blogger/preview-image-min.jpg',
      'import_notice'              => '',
    ),
    
  );
}

function kadence_before_widgets_ascend_import_action($selected_import) {
		$sidebars = $GLOBALS['wp_registered_sidebars'];
		if(!in_array('sidebar1', $sidebars) ){
			$sidebars['sidebar1'] = array(
			    'name' =>'Shop Sidebar',
			    'id' => 'sidebar1',
			    'description' => '',
			    'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
		    );
		}
		$GLOBALS['wp_registered_sidebars'] = $sidebars;
}

function kadence_ascend_after_import( $selected_import ) {
	// ASCEND PREMIUM
	if ( 'Base Demo' === $selected_import['import_file_name'] ) {
			// Assign Woo Pages.
			if( class_exists('Woocommerce') ) {
				kadence_import_demo_woocommerce();
			}

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Main Shopping', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'primary_navigation' => $main_menu->term_id,
					'mobile_navigation' => $main_menu->term_id,
				)
			);

			// Assign front page.			
			$homepage = get_page_by_title( 'Home' );
			if(isset( $homepage ) && $homepage->ID) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $homepage->ID); // Front Page
			}
	} elseif ( 'Gallery Demo' === $selected_import['import_file_name'] ) {
			// Assign Woo Pages.
			if( class_exists('Woocommerce') ) {
				kadence_import_demo_woocommerce();
			}

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Main2', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'primary_navigation' => $main_menu->term_id,
					'mobile_navigation' => $main_menu->term_id,
				)
			);

			// Assign front page.			
			$homepage = get_page_by_title( 'Home' );
			if(isset( $homepage ) && $homepage->ID) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $homepage->ID); // Front Page
			}
	}elseif ( 'Shop Demo' === $selected_import['import_file_name'] ) {
			// Assign Woo Pages.
			if( class_exists('Woocommerce') ) {
				kadence_import_demo_woocommerce();
			}

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Main Shopping', 'nav_menu' );
			$footer = get_term_by( 'name', 'Customer Service', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'left_navigation' => $main_menu->term_id,
					'mobile_navigation' => $main_menu->term_id,
					'footer_navigation' => $footer->term_id,
				)
			);

			// Assign front page.			
			$homepage = get_page_by_title( 'Home' );
			if(isset( $homepage ) && $homepage->ID) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $homepage->ID); // Front Page
			}
	} elseif ( 'Blogger Demo' === $selected_import['import_file_name'] ) {
			// Assign Woo Pages.

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Photography Menu', 'nav_menu' );
			$second = get_term_by( 'name', 'Blogger Second', 'nav_menu' );
			$footer = get_term_by( 'name', 'PhotoBlogger Footer', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'primary_navigation' => $main_menu->term_id,
					'mobile_navigation' => $main_menu->term_id,
					'secondary_navigation' => $second->term_id,
					'footer_navigation' => $footer->term_id,
				)
			);

			wp_delete_post(1);
	} 
}
