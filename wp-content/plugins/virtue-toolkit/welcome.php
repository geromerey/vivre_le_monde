<?php 
// Build Welcome Page
 if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'virtue_toolkit_welcome' ) ) {
    class virtue_toolkit_welcome {
    	private $my_theme;
    	public $theme_name;
    	public $version;
    	public function __construct() {
    		if ( is_admin() ) {
    		  	$this->my_theme = wp_get_theme(); // Get theme data
    		  	if($this->my_theme->get( 'Name' ) == 'Ascend' || $this->my_theme->get( 'Name' ) == 'Virtue' || $this->my_theme->get( 'Name' ) == 'Pinnacle' ) {
    		  		$this->theme_name = $this->my_theme->get( 'Name' );
    		  	} elseif ($this->my_theme->get( 'Template') == 'ascend' ){
					$this->theme_name = 'Ascend';
				} elseif($this->my_theme->get( 'Template') == 'virtue' ) {
					$this->theme_name = 'Virtue';
				} elseif($this->my_theme->get( 'Template') == 'pinnacle' ){
    		  		$this->theme_name = 'Pinnacle';
    		  	} else {
    		  		$this->theme_name = 'Not Kadence';
    		  	}
    		  	if($this->theme_name != 'Not Kadence') { 
    		  		add_action( 'admin_menu', array( $this, 'add_menu' ) );
    		  	}
    		  	add_action( 'tgmpa_register', array($this, 'register_importer'), 30);
    		  	add_filter( 'plugin_action_links_virtue-toolkit/virtue_toolkit.php',  array($this, 'add_settings_link') );
    		}
    	}
		// Add option page menu
        public function add_menu() {
            $page = add_theme_page(__( 'Getting Started with: ', 'virtue-toolkit' ) . $this->theme_name, __( 'Getting Started', 'virtue-toolkit' ), 'manage_options', 'kadence_welcome_page', array( $this, 'config_page'));
            add_action( 'admin_print_styles-' . $page, array( $this, 'css_scripts' ) );
        }
	     // Loads admin style sheets
	    public function css_scripts() {
	        wp_enqueue_style( 'toolkit-welcome-css', VIRTUE_TOOLKIT_URL . '/welcome/toolkit-welcome.css', array(), $this->version, 'all');
	       	wp_enqueue_script('toolkit-welcome-js', VIRTUE_TOOLKIT_URL . '/welcome/toolkit-welcome.js',array(), $this->version, true);
	        add_thickbox();
	    }
	    public function config_page() {
	    	if($this->theme_name == 'Not Kadence') { ?>
		    	<div class="wrap kt_theme_welcome">
	            <h2 class="notices"></h2>
	                <div class="kt_title_area">
	                    <h1>
	                        <?php echo __('The Kadence Toolkit is only designed to work with Kadence Themes', 'virtue-toolkit'); ?>
	                    </h1>
	                </div>
	            </div>
            <?php
	    	} else {

            ?>
	            <div class="wrap kt_theme_welcome">
	            <h2 class="notices"></h2>
	                <div class="kt_title_area">
	                    <h1>
	                        <?php echo apply_filters('kt_getting_started_page_title', __('Getting Started with ', 'virtue-toolkit'). $this->theme_name ); ?>
	                    </h1>
	                    <h4>
	                        <?php echo apply_filters('kt_getting_started_page_subtitle', __('Demo content, recomended plugins and helpful links.', 'virtue-toolkit') ); ?>
	                    </h4>
	                </div>
	                <?php ob_start(); ?>
	                <div class="kad-panel-left kt-admin-clearfix">
	                    <div class="kad-panel-contain">
	                        <h2 class="nav-tab-wrapper">
	                            <?php do_action('kt_getting_started_nav_before'); ?>
	                            <a class="nav-tab nav-tab-active" data-tab-id="kt-helplinks" href="#"><?php echo __('Helpful Links', 'virtue-toolkit');?></a>
	                            <a class="nav-tab" data-tab-id="kt-plugins" href="#"><?php echo __('Recomended Plugins', 'virtue-toolkit');?></a>
	                            <a class="nav-tab" data-tab-id="kt-demo-content" href="#"><?php echo __('Demo Content', 'virtue-toolkit');?></a>
	                            <?php do_action('kt_getting_started_nav_after'); ?>
	                        </h2>
	                        <?php do_action('kt_getting_started_before'); ?>
	                        <div id="kt-helplinks" class="nav-tab-content panel_open kt-admin-clearfix">
	                            <div class="kad-helpful-links kt-main">
	                                <h4><?php echo __('Getting Started', 'virtue-toolkit');?></h4>
	                                <?php echo '<a href="http://docs.kadencethemes.com/'.esc_attr(strtolower($this->theme_name)).'-free/" target="_blank">'.esc_html($this->theme_name) . __( ' Documention', 'virtue-toolkit').'</a>';?>
	                                <a href="https://www.kadencethemes.com/kadence-themes-demo-content/" target="_blank"><?php echo __('Demo Content', 'virtue-toolkit');?></a>
	                                <a href="https://www.kadencethemes.com/kadence-tutorials/" target="_blank"><?php echo __('Tutorials', 'virtue-toolkit');?></a>
	                                <h4 class="kt-next-section"><?php echo __('Support', 'virtue-toolkit');?></h4>
	                                <?php if($this->theme_name == 'Ascend') {
	                                	$link = 'https://wordpress.org/support/theme/ascend';
	                                } else if($this->theme_name == 'Virtue') {
	                                	$link = 'https://wordpress.org/support/theme/virtue';
	                                } else if($this->theme_name == 'Pinnacle') {
	                                	$link = 'https://wordpress.org/support/theme/pinnacle';
	                                }
	                                echo '<a href="'.esc_url($link).'" target="_blank">'.esc_html($this->theme_name) . __(' Support Forms', 'virtue-toolkit').'</a>';?>
	                            </div>
	                        </div>
	                        <div id="kt-plugins" class="nav-tab-content kt-admin-clearfix">
	                            <div class="kad-recomended-plugins kt-main">
	                                <h4><?php echo __('Recomended Plugins', 'virtue-toolkit');?></h4>
	                                <p><?php echo __('These are plugins are not required. Just some cool plugins with cool features that can really enhance your site.', 'virtue-toolkit');?></p>
	                                <div class="kt_suggest_section kt-admin-clearfix">
	                                <?php 
	                                    $suggested = $this->suggested_plugins();
	                                    foreach ($suggested as $plugin) {
	                                        echo '<div class="kt_plugin_box">';
	                                        echo '<img src="'.$plugin['image'].'">';
	                                        echo '<h3>'.$plugin['name'].'</h3>';
	                                         echo '<h5>'.$plugin['activated'].'</h5>';
	                                        echo '<p>'.$plugin['desc'].'</p>';
	                                        echo '<a class="kt_plugin_button '.esc_attr($plugin['class']).'" href="'.esc_attr($plugin['action']).'">'.esc_html($plugin['action_title']).'</a>';
	                                        echo '</div>';
	                                    }
	                                    ?>
	                                </div>
	                            </div>
	                        </div>
	                        <div id="kt-demo-content" class="nav-tab-content kt-admin-clearfix">
	                            <div class="kad-recomended-plugins kt-main">
	                                <h4><?php echo __('Install Demo Content Importer', 'virtue-toolkit');?></h4>
	                                <p><?php echo __('This importer plugin allows you to fill your site with demo content from one of the theme demos.', 'virtue-toolkit');?></p>
	                                 <p><?php echo __('For a turorial on how to use the Importer go here:', 'virtue-toolkit'). ' <a href="https://www.kadencethemes.com/kadence-themes-demo-content/" target="_blank">https://www.kadencethemes.com/kadence-themes-demo-content/</a>'; ?></p>
	                                <div class="kt_demo_section kt-admin-clearfix">
	                                    <div class="kt_plugin_box">
	                                       <img src="<?php echo  VIRTUE_TOOLKIT_URL . 'welcome/images/kip_logo.jpg';?>">
	                                        <h3>Kadence Importer</h3>
	                                         <?php if(is_plugin_active('kadence-importer/kadence-importer.php')) { ?>
	                                                <h5>Activated</h5>
	                                                <p>Kadence Importer plugin adds an import tool so you can fill your site with demo content</p>
	                                                <a class="kt_plugin_button" href="<?php echo admin_url('tools.php?page=kadence-importer');?>">Plugin Settings</a>
	                                        <?php } else { ?>
	                                                <h5>Inactive</h5>
	                                                <p>Kadence Importer plugin adds an import tool so you can fill your site with demo content</p>
	                                                <a class="kt_plugin_button" href="<?php echo admin_url('themes.php?page=install-recommended-plugins');?>">Install/Activate Plugin</a>
	                                        <?php } ?>
	                                    </div>
	                                </div>
	                            </div>
	                        </div>
	                        <?php do_action('kt_getting_started_after'); ?>
	                    </div>
	                </div>
	                <div class="kad-panel-bottom kt-admin-clearfix">
	                	<div class="kad-featured-items kt-admin-clearfix">
	                		<div class="featured">
	                			<h4><?php echo __('Featured Theme', 'virtue-toolkit');?></h4>
	                			<a href="https://www.kadencethemes.com/product/ascend-wordpress-theme/?utm_source=toolkit&utm_medium=cpc&utm_campaign=ascend_free" target="_blank">
	                			<img src="<?php echo  VIRTUE_TOOLKIT_URL . 'welcome/images/theme_feat.jpg';?>">
	                			</a>
	                            <a href="https://www.kadencethemes.com/product/ascend-wordpress-theme/?utm_source=toolkit&utm_medium=cpc&utm_campaign=ascend_free" target="_blank"><h3>Ascend</h3></a>
	                            <p>Ascend will surpass your expectations over and over again. Amazingly versatile, easily customizable and loaded with features you will love. Easily install and start right from one of our many demos.</p>
	                            <a href="https://www.kadencethemes.com/product/ascend-wordpress-theme/?utm_source=toolkit&utm_medium=cpc&utm_campaign=ascend_free" target="_blank"><h5>FREE THEME</h5></a>
	                		</div>
	                		<div class="featured">
	                			<h4><?php echo __('Featured Plugin', 'virtue-toolkit');?></h4>
	                			<a href="https://www.kadencethemes.com/product/kadence-woo-extras/?utm_source=toolkit&utm_medium=cpc&utm_campaign=woo_extra" target="_blank">
	                				<img src="<?php echo  VIRTUE_TOOLKIT_URL . 'welcome/images/plugin_feat.jpg';?>">
	                			</a>
	                            <a href="https://www.kadencethemes.com/product/kadence-woo-extras/?utm_source=toolkit&utm_medium=cpc&utm_campaign=woo_extra" target="_blank"><h3>Kadence Woo Extras</h3></a>
	                            <p>Kadence Woo Extras is a powerful plugin for adding special features to your e-commerce site. Advanced product galleries, reviews, cart notices, size charts, variation swatches, and more.</p>
	                            <a href="https://www.kadencethemes.com/product/ascend-wordpress-theme/?utm_source=toolkit&utm_medium=cpc&utm_campaign=ascend_free" target="_blank"><h5>PREMIUM PLUGIN</h5></a>
	                		</div>
	                	</div>
	                </div>
	                <?php 
	                $welcome_content = ob_get_clean();
	                echo apply_filters( 'kt_getting_started_content',  $welcome_content );
	                ?>
	            </div>
                <?php
            }
        }
        public function suggested_plugins() {
            $suggested = array(
            	'siteorigin-panels' => array(
                    'slug'          => 'siteorigin-panels',
                    'plugin_check'  => 'siteorigin-panels/siteorigin-panels.php',
                    'name'          => 'Page Builder',
                    'desc'          => 'A drag and drop, responsive page builder that simplifies building your website.',
                    'image'         => VIRTUE_TOOLKIT_URL . 'welcome/images/pb_logo.jpg',
                    'author'        => 'SiteOrigin'
                ),
                'black-studio-tinymce-widget' => array(
                    'slug'          => 'black-studio-tinymce-widget',
                    'plugin_check'  => 'black-studio-tinymce-widget/black-studio-tinymce-widget.php',
                    'name'          => 'Visual Editor Widget',
                    'desc'          => 'This plugin adds a new Visual Editor widget that allows you to insert rich text and media objects in your sidebars and pagebuilder widget areas.',
                    'image'         => VIRTUE_TOOLKIT_URL . 'welcome/images/ve_logo.jpg',
                    'author'        => 'Black Studio'
                ),
                'woocommerce' => array(
                    'slug'          => 'woocommerce',
                    'plugin_check'  => 'woocommerce/woocommerce.php',
                    'name'          => 'WooCommerce',
                    'desc'          => 'WooCommerce is a free eCommerce plugin that allows you to sell anything, beautifully. Built to integrate seamlessly with WordPress.',
                    'image'         => VIRTUE_TOOLKIT_URL . 'welcome/images/woo_logo.jpg',
                    'author'        => 'WooThemes'
                ),
                'wordpress-seo' => array(
                    'slug'          => 'wordpress-seo',
                    'plugin_check'  => 'wordpress-seo/wp-seo.php',
                    'name'          => 'Yoast SEO',
                    'desc'          => 'Improve your WordPress SEO: Write better content and have a fully optimized WordPress site using Yoast SEO plugin.',
                    'image'         => VIRTUE_TOOLKIT_URL . 'welcome/images/ws_logo.jpg',
                    'author'        => 'Yoast'
                ),
                'contact-form-7' => array(
                    'slug'          => 'contact-form-7',
                    'plugin_check'  => 'contact-form-7/contact-form-7.php',
                    'name'          => 'Contact Form 7',
                    'desc'          => 'Contact Form 7 can manage multiple contact forms, plus you can customize the form and the mail contents flexibly with simple markup.',
                    'image'         => VIRTUE_TOOLKIT_URL . 'welcome/images/cf7_logo.jpg',
                    'author'        => 'Contact Form 7'
                ),
            );
            foreach ($suggested as $plugin) {
                    if(is_plugin_active($plugin['plugin_check'])) {
                        $action = admin_url('plugins.php');
                        $action_title = __('Manage Plugins', 'pinnacle');
                        $activated = __('Activated', 'pinnacle');
                        $class = '';
                    } else {
                        $action = admin_url('plugin-install.php?tab=plugin-information&amp;plugin='.$plugin['slug']);
                        $action_title = 'Install/Activate Plugin';
                        $activated = 'Inactive';
                        $class = 'thickbox onclick';
                    }
                     $output[$plugin['slug']] = array(
                        'image'         => $plugin['image'],
                        'name'          => $plugin['name'],
                        'author'        => $plugin['author'],
                        'desc'          => $plugin['desc'],
                        'action'        => $action,
                        'action_title'  => $action_title,
                        'activated'     => $activated,
                        'class'         => $class,
                    );
            }
            
            return $output;

        }
        public function register_importer() {
			$plugins = array();
			$plugins[] = array(
				'name'     				=> 'Kadence Importer',
				'slug'     				=> 'kadence-importer',
				'source'   				=> 'https://s3.amazonaws.com/ktupdates/importer/kadence-importer.zip', 
				'required' 				=> false, 
				'version' 				=> '2.0', 
				'force_activation' 		=> false, 
				'force_deactivation' 	=> false, 
				'external_url' 			=> '',
			);
			$config = array(
				'domain'       		=> 'virtue-toolkit',         		// Text domain - likely want to be the same as your theme.
				'default_path' 		=> '',                         		// Default absolute path to pre-packaged plugins
				'parent_slug'  		=> 'themes.php',            		// Parent menu slug.
				'menu'         		=> 'install-recommended-plugins', 	// Menu slug
				'has_notices'      	=> false,                       	// Show admin notices or not
				'is_automatic'    	=> false,					   		// Automatically activate plugins after installation or not
				'message' 			=> '',								// Message to output right before the plugins table
				'strings'      		=> array(
					'page_title'                       			=> __( 'Install Recommended Plugins', 'virtue-toolkit' ),
					'menu_title'                       			=> __( 'Theme Plugins', 'virtue-toolkit' ),
					'installing'                       			=> __( 'Installing Plugin: %s', 'virtue-toolkit' ), // %1$s = plugin name
					'oops'                             			=> __( 'Something went wrong with the plugin API.', 'virtue-toolkit' ),
					'notice_can_install_required'     			=> _n_noop( 'This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.', 'virtue-toolkit' ), // %1$s = plugin name(s)
					'notice_can_install_recommended'			=> _n_noop( 'This theme comes packaged with the following premium plugin: %1$s. Plugin is not required, but suggested.', 'This theme comes packaged with the following premium plugins: %1$s. Plugins are not required, but suggested.', 'virtue-toolkit' ), // %1$s = plugin name(s)
					'notice_cannot_install'  					=> _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.', 'virtue-toolkit' ), // %1$s = plugin name(s)
					'notice_can_activate_required'    			=> _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.' , 'virtue-toolkit'), // %1$s = plugin name(s)
					'notice_can_activate_recommended'			=> _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.' , 'virtue-toolkit'), // %1$s = plugin name(s)
					'notice_cannot_activate' 					=> _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.', 'virtue-toolkit' ), // %1$s = plugin name(s)
					'notice_ask_to_update' 						=> _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.' , 'virtue-toolkit'), // %1$s = plugin name(s)
					'notice_cannot_update' 						=> _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.' , 'virtue-toolkit'), // %1$s = plugin name(s)
					'install_link' 					  			=> _n_noop( 'Begin installing plugin', 'Begin installing plugins', 'virtue-toolkit' ),
					'activate_link' 				  			=> _n_noop( 'Activate installed plugin', 'Activate installed plugins', 'virtue-toolkit' ),
					'return'                           			=> __( 'Return to recommended Plugins Installer', 'virtue-toolkit' ),
					'plugin_activated'                 			=> __( 'Plugin activated successfully.', 'virtue-toolkit' ),
					'complete' 									=> __( 'All plugins installed and activated successfully. %s', 'virtue-toolkit' ), // %1$s = dashboard link
					'nag_type'									=> 'updated' // Determines admin notice type - can only be 'updated' or 'error'
				)
			);
			if($this->theme_name != 'Not Kadence') { 
				tgmpa( $plugins, $config );
			}
		}
		public function add_settings_link( $links ) {
    		$settings_link = '<a href="'.admin_url('themes.php?page=kadence_welcome_page').'">' . __( 'Settings', 'virtue-toolkit' ) . '</a>';
    		array_push($links, $settings_link );
  			return $links;
		}
	}
}

$GLOBALS['virtue_toolkit_welcome'] = new virtue_toolkit_welcome;
