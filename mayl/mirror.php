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

const MIRROR_ERROR_PERMISSIONS = 0;
const MIRROR_ERROR_INVALID = 1;

// magic needed to run scripts when PHP safe_mode is on
$REROUTE_COMMANDS = False; 

// switched to PHP Flex (slower, but less ridiculous)
if ( $REROUTE_COMMANDS ) {
    /* initialize any magic secret strings / paths, etc
     * that are needed to call external commands.
     * (e.g. routing them through a cgi script that actually does the work)
     */
}

/**
 * Write a placeholder file--will make the "mirror" link not-broken until wget gets
 * around to overwriting it (and also handles the case of broken links).
 */
function write_placeholder_file($mirror_dir, $filename, $host_url) {
    $now = date("F j, Y, g:i a");
    $text = "<html>
<head><title>Loading mirror of $host_url</title></head>
<body>
<h1>$now: Loading...</h1>

<p>...mirror of <a href=\"$host_url\">$host_url</a>.</p>

<p>This page will contain the mirror once mirroring is complete.

<p>If the mirror link was created more than a few minutes ago and you still see this, there was an error mirroring the page.  Check that the page is accessible.  If it is, consult the server administrator and/or look at the error logs.
</body>
</html>
";
    mkdir($mirror_dir);
    $f = fopen($mirror_dir . "/" . $filename, 'w');
    fwrite($f, $text);
    fclose($f);
}

/*
 * Called from the plugin--create the mirror (or actually, call a script that will do the
 * actual work), and return a url to the mirrored page.
 */
function create_mirror( $host_url ) {
    global $log, $options, $REROUTE_COMMANDS;
   
    $log->logDebug("create_mirror($host_url)");

    $wget_path = get_key($options, 'wget_path', '/usr/local/bin/wget');
    $user_agent = get_key($options, 'user_agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:10.0.1) Gecko/20100101 Firefox/10.0.1');

    $log->logDebug("wget='$wget_path', ua='$user_agent'");
    
	// Test if the mirror directory is writeable.
	if (!is_writeable(plugin_dir_path(__FILE__) . 'mirrors'))
		return MIRROR_ERROR_PERMISSIONS;

	// Validate and parse the URL.
    if (!filter_var($host_url, FILTER_VALIDATE_URL))
        return MIRROR_ERROR_INVALID;
    
	$url_path = parse_url($host_url, PHP_URL_PATH);
	if ($url_path === FALSE)
		return MIRROR_ERROR_INVALID;

    // VS: By fiat, we insist that the main mirrored file is called index.html.
    // This is ensured in mirror.py
    // BUG: this will break direct links to images and other non-html content.
	$filename = "index.html";

	$mirror_id = create_db_entry($host_url);

    $plugin_path = plugin_dir_path(__FILE__);

	if ( $REROUTE_COMMANDS ) {
        // On some hosts that use chroot (e.g. nearlyfreespeech.net with PHP Fast),
        // the web server and cgi scripts see different paths to the same files.
        // If this is the case on your system, you'll need to figure out where
        // executed scripts will see the plugin directory
        // 
        // $script_plugin_path = FILL-IN-HERE
        throw new Exception('Not implemented: needs to be customized for your hosting server.');
    } else {
        // Normally, PHP and scripts agree on what the file system looks like
        $script_plugin_path = $plugin_path;
    }

	$mirror_dir = $plugin_path . "mirrors/$mirror_id";
	$mirror_log = $plugin_path . "mirrors/$mirror_id.log";

    // This function is still in php, so it gets the php path
    write_placeholder_file($mirror_dir, $filename, $host_url);

    // be careful about things that might have spaces or other weirdness
    $host_url = escapeshellarg($host_url);
    $ua = escapeshellarg($user_agent);

    // this may need to be adjusted if there is path weirdness
    $mirror_script_path = $plugin_path . "/mirror.py"; 

    $cmd = "$mirror_script_path --ua $ua --wget_path $wget_path $mirror_dir $host_url"; 
    
	$log->logDebug($cmd);
    if ( $REROUTE_COMMANDS ) {
        // Custom magic to actually run cmd.
        throw new Exception('Not implemented: needs to be customized for your hosting server.');
    } else {
        exec($cmd, $output);
    }
    
    $output = print_r($output, TRUE);
    $log->logDebug("Exec output: $output");
	
    return plugins_url("mirrors/$mirror_id/$filename", __FILE__);
}

?>
