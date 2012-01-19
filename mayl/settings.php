<?php

/**
 * settings.php : settings page for mirror-as-you-link plugin.
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

// useful reference:
// http://www.presscoders.com/2010/05/wordpress-settings-api-explained/

// if development mode, delete options when deactivating plugin
$DEVELOPMENT_MODE = 1;
    
function mayl_admin_add_page() {
    add_options_page('Mirror As You Link', 'Mirror As You Link',
                     'manage_options', 'mayl', 'mayl_options_page');
}

// display the admin options page
function mayl_options_page() {
?>
<div>
<h2>Mirror As You Link configurations</h2>
Configure Mirror As You Link here.
<form action="options.php" method="post">
<?php settings_fields('mayl_options'); ?>
<?php do_settings_sections('mayl'); ?>

<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
</form></div>

<?php
}

function mayl_section_text() {
    echo '<p>Configure things here.  Note that the mirror-all-links mode is experimental.</p>';
}

function mayl_textbox_fn($opt) {
    $options = get_option('mayl_options');
    echo "<input id='$opt' name='mayl_options[$opt]' size='20' type='text' value='{$options[$opt]}' />";
}

function mayl_checkbox_fn($opt) {
	$options = get_option('mayl_options');
	if( array_key_exists($opt, $options) && $options[$opt] ) {
        $checked = ' checked="checked" ';
    } else {
        $checked = '';
    }
	echo "<input ".$checked." id='$opt' name='mayl_options[$opt]' type='checkbox' />";
}

/**
 * TODO: validate options
 */
function mayl_options_validate($input) {
// Example validation:
//    $newinput['text_string'] = trim($input['text_string']);
//    if(!preg_match('/^[a-z0-9]{32}$/i', $newinput['text_string'])) {
//        $newinput['text_string'] = '';
//    }
//  return $newinput;
    return $input;
}

function mayl_activate_plugin() {
    // Set defaults the first time the plugin is loaded
	$tmp = get_option('mayl_options');
    if(!is_array($tmp)) {
		$arr = array("wget_path"=>"/usr/local/bin/wget",
                     "user_agent" => "Mozilla/5.0",
                     'href_css_class' => 'mirrored',
                     'mirror_css_class' => 'mirror',
                     'mirror_all' => '',
            );
		update_option('mayl_options', $arr);
	}
}

function mayl_uninstall_plugin() {
    delete_option('mayl_options');
}

function mayl_deactivate_plugin() {
    global $DEVELOPMENT_MODE, $log;
    $log->logDebug("mayl_deactivate_plugin() running");
    if ($DEVELOPMENT_MODE)
        delete_option('mayl_options');
}


function mayl_admin_init() {
    register_setting( 'mayl_options', 'mayl_options', 'mayl_options_validate' );
    add_settings_section('mayl_main', '', 'mayl_section_text', 'mayl');
    // textboxes
    add_settings_field('href_css_class', 'CSS class for cited links',
                       'mayl_textbox_fn', 'mayl', 'mayl_main', 'href_css_class');
    add_settings_field('mirror_css_class', 'CSS class for links to mirrors',
                       'mayl_textbox_fn', 'mayl', 'mayl_main', 'mirror_css_class');
    add_settings_field('wget_path', 'Path to wget on the server',
                       'mayl_textbox_fn', 'mayl', 'mayl_main', 'wget_path');
    add_settings_field('user_agent', 'User agent to use when mirroring',
                       'mayl_textbox_fn', 'mayl', 'mayl_main', 'user_agent');
    // checkboxes
    add_settings_field('mirror_all', '[BETA] turn on mirroring of all links in post',
                       'mayl_checkbox_fn', 'mayl', 'mayl_main', 'mirror_all');
}


function init_settings($main_file) {
    // need to pass path to main plugin file to register_* functions.
    // http://wordpress.org/support/topic/register_activation_hook-register_deactivation_hook
    register_activation_hook($main_file, 'mayl_activate_plugin');
    register_deactivation_hook($main_file, 'mayl_deactivate_plugin');
    register_uninstall_hook($main_file, 'mayl_uninstall_plugin');
    add_action('admin_init', 'mayl_admin_init');
    add_action('admin_menu', 'mayl_admin_add_page');
}

?>