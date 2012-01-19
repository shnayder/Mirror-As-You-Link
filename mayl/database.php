<?php

/*
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License as published by 
 * the Free Software Foundation; version 2 of the License.
 * 
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
 * GNU General Public License for more details. 
 * 
 * You should have received a copy of the GNU General Public License 
 * along with this program; if not, write to the Free Software 
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * @author Victor Shnayder (shnayder seas.harvard.edu)
 * @author Dan Margo (dmargo eecs.harvard.edu)
 */

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

global $mayl_db_version;
$mayl_db_version = "1.1";   // increment if changing db schema

const MIRROR_TABLE = 'mayl_mirrors';

function create_db_entry( $host_url ) {
	global $wpdb, $post;

	$wpdb->insert($wpdb->prefix . MIRROR_TABLE, array(
	                'post_id' => $post->ID,
	                'host_url' => $host_url,
	                'mirror_time' => current_time('mysql')));

	return $wpdb->insert_id;
}

function db_install() {
  global $wpdb, $mayl_db_version;

  $installed_ver = get_option( "mayl_db_version" );

  if( $installed_ver != $mayl_db_version ) {
    dbDelta("CREATE TABLE $wpdb->prefix" . MIRROR_TABLE . " (
	           id SERIAL,
	           post_id BIGINT(20) UNSIGNED NOT NULL,
	           host_url LONGTEXT NOT NULL,
	           mirror_time DATETIME NOT NULL,
	           mirror_size INTEGER UNSIGNED,
	           PRIMARY KEY  (id)
	         );");

    update_option("mayl_db_version", $mayl_db_version);
  }
}

// http://codex.wordpress.org/Creating_Tables_with_Plugins
function mayl_update_db_check() {
  global $mayl_db_version;
  if (get_site_option('mayl_db_version') != $mayl_db_version) {
    db_install();
  }
}

function init_database($main_file) {
  register_activation_hook($main_file,'db_install');
  add_action('plugins_loaded', 'mayl_update_db_check');
}

?>
