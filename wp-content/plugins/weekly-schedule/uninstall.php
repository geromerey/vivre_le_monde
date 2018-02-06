<?php
// Check that code was called from WordPress with
// uninstallation constant declared
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit;

function ws_db_prefix() {
    global $wpdb;
    if ( method_exists( $wpdb, "get_blog_prefix" ) ) {
        return $wpdb->get_blog_prefix();
    } else {
        return $wpdb->prefix;
    }
}

// Check if options exist and delete them if present
if ( get_option( 'WeeklyScheduleGeneral' ) != false ) {
    
    $genoptions = get_option( 'WeeklyScheduleGeneral' );

    for ($i = 1; $i <= $genoptions['numberschedules']; $i++) {
        $settingsname = 'WS_PP' . $i;
        
        delete_option( $settingsname );
    }
    
    delete_option( 'WeeklyScheduleGeneral' );
}

global $wpdb;

$wpdb->wscategories = ws_db_prefix() . 'wscategories';

$deletionquery1 = 'DROP TABLE IF EXISTS ' . $wpdb->wscategories;

$wpdb->get_results( $deletionquery1 );

$wpdb->wsdays = ws_db_prefix() . 'wsdays';

$deletionquery2 = 'DROP TABLE IF EXISTS ' . $wpdb->wsdays;

$wpdb->get_results( $deletionquery2 );

$wpdb->wsitems = ws_db_prefix() . 'wsitems';

$deletionquery3 = 'DROP TABLE IF EXISTS ' . $wpdb->wsitems;

$wpdb->get_results( $deletionquery3 );
