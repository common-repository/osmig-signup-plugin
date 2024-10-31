<?php
if( !defined( 'OSMIG_LOADED' ) ) {
	die( 'Direct access not permitted' );
}

####################################################################
#
# DASHBOARD WIDGET
#
####################################################################
function osmig_dashboard_widget() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "osmig_signups";
	$attendee_count = $wpdb->query("SELECT COUNT(id) as CNT FROM $table_name GROUP BY userkey");
	echo "Signups so far: <strong>" . $attendee_count . "</strong>";
} 

// Create the function use in the action hook

function add_osmig_dashboard_widget() {
	wp_add_dashboard_widget('osmig_dashboard_widget', 'Osmig Signups', 'osmig_dashboard_widget');	
} 

// Hook into the 'wp_dashboard_setup' action to register our other functions

add_action('wp_dashboard_setup', 'add_osmig_dashboard_widget' );