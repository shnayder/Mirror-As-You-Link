<?php
/**
 * @package Mayl
 */
/*
Plugin Name: Mirror As You Link
Plugin URI: http://mirrorasyoulink.org
Description: Creates a [cite] shortcode that mirrors web pages that you link to, so their content is not lost.
Version: 1.1
Author: Dan Margo, Victor Shnayder
Author URI: http://www.eecs.harvard.edu/~shnayder/
License: GPL2
*/

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
 *
 */

require_once plugin_dir_path(__FILE__) . 'options.php';
require_once plugin_dir_path(__FILE__) . 'database.php';
require_once plugin_dir_path(__FILE__) . 'mirror.php';
require_once plugin_dir_path(__FILE__) . 'settings.php';

// These use the MIT license:
require_once plugin_dir_path(__FILE__) . 'KLogger.php';
require_once plugin_dir_path(__FILE__) . 'simple_html_dom.php';

$logdir = plugin_dir_path(__FILE__) . 'mirrors/';
global $log;
$log = KLogger::instance("$logdir", KLogger::DEBUG);

init_database(__FILE__);
init_settings(__FILE__);

global $options;
$mirrored_css_class = get_key($options, 'href_css_class', 'mirrored');
$mirror_css_class = get_key($options, 'mirror_css_class', 'mirror');
$mayl_icon = '<img src="' . plugins_url('mayl.png', __FILE__) . '"/>';

/**
 * Actually handle the cite (aka mirror) shortcode
 */
function cite_shortcode_func( $atts, $content = NULL ) {
    global $log, $mirrored_css_class, $mirror_css_class, $mayl_icon;

    $log->logDebug("content='$content', atts='" . var_export($atts, true) . "'");

	// Parse and handle input attributes.
	extract( shortcode_atts( array(
		'href' => NULL,
		'title' => ''
	), $atts ) );

	// If only one of $content or $href is NULL, fill in with the other.
	if ($content === NULL and $href === NULL)
		return '<span class="error">cite error: Both content and href are NULL.</span>';
	else if ($content === NULL)
		$content = $href;
	else if ($href === NULL)
		$href = $content;

	// Does nothing atm, because the shortcode parser fails in this case anyway.
	$href = trim($href, '\'"');

	$ret = "<a href=\"$href\" class=\"$mirrored_css_class\" title=\"$title\">$content</a>";

	// Create the mirror. If it fails, print an appropriate error.
	$mirror_url = create_mirror($href);

	if ($mirror_url === MIRROR_ERROR_PERMISSIONS)
		$ret .= ' <span class="error">cite error: Apache does not have write permissions on mirror directory.</span>';
	else if ($mirror_url === MIRROR_ERROR_INVALID)
		$ret .= " <span class=\"error\">cite error: Invalid URL '$href'</span>";
	else
		$ret .= " <a href=\"$mirror_url\" class=\"$mirror_css_class\" title=\"Mirror of $href\">$mayl_icon</a>";

	return $ret;
}

/**
 * Run the shortcode parser just for [mirror/cite]
 */
function do_mayl_shortcode( $content ) {

	global $shortcode_tags, $log;

    static $cnt;
    // trying to figure out why things are being called multiple times
    //$log->logDebug("bt: " . var_export(debug_backtrace(), true));
    if ($cnt > 0)
        $log->logDebug("count = $cnt is  > 0!  filter running multiple times");
    $cnt += 1;
        
	$tmp = $shortcode_tags;
	$shortcode_tags = array('cite' => 'cite_shortcode_func',
                            'mirror' => 'cite_shortcode_func');

	$content = do_shortcode($content);
    // if using wp_insert_post_data hook
    //$data['post_content'] = do_shortcode($data['post_content']);

	$shortcode_tags = $tmp;

//    return $data;        // if using wp_insert_post_data hook
	return $content;
}

/**
 * NOTE: ALPHA code.
 *
 * find all links that aren't already mirrored / mirrors (using css class),
 * and mirror them.
 */
function mirror_every_link( $content ) {
	global $log, $mirrored_css_class, $mirror_css_class, $mayl_icon;

	$log->logDebug('Attempting to mirror every link...');

	$html = str_get_html($content);
	if ($html === FALSE)
		return $content;

	foreach($html->find("a[href]") as $element) {
		// Parser has a bad tendency to mangle these, so needs some trim magic.
		if (isset($element->class)) {
			$class = trim($element->class, '"\\');
			if ($class === $mirrored_css_class or $class === $mirror_css_class)
				continue;
		}

		$href = trim($element->href, '\'"\\');
		if (!filter_var($href, FILTER_VALIDATE_URL, FILTER_FLAGS_HOST_REQUIRED))
			continue;

		// TODO: check if link is local.
		$log->logDebug("Mirroring $href");

		$mirror_url = create_mirror($href);

		if ($mirror_url === MIRROR_ERROR_PERMISSIONS)
			$element->outertext .= ' <span class="error">cite error: Apache does not have write permissions on mirror directory.</span>';
		else if ($mirror_url === MIRROR_ERROR_INVALID)
			$element->outertext .= " <span class=\"error\">cite error: Invalid URL $href</span>";
		else {
			$element->class = "\"$mirrored_css_class\"";
			$element->outertext .= " <a href=\"$mirror_url\" class=\"$mirror_css_class\" title=\"Mirror of $href\">$mayl_icon</a>";
		}
	}

	return $html->save();
}

$hook = 'content_save_pre';
//$hook = 'wp_insert_post_data';
add_filter($hook, 'do_mayl_shortcode', 11);

$automatic_test = get_key($options, 'mirror_all', '');
if ($automatic_test) {
	add_filter($hook, 'mirror_every_link', 11);
}

?>
