<?php

/**
 * Generate Terms
 */
class Term_Gen_CLI extends WP_CLI_Command {

	private $text;


	/**
	 * Generate some terms.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>
	 * : The taxonomy for the generated terms.
	 *
	 * [--count=<number>]
	 * : How many terms to generate. Default: 100
	 *
	 * [--max_depth=<number>]
	 * : Generate child terms down to a certain depth. Default: 1
	 *
	 * ## EXAMPLES
	 *
	 *     wp term-gen create category --count=50 --max_depth=6
	 */
	public function create( $args, $assoc_args ) {
		global $wpdb;

		list ( $taxonomy ) = $args;

		$defaults = array(
			'count'      => 100,
			'max_depth'  => 1,
		);

		extract( array_merge( $defaults, $assoc_args ), EXTR_SKIP );

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating terms', $count );


		if ( !taxonomy_exists( $taxonomy ) ) {
			WP_CLI::error( sprintf( "'%s' is not a registered taxonomy.", $taxonomy ) );
		}

		$label = get_taxonomy( $taxonomy )->labels->singular_name;
		$slug = sanitize_title_with_dashes( $label );

		$hierarchical = get_taxonomy( $taxonomy )->hierarchical;

		$previous_term_id = 0;
		$current_parent = 0;
		$current_depth = 1;

		$max_id = (int) $wpdb->get_var( "SELECT term_taxonomy_id FROM $wpdb->term_taxonomy ORDER BY term_taxonomy_id DESC LIMIT 1" );

		$suspend_cache_invalidation = wp_suspend_cache_invalidation( true );

		$created     = array();
		$names       = array();
		$this->text  = file_get_contents( plugin_dir_path( __FILE__ ) .'/lorem-terms.txt' );

		for ( $i = $max_id + 1; $i <= $max_id + $count; $i++ ) {

			if ( $hierarchical ) {

				if ( $previous_term_id && $this->maybe_make_child() && $current_depth < $max_depth ) {

					$current_parent = $previous_term_id;
					$current_depth++;

				} else if ( $this->maybe_reset_depth() ) {

					$current_parent = 0;
					$current_depth = 1;

				}
			}

			$name = $this->get_random_term_name();
			$name = $this->get_unique_term_name( $name, $taxonomy, $names );

			$args = array(
				'parent' => $current_parent,
				'slug' => sanitize_title( $name ),
			);

			$term = wp_insert_term( $name, $taxonomy, $args );
			if ( is_wp_error( $term ) ) {
				WP_CLI::warning( $term );
			} else {
				$created[] = $term['term_id'];
				$previous_term_id = $term['term_id'];
				$names[] = $name;
			}

			$notify->tick();
			if ( 0 == $i % 200 ) {
				sleep( 3 );
			}
		}

		wp_suspend_cache_invalidation( $suspend_cache_invalidation );
		clean_term_cache( $created, $taxonomy );

		$notify->finish();

		if ( count( $created ) ) {
			WP_CLI::success( sprintf( "%s terms created.", count( $created ) ) );
		} else {
			WP_CLI::warning( "No terms created," );
		}

	}


	/**
	 * Assign terms to a post type.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>
	 * : The taxonomy to assign to posts.
	 *
	 * [--max-terms=<number>]
	 * : How many terms to assign per post. Default random max terms: 8
	 *
	 * [--posts=<number>]
	 * : How many posts to assign terms to. Default: 100
	 *
	 * [--post-type=<post-type>]
	 * : Post type to assign taxonomy terms to. Default: 'post'
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp term-gen assign post_tag --max-terms=9 --posts=100 --taxonomy=post_tag
	 */
	public function assign( $args, $assoc_args ) {
		global $wpdb;

		list ( $taxonomy ) = $args;

		$defaults = array(
			'max-terms'    => 8, // per post
			'posts'    => 100, // posts to assign terms to
			'post-type' => 'post',
		);

		$args = wp_parse_args( $assoc_args, $defaults );

		$taxonomy   = trim( $taxonomy );
		$post_type  = sanitize_key( $args['post-type'] );
		$post_count = absint( $args['posts'] );
		$term_count = absint( $args['max-terms'] );

		if ( !taxonomy_exists( $taxonomy ) ) {
			WP_CLI::error( sprintf( "'%s' is not a registered taxonomy.", $taxonomy ) );
		}

		if ( !post_type_exists( $post_type ) ) {
			WP_CLI::error( sprintf( "'%s' is not a registered post type.", $post_type ) );
		}

		$taxonomy_names = get_object_taxonomies( $post_type );
		if ( !in_array( $taxonomy, $taxonomy_names ) ) {
			WP_CLI::error( sprintf( "'%s' is not a registered taxonomy for the post type %s.", $taxonomy, $post_type ) );
		}

		if ( !$post_count ) {
			WP_CLI::error( 'Post count is not a number' );
		}

		if ( !$term_count ) {
			WP_CLI::error( 'Term count is not a number' );
		}

		$args = array(
			'posts_per_page' => $post_count,
			'post_type'      => $post_type,
			'orderby'        => 'rand',
			'fields'         => 'ids'
		);

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			WP_CLI::error( sprintf( "No posts found for post type %s.", $post_type ) );
		}

		$notify = \WP_CLI\Utils\make_progress_bar( 'Assigning terms', (int) count( $posts ) );

		$term_args = array(
			'fields'     => 'ids',
			'hide_empty' => 0,
		);

		$assigned_terms   = 0;
		$default_category = get_option( 'default_category' );
		$terms_names      = array();
		$loop_count       = 0;

		foreach ( $posts as $post ) {

			$term_args['number'] = mt_rand( 0 , $term_count );

			if ( !$term_args['number'] ) {
				continue;
			}

			add_filter( 'get_terms_orderby', array( $this, 'get_random_terms' ) );
			$terms = get_terms( $taxonomy, $term_args );
			remove_filter( 'get_terms_orderby', array( $this, 'get_random_terms' ) );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			$terms = array_map( 'intval', $terms );

			$append = true;

			if ( ( 'category' === $taxonomy ) && in_array( $default_category, $terms ) ) {

				if ( mt_rand( 1, 100 ) <= 85 ) {
					$key = array_search( $default_category, $terms ); // $key = 2;
					unset( $terms[ $key ] );
				}
			}

			if ( empty( $terms ) ) {
				continue;
			}

			$assigned_terms += count( $terms );

			wp_set_object_terms( $post, $terms, $taxonomy );
			$notify->tick();

			if ( 0 == $loop_count % 200 ) {
				//WP_CLI::success('sleep ');
				sleep( 3 );
			}

			$loop_count++;
		}


		$notify->finish();
		if ( $assigned_terms ) {
			WP_CLI::success( sprintf( "%s terms assigned.", $assigned_terms ) );
		} else {
			WP_CLI::warning( sprintf( "No terms from %s assigned.", $taxonomy ) );
		}
	}

	function get_random_terms( $clause ) {
		return ' RAND()';
	}

	/**
	 * Returns a unique term name.
	 * Checks to see if a term name exists for the taxonomy.
	 * Adds a number to the term name to make it unique.
	 *
	 * @since 1.0
	 * @param string  $term     Term name.
	 * @param string  $taxonomy Taxonomy name
	 * @return string           Unique term name
	 */
	private function get_unique_term_name( $term, $taxonomy, $names = array() ) {
		global $wpdb;

		$query = "SELECT t.term_id FROM $wpdb->terms AS t
                  INNER JOIN $wpdb->term_taxonomy as tt ON tt.term_id = t.term_id
                  WHERE t.name = %s AND tt.taxonomy = %s ORDER BY t.term_id ASC LIMIT 1";

		if ( !in_array( $term, $names ) ) {

			$result = $wpdb->get_var( $wpdb->prepare( $query, $term, $taxonomy ) );
		} else {
			$result = 1;
		}

		if ( !empty( $result ) ) {
			$suffix = 2;
			do {
				$alt_term_name = $term . "-$suffix";
				$result = $wpdb->get_var( $wpdb->prepare( $query, $alt_term_name, $taxonomy ) );
				$term_name_check = !empty( $result ) ? true : false;
				$suffix++;
			} while ( $term_name_check );
			$term = $alt_term_name;
		}

		return $term;
	}


	private function get_random_term_name() {

		$length      = strlen( $this->text );
		$start       = mt_rand( 0 , $length -16 );
		// Make the chance for very long term names smaller (25%).
		$word_length = mt_rand( 4 , mt_rand( 1, 100 ) <= 75 ? 12: 16 );

		return substr( $this->text , $start, $word_length );
	}


	private function maybe_make_child() {
		// 50% chance of making child term
		return mt_rand( 1, 2 ) == 1;
	}


	private function maybe_reset_depth() {
		// 10% chance of reseting to root depth
		return mt_rand( 1, 10 ) == 7;
	}

}

WP_CLI::add_command( 'term-gen', 'Term_Gen_CLI' );
