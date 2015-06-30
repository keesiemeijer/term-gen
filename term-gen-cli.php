<?php

/**
 * Generate Terms
 */
class Term_Gen_CLI extends WP_CLI_Command {

	/**
	 * Generate multiple terms (More Options than core)
	 *
	 * ## OPTIONS
	 *
	 * <count>
	 * : Number of terms
	 *
	 * [--taxonomy=<taxonomy>]
	 * : Taxonomies, default 'category'.
	 *
	 * ## EXAMPLES
	 *
	 *     wp term-gen create 10
	 *
	 */
	public function create( $args = array(), $assoc_args = array() ) {
		list( $count ) = $args;

		$notify = \WP_CLI\Utils\make_progress_bar( "Generating $count terms(s)", $count );
		$counter = 1;
		do {
			term_gen_create_term( $assoc_args );
			$notify->tick();
			++$counter;
		} while ( $counter <= $count);

		$notify->finish();

		WP_CLI::success( "Done.");

	}

}

WP_CLI::add_command( 'term-gen', 'Term_Gen_CLI' );