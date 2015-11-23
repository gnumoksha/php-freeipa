<?php

/**
 * Print a message
 */
function _print( $string = NULL ) {
	if ( 'cli' == php_sapi_name() ) { // PHP is running in terminal
		$line_end = PHP_EOL;
	} else {
		$line_end = '<br/>';
	}

	if ( ! empty( $string ) ) {
		$file   = basename( $_SERVER["PHP_SELF"] );
		$output = date( 'd-m-Y_H:i:s ' ) . "[$file] $string $line_end";
	} else {
		$output = $line_end;
	}

	print ( $output );
}
