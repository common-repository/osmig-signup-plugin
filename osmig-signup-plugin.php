<?php
/*
Plugin Name: Osmig Signup Plugin
Plugin URI: https://campground.dk/
Description: A supersimple signup plugin.
Version: 2.0
Author: Thomas Mertz
Author URI: https://campground.dk/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  osmig
Domain Path:  /languages
*/

define( 'OSMIG_DB_VERSION', 1.0 );
define( 'OSMIG_LOADED', TRUE );

require_once( plugin_dir_path( __FILE__ ) . 'includes/assets.inc.php');
require_once( plugin_dir_path( __FILE__ ) . 'includes/widget.inc.php');
require_once( plugin_dir_path( __FILE__ ) . 'includes/shortcodes.inc.php');

####################################################################
#
# INSTALLATION
#
####################################################################
function osmig_install() {
	global $wpdb;

	$table_name = $wpdb->prefix . "osmig_fields";
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
				`id` MEDIUMINT( 11 ) NOT NULL AUTO_INCREMENT ,
				`name` VARCHAR( 200 ) NOT NULL ,
				`slug` VARCHAR( 200 ) NOT NULL ,
				`type` VARCHAR( 10 ) NOT NULL ,
				`helptext` LONGTEXT NOT NULL ,
				`default` LONGTEXT NOT NULL ,
				`ordering` INT( 2 ) NOT NULL ,
				PRIMARY KEY (  `id` ) ,
				INDEX (  `id` )
		);";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}
	
	$table_name = $wpdb->prefix . "osmig_signups";
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
				`id` MEDIUMINT( 11 ) NOT NULL AUTO_INCREMENT ,
				`replyToFieldID` MEDIUMINT( 11 ) NOT NULL ,
				`value` LONGTEXT NOT NULL ,
				`userkey` VARCHAR( 200 ) NOT NULL ,
				PRIMARY KEY (  `id` ) ,
				INDEX (  `id` )
		);";
				
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}
	add_option( "osmig_db_version", OSMIG_DB_VERSION );
}
register_activation_hook(__FILE__,'osmig_install');

####################################################################
#
# UNINSTALLATION
#
####################################################################
function osmig_uninstall() {
	global $wpdb;
	
	$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'osmig_signups;');
	$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'osmig_fields;');

	delete_option("osmig_db_version");
}
register_uninstall_hook(__FILE__,'osmig_uninstall');

####################################################################
#
# ADMINISTRATION OUTPUT
#
####################################################################
add_action('admin_menu', 'osmig_plugin_menu');

function osmig_plugin_menu() {
	add_menu_page('Osmig', 'Osmig', 'publish_posts', 'osmig', 'osmig_signups','',10);
	add_submenu_page('osmig', 'Configuration', 'Configuration', 'publish_posts', 'osmig-configuration', 'osmig_configuration');
	add_submenu_page('osmig', 'Help', 'Help', 'publish_posts', 'osmig-help', 'osmig_help');
}

function osmig_signups() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	global $wpdb;

	echo '<div class="wrap">';
	echo '<h2>Osmig Signups</h2>';
	echo '<p>' . __("This is a list of all the signups you've received so far. The table only shows the first 5 fields in your form.",'') . '</p>';
	
	if( isset( $_GET['_wpnonce'] ) ) {
		if( wp_verify_nonce($_GET['_wpnonce'], 'deleteSignup')) {
			$table_name = $wpdb->prefix . "osmig_signups";
			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE userkey='%s'", $_GET["signup"] ) );
			if($result) {
				echo '<div class="notice notice-success is-dismissible"><p>' . __("The signup was deleted.", '') . '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . __("An error occurred and the signup was not deleted. Please try again.",'') . '</p></div>';
			}
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>' . __("An error occurred and the signup was not deleted. Please try again.",'') . '</p></div>';
		}
	}
	
	$table_name = $wpdb->prefix . "osmig_fields";
	$fieldNames = $wpdb->get_results("SELECT name FROM $table_name ORDER BY ordering ASC LIMIT 5");
	
	$fieldCount = count( $fieldNames );
	
	if( $fieldCount == 0 ) {
		
		echo '<p><strong>' . __("Osmig hasn't been configured yet. You need to do that before you can use the plugin.",'') . '</strong></p>';
		
	} else {
		
		echo '<table class="widefat fixed" cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		foreach($fieldNames as $fieldName) {
			echo '<th scope="col">' . $fieldName->name . '</th>';
		}
		echo '<th width="5%"></th>';
		echo '</tr>';
		echo '</thead>';
		
		echo '<tfoot>';
		echo '<tr>';
		foreach($fieldNames as $fieldName) {
			echo '<th scope="col">' . $fieldName->name . '</th>';
		}
		echo '<th width="5%"></th>';
		echo '</tr>';
		echo '</tfoot>';
		
		echo '<tbody>';
		$table_name = $wpdb->prefix . "osmig_signups";
		$attendees = $wpdb->get_results("SELECT userkey FROM $table_name GROUP BY userkey");
		
		if( count( $attendees ) == 0 ) {
			echo '  <tr>';
			echo '    <td colspan="'.$fieldCount.'">No signups received yet.</td>';
			echo '  </tr>';
		} else {
			foreach($attendees as $attendee) {
				echo '  <tr>';
				$userdata = $wpdb->get_results("SELECT * FROM $table_name WHERE userkey='{$attendee->userkey}' ORDER BY replyToFieldID ASC");
				foreach($userdata as $data) {
					echo '    <td>' . ucfirst($data->value) . '</td>';
				}
				echo '<td width="5%" align="right"><a href="'.wp_nonce_url(admin_url('admin.php?page=osmig&signup='.$attendee->userkey), 'deleteSignup').'"><span class="dashicons dashicons-trash"></span></a></td>';
				echo '  </tr>';
			}
		}
		
		echo '</tbody>';
		echo '</table>';
	}
	
	echo '</div>';
}

function osmig_configuration() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	global $wpdb;

	echo '<div class="wrap">';
	echo '<h2>Osmig Configuration</h2>';
	if( isset( $_POST['_wpnonce'] ) ) {
		if( !wp_verify_nonce( $_POST['_wpnonce'], 'osmig-create-form-field' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>'.__("An error occurred and the field was not created. Please try again.",'') . '</p></div>';
		} else {
			function generateSlug($phrase, $maxLength) {
				$result = strtolower($phrase);
				$result = preg_replace("/[^a-z0-9\s-]/", "", $result);
				$result = trim(preg_replace("/[\s-]+/", " ", $result));
				$result = trim(substr($result, 0, $maxLength));
				$result = preg_replace("/\s/", "-", $result);
				return $result;
			}
			$input["name"] = $_POST["name"];
			$input["slug"] = generateSlug($_POST["name"],50);
			$input["type"] = $_POST["type"];
			$input["default"] = $_POST["default"];
			$input["helptext"] = $_POST["helptext"];
			$input["ordering"] = $_POST["ordering"];
			
			$table_name = $wpdb->prefix . "osmig_fields";
			$result = $wpdb->insert( $table_name, $input );
			if($result) {
				echo '<div class="notice notice-success is-dismissible"><p>'.__("Field has been added.",'') . '</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>'.__("Field was not added. Please try again.",'').'</p></div>';
			}
		}
	}
	
	if( isset( $_GET['_wpnonce'] ) ) {
		if( wp_verify_nonce($_GET['_wpnonce'], 'deleteField')) {
			$table_name = $wpdb->prefix . "osmig_fields";
			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE id='%d'", $_GET["fieldID"] ) );
			if($result) {
				$table_name = $wpdb->prefix . "osmig_signups";
				$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE replyToFieldID='%d'", $_GET["fieldID"] ) );
				if($result) {
					echo '<div class="notice notice-success is-dismissible"><p>'.__("Your field was successfully removed, along with any data associated with it.",'') . '</p></div>';
				} else {
					echo '<div class="notice notice-warning is-dismissible"><p>'.__("Your field was successfully removed. However we were unable to remove data associated with the field.",'') . '</p></div>';
				}
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>'.__("An error occurred and the field was not deleted. Please try again.",'') . '</p></div>';
			}
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>'.__("An error occurred and the field was not deleted. Please try again.",'') . '</p></div>';
		}
	}
	
	echo '<div id="col-container">';
	echo '<div id="col-right">';
	echo '<table class="widefat fixed" cellspacing="0">';
	echo '<thead>';
	echo '<tr>';
	echo '<th scope="col">'.__("Name",'').'</th>';
	echo '<th scope="col">'.__("Type",'').'</th>';
	echo '<th scope="col">'.__("Default",'').'</th>';
	echo '<th scope="col">'.__("Help text",'').'</th>';
	echo '<th scope="col">'.__("Order",'').'</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tfoot>';
	echo '<tr>';
	echo '<th scope="col">'.__("Name",'').'</th>';
	echo '<th scope="col">'.__("Type",'').'</th>';
	echo '<th scope="col">'.__("Default",'').'</th>';
	echo '<th scope="col">'.__("Help text",'').'</th>';
	echo '<th scope="col">'.__("Order",'').'</th>';
	echo '</tr>';
	echo '</tfoot>';
	
	echo '<tbody>';
	$table_name = $wpdb->prefix . "osmig_fields";
	$rows = $wpdb->get_results("SELECT * FROM $table_name");
	if( count( $rows ) < 1 ) {
		echo '  <tr>';
		echo '    <td colspan="5">'. __("No fields have been added yet.",'') . '</td>';
		echo '  </tr>';
	} else {
		foreach($rows as $row) {
			echo '  <tr>';
			echo '    <td>' . $row->name . '<br /><a href="'.wp_nonce_url(admin_url('admin.php?page=osmig-configuration&fieldID=' . $row->id), 'deleteField').'">'.__("Delete",'').'</a></td>';
			echo '    <td>' . $row->type . '</td>';
			echo '    <td>' . $row->default . '</td>';
			echo '    <td>' . $row->helptext . '</td>';
			echo '    <td>' . $row->ordering . '</td>';
			echo '  </tr>';
		}
	}
	echo '</tbody>';
	
	echo '</table>';
	echo '<p>' . __("Deleting a form field will also destroy any data saved to that form field. It is <strong>NOT</strong> possible to undo this action.", ''). '</p>';
	echo '</div>';
	echo '<div id="col-left">';
	echo '<div class="col-wrap"><div class="form-wrap">';
	echo '<h3>' . __("Add Field", ''). '</h3>';
	echo '<form method="post" action="'. $_SERVER["REQUEST_URI"] .'">';
	echo '<div class="form-field form-required">';
	echo '<label for="name">' . __("Name", ''). '</label>';
	echo '<input name="name" id="link-name" type="text" value="" size="40" aria-required="true">';
	echo '</div>';
	echo '<div class="form-field form-required">';
	echo '<label for="type">' . __("Type", ''). '</label>';
	echo '<select name="type"><option value="text">' . __("Text field", ''). '</option><option value="email">' . __("E-mail field", ''). '</option><option value="select">' . __("Select dropdown", ''). '</option><option value="textarea">' . __("Textarea", '') . '</option><option value="checkboxes">' . __("Checkboxes", '') .'</option></select>';
	echo '</div>';
	echo '<div class="form-field form-required">';
	echo '<label for="default">' . __("Default",'') . '</label>';
	echo '<textarea name="default" rows="4"></textarea>';
	echo '<p>' . __("This is the values that will appear as default in the form field you\'re creating. For select fields and multiple checkboxes you must separate the different options with commas.",'') . '</p>';
	echo '</div>';
	echo '<div class="form-field form-required">';
	echo '<label for="helptext">' . __("Help text", '') . '</label>';
	echo '<textarea name="helptext" rows="4"></textarea>';
	echo '<p>' . __("Write any helpful tips on how to fill out this field here.",'' ) . '</p>';
	echo '</div>';
	echo '<div class="form-field form-required">';
	echo '<label for="ordering">' . __("Ordering", '') . '</label>';
	echo '<input type="text" name="ordering" />';
	echo '<p>' . __("This field is optional. In a number from 1 to 99 place this field in the order you want it in the form. 1 is first, 99 is last. If you do not give your fields an order they will be output in the order you create them.",'') . '</p>';
	echo '</div>';
	echo '<p class="submit"><input type="submit" class="button" name="submit" value="' . __("Add Field",'') . '"></p>';
	echo '<input type="hidden" name="submit" value="yes" />';
	wp_nonce_field( 'osmig-create-form-field' );
	echo '</form>';
	echo '</div></div></div>';
	echo '</div>';
	echo '</div>';

}

function osmig_help() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	echo '<div class="wrap">';
	echo '<h2>' . __("Osmig Help", ''). '</h2>';
	echo '<p>' . __("Osmig has two distinct shortcodes you can use to include the plugin in the frontend of your site, [osmig-form] and [osmig-signups].", ''). '</p>';
	echo '<h3>[osmig-form] shortcode</h3>';
	echo '<p>' . __("This shortcode outputs the form you build in the Configuration page.</p><p><strong>Example:</strong> [osmig-form]", ''). '</p>';
	echo '<hr />';
	echo '<h3>[osmig-signups] shortcode</h3>';
	echo '<p>' . __("This shortcode outputs the list of signups received. It requires one variable, namely the slug of the field you'd like to list, ie. 'name'.</p><p><strong>Example:</strong> [osmig-signups slug=\"name\"]", ''). '</p>';
	echo '</div>';

}