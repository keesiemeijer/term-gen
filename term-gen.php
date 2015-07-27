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

if ( defined('WP_CLI') && WP_CLI ) {
	include plugin_dir_path( __FILE__ ) . '/term-gen-cli.php';
}