<?php

// Block direct access to the main plugin file.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once KADENCE_IMPORTER_PATH . 'kadencethemes/virtue-premium.php';
require_once KADENCE_IMPORTER_PATH . 'kadencethemes/pinnacle-premium.php';
require_once KADENCE_IMPORTER_PATH . 'kadencethemes/ascend-premium.php';
require_once KADENCE_IMPORTER_PATH . 'kadencethemes/virtue-bold.php';
require_once KADENCE_IMPORTER_PATH . 'kadencethemes/pinnacle.php';
require_once KADENCE_IMPORTER_PATH . 'kadencethemes/virtue.php';
require_once KADENCE_IMPORTER_PATH . 'kadencethemes/ascend.php';

function kadence_init_importer() {

	if ( isset ( $_GET['page'] ) && $_GET['page'] == "kadence-importer" ) {
		 	add_action( 'admin_head', 'kadence_importer_admin_styles' );
	}
	$theme = kadence_themename();
    	if('Virtue - Premium' == $theme) {
                     add_filter( 'pt-ocdi/import_files', 'kadence_import_virtue_premium_files' );
                     add_action( 'pt-ocdi/before_widgets_import', 'kadence_before_widgets_virtue_premium_import_action', 3 );
                     add_action( 'pt-ocdi/after_import', 'kadence_virtue_premium_after_import' );
        } elseif('Pinnacle Premium' == $theme) {
                    add_filter( 'pt-ocdi/import_files', 'kadence_import_pinnacle_premium_files' );
                    add_action( 'pt-ocdi/before_widgets_import', 'kadence_before_widgets_pinnacle_premium_import_action', 3 );
                    add_action( 'pt-ocdi/after_import', 'kadence_pinnacle_premium_after_import' );
    	} elseif('Virtue' == $theme) {
                	add_filter( 'pt-ocdi/import_files', 'kadence_import_virtue_files' ); 
                	add_action( 'pt-ocdi/after_import', 'kadence_virtue_after_import' );  
    	} elseif('Pinnacle' == $theme) {
                	add_filter( 'pt-ocdi/import_files', 'kadence_import_pinnacle_files' );
                	add_action( 'pt-ocdi/after_import', 'kadence_pinnacle_after_import' );
        } elseif('Virtue Premium - Bold' == $theme) {
                    add_filter( 'pt-ocdi/import_files', 'kadence_import_virtue_bold_files' );
                    add_action( 'pt-ocdi/after_import', 'kadence_virtue_bold_after_import' );
        } elseif('Ascend - Premium' == $theme) {     
                     add_filter( 'pt-ocdi/import_files', 'kadence_import_ascend_premium_files' );
                     add_action( 'pt-ocdi/before_widgets_import', 'kadence_before_widgets_ascend_premium_import_action', 3 );
                     add_action( 'pt-ocdi/after_import', 'kadence_ascend_premium_after_import' );
        } elseif('Ascend' == $theme) { 
                     add_filter( 'pt-ocdi/import_files', 'kadence_import_ascend_files' );
                     add_action( 'pt-ocdi/before_widgets_import', 'kadence_before_widgets_ascend_import_action', 3 );
                     add_action( 'pt-ocdi/after_import', 'kadence_ascend_after_import' );
		}
}
add_action('plugins_loaded','kadence_init_importer');
function kadence_importer_admin_styles() {
	?>
    <link rel='stylesheet' id='importer-css' href='<?php echo KADENCE_IMPORTER_URL ?>kadencethemes/styles.css' type='text/css' media='all'/>
   <?php
}
function kadence_themename() {
	$the_theme = wp_get_theme();
	$child = $the_theme->get('template');
	if( isset( $child ) && !empty( $child ) ) {
		if($the_theme->get('template') == 'virtue') {
			return "Virtue";
		} else if ($the_theme->get('template') == 'virtue_premium'){
			return "Virtue - Premium";
		} else if ($the_theme->get('template') == 'ascend_premium'){
			return "Ascend - Premium";
		} else if ($the_theme->get('template') == 'ascend'){
			return "Ascend";
		} else if ($the_theme->get('template') == 'pinnacle'){
			return "Pinnacle";
		} else if ($the_theme->get('template') == 'pinnacle_premium'){
			return "Pinnacle Premium";
		} else {
			return "notkadence";
		}
	} else {
		return $the_theme->get('Name');
	}
}
function kt_check_woocommerce() {
	if (class_exists('woocommerce'))  {
		return true;
	}
	return false;
}
function kt_check_kadenceslider() {
	if( is_plugin_active('kadence-slider/kadence-slider.php'))  {
		return true;
	}
	return false;
}
function kt_check_pagebuilder() {
	if( is_plugin_active('siteorigin-panels/siteorigin-panels.php'))  {
		return true;
	}
	return false;
}
function kt_check_visualeditor() {
	if( is_plugin_active('black-studio-tinymce-widget/black-studio-tinymce-widget.php'))  {
		return true;
	}
	return false;
}
function kt_check_virtuetoolkit() {
	if( is_plugin_active('virtue-toolkit/virtue_toolkit.php'))  {
		return true;
	}
	return false;
}
function kadence_importer_page_setup( $default_settings ) {
	$default_settings['parent_slug'] = 'tools.php';
	$default_settings['page_title']  = esc_html__( 'Kadence Themes Importer' , 'kadence-importer' );
	$default_settings['menu_title']  = esc_html__( 'Kadence Importer' , 'pt-ocdi' );
	$default_settings['capability']  = 'import';
	$default_settings['menu_slug']   = 'kadence-importer';

	return $default_settings;
}
add_filter( 'pt-ocdi/plugin_page_setup', 'kadence_importer_page_setup' );
function kadence_importer_page_title() {
	echo '<h1>Kadence Themes Importer</h1>';
}
add_filter( 'pt-ocdi/plugin_page_title', 'kadence_importer_page_title' );

function kadence_importer_page_content() {
	?>
	<div class="kadence-importer-wrap">
	<div class="importer-notice">
	<h3>Notice</h3>
	<h4 class="kt-subhead"><?php echo __( '*Using this importer will override all your theme options settings.', 'kadence-importer' ); ?></h4>
	<h4 class="kt-subhead"><?php echo __( '*This importer is designed for new/empty sites with no content.', 'kadence-importer' ); ?></h4>
	<h4 class="kt-subhead"><?php echo __( '*If you want to reset your site after testing out the demo content you can use this plugin:', 'kadence-importer'); 
	echo '<a href="https://wordpress.org/plugins/wordpress-reset/">'.__("WordPress Reset", "kadence-importer").'</a>'; ?></h4>
	</div>
	<div class="plugin-theme-notice">
    <?php 
    	$theme = kadence_themename();
    	switch($theme) {
    			case 'Virtue - Premium' :

                     $theme_data = array(
                        'plugins' => array(
                            '0' => 'pagebuilder',
                            '1' => 'woocommerce',
                            '3' => 'kadenceslider',
                            ),
                        );
    			
    			break;
    			case 'Pinnacle Premium' : 

                    $theme_data = array(
                       
                        'plugins' => array(
                            '0' => 'pagebuilder',
                            '1' => 'visualeditor',
                            '2' => 'woocommerce',
                            '4' => 'kadenceslider',
                            ),
                        );
    			
    			break;
    			case 'Virtue' : 

                $theme_data = array(
                        'plugins' => array(
                            '0' => 'virtuetoolkit',
                            '1' => 'woocommerce',
                            ),
                        );    			
    			break;
    			case 'Pinnacle' : 

                $theme_data = array(
                        'plugins' => array(
                            '0' => 'virtuetoolkit',
                            '1' => 'woocommerce',
                            ),
                        );

    			break;
                case 'Virtue Premium - Bold' :
                     $theme_data = array(
                        'plugins' => array(
                            '0' => 'pagebuilder',
                            '1' => 'woocommerce',
                            '3' => 'kadenceslider',
                            ),
                        );
                
                break;
                case 'Ascend - Premium' :
                     $theme_data = array(
                        'plugins' => array(
                            '0' => 'pagebuilder',
                            '1' => 'woocommerce',
                            '3' => 'kadenceslider',
                            ),
                        );
                
                break;
                case 'Ascend' :
                     $theme_data = array(
                        'plugins' => array(
                            '0' => 'virtuetoolkit',
                            '1' => 'woocommerce',
                            ),
                        );
                
                break;
				default:
				 	$theme_data = 'none';
					echo '<div class="not-kadence-notice">'.__('No Kadence Theme activated. If you are using a child theme please activate the Parent Theme to import demo content.', 'kadence-importer').'</div>';

		} 
    	if($theme_data != 'none') {
                    foreach ($theme_data['plugins'] as $key => $pluginname) {
                        if($pluginname == 'pagebuilder') {
                            if(kt_check_pagebuilder()) {
                                echo '<p class="kt-active-plugin">'.__('Page Builder Activated', 'kadence-importer').'</p>';
                            } else {
                                echo '<p class="kt-inactive-plugin"><span>'.__('Page Builder Inactive.', 'kadence-importer').'</span><br>'.__('If you would like to install demo content with page builder elements please activate page builder', 'kadence-importer').'</p>';
                            }
                        }
                        if($pluginname == 'visualeditor') {
                            if(kt_check_visualeditor()) {
                                echo '<p class="kt-active-plugin">'.__('Black Studio TinyMCE Widget Activated', 'kadence-importer').'</p>';
                            } else {
                                echo '<p class="kt-inactive-plugin"><span>'.__('Black Studio TinyMCE Widget Inactive.', 'kadence-importer').'</span><br>'.__('If you would like to install demo content with page builder elements please activate Black Studio TinyMCE Widget', 'kadence-importer').'</p>';
                            }
                        }
                        if($pluginname == 'woocommerce') {
                            if(kt_check_woocommerce()) {
                                echo '<p class="kt-active-plugin">'.__('Woocommerce Activated', 'kadence-importer').'</p>';
                            } else {
                                echo '<p class="kt-inactive-plugin kt-woonotice"><span>'.__('Woocommerce Inactive.', 'kadence-importer').'</span><br>'.__('If you would like to install the shop demo please activate woocommerce', 'kadence-importer').'</p>';
                            }
                        }
                        if($pluginname == 'kadenceslider') {
                            if(kt_check_kadenceslider()) {
                                echo '<p class="kt-active-plugin">'.__('Kadence Slider Activated', 'kadence-importer').'</p>';
                            } else {
                                echo '<p class="kt-inactive-plugin"><span>'.__('Kadence Slider Inactive.', 'kadence-importer').'</span><br>'.__('If you would like to install the demo sliders activate the Kadence Slider.', 'kadence-importer').'</p>';
                            }
                        }
                        if($pluginname == 'virtuetoolkit') {
                            if(kt_check_virtuetoolkit()) {
                                echo '<p class="kt-active-plugin">'.__('Kadence Toolkit Activated', 'kadence-importer').'</p>';
                            } else {
                                echo '<p class="kt-inactive-plugin"><span>'.__('Kadence Toolkit Inactive.', 'kadence-importer').'</span><br>'.__('If you would like to install demo content with portfolio options please activate the toolkit.', 'kadence-importer').'</p>';
                            }
                        }
                    }
    }
                ?>
                </div>
</div>
<?php
}
add_filter( 'pt-ocdi/plugin_intro_text', 'kadence_importer_page_content' );
add_filter( 'pt-ocdi/disable_pt_branding', '__return_true' );
add_filter( 'pt-ocdi/regenerate_thumbnails_in_content_import', '__return_false' );

function kadence_import_demo_woocommerce($shop = 'Shop', $cart = 'Cart', $checkout = 'Checkout', $myaccount = 'My Account') {

		$woopages = array(
			'woocommerce_shop_page_id' => $shop,
			'woocommerce_cart_page_id' => $cart ,
			'woocommerce_checkout_page_id' => $checkout,
			'woocommerce_myaccount_page_id' => $myaccount,
		);
		foreach($woopages as $woo_page_name => $woo_page_title) {
			$woopage = get_page_by_title( $woo_page_title );
			if(isset( $woopage ) && $woopage->ID) {
				update_option($woo_page_name, $woopage->ID); // Front Page
			}
		}

		// We no longer need to install pages
		delete_option( '_wc_needs_pages' );
		delete_transient( '_wc_activation_redirect' );

		// Flush rules after install
		flush_rewrite_rules();

}
