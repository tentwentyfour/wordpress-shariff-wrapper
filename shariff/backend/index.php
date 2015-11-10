<?php
// prevent caching
header( "Expires: Sat, 24 Jan 1970 04:10:00 GMT" ); // date from the past
header( "Last-Modified: " . gmdate("D, d M Y H:i:s" ) . " GMT" ); // always changed
header( "Cache-Control: no-store, no-cache, must-revalidate" );
header( "Cache-Control: post-check=0, pre-check=0", false ); // just for MSIE 5
header( "Pragma: no-cache" );

// send correct headers
header('Content-type: application/json; charset=utf-8');

// exit if no url is provided
if ( ! isset( $_GET["url"] ) ) { 
	echo 'No URL provided!';
	return; 
}
	
// build the wp root path
$wp_root_path = dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );

// if wp-blog-header.php doesn't exist at $wp_root_path, then use constant
if( ! file_exists( $wp_root_path . '/wp-blog-header.php') ) {
	$wp_root_path = SHARIFF_WP_ROOT_PATH;
}

// fire up WordPress without theme support
define('WP_USE_THEMES', false);
require ( $wp_root_path . '/wp-blog-header.php');

// make sure that the provided url matches the WordPress domain
$get_url = parse_url( esc_url( $_GET["url"] ) );
$wp_url = parse_url( esc_url( get_bloginfo('url') ) );
if ( $get_url['host'] != $wp_url['host'] ) {
   	echo 'Wrong domain!';
	return; 
}

// get shariff options (fb id, fb secret and ttl)
$shariff3UU_advanced = (array) get_option( 'shariff3UU_advanced' );
	
// if we have a constant for the ttl
if ( defined( 'SHARIFF_BACKEND_TTL' ) ) $ttl = SHARIFF_BACKEND_TTL;
// elseif check for option from the WordPress plugin, must be between 120 and 7200 seconds
elseif ( isset( $shariff3UU_advanced['ttl'] ) ) {
	$ttl = absint( $shariff3UU_advanced['ttl'] );
	// make sure ttl is a reasonable number
	if ( $ttl < '61' ) $ttl = '60';
	elseif ( $ttl > '7200' ) $ttl = '7200';
}
// else set it to default (60 seconds)
else {
	$ttl = '60';
}

$ttl = '1';

// get url
$post_url  = urlencode( esc_url( $_GET["url"] ) );
$post_url2 = esc_url( $_GET["url"] );

// set transient name
$post_hash = hash( "md5", $post_url );

// check if transient exist and is valid
if ( get_transient( $post_hash ) !== false ) {
	// use stored data
	$share_counts = get_transient( $post_hash );
}
// if transient doesn't exit or is outdated, we fetch all counts
else {
	// Facebook
	include ( 'services/facebook.php' );
	// Twitter
	include ( 'services/twitter.php' );
	// Google
	include ( 'services/google.php' );
	// save transient if we have counts
	if ( isset( $share_counts ) && $share_counts != null ) {
		set_transient( $post_hash, $share_counts, $ttl );
	}
}

// draw results, if we have some
if ( isset( $share_counts ) && $share_counts != null ) {
	echo json_encode( $share_counts );
}

?>
