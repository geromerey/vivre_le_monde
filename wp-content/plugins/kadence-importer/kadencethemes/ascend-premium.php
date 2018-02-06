<?php

// Block direct access to the main plugin file.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Ascend Premium
function kadence_import_ascend_premium_files() {
  return array(
  	array(
      'import_file_name'           => 'Base Site',
      'categories'                 => array( 'Woocommerce', 'Kadence Slider' ),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/base_site/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/base_site/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/base_site/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/base_site/preview-image.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Travel Site',
      'categories'                 => array( 'Woocommerce', 'Kadence Slider' ),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/travel_site/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/travel_site/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/travel_site/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/travel_site/preview-image.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Shopping Site',
      'categories'                 => array( 'Woocommerce', 'Kadence Slider' ),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/shopping_site/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/shopping_site/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/shopping_site/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/shopping_site/preview-image.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Agency Site',
      'categories'                 => array('Portfolio'),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/agency_site/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/agency_site/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/agency_site/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/agency_site/preview-image.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Video Portfolio Site',
      'categories'                 => array('Kadence Slider', 'Portfolio' ),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/video_site/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/video_site/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/video_site/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/video_site/preview-image.jpg',
      'import_notice'              => __( 'Due to the size of video files this importer only imports a very low quality 3 sec video for the slider.' ),
    ),
    array(
      'import_file_name'           => 'Magazine Site',
      'categories'                 => array(),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/mag_site/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/mag_site/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/mag_site/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/mag_site/preview-image.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Photographer Site',
      'categories'                 => array('Portfolio'),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/photo_site/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/photo_site/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/photo_site/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/photo_site/preview-image.jpg',
      'import_notice'              => '',
    ),
    array(
      'import_file_name'           => 'Church Site',
      'categories'                 => array( 'Kadence Slider' ),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/church_site/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/church_site/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/church_site/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/church_site/preview-image.jpg',
      'import_notice'              => 'IMPORTANT, this demo includes content from these free plugins: <a target="blank" href="https://wordpress.org/plugins/event-organiser/">Event Organiser</a>, <a href="https://wordpress.org/plugins/seriously-simple-podcasting/" target="blank">Seriously Simple Podcasting</a> and <a href="https://wordpress.org/plugins/give/" target="blank">Give - Donation Plugin</a>. Without these plugins installed the entire demo will not be able to fully install. BEFORE YOU IMPORT go and install those plugins.',
    ),
    array(
      'import_file_name'           => 'Resturant Site',
      'categories'                 => array( 'Kadence Slider' ),
      'import_file_url'            => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/restaurant_site/demo_content.xml',
      'import_widget_file_url'     => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/restaurant_site/widget_data.json',
      'import_customizer_file_url' => '',
      'import_redux'               => array(
        array(
          'file_url'    => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/restaurant_site/theme_options.json',
          'option_name' => 'ascend',
        ),
      ),
      'import_preview_image_url'   => 'https://s3.amazonaws.com/ktdemocontent/ascend-premium/restaurant_site/preview-image.jpg',
      'import_notice'              => '',
    ),
     array(
      'import_file_name'           => 'Free Demo Site',
      'categories'                 => array('Woocommerce', 'Portfolio'),
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
      'import_file_name'           => 'Free Demo Gallery Site',
      'categories'                 => array('Woocommerce', 'Portfolio'),
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
      'import_file_name'           => 'Free Demo Shop Site',
      'categories'                 => array('Woocommerce', 'Portfolio'),
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
      'import_file_name'           => 'Free Demo Blogger Site',
      'categories'                 => array('Woocommerce', 'Portfolio'),
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
function kadence_before_widgets_ascend_premium_import_action($selected_import) {
	if ( 'Free Demo Site' === $selected_import['import_file_name'] || 'Free Demo Gallery Site' === $selected_import['import_file_name'] || 'Free Demo Shop Site' === $selected_import['import_file_name'] || 'Shopping Site' === $selected_import['import_file_name'] ) {
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
}
function kadence_ascend_premium_after_import( $selected_import ) {
	// ASCEND PREMIUM
	if ( 'Base Site' === $selected_import['import_file_name'] ) {

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );

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

	} else  if ( 'Travel Site' === $selected_import['import_file_name'] ) {
			// Assign Woo Pages.
			if( class_exists('Woocommerce') ) {
				kadence_import_demo_woocommerce('Travel Gear');
			}

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Travel Menu', 'nav_menu' );

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

			// Import Sliders
			$kspslider_directory = KADENCE_IMPORTER_PATH . 'kadencethemes/ascend_premium/travel_site/ksp_sliders/';
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
	} elseif ( 'Shopping Site' === $selected_import['import_file_name'] ) {
		// Assign Woo Pages.
			if( class_exists('Woocommerce') ) {
				kadence_import_demo_woocommerce();
			}

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Main Shopping', 'nav_menu' );
			$top = get_term_by( 'name', 'Resources Shopping', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'secondary_navigation' => $main_menu->term_id,
					'mobile_navigation' => $main_menu->term_id,
					'topbar_navigation' => $top->term_id,
				)
			);

			// Assign front page.			
			$homepage = get_page_by_title( 'Home' );
			if(isset( $homepage ) && $homepage->ID) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $homepage->ID); // Front Page
			}

			// Import Sliders
			$kspslider_directory = KADENCE_IMPORTER_PATH . 'kadencethemes/ascend_premium/shopping_site/ksp_sliders/';
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
	} elseif ( 'Agency Site' === $selected_import['import_file_name'] ) {

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Agency Menu', 'nav_menu' );
			$footer = get_term_by( 'name', 'Agency Footer', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'primary_navigation' => $main_menu->term_id,
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

	} elseif ( 'Video Portfolio Site' === $selected_import['import_file_name'] ) {

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Video Menu', 'nav_menu' );

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
			// Import Sliders
			$kspslider_directory = KADENCE_IMPORTER_PATH . 'kadencethemes/ascend_premium/video_site/ksp_sliders/';
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

	} elseif ( 'Magazine Site' === $selected_import['import_file_name'] ) {

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Magazine Menu', 'nav_menu' );
			$top = get_term_by( 'name', 'Magazine Topbar', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'secondary_navigation' => $main_menu->term_id,
					'mobile_navigation' => $main_menu->term_id,
					'topbar_navigation' => $top->term_id,
				)
			);

			// Assign front page.			
			$homepage = get_page_by_title( 'Home' );
			if(isset( $homepage ) && $homepage->ID) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $homepage->ID); // Front Page
			}

	} elseif ( 'Photographer Site' === $selected_import['import_file_name'] ) {

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Photography Menu', 'nav_menu' );
			$footer = get_term_by( 'name', 'Photography Footer', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'primary_navigation' => $main_menu->term_id,
					'mobile_navigation' => $main_menu->term_id,
					'footer_navigation' => $top->term_id,
				)
			);

			// Assign front page.			
			$homepage = get_page_by_title( 'Home' );
			if(isset( $homepage ) && $homepage->ID) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $homepage->ID); // Front Page
			}

	} elseif ( 'Church Site' === $selected_import['import_file_name'] ) {

			// Assign menus to their locations.
			$main_menu = get_term_by( 'name', 'Church Main', 'nav_menu' );
			$footer = get_term_by( 'name', 'Church Resources', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'primary_navigation' => $main_menu->term_id,
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

			// Import Sliders
			$kspslider_directory = KADENCE_IMPORTER_PATH . 'kadencethemes/ascend_premium/church_site/ksp_sliders/';
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
	} elseif ( 'Resturant Site' === $selected_import['import_file_name'] ) {

			// Assign menus to their locations.
			$right_menu = get_term_by( 'name', 'R Right Menu', 'nav_menu' );
			$left_menu = get_term_by( 'name', 'R Left Main', 'nav_menu' );
			$full = get_term_by( 'name', 'Mobile Nav', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'left_navigation' => $left_menu ->term_id,
					'right_navigation' => $right_menu->term_id,
					'mobile_navigation' => $full->term_id,
					'footer_navigation' => $full->term_id,
				)
			);

			// Assign front page.			
			$homepage = get_page_by_title( 'Home' );
			if(isset( $homepage ) && $homepage->ID) {
				update_option('show_on_front', 'page');
				update_option('page_on_front', $homepage->ID); // Front Page
			}

			// Import Sliders
			$kspslider_directory = KADENCE_IMPORTER_PATH . 'kadencethemes/ascend_premium/restaurant_site/ksp_sliders/';
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
	} else if ( 'Free Demo Site' === $selected_import['import_file_name'] ) {
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
	} elseif ( 'Free Demo Gallery Site' === $selected_import['import_file_name'] ) {
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
	} elseif ( 'Free Demo Shop Site' === $selected_import['import_file_name'] ) {
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
	} elseif ( 'Free Demo Blogger Site' === $selected_import['import_file_name'] ) {
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
