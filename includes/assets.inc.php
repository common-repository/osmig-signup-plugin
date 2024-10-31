<?php
if( !defined( 'OSMIG_LOADED' ) ) {
	die( 'Direct access not permitted' );
}

####################################################################
#
# STYLESHEET INCLUSION
#
####################################################################
function osmig_assets_queue() {
	wp_enqueue_style('osmig', plugins_url('osmig/css/osmig.css'), false, OSMIG_DB_VERSION, 'all');
}
add_action( 'wp_enqueue_scripts', 'osmig_assets_queue' );