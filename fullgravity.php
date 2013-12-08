<?php

/*
  Plugin Name: Full Gravity
  Plugin URI: http://fullgravity.tk
  Description: Adds Full Contact Data To Gravity Forms Entry Detail Page
  Author: Taylor Hicks
  Version: 1.0
  Author URI: http://newsite.taylormhicks.com
 */

class tmh_full_gravity {

    protected $full_contact_api_key = null;
    protected $api_endpoint = 'http://api.fullcontact.com/v2/person.html';

    public function __construct() {
	add_action('gform_entry_detail_content_before', array(&$this, 'tmh_fg_add_to_details'), 10, 2);
	add_action('admin_head', array(&$this, 'tmh_fg_admin_head'));
	add_action('admin_menu', array(&$this, 'tmh_fg_add_options_page'), 10, 0);
	add_action('admin_init', array(&$this, 'tmh_fg_register_settings'));
	//add_filter("gform_addon_navigation", array(&$this,"tmh_add_menu_item"));
	$this->full_contact_api_key = get_option('fc_api_key');
    }

    public function tmh_fg_add_to_details($form, $lead) {
	if ($this->full_contact_api_key) {
	    $email_field = $this->tmh_fg_find_email_field($form);
	    if ($email_field) {
		$email = $lead[$email_field];
		$url = $this->api_endpoint . '?email=' . $email . '&apiKey=' . $this->full_contact_api_key;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($ch);
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		switch ($response_code) {
		    case 200:
			$start = strpos($result, '<div class="contact-card">');
			$stop = strpos($result, '<!--end .contact-card-->');
			$length = $stop - $start;
			$contact_card = substr($result, $start, $length);

			if (strlen($contact_card) > 0) {
			    $contact_card .= "<div id='fullcontact-badge' data-color='dark'></div><div class='clear'></div>";
			    $this->tmh_fg_display_box($contact_card);
//			echo "</div></div>";
//			echo "<style scoped> .entry-detail-view {display:none !important;} </style>";
//			return;
			}
			else
			    $this->tmh_fg_display_box("<p>Sorry, we couldn't find anything associated with this email.</p>");;
			break;
		    case 202:
			$this->tmh_fg_display_box('<p>Full Contact is gathering information on this contact, please check back later to see what they found.</p>');
			break;
		    case 403:
			$this->tmh_fg_display_box("<p>Your API key is invalid, missing, or has exceeded its quota</p>");
			break;
		    case 404:
			$this->tmh_fg_display_box("<p>This person was searched in the past 24 hours and nothing was found.</p>");
			break;
		    default:
			$this->tmh_fg_display_box("<p>Uh ohh, the request failed.</p>");
			break;
		}
	    }
	    else
		$this->tmh_fg_display_box("<p>Sorry, we couldn't find an email address in this form</p>");
	}
	else
	    $this->tmh_fg_display_box("<p>Please enter a Full Contact API key on the settings page.</p>");
    }

    public function tmh_fg_options_page() {
	echo "<div class='wrap'>";
	screen_icon();
	echo "<h2>Full Gravity Options</h2>";
	echo "<p>If you don't have a Full Contact API key get one for free <a href='https://www.fullcontact.com/developer/pricing/' target='_blank'>here</a>.";
	echo "<form method='post' action='options.php'>";
	settings_fields('fg-options');
	do_settings_sections('fg-options');
	echo "<label for='fc_api_key'>Full Contact API Key</label><input id='fc_api_key' type='text' name='fc_api_key' value='" . get_option('fc_api_key', '') . "'/>";
	submit_button();
	echo "</form></div>";
    }

    public function tmh_fg_add_options_page() {
	add_options_page('Full Gravity Options', 'Full Gravity', 'manage_options', 'fg-options', array(&$this, 'tmh_fg_options_page'));
    }

    public function tmh_fg_add_menu_item($menu_items) {
	$menu_items[] = array("name" => "Full Gravity Options", "label" => "Full Gravity", "callback" => array(&$this, 'tmh_fg_options_page'), "permission" => "manage_options");
	return $menu_items;
    }

    public function tmh_fg_register_settings() {
	register_setting('fg-options', 'fc_api_key');
    }

    public function tmh_fg_admin_head() {
	?>
	<script src="https://s3.amazonaws.com/fullcontact-static/js/badges/badges.js"></script>
	<style>
	    /* @group Button */  

	    .btn {
		background-color: rgb(0,158,187);
		border: 1px solid rgb(0,138,167);
		border-radius: 6px;
		box-shadow: inset 0 1px 0 0 rgba(255,255,255,.4);
		color: rgb(255,255,255);
		display: inline-block;
		font-size: 1.2em;	
		font-weight: bold;
		padding: .5em 1em;
		text-decoration: none;
		text-shadow: 0 -1px 0 rgba(0,0,0,.4);
	    }

	    .btn:hover {
		background-color: rgb(0,178,207);
		color: rgb(255,255,255);
	    }

	    .btn:active {
		background-color: rgb(0,128,147);
		box-shadow: inset 0 0 6px 1px rgba(0,0,0,.2);
	    }

	    /* @end */

	    /* @group Layout */

	    .contact-card, 
	    .provided-by {
		margin: auto;
	    }

	    .contact-card {
		background-color: rgb(255,255,255);
		border: 1px solid rgb(200,200,200);
		border-radius: 24px;
		box-shadow: 0 1px 0 0 rgb(190,190,190), 0 2px 5px 0 rgba(0,0,0,.5), 0 8px 10px 0 rgba(0,0,0,.2);
		color: rgb(120,120,120);
		margin: 20px;
		position: relative;
	    }

	    .box {
		display: table-cell;
		padding: 20px;
		vertical-align: top;
	    }

	    .box.contact-pic {
		background-color: rgb(230,230,230);
		border-radius: 24px 0 0 24px;
	    }

	    .column {
		display: table-cell;
		padding: 0 20px;
	    }

	    .column:nth-child(2) {
		width: 30%;
	    }

	    .contact-pic > img {	
		border-radius: 6px;
		height: 100px;
		width: 100px;
	    }

	    .identity,
	    .demographics,
	    .social,
	    .other-orgs,
	    .klout,
	    .klout-topics {
		margin-bottom: 20px;
	    }

	    .provided-by {
		color: rgb(140,140,140);
		padding: 20px;
		text-align: center;
	    }

	    .provided-by h6 {
		font-weight: normal;
		padding-bottom: .5em;
	    }

	    /* @end */

	    /* @group identity */

	    .identity-fullname {
		font-size: 1.6em;
		font-weight: bold;
	    }

	    .identity-title {
		font-size: 1.2em;
		font-style: italic;
		font-weight: normal;
	    }

	    .identity-org {
		font-size: 1.2em;
		font-weight: bold;
	    }

	    /* @end */

	    /* @group Social */

	    .social > li {
		display: inline-block;
	    }

	    /* @end */

	    /* @group Klout */

	    .klout-topics .topic {
		background-color: rgb(230,230,230);
		border-radius: 100px;
		display: inline-block;
		font-size: .825em;
		margin-right: 10px;
		margin-top: 10px;
		padding: 5px 10px;
	    }

	    .klout-scores {
		display: table;
	    }

	    .klout-scores > li {
		display: table-row;
	    }

	    .klout-scores span {
		display: table-cell;
		padding: 3px 0;
	    }

	    .klout-scores .score {
		font-weight: bold;
		padding-left: 20px;
	    }

	    /* @end */

	    /* @group Other Orgs */  

	    .org {
		list-style: disc;
		margin-left: 25px;
		padding: 5px 0 5px 5px;
	    }

	    /* @end */

	    @media screen and (max-width: 500px) {
		body {
		    background-color: white;
		    font-size: 14px;
		}

		.box {
		    display: block;
		    padding: 0;
		}

		.box.contact-pic {
		    background: none;
		    padding: 0;
		}

		.column {
		    display: block;
		    padding: 0;
		}

		.column:nth-child(2) {
		    width: auto;
		}

		.contact-card {
		    background: none;
		    border: none;
		    border-bottom: 1px solid rgb(200,200,200);
		    border-radius: 0;
		    box-shadow: none;
		}

		.social img {
		    width: 16px;
		}

		.provided-by h6 {
		    font-size: 10px;
		    font-weight: normal;
		}

		.provided-by img {
		    width: 120px;
		}
	    }

	    @media screen and (max-width: 800px) {
		.column {
		    display: block;
		    padding: 0;
		}

		.column:nth-child(2) {
		    width: auto;
		}
	    }

	    /* @group Error */  

	    .status-msg {
		padding: 20px;
	    }

	    .api-error {
		border: 1px solid rgb(200,200,200);
		margin: auto;
		margin-bottom: 40px;
		margin-top: 40px;
		max-width: 900px;
		min-width: 200px;
		padding: 20px;
	    }

	    .api-error > h1 {
		border-bottom: 1px solid rgb(215,215,215);
		margin-bottom: 20px;
		font-weight: normal;
		padding-bottom: 10px;
	    }

	    .api-error > h1 > strong {
		color: rgb(196,22,28);
		font-size: 1.6em;
	    }

	    .api-error > h2 {
		margin-bottom: 0;
		padding-bottom: 0;
	    }
	    /* @end */

	    #fullcontact-badge
	    {
		float: right !important;
		/*clear: both;*/
	    }

	    .clear
	    {
		clear: both;
	    }
	</style>
	<?php

    }

    private function tmh_fg_find_email_field($form) {
	$email_field = false;
	for ($x = 0; $x < count($form['fields']); $x++) {
	    if ($form['fields'][$x]['type'] == 'email') {
		$email_field = $form['fields'][$x]['id'];
		break;
	    }
	}
	return $email_field;
    }

    private function tmh_fg_display_box($content = "", $return = FALSE) {
	$box = "<div class='stuffbox'><h3>Full Gravity</h3><div class='inside'>" . $content . "</div></div>";
	if ($return === FALSE)
	    echo $box;
	else
	    return $box;
    }

}

$gforms_fullcontact = new tmh_full_gravity();