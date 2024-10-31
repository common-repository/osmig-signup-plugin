<?php
if( !defined( 'OSMIG_LOADED' ) ) {
	die( 'Direct access not permitted' );
}

####################################################################
#
# THEME OUTPUT
#
####################################################################
function displaySignups($atts) {
	
	global $wpdb;
	
	$output .= '<table cellpadding="0" cellspacing="0" width="100%" class="osmig-signups">';
	
	$output .= '<thead>';
	$output .= '<tr>';
	$table_name = $wpdb->prefix . "osmig_fields";
	$rows = $wpdb->get_results("SELECT id,name FROM {$table_name} WHERE slug = '{$atts["slug"]}'");
	foreach($rows as $row) {
		$output .= '<th>' . $row->name . '</th>';
	}
	$output .= '</tr>';
	$output .= '</thead>';
	$output .= '';
	
	$output .= '<tbody>';
	
	$table_name = $wpdb->prefix . "osmig_signups";
	$attendees = $wpdb->get_results("SELECT userkey FROM $table_name GROUP BY userkey");
	foreach($attendees as $attendee) {
		$output .= '  <tr>';
		
		$userdata = $wpdb->get_results("SELECT value FROM $table_name WHERE userkey='{$attendee->userkey}' AND replyToFieldID='{$row->id}' ORDER BY replyToFieldID ASC LIMIT 1");
		foreach($userdata as $data) {
			$output .= '    <td>' . ucfirst($data->value) . '</td>';
		}
		$output .= '  </tr>';
	}
	$output .= '</tbody>';
		
	$output .= '</table>';
	
	echo $output;
}
add_shortcode('osmig-signups', 'displaySignups');

function displayForm() {
	global $wpdb;
	
	$output = '';
	
	if( isset( $_POST['_wpnonce'] ) ) {
		if( !wp_verify_nonce( $_POST['_wpnonce'], 'osmig_perform_signup' ) ) {
			echo '<p>' . __("Sorry, something went wrong and we weren't able to process your signup. Please try again.",'') .'</p>';
		} else {
			$table_name = $wpdb->prefix . "osmig_signups";
			
			unset( $_POST['_wp_http_referer'] );
			unset( $_POST['_wpnonce'] );

			foreach ($_POST as $k => $v) {
				if (substr($k, 0, 6) == "osmig_") {
					$new_key = substr($k, 6);
					$_POST[$new_key] = $v;
					unset($_POST[$k]);
				}
			}
			$uuid = $_POST["uuid"];
	
			foreach($_POST as $key => $value) {
				if(is_array($value)) {
					foreach($value as $subkey=>$subvalue) {
						$output .= $subvalue . ",";
						$values = substr($subvalue . ",", 0, -1);
					}
					$wpdb->insert($table_name, array( 'replyToFieldID' => $key , 'value' => $values , 'userkey' => $uuid ));
				} else {
					if($key<>"uuid" && $key<>"submit") {
						$wpdb->insert($table_name, array( 'replyToFieldID' => $key , 'value' => $value , 'userkey' => $uuid ));
					}
				}
			}
			
			$output .= '<h3>' . __('Thank you for signing up.','') . '</h3>';
		}
	}
	
	$table_name = $wpdb->prefix . "osmig_fields";
	$fields = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY ordering ASC");
	
	$output .= "<form class=\"osmig\" method=\"post\" action=\"\">\n";
	$output .= "<input type=\"hidden\" name=\"uuid\" value=\"" . sha1(time().$_SERVER['REMOTE_ADDR']) . "\" />";

	foreach($fields as $field) {
		$output .= "<p>";
		$output .= "<label>" . $field->name . "</label>\n";
	
		switch($field->type) {
		case "text":
  			$output .= "<input type=\"text\" name=\"osmig_" . $field->id . "\" placeholder=\"" . $field->default . "\" />\n";
		break;
		case "textarea":
			$output .= "<textarea name=\"osmig_" . $field->id . "\" rows=\"4\" placeholder=\"" . $field->default . "\"></textarea>\n";
		break;
		case "select":
			$output .= "<select name=\"osmig_" . $field->id . "\">\n";
			$options = explode(",",$field->default);
			foreach($options as $option) {
				$output .= "<option value=\"{$option}\">" . ucfirst($option) . "</option>\n";
			}
			$output .= "</select>\n";
		break;
		case 'checkboxes':
			$options = explode(",",$field->default);
			foreach($options as $option) {
				$output .= "<div class=\"checkbox\"><input type=\"checkbox\" name=\"osmig_" . $field->id . "[]\" value=\"" . $option . "\" />" . ucfirst($option) . "</div>\n";
			}
		break;
		}
		if( strlen( $field->helptext ) > 0 ) {
			$output .= '<small class="help">' . $field->helptext . '</small>';
		}
		$output .= "</p>";
	}
	$output .= "<p><button type=\"submit\">" . __("Sign up","") . "</button></p>\n";
	$output .= wp_nonce_field( 'osmig_perform_signup', '_wpnonce', TRUE, FALSE );
	$output .= "</form>\n";
	
	echo $output;
}
add_shortcode('osmig-form', 'displayForm');