<?php
/*Plugin Name: Weekly Schedule
Plugin URI: http://ylefebvre.ca/wordpress-plugins/weekly-schedule
Description: A plugin used to create a page with a list of TV shows
Version: 3.1
Author: Yannick Lefebvre
Author URI: http://ylefebvre.ca
Copyright 2016  Yannick Lefebvre  (email : ylefebvre@gmail.com)

Contributions to version 2.7 by Daniel R. Baleato

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA*/

$ws_pagehooktop          = "";
$ws_pagehookmoderate     = "";
$ws_pagehooksettingssets = "";
$ws_pagehookstylesheet   = "";
$ws_pagehookreciprocal   = "";

$wsstylesheet = "";

define( 'WEEKLY_SCHEDULE_ADMIN_PAGE_NAME', 'weekly-schedule' );

function ws_get_general_options() {
	$options = get_option( 'WeeklyScheduleGeneral', array() );

	$new_options['stylesheet']           = 'stylesheettemplate.css';
	$new_options['numberschedules']      = 2;
	$new_options['debugmode']            = false;
	$new_options['includestylescript']   = '';
	$new_options['frontpagestylescript'] = false;
	$new_options['version']              = '2.7';
	$new_options['accesslevel']          = 'admin';
	$new_options['csvdelimiter']         = ',';

	$merged_options = wp_parse_args( $options, $new_options );
	$compare_options = array_diff_key( $new_options, $options );
	if ( empty( $options ) || !empty( $compare_options ) ) {
		update_option( 'WS_PP', $merged_options );
	}
	return $merged_options;
}

global $accesslevelcheck;
$accesslevelcheck = '';

$genoptions = ws_get_general_options( 'WeeklyScheduleGeneral' );

if ( !isset( $genoptions['accesslevel'] ) || empty( $genoptions['accesslevel'] ) ) {
	$genoptions['accesslevel'] = 'admin';
}

switch ( $genoptions['accesslevel'] ) {
	case 'admin':
		$accesslevelcheck = 'manage_options';
		break;

	case 'editor':
		$accesslevelcheck = 'manage_categories';
		break;

	case 'author':
		$accesslevelcheck = 'publish_posts';
		break;

	case 'contributor':
		$accesslevelcheck = 'edit_posts';
		break;

	case 'subscriber':
		$accesslevelcheck = 'read';
		break;

	default:
		$accesslevelcheck = 'manage_options';
		break;
}

function ws_get_options( $schedule_id ) {
	$options = get_option( 'WS_PP' . $schedule_id, array() );

	$new_options['starttime']             = 19;
	$new_options['endtime']               = 22;
	$new_options['timedivision']          = 0.5;
	$new_options['tooltipwidth']          = 300;
	$new_options['tooltiptarget']         = 'right center';
	$new_options['tooltippoint']          = 'left center';
	$new_options['tooltipcolorscheme']    = 'ui-tooltip';
	$new_options['displaydescription']    = "tooltip";
	$new_options['daylist']               = '';
	$new_options['timeformat']            = '24hours';
	$new_options['layout']                = 'horizontal';
	$new_options['adjusttooltipposition'] = true;
	$new_options['schedulename']          = 'Default';
	$new_options['linktarget']            = 'newwindow';
	$new_options['floatthead']			  = false;

	$merged_options = wp_parse_args( $options, $new_options );
	$compare_options = array_diff_key( $new_options, $options );
	if ( empty( $options ) || !empty( $compare_options ) ) {
		update_option( 'WS_PP' . $schedule_id, $merged_options );
	}
	return $merged_options;
}

function ws_db_prefix() {
	global $wpdb;
	if ( method_exists( $wpdb, "get_blog_prefix" ) ) {
		return $wpdb->get_blog_prefix();
	} else {
		return $wpdb->prefix;
	}
}

function ws_install() {
	global $wpdb;

	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		if ( isset( $_GET['networkwide'] ) && ( $_GET['networkwide'] == 1 ) ) {
			$originalblog = $wpdb->blogid;

			$bloglist = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );
			foreach ( $bloglist as $blog ) {
				switch_to_blog( $blog );
				ws_create_table_and_settings();
			}
			switch_to_blog( $originalblog );

			return;
		}
	}
	ws_create_table_and_settings();
}

function ws_new_network_site( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
	global $wpdb;

	if ( ! function_exists( 'is_plugin_active_for_network' ) )
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

	if ( is_plugin_active_for_network( 'weekly-schedule/weekly-schedule.php' ) ) {
		$originalblog = $wpdb->blogid;
		switch_to_blog( $blog_id );
		ws_create_table_and_settings();
		switch_to_blog( $originalblog );
	}
}

function ws_create_table_and_settings() {
	global $wpdb;

	$wpdb->wscategories = ws_db_prefix() . 'wscategories';

	$result = $wpdb->query(
		"
			CREATE TABLE IF NOT EXISTS `$wpdb->wscategories` (
				`id` int(10) unsigned NOT NULL auto_increment,
				`name` varchar(255) CHARACTER SET utf8 NOT NULL,
				`scheduleid` int(10) default NULL,
				`backgroundcolor` varchar(7) NULL,
				PRIMARY KEY  (`id`)
				) "
	);

	$catsresult = $wpdb->query(
		"
			SELECT * from `$wpdb->wscategories`"
	);

	if ( ! $catsresult ) {
		$result = $wpdb->query(
			"
			INSERT INTO `$wpdb->wscategories` (`name`, `scheduleid`, `backgroundcolor`) VALUES
			('Default', 1, NULL)"
		);
	}

	$wpdb->wsdays = ws_db_prefix() . 'wsdays';

	$result = $wpdb->query(
		"
			CREATE TABLE IF NOT EXISTS `$wpdb->wsdays` (
				`id` int(10) unsigned NOT NULL,
				`name` varchar(12) CHARACTER SET utf8 NOT NULL,
				`rows` int(10) unsigned NOT NULL,
				`scheduleid` int(10) NOT NULL default '0',
				PRIMARY KEY  (`id`, `scheduleid`)
				) "
	);

	$daysresult = $wpdb->query(
		"
			SELECT * from `$wpdb->wsdays`"
	);

	if ( ! $daysresult ) {
		$result = $wpdb->query(
			"
			INSERT INTO `$wpdb->wsdays` (`id`, `name`, `rows`, `scheduleid`) VALUES
			(1, 'Sun', 1, 1),
			(2, 'Mon', 1, 1),
			(3, 'Tue', 1, 1),
			(4, 'Wed', 1, 1),
			(5, 'Thu', 1, 1),
			(6, 'Fri', 1, 1),
			(7, 'Sat', 1, 1)"
		);
	}

	$wpdb->wsitems = ws_db_prefix() . 'wsitems';

	$item_table_creation_query = "
			CREATE TABLE " . $wpdb->wsitems . " (
				id int(10) unsigned NOT NULL auto_increment,
				name varchar(255) CHARACTER SET utf8,
				description text CHARACTER SET utf8 NOT NULL,
				address varchar(255) NOT NULL,
				starttime float unsigned NOT NULL,
				duration float NOT NULL,
				row int(10) unsigned NOT NULL,
				day int(10) unsigned NOT NULL,
				category int(10) unsigned NOT NULL,
				scheduleid int(10) NOT NULL default '0',
                backgroundcolor varchar(7) NULL,
                titlecolor varchar(7) NULL,
				UNIQUE KEY  ( id, scheduleid )
			);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $item_table_creation_query );

	$options = ws_get_options( '1' );
	$genoptions = ws_get_general_options( 'WeeklyScheduleGeneral' );
}

register_activation_hook( __FILE__, 'ws_install' );

function ws_uninstall() {
	$genoptions = get_option( 'WeeklyScheduleGeneral' );

	if ( $genoptions != '' ) {
		if ( isset( $genoptions['stylesheet'] ) && isset( $genoptions['fullstylesheet'] ) && !empty( $genoptions['stylesheet'] ) && empty( $genoptions['fullstylesheet'] ) ) {
			$stylesheetlocation           = plugins_url( $genoptions['stylesheet'], __FILE__ );
			$genoptions['fullstylesheet'] = file_get_contents( $stylesheetlocation );

			update_option( 'WeeklyScheduleGeneral', $genoptions );
		}
	}
}

register_activation_hook( __FILE__, 'ws_uninstall' );

if ( is_admin() && !class_exists( 'WS_Admin' ) ) {
	class WS_Admin {
		function __construct() {
			// adds the menu item to the admin interface
			add_action( 'admin_menu', array( $this, 'add_config_page' ) );
		}

		function add_config_page() {
			global $wpdb, $ws_pagehooktop, $ws_pagehookgeneraloptions, $ws_pagehookstylesheet;
			global $accesslevelcheck;
			$ws_pagehooktop            = add_menu_page( 'Weekly Schedule - ' . __( 'General Options', 'weekly-schedule' ), 'Weekly Schedule', $accesslevelcheck, WEEKLY_SCHEDULE_ADMIN_PAGE_NAME, array( $this, 'config_page' ), plugins_url( 'icons/calendar-icon-16.png', __FILE__ ) );
			$ws_pagehookgeneraloptions = add_submenu_page( WEEKLY_SCHEDULE_ADMIN_PAGE_NAME, 'Weekly Schedule - ' . __( 'General Options', 'weekly-schedule' ), __( 'General Options', 'weekly-schedule' ), $accesslevelcheck, WEEKLY_SCHEDULE_ADMIN_PAGE_NAME, array( $this, 'config_page' ) );
			$ws_pagehookstylesheet     = add_submenu_page( WEEKLY_SCHEDULE_ADMIN_PAGE_NAME, 'Weekly Schedule - ' . __( 'Stylesheet', 'weekly-schedule' ), __( 'Stylesheet', 'weekly-schedule' ), $accesslevelcheck, 'weekly-schedule-stylesheet', array( $this, 'stylesheet_config_page' ) );

			//add_options_page('Weekly Schedule for Wordpress', 'Weekly Schedule', 9, basename(__FILE__), array('WS_Admin','config_page'));
			add_filter( 'plugin_action_links', array( $this, 'filter_plugin_actions' ), 10, 2 );
		} // end add_WS_config_page()

		function filter_plugin_actions( $links, $file ) {
			//Static so we don't call plugin_basename on every plugin row.
			static $this_plugin;
			if ( !$this_plugin ) {
				$this_plugin = plugin_basename( __FILE__ );
			}
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="options-general.php?page=weekly-schedule">' . __( 'Settings' ) . '</a>';

				array_unshift( $links, $settings_link ); // before other links
			}

			return $links;
		}

		function stylesheet_config_page() {
			$genoptions = get_option( 'WeeklyScheduleGeneral' );

			if ( isset( $_POST['resetstyle'] ) ) {
				$stylesheetlocation = plugins_url( 'stylesheettemplate.css', __FILE__ );

				$genoptions['fullstylesheet'] = @file_get_contents( $stylesheetlocation );

				update_option( 'WeeklyScheduleGeneral', $genoptions );
				echo '<div id="warning" class="updated fade"><p><strong>Reset stylesheet to default.</strong></div>';
			}

			if ( isset( $_POST['submitstyle'] ) ) {
				$genoptions['fullstylesheet'] = $_POST['fullstylesheet'];

				update_option( 'WeeklyScheduleGeneral', $genoptions );
			}

			?>
			<div class="wrap">
				<h2>Weekly Schedule Stylesheet Editor</h2>
				<a href="http://yannickcorner.nayanna.biz/wordpress-plugins/weekly-schedule/" target="weeklyschedule"><img src="<?php echo plugins_url( '/icons/btn_donate_LG.gif', __FILE__ ); ?>" /></a> |
				<a target='wsinstructions' href='http://wordpress.org/extend/plugins/weekly-schedule/installation/'>Installation Instructions</a> |
				<a href='http://wordpress.org/extend/plugins/weekly-schedule/faq/' target='llfaq'>FAQ</a> |
				<a href='http://yannickcorner.nayanna.biz/contact-me'>Contact the Author</a><br />

				<p>If the stylesheet editor is empty after upgrading, reset to the default stylesheet using the button below or copy/paste your backup stylesheet into the editor.</p>

				<form name='wsadmingenform' action="<?php echo add_query_arg( 'page', 'weekly-schedule-stylesheet', admin_url( 'admin.php' ) ); ?>" method="post" id="ws-conf">
					<?php
					if ( function_exists( 'wp_nonce_field' ) ) {
						wp_nonce_field( 'wspp-config' );
					}
					?>
					<textarea name='fullstylesheet' id='fullstylesheet' style='font-family:Courier' rows="30" cols="100"><?php echo stripslashes( $genoptions['fullstylesheet'] ); ?></textarea>

					<div>
						<input type="submit" name="submitstyle" value="<?php _e( 'Submit', 'weekly-schedule' ); ?>" /><span style='padding-left: 650px'><input type="submit" name="resetstyle" value="<?php _e( 'Reset to default', 'weekly-schedule' ); ?>" /></span>
					</div>
				</form>
			</div>
		<?php
		}

		function config_page() {
			global $dlextensions;
			global $wpdb;

			$adminpage = '';
			$mode = '';

			$genoptions = ws_get_general_options( 'WeeklyScheduleGeneral' );

			if ( isset( $_GET['schedule'] ) ) {
				$schedule = $_GET['schedule'];
			} elseif ( isset( $_POST['schedule'] ) ) {
				$schedule = $_POST['schedule'];
			} else {
				$schedule = 1;
			}

			if ( isset( $_GET['copy'] ) ) {
				$destination = $_GET['copy'];
				$source      = $_GET['source'];

				$sourcesettingsname = 'WS_PP' . $source;
				$sourceoptions      = ws_get_options( $sourcesettingsname );

				$destinationsettingsname = 'WS_PP' . $destination;
				update_option( $destinationsettingsname, $sourceoptions );

				$schedule = $destination;
			}

			if ( isset( $_GET['reset'] ) && $_GET['reset'] == "true" ) {

				$options['starttime']             = 19;
				$options['endtime']               = 22;
				$options['timedivision']          = 0.5;
				$options['tooltipwidth']          = 300;
				$options['tooltiptarget']         = 'right center';
				$options['tooltippoint']          = 'left center';
				$options['tooltipcolorscheme']    = 'ui-tooltip';
				$options['displaydescription']    = "tooltip";
				$options['daylist']               = "";
				$options['timeformat']            = "24hours";
				$options['layout']                = 'horizontal';
				$options['adjusttooltipposition'] = true;
				$options['schedulename']          = "Default";
				$options['linktarget']            = "newwindow";
				$options['floatthead']			  = false;

				$schedule     = $_GET['reset'];
				$schedulename = 'WS_PP' . $schedule;

				update_option( $schedulename, $options );
			}
			if ( isset( $_GET['settings'] ) ) {
				if ( $_GET['settings'] == 'categories' ) {
					$adminpage = 'categories';
				} elseif ( $_GET['settings'] == 'items' ) {
					$adminpage = 'items';
				} elseif ( $_GET['settings'] == 'general' ) {
					$adminpage = 'general';
				} elseif ( $_GET['settings'] == 'days' ) {
					$adminpage = 'days';
				}

			}
			if ( isset( $_POST['submit'] ) ) {
				global $accesslevelcheck;
				if ( !current_user_can( $accesslevelcheck ) ) {
					die( __( 'You cannot edit the Weekly Schedule for WordPress options.' ) );
				}
				check_admin_referer( 'wspp-config' );

				if ( isset( $_GET['schedule'] ) ) {
					$scheduleid = $_GET['schedule'];
				} else {
					$schedule_id = '1';
				}

				$options = ws_get_options( $schedule_id );

				if ( $_POST['timedivision'] != $options['timedivision'] && $_POST['timedivision'] == "3.0" ) {
					$itemsquarterhour = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE MOD(duration, 1) = 0.25 and scheduleid = " . $schedule );
					$itemshalfhour    = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE MOD(duration, 1) = 0.5 and scheduleid = " . $schedule );
					$itemshour        = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE MOD(duration, 1) = 1.0 and scheduleid = " . $schedule );
					$itemstwohour     = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE MOD(duration, 1) = 2.0 and scheduleid = " . $schedule );

					if ( $itemsquarterhour ) {
						echo '<div id="warning" class="updated fade"><p><strong>Cannot change time division to tri-hourly since some items have quarter-hourly durations</strong></div>';
						$options['timedivision'] = "0.25";
					} elseif ( $itemshalfhour ) {
						echo '<div id="warning" class="updated fade"><p><strong>Cannot change time division to tri-hourly since some items have half-hourly durations</strong></div>';
						$options['timedivision'] = "0.5";
					} elseif ( $itemshour ) {
						echo '<div id="warning" class="updated fade"><p><strong>Cannot change time division to tri-hourly since some items have hourly durations</strong></div>';
						$options['timedivision'] = "1.0";
					} elseif ( $itemstwohour ) {
						echo '<div id="warning" class="updated fade"><p><strong>Cannot change time division to tri-hourly since some items have hourly durations</strong></div>';
						$options['timedivision'] = "2.0";
					} else {
						$options['timedivision'] = $_POST['timedivision'];
					}
				} elseif ( $_POST['timedivision'] != $options['timedivision'] && $_POST['timedivision'] == "2.0" ) {
					$itemsquarterhour = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE MOD(duration, 1) = 0.25 and scheduleid = " . $schedule );
					$itemshalfhour    = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE MOD(duration, 1) = 0.5 and scheduleid = " . $schedule );
					$itemshour        = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE MOD(duration, 1) = 1.0 and scheduleid = " . $schedule );

					if ( $itemsquarterhour ) {
						echo '<div id="warning" class="updated fade"><p><strong>Cannot change time division to bi-hourly since some items have quarter-hourly durations</strong></div>';
						$options['timedivision'] = "0.25";
					} elseif ( $itemshalfhour ) {
						echo '<div id="warning" class="updated fade"><p><strong>Cannot change time division to bi-hourly since some items have half-hourly durations</strong></div>';
						$options['timedivision'] = "0.5";
					} elseif ( $itemshour ) {
						echo '<div id="warning" class="updated fade"><p><strong>Cannot change time division to bi-hourly since some items have hourly durations</strong></div>';
						$options['timedivision'] = "1.0";
					} else {
						$options['timedivision'] = $_POST['timedivision'];
					}
				} elseif ( $_POST['timedivision'] != $options['timedivision'] && $_POST['timedivision'] == "1.0" ) {
					$itemsquarterhour = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE MOD(duration, 1) = 0.25 and scheduleid = " . $schedule );
					$itemshalfhour    = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE MOD(duration, 1) = 0.5 and scheduleid = " . $schedule );

					if ( $itemsquarterhour ) {
						echo '<div id="warning" class="updated fade"><p><strong>Cannot change time division to hourly since some items have quarter-hourly durations</strong></div>';
						$options['timedivision'] = "0.25";
					} elseif ( $itemshalfhour ) {
						echo '<div id="warning" class="updated fade"><p><strong>Cannot change time division to hourly since some items have half-hourly durations</strong></div>';
						$options['timedivision'] = "0.5";
					} else {
						$options['timedivision'] = $_POST['timedivision'];
					}
				} elseif ( $_POST['timedivision'] != $options['timedivision'] && $_POST['timedivision'] == "0.5" ) {
					$itemsquarterhour = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE MOD(duration, 1) = 0.25 and scheduleid = " . $schedule );

					if ( $itemsquarterhour ) {
						echo '<div id="warning" class="updated fade"><p><strong>Cannot change time division to hourly since some items have quarter-hourly durations</strong></div>';
						$options['timedivision'] = "0.25";
					} else {
						$options['timedivision'] = $_POST['timedivision'];
					}
				} else {
					$options['timedivision'] = $_POST['timedivision'];
				}

				foreach (
					array(
						'starttime', 'endtime', 'tooltipwidth', 'tooltiptarget', 'tooltippoint', 'tooltipcolorscheme',
						'displaydescription', 'daylist', 'timeformat', 'layout', 'schedulename', 'linktarget'
					) as $option_name
				) {
					if ( isset( $_POST[$option_name] ) ) {
						$options[$option_name] = $_POST[$option_name];
					}
				}

				foreach ( array( 'adjusttooltipposition', 'floatthead' ) as $option_name ) {
					if ( isset( $_POST[$option_name] ) ) {
						$options[$option_name] = true;
					} else {
						$options[$option_name] = false;
					}
				}


				$schedulename = 'WS_PP' . $schedule;
				update_option( $schedulename, $options );

				echo '<div id="message" class="updated fade"><p><strong>Weekly Schedule: Schedule ' . $schedule . ' Updated</strong></div>';
			}
			if ( isset( $_POST['submitgen'] ) ) {
				global $accesslevelcheck;
				if ( !current_user_can( $accesslevelcheck ) ) {
					die( __( 'You cannot edit the Weekly Schedule for WordPress options.' ) );
				}
				check_admin_referer( 'wspp-config' );

				foreach ( array( 'stylesheet', 'numberschedules', 'includestylescript', 'accesslevel', 'csvdelimiter' ) as $option_name ) {
					if ( isset( $_POST[$option_name] ) ) {
						$genoptions[$option_name] = $_POST[$option_name];
					}
				}

				foreach ( array( 'debugmode', 'frontpagestylescript' ) as $option_name ) {
					if ( isset( $_POST[$option_name] ) ) {
						$genoptions[$option_name] = true;
					} else {
						$genoptions[$option_name] = false;
					}
				}

				update_option( 'WeeklyScheduleGeneral', $genoptions );
			} elseif ( isset( $_POST['importschedule'] ) ) {
				wp_suspend_cache_addition( true );
				set_time_limit( 600 );

				global $wpdb;

				$handle = fopen( $_FILES['schedulefile']['tmp_name'], 'r' );

				$importmessage = '';
				$filerow = 0;
				$successfulimport = 0;

				if ( !isset( $genoptions['csvdelimiter'] ) ) {
					$genoptions['csvdelimiter'] = ',';
				}

				if ( $handle ) {
					while ( ( $data = fgetcsv( $handle, 5000, $genoptions['csvdelimiter'] ) ) !== false ) {
						$filerow += 1;

						if ( $filerow >= 2 ) {
							$start_time = $data[3];
							$colon_position = strpos( $start_time, ':' );

							if ( false !== $colon_position ) {
								$calc_start_time = substr( $start_time, 0, $colon_position );
								$calc_start_minute = substr( $start_time, $colon_position + 1, 2 );
								$calc_start_minute = ( round ( $calc_start_minute / 15 ) / 4 );
								if ( $calc_start_minute >= 1 )
									$calc_start_minute = 0;

								$start_time = $calc_start_time + $calc_start_minute;
							} else {
								$start_time = floatval( $start_time );
							}

						    if ( count( $data ) > 0 && count( $data ) == 10 ) {
								$newitem = array(
									'name'            => wp_kses_post( stripslashes( $data[5] ) ),
									'description'     => wp_kses_post( stripslashes( $data[6] ) ),
									'address'         => esc_url( stripslashes( $data[7] ) ),
									'starttime'       => $start_time,
									'duration'        => floatval( $data[4] ),
									'row'             => '',
									'day'             => intval( $data[2] ),
									'category'        => intval( $data[1] ),
									'scheduleid'      => intval( $data[0] ),
									'backgroundcolor' => esc_html( stripslashes( $data[8] ) ),
									'titlecolor'      => esc_html( stripslashes( $data[9] ) )
								);

							    $rowsearch = 1;
							    $row       = 1;

							    while ( $rowsearch == 1 ) {
								    $endtime = $newitem['starttime'] + $newitem['duration'];

								    $conflictquery = "SELECT * from " . ws_db_prefix() . "wsitems where day = " . $newitem['day'];
								    $conflictquery .= " and row = " . $row;
								    $conflictquery .= " and scheduleid = " . $newitem['scheduleid'];
								    $conflictquery .= " and ((" . $newitem['starttime'] . " < starttime and " . $endtime . " > starttime) or";
								    $conflictquery .= "      (" . $newitem['starttime'] . " >= starttime and " . $newitem['starttime'] . " < starttime + duration)) ";

								    $conflictingitems = $wpdb->get_results( $conflictquery );

								    if ( $conflictingitems ) {
									    $row ++;
								    } else {
									    $rowsearch = 0;
								    }
							    }

							    $dayrow = $wpdb->get_row( "SELECT * from " . ws_db_prefix() . "wsdays where id = " . $newitem['day'] . " AND scheduleid = " . $newitem['scheduleid'] );
							    if ( $dayrow->rows < $row ) {
								    $dayid     = array( 'id' => $newitem['day'], 'scheduleid' => $newitem['scheduleid'] );
								    $newdayrow = array( 'rows' => $row );

								    $wpdb->update( ws_db_prefix() . 'wsdays', $newdayrow, $dayid );
							    }

							    $newitem['row'] = $row;

							    $wpdb->insert( ws_db_prefix() . 'wsitems', $newitem );
								$successfulimport++;
							} elseif ( count( $data ) > 0 && count( $data ) != 10 ) {
								$importmessage = 1;
							}
						}
					}
				}

				if ( $successfulimport > 0 ) {
					echo '<div id="message" class="updated fade"><p><strong>Successfully imported ' . $successfulimport . ' record(s) from ' . ( $filerow - 1 ) . ' line(s) in import file</strong></div>';
				}

				if ( $importmessage == 1 ) {
					echo '<div id="message" class="updated fade"><p><strong>Some records did not have the right number of fields</strong></div>';
				}

				wp_suspend_cache_addition( false );
			}

			if ( isset( $_GET['editcat'] ) ) {
				$adminpage = 'categories';

				$mode = 'edit';

				$selectedcat = $wpdb->get_row( "select * from " . ws_db_prefix() . "wscategories where id = " . intval( $_GET['editcat'] ) );
			}
			if ( isset( $_POST['newcat'] ) || isset( $_POST['updatecat'] ) ) {
				global $accesslevelcheck;
				if ( !current_user_can( $accesslevelcheck ) ) {
					die( __( 'You cannot edit the Weekly Schedule for WordPress options.' ) );
				}
				check_admin_referer( 'wspp-config' );

				if ( isset( $_POST['name'] ) ) {
					$newcat = array(
						"name"            => $_POST['name'],
						"scheduleid"      => $_POST['schedule'],
						'backgroundcolor' => $_POST['backgroundcolor']
					);
				} else {
					$newcat = "";
				}

				if ( isset( $_POST['id'] ) ) {
					$id = array( "id" => $_POST['id'] );
				}


				if ( isset( $_POST['newcat'] ) ) {
					$wpdb->insert( ws_db_prefix() . 'wscategories', $newcat );
					echo '<div id="message" class="updated fade"><p><strong>Inserted New Category</strong></div>';
				} elseif ( isset( $_POST['updatecat'] ) ) {
					$wpdb->update( ws_db_prefix() . 'wscategories', $newcat, $id );
					echo '<div id="message" class="updated fade"><p><strong>Category Updated</strong></div>';
				}

				$mode = '';

				$adminpage = 'categories';
			}
			if ( isset( $_GET['deletecat'] ) ) {
				$adminpage = 'categories';

				$catexist = $wpdb->get_row( "SELECT * from " . ws_db_prefix() . "wscategories WHERE id = " . intval( $_GET['deletecat'] ) );

				if ( $catexist ) {
					$wpdb->query( "DELETE from " . ws_db_prefix() . "wscategories WHERE id = " . intval( $_GET['deletecat'] ) );
					echo '<div id="message" class="updated fade"><p><strong>Category Deleted</strong></div>';
				}
			}
			if ( isset( $_GET['edititem'] ) ) {
				$adminpage = 'items';

				$mode = 'edit';

				$selecteditem = $wpdb->get_row( "select * from " . ws_db_prefix() . "wsitems where id = " . intval( $_GET['edititem'] ) . " AND scheduleid = " . intval( $_GET['schedule'] ) );
			}
			if ( isset( $_POST['newitem'] ) || isset( $_POST['updateitem'] ) ) {
				// Need to re-work all of this to support multiple schedules
				global $accesslevelcheck;
				if ( !current_user_can( $accesslevelcheck ) ) {
					die( __( 'You cannot edit the Weekly Schedule for WordPress options.' ) );
				}
				check_admin_referer( 'wspp-config' );

				if ( isset( $_POST['name'] ) && isset( $_POST['starttime'] ) && isset( $_POST['duration'] ) ) {
					$newitem = array(
						'name'            => wp_kses_post( stripslashes( $_POST['name'] ) ),
						'description'     => wp_kses_post( stripslashes( $_POST['description'] ) ),
						'address'         => esc_url( $_POST['address'] ),
						'starttime'       => floatval( $_POST['starttime'] ),
						'category'        => intval( $_POST['category'] ),
						'duration'        => $_POST['duration'],
						'day'             => $_POST['day'],
						'scheduleid'      => $_POST['schedule'],
						'backgroundcolor' => $_POST['backgroundcolor'],
						'titlecolor'      => $_POST['titlecolor']
					);

					if ( isset( $_POST['updateitem'] ) ) {
						$origrow = $_POST['oldrow'];
						$origday = $_POST['oldday'];
					}

					$rowsearch = 1;
					$row       = 1;

					while ( $rowsearch == 1 ) {
						if ( $_POST['id'] != "" ) {
							$checkid = " and id <> " . $_POST['id'];
						} else {
							$checkid = "";
						}

						$endtime = $newitem['starttime'] + $newitem['duration'];

						$conflictquery = "SELECT * from " . ws_db_prefix() . "wsitems where day = " . intval( $newitem['day'] ) . $checkid;
						$conflictquery .= " and row = " . intval( $row );
						$conflictquery .= " and scheduleid = " . intval( $newitem['scheduleid'] );
						$conflictquery .= " and ((" . $newitem['starttime'] . " < starttime and " . $endtime . " > starttime) or";
						$conflictquery .= "      (" . $newitem['starttime'] . " >= starttime and " . $newitem['starttime'] . " < starttime + duration)) ";

						$conflictingitems = $wpdb->get_results( $conflictquery );

						if ( $conflictingitems ) {
							$row ++;
						} else {
							$rowsearch = 0;
						}
					}

					if ( isset( $_POST['updateitem'] ) ) {
						if ( $origrow != $row || $origday != $_POST['day'] ) {
							if ( $origrow > 1 ) {
								$itemday = $wpdb->get_row( "SELECT * from " . ws_db_prefix() . "wsdays WHERE id = " . intval( $origday ) . " AND scheduleid = " . intval( $_POST['schedule'] ) );

								$othersonrow = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE day = " . intval( $origday ) . " AND row = " . intval( $origrow ) . " AND scheduleid = " . intval( $_POST['schedule'] ) . " AND id != " . intval( $_POST['id'] ) );
								if ( !$othersonrow ) {
									if ( $origrow != $itemday->rows ) {
										for ( $i = $origrow + 1; $i <= $itemday->rows; $i ++ ) {
											$newrow    = $i - 1;
											$changerow = array( 'row' => $newrow );
											$oldrow    = array( 'row' => $i, 'day' => $origday );
											$wpdb->update( ws_db_prefix() . 'wsitems', $changerow, $oldrow );
										}
									}

									$dayid     = array( 'id' => $itemday->id, 'scheduleid' => intval( $_POST['schedule'] ) );
									$newrow    = $itemday->rows - 1;
									$newdayrow = array( 'rows' => $newrow );

									$wpdb->update( ws_db_prefix() . 'wsdays', $newdayrow, $dayid );
								}
							}
						}
					}

					$dayrow = $wpdb->get_row( "SELECT * from " . ws_db_prefix() . "wsdays where id = " . intval( $_POST['day'] ) . " AND scheduleid = " . intval( $_POST['schedule'] ) );
					if ( $dayrow->rows < $row ) {
						$dayid     = array( 'id' => intval( $_POST['day'] ), 'scheduleid' => intval( $_POST['schedule'] ) );
						$newdayrow = array( 'rows' => $row );

						$wpdb->update( ws_db_prefix() . 'wsdays', $newdayrow, $dayid );
					}

					$newitem['row'] = $row;

					if ( isset( $_POST['id'] ) ) {
						$id = array( 'id' => intval( $_POST['id'] ), 'scheduleid' => intval( $_POST['schedule'] ) );
					}

					if ( isset( $_POST['newitem'] ) ) {
						$wpdb->insert( ws_db_prefix() . 'wsitems', $newitem );
						echo '<div id="message" class="updated fade"><p><strong>Inserted New Item</strong></div>';
					} elseif ( isset( $_POST['updateitem'] ) ) {
						$wpdb->update( ws_db_prefix() . 'wsitems', $newitem, $id );
						echo '<div id="message" class="updated fade"><p><strong>Item Updated</strong></div>';
					}
				}

				$mode = '';

				$adminpage = 'items';
			}
			if ( isset( $_GET['deleteitem'] ) ) {
				$adminpage = 'items';

				$itemexist = $wpdb->get_row( "SELECT * from " . ws_db_prefix() . "wsitems WHERE id = " . intval( $_GET['deleteitem'] ) . " AND scheduleid = " . intval( $_GET['schedule'] ) );
				$itemday   = $wpdb->get_row( "SELECT * from " . ws_db_prefix() . "wsdays WHERE id = " . $itemexist->day . " AND scheduleid = " . intval( $_GET['schedule'] ) );

				if ( $itemexist ) {
					$wpdb->query( "DELETE from " . ws_db_prefix() . "wsitems WHERE id = " . intval( $_GET['deleteitem'] ) . " AND scheduleid = " . intval( $_GET['schedule'] ) );

					if ( $itemday->rows > 1 ) {
						$othersonrow = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsitems WHERE day = " . $itemexist->day . " AND scheduleid = " . intval( $_GET['schedule'] ) . " AND row = " . $itemexist->row );
						if ( !$othersonrow ) {
							if ( $itemexist->row != $itemday->rows ) {
								for ( $i = $itemexist->row + 1; $i <= $itemday->rows; $i ++ ) {
									$newrow    = $i - 1;
									$changerow = array( 'row' => $newrow );
									$oldrow    = array( 'row' => $i, 'day' => $itemday->id );
									$wpdb->update( ws_db_prefix() . 'wsitems', $changerow, $oldrow );
								}
							}

							$dayid     = array( 'id' => $itemexist->day, 'scheduleid' => intval( $_GET['schedule'] ) );
							$newrow    = $itemday->rows - 1;
							$newdayrow = array( 'rows' => $newrow );

							$wpdb->update( ws_db_prefix() . 'wsdays', $newdayrow, $dayid );
						}
					}
					echo '<div id="message" class="updated fade"><p><strong>Item Deleted</strong></div>';
				}
			}
			if ( isset( $_POST['updatedays'] ) ) {
				$dayids = array( 1, 2, 3, 4, 5, 6, 7 );

				foreach ( $dayids as $dayid ) {
					$daynamearray = array( "name" => $_POST[$dayid] );
					$dayidarray   = array( "id" => $dayid, "scheduleid" => intval( $_POST['schedule'] ) );

					$wpdb->update( ws_db_prefix() . 'wsdays', $daynamearray, $dayidarray );
				}
			}
			if ( isset( $_POST['deleteallitems'] ) && isset( $_GET['schedule'] ) ) {
				$deletion_query = 'delete from ' . ws_db_prefix() . 'wsitems where scheduleid = ' . intval( $_GET['schedule'] );
				$wpdb->get_results( $deletion_query );

				$days_row_query = 'update ' . ws_db_prefix() . 'wsdays set rows = 1 where scheduleid = ' . intval( $_GET['schedule'] );
				$wpdb->get_results( $days_row_query);
			}

			$wspluginpath = WP_CONTENT_URL . '/plugins/' . plugin_basename( dirname( __FILE__ ) ) . '/';

			if ( $schedule == '' ) {
				$options = ws_get_options( '1' );
				$schedule = 1;
			} else {
				$options      = ws_get_options( $schedule );
			}

			if ( $options == "" ) {
				$options['starttime']             = 19;
				$options['endtime']               = 22;
				$options['timedivision']          = 0.5;
				$options['tooltipwidth']          = 300;
				$options['tooltiptarget']         = 'right center';
				$options['tooltippoint']          = 'left center';
				$options['tooltipcolorscheme']    = 'ui-tooltip';
				$options['displaydescription']    = "tooltip";
				$options['daylist']               = "";
				$options['timeformat']            = "24hours";
				$options['layout']                = 'horizontal';
				$options['adjusttooltipposition'] = true;
				$options['schedulename']          = "Default";
				$options['linktarget']            = "newwindow";

				$schedulename = 'WS_PP' . $schedule;

				update_option( $schedulename, $options );

				$catsresult = $wpdb->query( "SELECT * from " . ws_db_prefix() . "wscategories where scheduleid = " . $schedule );

				if ( !$catsresult ) {
					$sqlstatement = "INSERT INTO " . ws_db_prefix() . "wscategories (`name`, `scheduleid`) VALUES
									('Default', " . $schedule . ")";
					$result       = $wpdb->query( $sqlstatement );
				}

				$wpdb->wsdays = ws_db_prefix() . 'wsdays';

				$daysresult = $wpdb->query( "SELECT * from " . ws_db_prefix() . "wsdays where scheduleid = " . $schedule );

				if ( !$daysresult ) {
					$sqlstatement = "INSERT INTO " . ws_db_prefix() . "wsdays (`id`, `name`, `rows`, `scheduleid`) VALUES
									(1, 'Sun', 1, " . $schedule . "),
									(2, 'Mon', 1, " . $schedule . "),
									(3, 'Tue', 1, " . $schedule . "),
									(4, 'Wes', 1, " . $schedule . "),
									(5, 'Thu', 1, " . $schedule . "),
									(6, 'Fri', 1, " . $schedule . "),
									(7, 'Sat', 1, " . $schedule . ")";
					$result       = $wpdb->query( $sqlstatement );
				}
			}

			?>
			<div class="wrap">
			<h2>Weekly Schedule Configuration</h2>
			<a href="http://yannickcorner.nayanna.biz/wordpress-plugins/weekly-schedule/" target="weeklyschedule"><img src="<?php echo plugins_url( '/icons/btn_donate_LG.gif', __FILE__ ); ?>" /></a> |
			<a target='wsinstructions' href='http://wordpress.org/extend/plugins/weekly-schedule/installation/'>Installation Instructions</a> |
			<a href='http://wordpress.org/extend/plugins/weekly-schedule/faq/' target='llfaq'>FAQ</a> |
			<a href='http://yannickcorner.nayanna.biz/contact-me'>Contact the Author</a><br /><br />

			<form name='wsadmingenform' action="<?php echo add_query_arg( 'page', 'weekly-schedule', admin_url( 'options-general.php' ) ); ?>" method="post" id="ws-conf" enctype="multipart/form-data">
				<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
				<?php
				if ( function_exists( 'wp_nonce_field' ) ) {
					wp_nonce_field( 'wspp-config' );
				}
				?>
				<fieldset style='border:1px solid #CCC;padding:10px'>
					<legend class="tooltip" title='These apply to all schedules' style='padding: 0 5px 0 5px;'>
						<strong>General Settings
							<span style="border:0;padding-left: 15px;" class="submit"><input type="submit" name="submitgen" value="Update General Settings &raquo;" /></span></strong>
					</legend>
					<table>
						<tr>
							<td style='padding: 8px; vertical-align: top'>
								<table>
									<tr>
										<td>Import Schedule Items (<a href="<?php echo plugins_url( 'importtemplate.csv', __FILE__ ); ?>">Template</a>)</td>
										<td><input size="80" name="schedulefile" type="file" /></td>
										<td><input type="submit" name="importschedule" value="Import Items" /></td>
									</tr>
									<tr>
										<td>Import File Delimiter</td>
										<td>
											<input type="text" id="csvdelimiter" name="csvdelimiter" size="1" value="<?php if ( !isset( $genoptions['csvdelimiter'] ) ) $genoptions['csvdelimiter'] = ','; echo $genoptions['csvdelimiter']; ?>" /></td>
									</tr>
									<tr>
										<td style='width:200px'>Stylesheet File Name</td>
										<td>
											<input type="text" id="stylesheet" name="stylesheet" size="40" value="<?php echo $genoptions['stylesheet']; ?>" />
										</td>
									</tr>
									<?php if (current_user_can( 'manage_options' )) { ?>
									<tr>
										<td style='width:200px'>Access level required</td>
										<td>
											<?php } ?>
											<select <?php if ( !current_user_can( 'manage_options' ) ) {
												echo 'style="display: none"';
											} ?> id="accesslevel" name="accesslevel">
												<?php $levels = array( 'admin' => 'Administrator', 'editor' => 'Editor', 'author' => 'Author', 'contributor' => 'Contributor', 'subscriber' => 'Subscriber' );
												if ( !isset( $genoptions['accesslevel'] ) || empty( $genoptions['accesslevel'] ) ) {
													$genoptions['accesslevel'] = 'admin';
												}

												foreach ( $levels as $key => $level ) {
													echo '<option value="' . $key . '" ' . selected( $genoptions['accesslevel'], $key, false ) . '>' . $level . '</option>';
												} ?>
											</select>
											<?php if (current_user_can( 'manage_options' )) { ?>
										</td>
									</tr>
								<?php } ?>
									<tr>
										<td>Number of Schedules</td>
										<td>
											<input type="text" id="numberschedules" name="numberschedules" size="5" value="<?php if ( $genoptions['numberschedules'] == '' ) {
												echo '2';
											}
											echo $genoptions['numberschedules']; ?>" /></td>
									</tr>
									<tr>
										<td style="padding-left: 10px;padding-right:10px">Debug Mode</td>
										<td>
											<input type="checkbox" id="debugmode" name="debugmode" <?php if ( $genoptions['debugmode'] ) {
												echo ' checked="checked" ';
											} ?>/></td>
									</tr>
									<tr>
										<td colspan="2">Additional pages to style (Comma-Separated List of Page IDs)</td>
									</tr>
									<tr>
										<td colspan="2">
											<input type='text' name='includestylescript' style='width: 200px' value='<?php echo $genoptions['includestylescript']; ?>' />
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>

			<div style='padding-top: 15px;clear:both'>
				<fieldset style='border:1px solid #CCC;padding:10px'>
					<legend style='padding: 0 5px 0 5px;'><strong>Schedule Selection and Usage Instructions</strong>
					</legend>
					<FORM name="scheduleselection">
						Select Current Schedule:
						<SELECT name="schedulelist" style='width: 300px'>
							<?php if ( $genoptions['numberschedules'] == '' ) {
								$numberofschedules = 2;
							} else {
								$numberofschedules = $genoptions['numberschedules'];
							}
							for ( $counter = 1; $counter <= $numberofschedules; $counter ++ ): ?>
								<?php $tempoptions          = ws_get_options( $counter ); ?>
								<option value="<?php echo $counter ?>" <?php if ( $schedule == $counter ) {
									echo 'SELECTED';
								} ?>>Schedule <?php echo $counter ?><?php if ( $tempoptions != "" ) {
										echo " (" . $tempoptions['schedulename'] . ")";
									} ?></option>
							<?php endfor; ?>
						</SELECT>
						<INPUT type="button" name="go" value="Go!" onClick="window.location= '?page=weekly-schedule&amp;settings=<?php echo $adminpage; ?>&amp;schedule=' + document.scheduleselection.schedulelist.options[document.scheduleselection.schedulelist.selectedIndex].value">
						Copy from:
						<SELECT name="copysource" style='width: 300px'>
							<?php if ( $genoptions['numberschedules'] == '' ) {
								$numberofschedules = 2;
							} else {
								$numberofschedules = $genoptions['numberschedules'];
							}
							for ( $counter = 1; $counter <= $numberofschedules; $counter ++ ): ?>
								<?php $tempoptions          = ws_get_options( $counter );
								if ( $counter != $schedule ):?>
									<option value="<?php echo $counter ?>" <?php if ( $schedule == $counter ) {
										echo 'SELECTED';
									} ?>>Schedule <?php echo $counter ?><?php if ( $tempoptions != "" ) {
											echo " (" . $tempoptions['schedulename'] . ")";
										} ?></option>
								<?php endif;
							endfor; ?>
						</SELECT>
						<INPUT type="button" name="copy" value="Copy!" onClick="window.location= '?page=weekly-schedule&amp;copy=<?php echo $schedule; ?>&source=' + document.scheduleselection.copysource.options[document.scheduleselection.copysource.selectedIndex].value">
						<br />
						<br />
						<table class='widefat' style='clear:none;width:100%;background: #DFDFDF url(/wp-admin/images/gray-grad.png) repeat-x scroll left top;'>
							<thead>
							<tr>
								<th style='width:80px' class="tooltip">
									Schedule #
								</th>
								<th style='width:130px' class="tooltip">
									Schedule Name
								</th>
								<th class="tooltip">
									Code to insert on a Wordpress page to see Weekly Schedule
								</th>
							</tr>
							</thead>
							<tr>
								<td style='background: #FFF'><?php echo $schedule; ?></td>
								<td style='background: #FFF'><?php echo $options['schedulename']; ?></a></td>
								<td style='background: #FFF'><?php echo "[weekly-schedule schedule=" . $schedule . "]"; ?></td>
								<td style='background: #FFF;text-align:center'></td>
							</tr>
						</table>
						<br />
					</FORM>
				</fieldset>
			</div>
			<br />


			<fieldset style='border:1px solid #CCC;padding:10px'>
			<legend style='padding: 0 5px 0 5px;'>
				<strong>Settings for Schedule <?php echo $schedule; ?> - <?php echo $options['schedulename']; ?></strong>
			</legend>
			<?php if (( $adminpage == "" ) || ( $adminpage == "general" )): ?>
			<a href="?page=weekly-schedule&amp;settings=general&amp;schedule=<?php echo $schedule; ?>"><strong>General Settings</strong></a> |
			<a href="?page=weekly-schedule&amp;settings=categories&amp;schedule=<?php echo $schedule; ?>">Manage Schedule Categories</a> |
			<a href="?page=weekly-schedule&amp;settings=items&amp;schedule=<?php echo $schedule; ?>">Manage Schedule Items</a> |
			<a href="?page=weekly-schedule&amp;settings=days&amp;schedule=<?php echo $schedule; ?>">Manage Days Labels</a><br /><br />

			<form name="wsadminform" action="<?php echo add_query_arg( 'page', 'weekly-schedule', admin_url( 'options-general.php' ) ); ?>" method="post" id="ws-config">
			<?php
			if ( function_exists( 'wp_nonce_field' ) ) {
				wp_nonce_field( 'wspp-config' );
			}
			?>
			Schedule Name:
			<input type="text" id="schedulename" name="schedulename" size="80" value="<?php echo $options['schedulename']; ?>" /><br /><br />
			<strong>Time-related Settings</strong><br />
			<input type="hidden" name="schedule" value="<?php echo $schedule; ?>" />
			<table>
				<tr>
					<td>Schedule Layout</td>
					<td><select style="width: 200px" name='layout'>
							<?php $layouts = array( "horizontal" => "Horizontal", "vertical" => "Vertical" );
							foreach ( $layouts as $key => $layout ) {
								echo "<option value='" . $key . "' " . selected( $key, $options['layout'], false ) . ">" . $layout . "\n";
							}
							?>
						</select></td>
					<td>Time Display Format</td>
					<td><select style="width: 200px" name='timeformat'>
							<?php $descriptions = array( "24hours" => "24 Hours (e.g. 17h30)", "24hourscolon" => "24 Hours with Colon (e.g. 17:30)", "12hours" => "12 Hours (e.g. 1:30pm)" );
							foreach ( $descriptions as $key => $description ) {
								echo "<option value='" . $key . "' " . selected( $key, $options['timeformat'], false ) . ">" . $description . "\n";
							}
							?>
						</select></td>
				</tr>
				<tr>
					<td>Start Time</td>
					<td><select style='width: 200px' name="starttime">
							<?php $timedivider = ( in_array( $options['timedivision'], array( '1.0', '2.0', '3.0' ) ) ? '1.0' : $options['timedivision'] );
							$maxtime           = 24 + $timedivider;
							for ( $i = 0; $i < $maxtime; $i += $timedivider ) {
								if ( $options['timeformat'] == '24hours' || $options['timeformat'] == '24hourscolon' ) {
									$hour = floor( $i );
								} elseif ( $options['timeformat'] == '12hours' ) {
									if ( $i < 12 ) {
										$timeperiod = "am";
										if ( $i == 0 ) {
											$hour = 12;
										} else {
											$hour = floor( $i );
										}
									} else {
										$timeperiod = "pm";
										if ( $i >= 12 && $i < 13 ) {
											$hour = floor( $i );
										} else {
											$hour = floor( $i ) - 12;
										}
									}
								}

								if ( fmod( $i, 1 ) == 0.25 ) {
									$minutes = "15";
								} elseif ( fmod( $i, 1 ) == 0.50 ) {
									$minutes = "30";
								} elseif ( fmod( $i, 1 ) == 0.75 ) {
									$minutes = "45";
								} else {
									$minutes = "00";
								}

								if ( $options['timeformat'] == '24hours' ) {
									echo "<option value='" . $i . "'" . selected( $i, $options['starttime'], false ) . ">" . $hour . "h" . $minutes . "\n";
								} else if ( $options['timeformat'] == '24hourscolon' ) {
									echo "<option value='" . $i . "'" . selected( $i, $options['starttime'], false ) . ">" . $hour . ":" . $minutes . "\n";
								} else if ( $options['timeformat'] == '12hours' ) {
									echo "<option value='" . $i . "'" . selected( $i, $options['starttime'], false ) . ">" . $hour . ":" . $minutes . $timeperiod . "\n";
								}
							}
							?>
						</select></td>
					<td>End Time</td>
					<td><select style='width: 200px' name="endtime">
							<?php for ( $i = 0; $i < $maxtime; $i += $timedivider ) {
								if ( $options['timeformat'] == '24hours' || $options['timeformat'] == '24hourscolon' ) {
									$hour = floor( $i );
								} elseif ( $options['timeformat'] == '12hours' ) {
									if ( $i < 12 ) {
										$timeperiod = "am";
										if ( $i == 0 ) {
											$hour = 12;
										} else {
											$hour = floor( $i );
										}
									} else {
										$timeperiod = "pm";
										if ( $i >= 12 && $i < 13 ) {
											$hour = floor( $i );
										} else {
											$hour = floor( $i ) - 12;
										}
									}
								}

								if ( fmod( $i, 1 ) == 0.25 ) {
									$minutes = "15";
								} elseif ( fmod( $i, 1 ) == 0.50 ) {
									$minutes = "30";
								} elseif ( fmod( $i, 1 ) == 0.75 ) {
									$minutes = "45";
								} else {
									$minutes = "00";
								}

								if ( $options['timeformat'] == '24hours' ) {
									echo "<option value='" . $i . "'" . selected( $i, $options['endtime'], false ) . ">" . $hour . "h" . $minutes . "\n";
								} elseif ( $options['timeformat'] == '24hourscolon' ) {
									echo "<option value='" . $i . "'" . selected( $i, $options['endtime'], false ) . ">" . $hour . ":" . $minutes . "\n";
								} elseif ( $options['timeformat'] == '12hours' ) {
									echo "<option value='" . $i . "'" . selected( $i, $options['endtime'], false ) . ">" . $hour . ":" . $minutes . $timeperiod . "\n";
								}
							}
							?>
						</select></td>
				</tr>
				<tr>
					<td>Cell Time Division</td>
					<td><select style='width: 250px' name='timedivision'>
							<?php $timedivisions = array(
								"0.25" => "Quarter-Hourly (15 min intervals)",
								".50"  => "Half-Hourly (30 min intervals)",
								"1.0"  => "Hourly (60 min intervals)",
								"2.0"  => "Bi-Hourly (120 min intervals)",
								"3.0"  => "Tri-Hourly (180 min intervals)"
							);
							foreach ( $timedivisions as $key => $timedivision ) {
								echo "<option value='" . $key . "' " . selected( $key, $options['timedivision'], false ) . ">" . $timedivision . "\n";
							}
							?>
						</select></td>
					<td>Show Description</td>
					<td><select style="width: 200px" name='displaydescription'>
							<?php $descriptions = array( "tooltip" => "Show as tooltip", "cell" => "Show in cell after item name", "none" => "Do not display" );
							foreach ( $descriptions as $key => $description ) {
								echo "<option value='" . $key . "' " . selected( $key, $options['displaydescription'], false ) . ">" . $description . "\n";
							}
							?>
						</select></td>
				</tr>
				<tr>
					<td>Make heading row sticky</td>
					<td><input type="checkbox" id="floatthead" name="floatthead" <?php checked( $options['floatthead'] ); ?>/></td>
				</tr>
				<tr>
					<td colspan='2'>Day List (comma-separated Day IDs to specify days to be displayed and their order)
					</td>
					<td colspan='2'>
						<input type='text' name='daylist' style='width: 200px' value='<?php echo $options['daylist']; ?>' />
					</td>
				</tr>
				<tr>
					<td>Target Window Name
					</td>
					<td>
						<input type='text' name='linktarget' style='width: 250px' value='<?php echo $options['linktarget']; ?>' />
					</td>
				</tr>
			</table>
			<br /><br />
			<strong>Tooltip Configuration</strong>
			<table>
				<tr>
					<td>Tooltip Color Scheme</td>
					<td><select name='tooltipcolorscheme' style='width: 100px'>
							<?php $colors = array( 'qtip-cream' => 'cream', 'qtip-dark' => 'dark', 'qtip-green' => 'green', 'qtip-light' => 'light', 'qtip-red' => 'red', 'qtip-blue' => 'blue' );
							foreach ( $colors as $key => $color ) {
								echo "<option value='" . $key . "' " . selected( $key, $options['tooltipcolorscheme'], false ) . ">" . $color . "\n";
							}
							?>
						</select></td>
					<td>Tooltip Width</td>
					<td>
						<input type='text' name='tooltipwidth' style='width: 100px' value='<?php echo $options['tooltipwidth']; ?>' />
					</td>
				</tr>
				<tr>
					<td>Tooltip Anchor Point on Data Cell</td>
					<td><select name='tooltiptarget' style='width: 200px'>
							<?php $positions = array(
								'top left'     => 'Top-Left Corner', 'top center' => 'Middle of Top Side',
								'top right'    => 'Top-Right Corner', 'right top' => 'Right Side of Top-Right Corner',
								'right center' => 'Middle of Right Side', 'right bottom' => 'Right Side of Bottom-Right Corner',
								'bottom left'  => 'Under Bottom-Left Side', 'bottom center' => 'Under Middle of Bottom Side',
								'bottom right' => 'Under Bottom-Right Side', 'left top' => 'Left Side of Top-Left Corner',
								'left center'  => 'Middle of Left Side', 'left bottom' => 'Left Side of Bottom-Left Corner'
							);

							foreach ( $positions as $index => $position ) {
								echo "<option value='" . $index . "' " . selected( $index, $options['tooltiptarget'], false ) . ">" . $position . "\n";
							}

							?>
						</select></td>
					<td>Tooltip Attachment Point</td>
					<td><select name='tooltippoint' style='width: 200px'>
							<?php $positions = array(
								'top left'     => 'Top-Left Corner', 'top center' => 'Middle of Top Side',
								'top right'    => 'Top-Right Corner', 'right top' => 'Right Side of Top-Right Corner',
								'right center' => 'Middle of Right Side', 'right bottom' => 'Right Side of Bottom-Right Corner',
								'bottom left'  => 'Under Bottom-Left Side', 'bottom center' => 'Under Middle of Bottom Side',
								'bottom right' => 'Under Bottom-Right Side', 'left top' => 'Left Side of Top-Left Corner',
								'left center'  => 'Middle of Left Side', 'left bottom' => 'Left Side of Bottom-Left Corner'
							);

							foreach ( $positions as $index => $position ) {
								echo "<option value='" . $index . "' " . selected( $index, $options['tooltippoint'], false ) . ">" . $position . "\n";
							}

							?>
						</select></td>
				</tr>
				<tr>
					<td>Auto-Adjust Position to be visible</td>
					<td>
						<input type="checkbox" id="adjusttooltipposition" name="adjusttooltipposition" <?php checked( $options['adjusttooltipposition'] ); ?>/></td>
					<td></td>
					<td></td>
				</tr>
			</table>
			<p style="border:0;" class="submit"><input type="submit" name="submit" value="Update Settings &raquo;" />
			</p>
			</form>
			</fieldset>
			<?php /* --------------------------------------- Categories --------------------------------- */ ?>
			<?php elseif ( $adminpage == "categories" ): ?>
				<a href="?page=weekly-schedule&amp;settings=general&amp;schedule=<?php echo $schedule; ?>">General Settings</a> |
				<a href="?page=weekly-schedule&amp;settings=categories&amp;schedule=<?php echo $schedule; ?>"><strong>Manage Schedule Categories</strong></a> |
				<a href="?page=weekly-schedule&amp;settings=items&amp;schedule=<?php echo $schedule; ?>">Manage Schedule Items</a> |
				<a href="?page=weekly-schedule&amp;settings=days&amp;schedule=<?php echo $schedule; ?>">Manage Days Labels</a>
				<br /><br />
				<div style='float:left;margin-right: 15px'>
					<form name="wscatform" action="" method="post" id="ws-config">
						<?php
						if ( function_exists( 'wp_nonce_field' ) ) {
							wp_nonce_field( 'wspp-config' );
						}
						?>
						<?php if ( $mode == "edit" ): ?>
							<strong>Editing Category #<?php echo $selectedcat->id; ?></strong><br />
						<?php endif; ?>
						Category Name: <input style="width:300px" type="text" name="name" <?php if ( $mode == "edit" ) {
							echo "value='" . $selectedcat->name . "'";
						} ?>/>
						<br>Background Cell Color (optional)
						<input style="width:100px" type="text" name="backgroundcolor" <?php if ( $mode == "edit" ) {
							echo "value='" . $selectedcat->backgroundcolor . "'";
						} ?>/>
						<input type="hidden" name="id" value="<?php if ( $mode == "edit" ) {
							echo $selectedcat->id;
						} ?>" />
						<input type="hidden" name="schedule" value="<?php echo $schedule; ?>" />
						<?php if ( $mode == "edit" ): ?>
							<p style="border:0;" class="submit">
								<input type="submit" name="updatecat" value="Update &raquo;" /></p>
						<?php else: ?>
							<p style="border:0;" class="submit">
								<input type="submit" name="newcat" value="Insert New Category &raquo;" /></p>
						<?php endif; ?>
					</form>
				</div>
				<div>
					<?php $cats = $wpdb->get_results( "SELECT count( i.id ) AS nbitems, c.name, c.id, c.backgroundcolor, c.scheduleid FROM " . ws_db_prefix() . "wscategories c LEFT JOIN " . ws_db_prefix() . "wsitems i ON i.category = c.id WHERE c.scheduleid = " . $schedule . " GROUP BY c.id" );

					if ( $cats ): ?>
						<table class='widefat' style='clear:none;width:400px;background: #DFDFDF url(/wp-admin/images/gray-grad.png) repeat-x scroll left top;'>
							<thead>
							<tr>
								<th scope='col' style='width: 50px' id='id' class='manage-column column-id'>ID</th>
								<th scope='col' id='name' class='manage-column column-name' style=''>Name</th>
								<th scope='col' style='width: 50px;text-align: right' id='color' class='manage-column column-color' style=''>Color</th>
								<th scope='col' style='width: 50px;text-align: right' id='items' class='manage-column column-items' style=''>Items</th>
								<th style='width: 30px'></th>
							</tr>
							</thead>

							<tbody id='the-list' class='list:link-cat'>

							<?php foreach ( $cats as $cat ): ?>
								<tr>
									<td class='name column-name' style='background: #FFF'><?php echo $cat->id; ?></td>
									<td style='background: #FFF'>
										<a href='?page=weekly-schedule&amp;editcat=<?php echo $cat->id; ?>&schedule=<?php echo $schedule; ?>'><strong><?php echo $cat->name; ?></strong></a>
									</td>
									<td style='background: <?php echo $cat->backgroundcolor != null ? $cat->backgroundcolor : '#FFF'; ?>;text-align:right'></td>
									<td style='background: #FFF;text-align:right'><?php echo $cat->nbitems; ?></td>
									<?php if ( $cat->nbitems == 0 ): ?>
										<td style='background:#FFF'>
											<a href='?page=weekly-schedule&amp;deletecat=<?php echo $cat->id; ?>&schedule=<?php echo $schedule; ?>'
												<?php echo "onclick=\"if ( confirm('" . esc_js( sprintf( __( "You are about to delete this category '%s'\n  'Cancel' to stop, 'OK' to delete." ), $cat->name ) ) . "') ) { return true;}return false;\"" ?>><img src='<?php echo plugins_url( '/icons/delete.png', __FILE__ ); ?>' /></a>
										</td>
									<?php else: ?>
										<td style='background: #FFF'></td>
									<?php endif; ?>
								</tr>
							<?php endforeach; ?>

							</tbody>
						</table>

					<?php endif; ?>

					<p>Categories can only be deleted when they don't have any associated items.</p>
				</div>
				<?php /* --------------------------------------- Items --------------------------------- */ ?>
			<?php
			elseif ( $adminpage == "items" ): ?>
				<a href="?page=weekly-schedule&amp;settings=general&amp;schedule=<?php echo $schedule; ?>">General Settings</a> |
				<a href="?page=weekly-schedule&amp;settings=categories&amp;schedule=<?php echo $schedule; ?>">Manage Schedule Categories</a> |
				<a href="?page=weekly-schedule&amp;settings=items&amp;schedule=<?php echo $schedule; ?>"><strong>Manage Schedule Items</strong></a> |
				<a href="?page=weekly-schedule&amp;settings=days&amp;schedule=<?php echo $schedule; ?>">Manage Days Labels</a>
				<br /><br />
				<div style='float:left;margin-right: 15px;width: 500px;'>
					<form name="wsitemsform" action="" method="post" id="ws-config">
						<?php
						if ( function_exists( 'wp_nonce_field' ) ) {
							wp_nonce_field( 'wspp-config' );
						}
						?>

						<input type="hidden" name="id" value="<?php if ( $mode == 'edit' && isset( $selecteditem ) ) {
							echo $selecteditem->id;
						} ?>" />
						<input type="hidden" name="oldrow" value="<?php if ( $mode == "edit" && isset( $selecteditem ) ) {
							echo $selecteditem->row;
						} ?>" />
						<input type="hidden" name="oldday" value="<?php if ( $mode == "edit"  && isset( $selecteditem ) ) {
							echo $selecteditem->day;
						} ?>" />
						<input type="hidden" name="schedule" value="<?php echo $schedule; ?>" />
						<?php if ( $mode == "edit"  && isset( $selecteditem ) ): ?>
							<strong>Editing Item #<?php echo $selecteditem->id; ?></strong>
						<?php endif; ?>

						<table>
							<?php
							if ( function_exists( 'wp_nonce_field' ) ) {
								wp_nonce_field( 'wspp-config' );
							}
							?>
							<tr>
								<td style='width: 180px'>Item Title</td>
								<td><input style="width:360px" type="text" name="name" <?php if ( $mode == "edit" && isset( $selecteditem ) ) {
										echo "value='" . stripslashes( $selecteditem->name ) . "'";
									} ?>/></td>
							</tr>
							<tr>
								<td>Category</td>
								<td><select style='width: 360px' name="category">
										<?php $cats = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wscategories where scheduleid = " . $schedule . " ORDER by name" );

										foreach ( $cats as $cat ) {
											if ( isset( $selecteditem ) && $cat->id == $selecteditem->category ) {
												$selectedstring = "selected='selected'";
											} else {
												$selectedstring = "";
											}

											echo "<option value='" . $cat->id . "' " . $selectedstring . ">" . $cat->name . "\n";
										}
										?></select></td>
							</tr>
							<tr>
								<td>Description</td>
								<td>
									<textarea id="description" rows="5" cols="45" name="description"><?php if ( $mode == "edit" && isset( $selecteditem ) ) {
											echo stripslashes( $selecteditem->description );
										} ?></textarea></td>
							</tr>
							<tr>
								<td>Web Address</td>
								<td><input style="width:360px" type="text" name="address" <?php if ( $mode == "edit" && isset( $selecteditem ) ) {
										echo "value='" . $selecteditem->address . "'";
									} ?>/></td>
							</tr>
							<tr>
								<td>Day</td>
								<td><select style='width: 360px' name="day">
										<?php $days = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsdays where scheduleid = " . $schedule . " ORDER by id" );

										foreach ( $days as $day ) {
											echo "<option value='" . $day->id . "' " . selected( $day->id, $selecteditem->day, false ) . ">" . $day->name . "\n";
										}
										?></select></td>
							</tr>
							<tr>
								<td>Start Time</td>
								<td><select style='width: 360px' name="starttime">
										<?php for ( $i = $options['starttime']; $i < $options['endtime']; $i += $options['timedivision'] ) {
											if ( $options['timeformat'] == '24hours' || $options['timeformat'] == '24hourscolon' ) {
												$hour = floor( $i );
											} elseif ( $options['timeformat'] == '12hours' ) {
												if ( $i < 12 ) {
													$timeperiod = "am";
													if ( $i == 0 ) {
														$hour = 12;
													} else {
														$hour = floor( $i );
													}
												} else {
													$timeperiod = "pm";
													if ( $i >= 12 && $i < 13 ) {
														$hour = floor( $i );
													} else {
														$hour = floor( $i ) - 12;
													}
												}
											}


											if ( fmod( $i, 1 ) == 0.25 ) {
												$minutes = "15";
											} elseif ( fmod( $i, 1 ) == 0.50 ) {
												$minutes = "30";
											} elseif ( fmod( $i, 1 ) == 0.75 ) {
												$minutes = "45";
											} else {
												$minutes = "00";
											}

											if ( $options['timeformat'] == '24hours' ) {
												echo "<option value='" . $i . "'" . selected( $i, $selecteditem->starttime, false ) . ">" . $hour . "h" . $minutes . "\n";
											} elseif ( $options['timeformat'] == '24hourscolon' ) {
												echo "<option value='" . $i . "'" . selected( $i, $selecteditem->starttime, false ) . ">" . $hour . ":" . $minutes . "\n";
											} elseif ( $options['timeformat'] == '12hours' ) {
												echo "<option value='" . $i . "'" . selected( $i, $selecteditem->starttime, false ) . ">" . $hour . ":" . $minutes . $timeperiod . "\n";
											}
										}
										?></select></td>
							</tr>
							<tr>
								<td>Duration</td>
								<td><select style='width: 360px' name="duration">
										<?php for ( $i = $options['timedivision']; $i <= ( $options['endtime'] - $options['starttime'] ); $i += $options['timedivision'] ) {
											if ( fmod( $i, 1 ) == 0.25 ) {
												$minutes = "15";
											} elseif ( fmod( $i, 1 ) == 0.50 ) {
												$minutes = "30";
											} elseif ( fmod( $i, 1 ) == 0.75 ) {
												$minutes = "45";
											} else {
												$minutes = "00";
											}

											echo "<option value='" . $i . "' " . selected( $i, $selecteditem->duration, false ) . ">" . floor( $i ) . "h" . $minutes . "\n";
										}
										?></select></td>
							</tr>
							<tr>
								<td>Background Cell Color (optional)</td>
								<td>
									<input style="width:100px" type="text" name="backgroundcolor" <?php if ( $mode == "edit" && isset( $selecteditem ) ) {
										echo "value='" . $selecteditem->backgroundcolor . "'";
									} ?>/></td>
							</tr>
							<tr>
								<td>Title Color (optional)</td>
								<td>
									<input style="width:100px" type="text" name="titlecolor" <?php if ( $mode == "edit" && isset( $selecteditem )) {
										echo "value='" . $selecteditem->titlecolor . "'";
									} ?>/></td>
							</tr>
						</table>
						<?php if ( $mode == "edit" ): ?>
							<p style="border:0;" class="submit">
								<input type="submit" name="updateitem" value="Update &raquo;" /></p>
						<?php else: ?>
							<p style="border:0;" class="submit">
								<input type="submit" name="newitem" value="Insert New Item &raquo;" /></p>
						<?php endif; ?>
					</form>
				</div>
				<div>
					<?php
					$itemquery = "SELECT d.name as dayname, i.id, i.name, i.backgroundcolor, i.day, i.starttime FROM " . ws_db_prefix() . "wsitems as i, " . ws_db_prefix() . "wsdays as d WHERE i.day = d.id
								and i.scheduleid = " . $schedule . " and d.scheduleid = " . $_GET['schedule'] . " ORDER by d.id, starttime, name";
					$items = $wpdb->get_results( $itemquery );

					if ( $items ): ?>
						<form name="wsitemdeletionform" action="?page=weekly-schedule&settings=items&schedule=<?php echo $schedule; ?>" method="post" id="ws-config">
							<?php
							if ( function_exists( 'wp_nonce_field' ) ) {
								wp_nonce_field( 'wspp-config' );
							}
							?>

							<input class="button" type="submit" name="deleteallitems" value="Delete all items in Schedule <?php echo $schedule; ?>" onclick="return confirm('Are you sure you want to delete all items in Schedule <?php echo $schedule; ?>?')" />
						</form>
						<br />
						<table class='widefat' style='clear:none;width:500px;background: #DFDFDF url(/wp-admin/images/gray-grad.png) repeat-x scroll left top;'>
							<thead>
							<tr>
								<th scope='col' style='width: 50px' id='id' class='manage-column column-id'>ID</th>
								<th scope='col' id='name' class='manage-column column-name' style=''>Name</th>
								<th scope='col' id='color' class='manage-column column-color' style=''>Color</th>
								<th scope='col' id='day' class='manage-column column-day' style='text-align: right'>Day</th>
								<th scope='col' style='width: 50px;text-align: right' id='starttime' class='manage-column column-items' style=''>Start Time</th>
								<th style='width: 30px'></th>
							</tr>
							</thead>

							<tbody id='the-list' class='list:link-cat'>

							<?php foreach ( $items as $item ): ?>
								<tr>
									<td class='name column-name' style='background: #FFF'>
										<a href='?page=weekly-schedule&amp;edititem=<?php echo $item->id; ?>&amp;schedule=<?php echo $schedule; ?>'><strong><?php echo $item->id; ?></strong></a>
									</td>
									<td style='background: #FFF'>
										<a href='?page=weekly-schedule&amp;edititem=<?php echo $item->id; ?>&amp;schedule=<?php echo $schedule; ?>'><strong><?php echo stripslashes( $item->name ); ?></strong></a>
									</td>

									<td style='background: <?php echo $item->backgroundcolor ? $item->backgroundcolor : '#FFF'; ?>'></td>
									<td style='background: #FFF;text-align:right'><?php echo $item->dayname; ?></td>
									<td style='background: #FFF;text-align:right'>
										<?php

										if ( $options['timeformat'] == '24hours' || $options['timeformat'] == '24hourscolon' ) {
											$hour = floor( $item->starttime );
										} elseif ( $options['timeformat'] == '12hours' ) {
											if ( $item->starttime < 12 ) {
												$timeperiod = "am";
												if ( $item->starttime == 0 ) {
													$hour = 12;
												} else {
													$hour = floor( $item->starttime );
												}
											} else {
												$timeperiod = "pm";
												if ( $item->starttime == 12 ) {
													$hour = $item->starttime;
												} else {
													$hour = floor( $item->starttime ) - 12;
												}
											}
										}

										if ( fmod( $item->starttime, 1 ) == 0.25 ) {
											$minutes = "15";
										} elseif ( fmod( $item->starttime, 1 ) == 0.50 ) {
											$minutes = "30";
										} elseif ( fmod( $item->starttime, 1 ) == 0.75 ) {
											$minutes = "45";
										} else {
											$minutes = "00";
										}

										if ( $options['timeformat'] == '24hours' ) {
											echo $hour . "h" . $minutes . "\n";
										} elseif ( $options['timeformat'] == '24hourscolon' ) {
											echo $hour . ":" . $minutes . "\n";
										} elseif ( $options['timeformat'] == '12hours' ) {
											echo $hour . ":" . $minutes . $timeperiod . "\n";
										}
										?></td>
									<td style='background:#FFF'>
										<a href='?page=weekly-schedule&amp;deleteitem=<?php echo $item->id; ?>&amp;schedule=<?php echo $schedule; ?>'
											<?php echo "onclick=\"if ( confirm('" . esc_js( sprintf( __( "You are about to delete the item '%s'\n  'Cancel' to stop, 'OK' to delete." ), $item->name ) ) . "') ) { return true;}return false;\""; ?>><img src='<?php echo plugins_url( '/icons/delete.png', __FILE__ ); ?>' /></a>
									</td>
								</tr>
							<?php endforeach; ?>

							</tbody>
						</table>
					<?php else: ?>
						<p>No items to display</p>
					<?php endif; ?>
				</div>
			<?php
			elseif ( $adminpage == "days" ): ?>
				<div>
					<a href="?page=weekly-schedule&amp;settings=general&amp;schedule=<?php echo $schedule; ?>">General Settings</a> |
					<a href="?page=weekly-schedule&amp;settings=categories&amp;schedule=<?php echo $schedule; ?>">Manage Schedule Categories</a> |
					<a href="?page=weekly-schedule&amp;settings=items&amp;schedule=<?php echo $schedule; ?>">Manage Schedule Items</a> |
					<a href="?page=weekly-schedule&amp;settings=days&amp;schedule=<?php echo $schedule; ?>"><strong>Manage Days Labels</strong></a><br /><br />

					<div>
						<form name="wsdaysform" action="" method="post" id="ws-config">
							<?php
							if ( function_exists( 'wp_nonce_field' ) ) {
								wp_nonce_field( 'wspp-config' );
							}

							$days = $wpdb->get_results( "SELECT * from " . ws_db_prefix() . "wsdays WHERE scheduleid = " . $schedule . " ORDER by id" );

							if ( $days ):
								?>
								<input type="hidden" name="schedule" value="<?php echo $schedule; ?>" />
								<table>
									<tr>
										<th style='text-align:left'><strong>ID</strong></th>
										<th style='text-align:left'><strong>Name</strong></th>
									</tr>
									<?php foreach ( $days as $day ): ?>
										<tr>
											<td style='width:30px;'><?php echo $day->id; ?></td>
											<td>
												<input style="width:300px" type="text" name="<?php echo $day->id; ?>" value='<?php echo $day->name; ?>' />
											</td>
										</tr>
									<?php endforeach; ?>
								</table>

								<p style="border:0;" class="submit">
									<input type="submit" name="updatedays" value="Update &raquo;" /></p>

							<?php endif; ?>

						</form>
					</div>
				</div>
			<?php
			endif; ?>
			</div>
		<?php
		} // end config_page()

	} // end class WS_Admin

	$my_ws_admin = new WS_Admin;
} //endif

function get_wsdays() {
}

function ws_library_func( $atts ) {
	$schedule = 1;
	extract(
		shortcode_atts(
			array(
				'schedule' => '',
				'cats' => ''
			), $atts
		)
	);

	if ( $schedule == '' ) {
		$options  = ws_get_options( '1' );
		$schedule = 1;
	} else {
		$options      = ws_get_options( $schedule );
	}

	if ( $options == false ) {
		return "Requested schedule (Schedule " . $schedule . ") is not available from Weekly Schedule<br />";
	}

	return ws_library(
		$schedule, $options['starttime'], $options['endtime'], $options['timedivision'], $options['layout'], $options['tooltipwidth'], $options['tooltiptarget'],
		$options['tooltippoint'], $options['tooltipcolorscheme'], $options['displaydescription'], $options['daylist'], $options['timeformat'],
		$options['adjusttooltipposition'], $options['linktarget'], $cats, $options['floatthead']
	);
}

function ws_library_flat_func( $atts ) {
	$schedule = 1;

	extract(
		shortcode_atts(
			array(
				'schedule' => '',
				'cats' => ''
			), $atts
		)
	);

	if ( $schedule == '' ) {
		$options  = ws_get_options( '1' );
		$schedule = 1;
	} else {
		$options      = ws_get_options( $schedule );
	}

	if ( $options == false ) {
		return "Requested schedule (Schedule " . $schedule . ") is not available from Weekly Schedule<br />";
	}

	return ws_library_flat(
		$schedule, $options['starttime'], $options['endtime'], $options['timedivision'], $options['layout'], $options['tooltipwidth'], $options['tooltiptarget'],
		$options['tooltippoint'], $options['tooltipcolorscheme'], $options['displaydescription'], $options['daylist'], $options['timeformat'],
		$options['adjusttooltipposition'], $cats, $options['floatthead']
	);
}


function ws_library(
	$scheduleid = 1, $starttime = 19, $endtime = 22, $timedivision = 0.5, $layout = 'horizontal', $tooltipwidth = 300, $tooltiptarget = 'right center',
	$tooltippoint = 'leftMiddle', $tooltipcolorscheme = 'ui-tooltip', $displaydescription = 'tooltip', $daylist = '', $timeformat = '24hours',
	$adjusttooltipposition = true, $linktarget = 'newwindow', $cats = '', $floatthead = false
) {
	global $wpdb;

	$today = date( 'w', current_time( 'timestamp', 0 ) ) + 1;
	$system_hour = date( 'H', current_time( 'timestamp', 0 ) );
	$system_minute = date( 'i', current_time( 'timestamp', 0 ) ) / 60;
	$time_now = $system_hour + $system_minute;

	$numberofcols = ( $endtime - $starttime ) / $timedivision;

	$output = "<!-- Weekly Schedule Output -->\n";

	$output .= "<div class='ws-schedule' id='ws-schedule" . $scheduleid . "'>\n";

	if ( $layout == 'horizontal' || $layout == '' ) {
		$output .= "<table class='horizontal'><thead>\n";
	} elseif ( $layout == 'vertical' ) {
		$output .= "<div class='verticalcolumn'>\n";
		$output .= "<table class='verticalheader'>\n";
	}

	$output .= "<tr class='topheader'>";

	$output .= "<th class='rowheader'></th>";

	if ( $layout == 'vertical' ) {
		$output .= "</tr>\n";
	}

	for ( $i = $starttime; $i < $endtime; $i += $timedivision ) {

		if ( fmod( $i, 1 ) == 0.25 ) {
			$minutes = "15";
		} elseif ( fmod( $i, 1 ) == 0.50 ) {
			$minutes = "30";
		} elseif ( fmod( $i, 1 ) == 0.75 ) {
			$minutes = "45";
		} else {
			$minutes = "";
		}


		if ( $timeformat == "24hours" || $timeformat == "" ) {
			if ( $layout == 'vertical' ) {
				$output .= "<tr class='datarow'>";
			}

			$output .= "<th>" . floor( $i ) . "h" . $minutes . "</th>";

			if ( $layout == 'vertical' ) {
				$output .= "</tr>\n";
			}

		} else if ( $timeformat == "24hourscolon" ) {
			if ( $layout == 'vertical' ) {
				$output .= "<tr class='datarow'>";
			}

			$output .= "<th>" . floor( $i ) . ":" . ( empty( $minutes ) ? "00" : $minutes ) . "</th>";

			if ( $layout == 'vertical' ) {
				$output .= "</tr>\n";
			}

		} else if ( $timeformat == "12hours" ) {
			if ( $i < 12 ) {
				$timeperiod = "am";
				if ( $i == 0 ) {
					$hour = 12;
				} else {
					$hour = floor( $i );
				}
			} else {
				$timeperiod = "pm";
				if ( $i >= 12 && $i < 13 ) {
					$hour = floor( $i );
				} else {
					$hour = floor( $i ) - 12;
				}
			}

			if ( $layout == 'vertical' ) {
				$output .= "<tr class='datarow'>";
			}

			$output .= "<th>" . $hour;
			if ( $minutes != "" ) {
				$output .= ":" . $minutes;
			}
			$output .= $timeperiod . "</th>";

			if ( $layout == 'vertical' ) {
				$output .= "</tr>\n";
			}
		}
	}

	if ( $layout == 'horizontal' || $layout == '' ) {
		$output .= "</tr></thead><tbody>\n";
	} elseif ( $layout == 'vertical' ) {
		$output .= "</table>\n";
		$output .= "</div>\n";
	}


	$sqldays = "SELECT * from " . ws_db_prefix() . "wsdays where scheduleid = %d";

	if ( !empty( $daylist ) ) {
		$sqldays .= " AND id in ( %s ) ORDER BY FIELD(id, %s)";
		$sqldaysquery = $wpdb->prepare( $sqldays, $scheduleid, $daylist, $daylist );
		$sqldaysquery = str_replace( '\'', '', $sqldaysquery );
		$daysoftheweek = $wpdb->get_results( $sqldaysquery );
	} else {
		$daysoftheweek = $wpdb->get_results( $wpdb->prepare( $sqldays, $scheduleid ) );
	}

	foreach ( $daysoftheweek as $day ) {
		for ( $daysrow = 1; $daysrow <= $day->rows; $daysrow ++ ) {
			$columns = $numberofcols;
			$time    = $starttime;
			$firstrowofday = 0;

			if ( $layout == 'vertical' ) {
				$output .= "<div class='verticalcolumn" . $day->rows . "'>\n";
				$output .= "<table class='vertical" . $day->rows . "'>\n";
				$output .= "<thead class='vertrow" . $day->rows . "'>";
			} elseif ( $layout == 'horizontal' || $layout == '' ) {
				$output .= "<tr class='row" . $day->rows . " ";
				if ( !$firstrowofday ) {
					$output .= "firstrowofday";
					$firstrowofday = 1;
				}
				$output .= "'>\n";
			}

			if ( $daysrow == 1 && ( $layout == 'horizontal' || $layout == '' ) ) {
				$output .= "<th rowspan='" . $day->rows . "' class='rowheader'>" . $day->name . "</th>\n";
			}
			if ( $daysrow == 1 && $layout == 'vertical' && $day->rows == 1 ) {
				$output .= "<th class='rowheader'>" . $day->name . "</th>\n";
			}
			if ( $daysrow == 1 && $layout == 'vertical' && $day->rows > 1 ) {
				$output .= "<th class='rowheader'>&laquo; " . $day->name . "</th>\n";
			} elseif ( $daysrow != 1 && $layout == 'vertical' ) {
				if ( $daysrow == $day->rows ) {
					$output .= "<th class='rowheader'>" . $day->name . " &raquo;</th>\n";
				} else {
					$output .= "<th class='rowheader'>&laquo; " . $day->name . " &raquo;</th>\n";
				}
			}

			if ( $layout == 'vertical' ) {
				$output .= "</thead><tbody>\n";
			}

			$sqlitems = "SELECT *, i.name as itemname, c.name as categoryname, c.id as catid, i.backgroundcolor as itemcolor, c.backgroundcolor as categorycolor, i.day as dayid from " . ws_db_prefix() .
				"wsitems i, " . ws_db_prefix() . "wscategories c WHERE day = " . $day->id .
				" AND i.scheduleid = %d AND row = " . $daysrow . " AND i.category = c.id AND i.starttime >= %f AND i.starttime < %f ";

			if ( !empty( $cats ) ) {
				$sqlitems .= ' AND category IN ( %s ) ';
			}

			$sqlitems .= " ORDER by starttime";

			if ( empty( $cats ) ) {
				$items = $wpdb->get_results( $wpdb->prepare( $sqlitems, $scheduleid, $starttime, $endtime ) );
			} else {
				$items = $wpdb->get_results( $wpdb->prepare( $sqlitems, $scheduleid, $starttime, $endtime, $cats ) );
			}

			if ( $items ) {
				foreach ( $items as $item ) {

					for ( $i = $time; $i < $item->starttime; $i += $timedivision ) {
						if ( $layout == 'vertical' ) {
							$output .= "<tr class='datarow'>\n";
						}

						$output .= "<td></td>\n";

						if ( $layout == 'vertical' ) {
							$output .= "</tr>\n";
						}

						$columns -= 1;

					}

					$colspan = $item->duration / $timedivision;

					if ( $colspan > $columns ) {
						$colspan = $columns;
						$columns -= $columns;

						if ( $layout == 'horizontal' ) {
							$continue = "id='continueright' ";
						} elseif ( $layout == 'vertical' ) {
							$continue = "id='continuedown' ";
						}
					} else {
						$columns -= $colspan;
						$continue = "";
					}

					if ( $layout == 'vertical' ) {
						$output .= "<tr class='datarow" . $colspan . "'>";
					}

					$output .= '<td class="';

					if ( $item->starttime < $time_now && $time_now < ( $item->starttime + $item->duration ) && $today == $item->dayid ) {
						$output .= 'now-playing ';
					}

					$output .= 'ws-item-' . $item->id . ' cat' . $item->catid . '" ';


					if ( !empty( $item->itemcolor ) || !empty( $item->categorycolor ) ) {

						$output .= 'style= "' . 'background-color:' . ( !empty( $item->itemcolor ) ? $item->itemcolor : $item->categorycolor ) . ';"';
					}

					if ( $displaydescription == "tooltip" && $item->description != "" ) {
						$output .= "tooltip='" . htmlspecialchars( stripslashes( $item->description ), ENT_QUOTES ) . "' ";
					}

					$output .= $continue;

					if ( $layout == 'horizontal' || $layout == '' ) {
						$output .= "colspan='" . $colspan . "'";
					}
					
					$output .= '>';

					$output .= '<div class="';

					if ( $item->starttime < $time_now && $time_now < ( $item->starttime + $item->duration ) && $today == $item->dayid ) {
						$output .= 'now-playing ';
					}

					$output .= 'ws-item-title ws-item-title-' . $item->id . '"';

					if ( !empty( $item->titlecolor ) ) {
						$output .= ' style="color:' . $item->titlecolor . '"';
					}

					$output .= ">";

					if ( $item->address != "" ) {
						$output .= "<a target='" . $linktarget . "'href='" . $item->address . "'>";
					}

					$output .= stripslashes( $item->itemname );

					if ( $item->address != "" ) {
						"</a>";
					}

					$output .= "</div>";

					if ( $displaydescription == "cell" ) {
						$output .= "<br />" . stripslashes( $item->description );
					}

					$output .= "</td>";
					$time = $item->starttime + $item->duration;

					if ( $layout == 'vertical' ) {
						$output .= "</tr>\n";
					}

				}

				for ( $x = $columns; $x > 0; $x -- ) {

					if ( $layout == 'vertical' ) {
						$output .= "<tr class='datarow'>";
					}

					$output .= "<td></td>";
					$columns -= 1;

					if ( $layout == 'vertical' ) {
						$output .= "</tr>";
					}
				}
			} else {
				for ( $i = $starttime; $i < $endtime; $i += $timedivision ) {
					if ( $layout == 'vertical' ) {
						$output .= "<tr class='datarow'>";
					}

					$output .= "<td></td>";

					if ( $layout == 'vertical' ) {
						$output .= "</tr>";
					}
				}
			}

			if ( $layout == 'horizontal' || $layout == '' ) {
				$output .= "</tr>";
			}

			if ( $layout == 'vertical' ) {
				$output .= "</tbody></table>\n";
				$output .= "</div>\n";
			}
		}
	}

	if ( $layout == 'horizontal' || $layout == '' ) {
		$output .= "</tbody></table>";
	}

	$output .= "</div>\n";

	if ( $displaydescription == "tooltip" ) {
		$output .= "<script type=\"text/javascript\">\n";
		$output .= "// Create the tooltips only on document load\n";

		$output .= "jQuery(document).ready(function()\n";
		$output .= "\t{\n";
		$output .= "\t// Notice the use of the each() method to acquire access to each elements attributes\n";
		$output .= "\tjQuery('.ws-schedule td[tooltip]').each(function()\n";
		$output .= "\t\t{\n";
		$output .= "\t\tjQuery(this).qtip({\n";
		$output .= "\t\t\tcontent: jQuery(this).attr('tooltip'), // Use the tooltip attribute of the element for the content\n";
		$output .= "\t\t\tstyle: {\n";
		$output .= "\t\t\t\twidth: " . $tooltipwidth . ",\n";
		$output .= "\t\t\t\tclasses: '" . $tooltipcolorscheme . "' // Give it a crea mstyle to make it stand out\n";
		$output .= "\t\t\t},\n";
		$output .= "\t\t\tposition: {\n";
		if ( $adjusttooltipposition ) {
			$output .= "\t\t\t\tadjust: {method: 'flip flip'},\n";
		}
		$output .= "\t\t\t\tviewport: jQuery(window),\n";
		$output .= "\t\t\t\tat: '" . $tooltiptarget . "',\n";
		$output .= "\t\t\t\tmy: '" . $tooltippoint . "'\n";
		$output .= "\t\t\t}\n";
		$output .= "\t\t});\n";
		$output .= "\t});\n";
		$output .= "});\n";
		$output .= "</script>\n";

	}

	if ( $floatthead ) {
		$output .= "<script type=\"text/javascript\">\n";

		if ( 'vertical' == $layout ) {
			$output .= "jQuery( '.vertical1' ).floatThead();\n";
		} elseif ( 'horizontal' == $layout ) {
			$output .= "jQuery( '.horizontal' ).floatThead();\n";
		}

		$output .= "</script>\n";
	}

	$output .= "<!-- End of Weekly Schedule Output -->\n";

	return $output;
}

function ws_library_flat(
	$scheduleid = 1, $starttime = 19, $endtime = 22, $timedivision = 0.5, $layout = 'horizontal', $tooltipwidth = 300, $tooltiptarget = 'right center',
	$tooltippoint = 'leftMiddle', $tooltipcolorscheme = 'ui-tooltip', $displaydescription = 'tooltip', $daylist = '', $timeformat = '24hours',
	$adjusttooltipposition = true, $cats = '', $floatthead = false
) {
	global $wpdb;

	$today = date( 'w', current_time( 'timestamp', 0 ) ) + 1;
	$system_hour = date( 'H', current_time( 'timestamp', 0 ) );
	$system_minute = date( 'i', current_time( 'timestamp', 0 ) ) / 60;
	$time_now = $system_hour + $system_minute;

	$linktarget = 'newwindow';

	$output = "<!-- Weekly Schedule Flat Output -->\n";

	$output .= "<div class='ws-schedule' id='ws-schedule<?php echo $scheduleid; ?>'>\n";

	$sqldays = "SELECT * from " . ws_db_prefix() . "wsdays where scheduleid = %d";

	if ( !empty( $daylist ) ) {
		$sqldays .= " AND id in ( %s ) ORDER BY FIELD(id, %s )";
		$sqldaysquery = $wpdb->prepare( $sqldays, $scheduleid, $daylist, $daylist );
		$sqldaysquery = str_replace( '\'', '', $sqldaysquery );
		$daysoftheweek = $wpdb->get_results( $sqldaysquery );
	} else {
		$daysoftheweek = $wpdb->get_results( $wpdb->prepare( $sqldays, $scheduleid ) );
	}

	$output .= "<table>\n";

	foreach ( $daysoftheweek as $day ) {
		for ( $daysrow = 1; $daysrow <= $day->rows; $daysrow ++ ) {
			$output .= "<tr><td colspan='3'>" . $day->name . "</td></tr>\n";

			$sqlitems = "SELECT *, i.name as itemname, c.name as categoryname, c.id as catid, i.day as dayid from " . ws_db_prefix() .
				"wsitems i, " . ws_db_prefix() . "wscategories c WHERE day = " . $day->id .
				" AND i.scheduleid = %d AND row = " . $daysrow . " AND i.category = c.id AND i.starttime >= %f AND i.starttime < %f ";

			if ( !empty( $cats ) ) {
				$sqlitems .= 'AND category IN ( %s )';
			}

			$sqlitems .= "ORDER by starttime";

			if ( empty( $cats ) ) {
				$items = $wpdb->get_results( $wpdb->prepare( $sqlitems, $scheduleid, $starttime, $endtime ) );
			} else {
				$items = $wpdb->get_results( $wpdb->prepare( $sqlitems, $scheduleid, $starttime, $endtime, $cats ) );
			}

			if ( $items ) {
				foreach ( $items as $item ) {

					$output .= "<tr>\n";

					if ( $timeformat == '24hours' || $timeformat == '24hourscolon' ) {
						$hour = floor( $item->starttime );
					} elseif ( $timeformat == '12hours' ) {
						if ( $item->starttime < 12 ) {
							$timeperiod = "am";
							if ( $item->starttime == 0 ) {
								$hour = 12;
							} else {
								$hour = floor( $item->starttime );
							}
						} else {
							$timeperiod = "pm";
							if ( $item->starttime == 12 ) {
								$hour = $item->starttime;
							} else {
								$hour = floor( $item->starttime ) - 12;
							}
						}
					}

					if ( fmod( $item->starttime, 1 ) == 0.25 ) {
						$minutes = "15";
					} elseif ( fmod( $item->starttime, 1 ) == 0.50 ) {
						$minutes = "30";
					} elseif ( fmod( $item->starttime, 1 ) == 0.75 ) {
						$minutes = "45";
					} else {
						$minutes = "00";
					}

					if ( $timeformat == '24hours' ) {
						$output .= "<td>" . $hour . "h" . $minutes . " - ";
					} elseif ( $timeformat == '24hourscolon' ) {
						$output .= "<td>" . $hour . ":" . $minutes . " - ";
					} elseif ( $timeformat == '12hours' ) {
						$output .= "<td>" . $hour . ":" . $minutes . $timeperiod . " - ";
					}

					$endtime = $item->starttime + $item->duration;

					if ( $timeformat == '24hours' || $timeformat == '24hourscolon' ) {
						$hour = floor( $endtime );
					} elseif ( $timeformat == '12hours' ) {
						if ( $endtime < 12 ) {
							$timeperiod = "am";
							if ( $endtime == 0 ) {
								$hour = 12;
							} else {
								$hour = floor( $endtime );
							}
						} else {
							$timeperiod = "pm";
							if ( $endtime == 12 ) {
								$hour = $endtime;
							} else {
								$hour = floor( $endtime ) - 12;
							}
						}
					}

					if ( fmod( $endtime, 1 ) == 0.25 ) {
						$minutes = "15";
					} elseif ( fmod( $endtime, 1 ) == 0.50 ) {
						$minutes = "30";
					} elseif ( fmod( $endtime, 1 ) == 0.75 ) {
						$minutes = "45";
					} else {
						$minutes = "00";
					}

					if ( $timeformat == '24hours' ) {
						$output .= $hour . "h" . $minutes . "</td>";
					} elseif ( $timeformat == '24hourscolon' ) {
						$output .= $hour . ":" . $minutes . "</td>";
					} elseif ( $timeformat == '12hours' ) {
						$output .= $hour . ":" . $minutes . $timeperiod . "</td>";
					}

					$output .= "<td";

					if ( $item->starttime < $time_now && $time_now < ( $item->starttime + $item->duration ) && $today == $item->dayid ) {
						$output .= ' class="now-playing"';
					}

					$output .= ">\n";

					if ( $item->address != "" ) {
						$output .= "<a target='" . $linktarget . "'href='" . $item->address . "'>";
					}

					$output .= $item->itemname;

					if ( $item->address != "" ) {
						"</a>";
					}

					$output .= "</td>";

					$output .= "<td>" . htmlspecialchars( stripslashes( $item->description ), ENT_QUOTES ) . "</td>";

					$output .= "</tr>";
				}
			}
		}
	}

	$output .= "</table>";

	$output .= "</div id='ws-schedule'>\n";

	$output .= "<!-- End of Weekly Schedule Flat Output -->\n";

	return $output;
}

$version = "1.0";

add_shortcode( 'weekly-schedule', 'ws_library_func' );

add_shortcode( 'flat-weekly-schedule', 'ws_library_flat_func' );

add_shortcode( 'daily-weekly-schedule', 'ws_day_list_func' );

function ws_day_list_func( $atts ) {
	$schedule  = 1;
	$max_items = 5;
	$empty_msg = 'No Items Found';

	extract(
		shortcode_atts(
			array(
				'schedule'  => 1,
				'cats'      => '',
				'max_items' => 5,
				'empty_msg' => 'No Items Found'
			), $atts
		)
	);

	$today  = date( 'w', current_time( 'timestamp', 0 ) ) + 1;
	$output = '<div class="ws_widget_output">';

	//fetch results
	global $wpdb;

	$schedule_query = 'SELECT * from ' . ws_db_prefix() .
		'wsitems WHERE day = ' . $today .
		' AND scheduleid = %d ';

	if ( !empty( $cats ) ) {
		$schedule_query .= ' AND category IN ( %s ) ';
	}

	$schedule_query .= 'ORDER by starttime ASC LIMIT 0, %d';

	if ( empty( $cats ) ) {
		$schedule_items = $wpdb->get_results( $wpdb->prepare( $schedule_query, $schedule, $max_items ) );
	} else {
		$schedule_items = $wpdb->get_results( $wpdb->prepare( $schedule_query, $schedule, $cats, $max_items ) );
	}

	if ( !empty( $schedule_items ) ) {
		$output .= '<ul>';

		foreach ( $schedule_items as $schedule_item ) {
			$item_name  = stripslashes( $schedule_item->name );
			$start_hour = $schedule_item->starttime;

			if ( strpos( $start_hour, '.' ) > 0 ) {
				$start_hour = substr( $start_hour, 0, strlen( $start_hour ) - strpos( $start_hour, '.' ) );
				$start_hour .= ':30';
			} else {
				$start_hour .= ":00";
			}

			$output .= '<li>';
			if ( !empty( $schedule_item->address ) ) {
				$output .= '<a href="' . $schedule_item->address . '">';
			}
			$output .= $start_hour . ' - ' . $item_name;

			if ( !empty( $schedule_item->address ) ) {
				$output .= '</a>';
			}
			$output .= '</li>';
		}

		$output .= '</ul>';
	} else {
		$output .= $empty_msg;
	}

	$output .= '</div>';

	return $output;
}

add_filter( 'the_posts', 'ws_conditional_header' ); // the_posts gets triggered before wp_head

function ws_conditional_header( $posts ) {
	if ( empty( $posts ) ) {
		return $posts;
	}

	$load_jquery = false;
	$load_qtip   = false;
	$load_floatthead = false;
	$load_style  = false;

	$genoptions = ws_get_general_options( 'WeeklyScheduleGeneral' );

	foreach ( $posts as $post ) {
		$continuesearch = true;
		$searchpos      = 0;
		$scheduleids    = array();

		while ( $continuesearch ) {
			$weeklyschedulepos = stripos( $post->post_content, 'weekly-schedule ', $searchpos );
			if ( $weeklyschedulepos == false ) {
				$weeklyschedulepos = stripos( $post->post_content, 'weekly-schedule]', $searchpos );
			}
			$continuesearch = $weeklyschedulepos;
			if ( $continuesearch ) {
				$load_style   = true;
				$shortcodeend = stripos( $post->post_content, ']', $weeklyschedulepos );
				if ( $shortcodeend ) {
					$searchpos = $shortcodeend;
				} else {
					$searchpos = $weeklyschedulepos + 1;
				}

				if ( $shortcodeend ) {
					$settingconfigpos = stripos( $post->post_content, 'settings=', $weeklyschedulepos );
					if ( $settingconfigpos && $settingconfigpos < $shortcodeend ) {
						$schedule = substr( $post->post_content, $settingconfigpos + 9, $shortcodeend - $settingconfigpos - 9 );

						$scheduleids[] = $schedule;
					} else if ( count( $scheduleids ) == 0 ) {
						$scheduleids[] = 1;
					}
				}
			}
		}
	}

	if ( $scheduleids ) {
		foreach ( $scheduleids as $scheduleid ) {
			$options      = ws_get_options( $scheduleid );

			if ( 'tooltip' == $options['displaydescription'] ) {
				$load_jquery = true;
				$load_qtip   = true;
			}

			if ( $options['floatthead'] ) {
				$load_jquery = true;
				$load_floatthead = true;
			}
		}
	}

	if ( isset( $genoptions['includestylescript'] ) && !empty( $genoptions['includestylescript'] ) ) {
		$pagelist = explode( ',', $genoptions['includestylescript'] );
		foreach ( $pagelist as $pageid ) {
			if ( is_page( $pageid ) ) {
				$load_jquery = true;
				$load_style  = true;
				$load_qtip   = true;
				$load_floatthead = true;
			}
		}
	}

	if ( $load_jquery ) {
		wp_enqueue_script( 'jquery' );
	}

	if ( $load_qtip ) {
		wp_enqueue_style( 'qtipstyle', plugins_url( 'jquery-qtip/jquery.qtip.min.css', __FILE__ ) );
		wp_enqueue_script( 'qtip', plugins_url( 'jquery-qtip/jquery.qtip.min.js', __FILE__ ) );
		wp_enqueue_script( 'imagesloaded', plugins_url( 'jquery-qtip/imagesloaded.pkg.min.js', __FILE__ ), 'qtip' );
	}

	if ( $load_floatthead ) {
		wp_enqueue_script( 'floatThead', plugins_url( 'float-thead/jquery.floatThead.min.js', __FILE__ ) );
	}

	return $posts;
}

add_filter( 'the_posts', 'ws_conditionally_add_scripts_and_styles' );

function ws_conditionally_add_scripts_and_styles( $posts ) {
	if ( empty( $posts ) ) {
		return $posts;
	}

	$load_style = false;

	$genoptions = ws_get_general_options( 'WeeklyScheduleGeneral' );

	if ( is_admin() ) {
		$load_jquery   = false;
		$load_thickbox = false;
		$load_style    = false;
	} else {
		foreach ( $posts as $post ) {
			$linklibrarypos = stripos( $post->post_content, 'weekly-schedule', 0 );
			if ( $linklibrarypos !== false ) {
				$load_style = true;
			}
		}
	}

	global $wsstylesheet;
	if ( $load_style ) {
		$wsstylesheet = true;
	} else {
		$wsstylesheet = false;
	}

	return $posts;
}

add_action( 'wp_head', 'ws_header_output' );

function ws_header_output() {
	global $wsstylesheet;
	$genoptions = ws_get_general_options( 'WeeklyScheduleGeneral' );

	if ( $wsstylesheet ) {
		echo "<style id='WeeklyScheduleStyle' type='text/css'>\n";
		echo stripslashes( $genoptions['fullstylesheet'] );
		echo "</style>\n";
	}
}


/* Register widgets */
add_action( 'widgets_init', 'ws_register_widget' );

function ws_register_widget() {
	register_widget( "WSTodayScheduleWidget" );
}

class WSTodayScheduleWidget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'weekly_schedule_widget', // Base ID
			'Weekly Schedule Widget', // Name
			array( 'description' => 'Displays a list of schedule items' ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		global $wp_locale;
		$before_widget = '';
		$before_title  = '';
		$after_title   = '';
		$after_widget  = '';

		extract( $args );

		$title       = apply_filters( 'widget_title', $instance['title'] );
		$max_items   = ( !empty( $instance['max_items'] ) ? $instance['max_items'] : 5 );
		$schedule_id = ( !empty( $instance['schedule_id'] ) ? $instance['schedule_id'] : 1 );
		$empty_msg   = ( !empty( $instance['empty_msg'] ) ? $instance['empty_msg'] : 'No Items Found' );
		$only_next_items   = ( !empty( $instance['only_next_items'] ) ? $instance['only_next_items'] : false );

		$options      = ws_get_options( $schedule_id );

		$today = date( 'w', current_time( 'timestamp', 0 ) ) + 1;
		$system_hour = date( 'H', current_time( 'timestamp', 0 ) );
		$system_minute = date( 'i', current_time( 'timestamp', 0 ) ) / 60;
		$time_now = $system_hour + $system_minute;

		echo $before_widget;
		if ( !empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		//fetch results
		global $wpdb;

		$schedule_query = 'SELECT * from ' . ws_db_prefix() .
			'wsitems WHERE day = ' . $today .
			' AND scheduleid = ' . $schedule_id . ' ORDER by starttime ASC';

		$schedule_items = $wpdb->get_results( $schedule_query );

		$itemcount = 0;

		if ( !empty( $schedule_items ) ) {
			echo '<ul>';

			foreach ( $schedule_items as $schedule_item ) {
				if ( $only_next_items && ! ( $schedule_item->starttime < $time_now && $time_now < ( $schedule_item->starttime + $schedule_item->duration ) ) && ! ( $schedule_item->starttime > $time_now ) ) {
					continue;
				}

				$itemcount++;

				if ( $itemcount > $max_items ) {
					break;
				}

				$item_name  = stripslashes( $schedule_item->name );
				$start_hour = $schedule_item->starttime;

				if ( fmod( $schedule_item->starttime, 1 ) == 0.25 ) {
					$minutes = '15';
				} elseif ( fmod( $schedule_item->starttime, 1 ) == 0.50 ) {
					$minutes = '30';
				} elseif ( fmod( $schedule_item->starttime, 1 ) == 0.75 ) {
					$minutes = '45';
				} else {
					$minutes = '';
				}

				if ( $options['timeformat'] == '24hours' || empty( $options['timeformat'] ) ) {
					$start_hour = floor( $schedule_item->starttime ) . "h" . $minutes;
				} else if ( $options['timeformat'] == '24hourscolon' ) {
					$start_hour = floor( $schedule_item->starttime ) . ":" . ( empty( $minutes ) ? "00" : $minutes );
				} else if ( $options['timeformat'] == '12hours' ) {
					if ( $schedule_item->starttime < 12 ) {
						$timeperiod = 'am';
						if ( $schedule_item->starttime == 0 ) {
							$hour = 12;
						} else {
							$hour = floor( $schedule_item->starttime );
						}
					} else {
						$timeperiod = 'pm';
						if ( $schedule_item->starttime >= 12 && $schedule_item->starttime < 13 ) {
							$hour = floor( $schedule_item->starttime );
						} else {
							$hour = floor( $schedule_item->starttime ) - 12;
						}
					}

					$start_hour = $hour;
					if ( !empty( $minutes ) ) {
						$start_hour .= ":" . $minutes;
					}
					$start_hour .= $timeperiod;
				}

				echo '<li';

				if ( $schedule_item->starttime < $time_now && $time_now < ( $schedule_item->starttime + $schedule_item->duration ) && $today == $schedule_item->day ) {
					echo ' class="now-playing"';
				}

				echo '>';
				if ( !empty( $schedule_item->address ) ) {
					echo '<a href="' . $schedule_item->address . '">';
				}
				echo $start_hour . ' - ' . $item_name;

				if ( !empty( $schedule_item->address ) ) {
					echo '</a>';
				}
				echo '</li>';
			}

			echo '</ul>';
		} else {
			echo $empty_msg;
		}

		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance              = array();
		$instance['title']     = strip_tags( $new_instance['title'] );
		$instance['max_items'] = strip_tags( $new_instance['max_items'] );

		if ( is_numeric( $new_instance['schedule_id'] ) ) {
			$instance['schedule_id'] = intval( $new_instance['schedule_id'] );
		} else {
			$instance['schedule_id'] = $instance['schedule_id'];
		}

		if ( isset( $new_instance['only_next_items'] ) ) {
			$instance['only_next_items'] = true;
		} else {
			$instance['only_next_items'] = false;
		}

		$instance['empty_msg'] = strip_tags( $new_instance['empty_msg'] );

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		/* Set initial values/defaults */
		$title       = ( !empty( $instance['title'] ) ? $instance['title'] : "Today's Scheduled Items" );
		$max_items   = ( !empty( $instance['max_items'] ) ? $instance['max_items'] : 5 );
		$schedule_id = ( !empty( $instance['schedule_id'] ) ? $instance['schedule_id'] : 1 );
		$empty_msg   = ( !empty( $instance['empty_msg'] ) ? $instance['empty_msg'] : 'No Items Found' );
		$only_next_items   = ( !empty( $instance['only_next_items'] ) ? $instance['only_next_items'] : false );

		$genoptions = ws_get_general_options( 'WeeklyScheduleGeneral' );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'empty_msg' ); ?>">Empty Item List Message:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'empty_msg' ); ?>" name="<?php echo $this->get_field_name( 'empty_msg' ); ?>" type="text" value="<?php echo esc_attr( $empty_msg ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'max_items' ); ?>">Max Number of Items:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'max_items' ); ?>" name="<?php echo $this->get_field_name( 'max_items' ); ?>" type="text" value="<?php echo esc_attr( $max_items ); ?>" />
			<span class='description'><?php __( 'Maximum number of items to display' ); ?></span>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'max_items' ); ?>">Only show current and later items</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'only_next_items' ); ?>" name="<?php echo $this->get_field_name( 'only_next_items' ); ?>" <?php checked( $only_next_items ); ?> />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'schedule_id' ); ?>">Schedule ID</label>

			<SELECT class="widefat" id="<?php echo $this->get_field_id( 'schedule_id' ); ?>" name="<?php echo $this->get_field_name( 'schedule_id' ); ?>">
				<?php if ( empty( $genoptions['numberschedules'] ) ) {
					$number_of_schedules = 2;
				} else {
					$number_of_schedules = $genoptions['numberschedules'];
				}
				for ( $counter = 1; $counter <= $number_of_schedules; $counter ++ ): ?>
					<?php $tempoptions = ws_get_options( $counter ); ?>
					<option value="<?php echo $counter ?>" <?php selected( $schedule_id, $counter, false ); ?>>Schedule <?php echo $counter ?><?php if ( $tempoptions != "" ) {
							echo " (" . $tempoptions['schedulename'] . ")";
						} ?></option>
				<?php endfor; ?>
			</SELECT>
		</p>

	<?php
	}

}


?>