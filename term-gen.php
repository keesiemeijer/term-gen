<?php
/*
 * Plugin Name: Term Gen
 * Plugin URI: https://github.com/keesiemeijer/term-gen
 * Description: Term generator.
 * Version:
 * Author: Kees Meijer
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * TextDomain: term-gen
 * DomainPath:
 * Network:
 *
 * Term gen is based on the excellent Post Generator by trepmal
 * https://github.com/trepmal/post-gen 
 */


/**
 * Generate one term
 *
 * @return void
 */
function term_gen_create_term( $args = array() ) {

	$defaults = array(
		'taxonomy'   => 'category'
	);

	$args = wp_parse_args( $args, $defaults );

	$taxonomies = explode( ',', $args['taxonomy'] );
	$taxonomies = array_map( 'trim', (array) $taxonomies );
	$taxonomies = array_filter( $taxonomies, 'taxonomy_exists' );

	// todo: create hierarchical terms

	if($taxonomies) {
		foreach($taxonomies as $taxonomy) { 
			$term = term_gen_get_random_term();
			$term = wp_insert_term( $term, $taxonomy );
		}
	}
}


/**
 * Generate multiple terms
 *
 * @param int $count Number terms to generate
 * @return void
 */
function term_gen_create_terms( $count = 1 ) {
	$counter = 1;
	do {
		term_gen_create_term();
		++$counter;
	} while ( $counter <= $count );
}


/**
 * Get random term name
 *
 * @return string Term name between 4 and 16 characters.
 */
function term_gen_get_random_term() {
	$text        = file_get_contents( plugin_dir_path(__FILE__) .'/lorem-terms.txt' );
	$length      = strlen ( $text );
	$start       = mt_rand ( 0 , $length -16 );
	// Make the chance for very long term names smaller (25%).
	$word_length = mt_rand ( 4 , mt_rand( 1, 100 ) <= 75 ? 12: 16 );

	return substr ( $text , $start, $word_length);
}


if ( defined('WP_CLI') && WP_CLI ) {
	include plugin_dir_path( __FILE__ ) . '/term-gen-cli.php';
}