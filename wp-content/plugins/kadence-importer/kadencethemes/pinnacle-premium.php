<?php

// Block direct access to the main plugin file.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Demo sites
function kadence_import_pinnacle_premium_files() {
  return array(
    array(
      'import_file_name'           => 'Style 01',
      'categories'                 => array( ),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_01/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_01/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_01/theme_options.json',
          'option_name' => 'pinnacle',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_01/preview-image.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Style 02',
      'categories'                 => array( ),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_02/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_02/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_02/theme_options.json',
          'option_name' => 'pinnacle',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_02/preview-image.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Style 03',
      'categories'                 => array(),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_03/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_03/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_03/theme_options.json',
          'option_name' => 'pinnacle',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/pinnacle-premium/site_style_03/preview-image.jpg',
      'import_notice'              => '',
    ),
  );
}
function kadence_before_widgets_pinnacle_premium_import_action($selected_import) {
	if ( 'Style 03' === $selected_import['import_file_name'] ) {
		$sidebars = $GLOBALS['wp_registered_sidebars'];
		if(!in_array('topbarright', $sidebars) ) {
			$sidebars['topbarright'] = array(
			    'name' =>'Topbar Widget',
			    'id' => 'topbarright',
			    'description' => '',
			    'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
		    );
		}
		if(!in_array('footer_double_2', $sidebars) ) {
			$sidebars['footer_double_2'] = array(
			    'name' =>'Topbar Widget',
			    'id' => 'footer_double_2',
			    'description' => '',
			    'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
		    );
		}
		$GLOBALS['wp_registered_sidebars'] = $sidebars;
	}
}
// after Import
function kadence_pinnacle_premium_after_import( $selected_import ) {
	if ( 'Style 01' === $selected_import['import_file_name'] ) {
			// Assign Woo Pages.
			if( class_exists('Woocommerce') ) {
				kadence_import_demo_woocommerce();
			}

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Main1', 'nav_menu' );
			$top_menu = get_term_by( 'name', 'Top1', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'primary_navigation' => $main_menu->term_id,
					'topbar_navigation' => $top_menu->term_id,
				)
			);

			// Assign front page.			
			$homepage = get_page_by_title( 'Home' );
			if(isset( $homepage ) && $homepage->ID) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $homepage->ID); // Front Page
			}

			// Import Sliders
			$kspslider_directory = KADENCE_IMPORTER_PATH . 'kadencethemes/pinnacle_premium/site_style_01/ksp_sliders/';
			if( function_exists('ksp_import_direct')  ){
				foreach( glob( $kspslider_directory . '*.zip' ) as $filename ) {
					$filename = basename($filename);
					$ksp_files[] = $kspslider_directory . $filename;
				}
				ob_start();
				foreach( $ksp_files as $ksp_file ) { 
					$response = ksp_import_direct($ksp_file);
				}
				ob_end_clean();
			}
	} elseif ( 'Style 02' === $selected_import['import_file_name'] ) {
		// Assign Woo Pages.
			if( class_exists('Woocommerce') ) {
				kadence_import_demo_woocommerce();
			}

			// Assign menus to their locations.
			$leftmain_menu = get_term_by( 'name', 'LeftMenu2', 'nav_menu' );
			$rightmain_menu = get_term_by( 'name', 'RightMenu2', 'nav_menu' );
			$mobile_menu = get_term_by( 'name', 'MobileMenu2', 'nav_menu' );
			$footer_menu = get_term_by( 'name', 'Footer2', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'left_navigation' => $leftmain_menu->term_id,
					'right_navigation' => $rightmain_menu->term_id,
					'mobile_navigation' => $mobile_menu->term_id,
					'footer_navigation' => $footer_menu->term_id,
				)
			);

			// Assign front page.			
			$homepage = get_page_by_title( 'Home' );
			if(isset( $homepage ) && $homepage->ID) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $homepage->ID); // Front Page
			}

	} elseif ( 'Style 03' === $selected_import['import_file_name'] ) {


			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'MainMenu3', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'mobile_navigation' => $main_menu->term_id,
					'topbar_navigation' => $main_menu->term_id,
				)
			);

			// Assign front page.			
			update_option('show_on_front', 'posts');

	}
}
