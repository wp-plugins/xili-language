<?php
/*
Plugin Name: xili-xl-bbp-addon
Plugin URI: http://dev.xiligroup.com
Description: add multilingual functions and features to bbPress (Localized forums). Delivered in xili-language package. If option activated, bbPress default theme will use bbpress.css in your theme directory.
Version: 2.8.1
Author: MS
Author URI: http://dev.xiligroup.com
*/
/*

Changelog:
2.8.1: - 20120915 - Initial release as class

*/

define('XILIXLBBPADDON_VER','2.8.1');

class xili_xl_bbp_addon {
	
	var $plugin_name = 'xili-xl-bbp-addon'; // filename and folder
	var $plugin_folder = 'xili-language';
	var $display_plugin_name = '©xili xl-bbPress add-on'; // menu and top
	var $settings_name = 'xili-xl-bbp-addon_settings'; // The settings string name for this plugin in options table
	var $xili_settings = array(); 
	var $xili_settings_ver = '1.0';
	var $plugin_local = "'xili_xl_bbp_addon'"; // text domain
	var $settings_list = "xili_xl_bbp_addon_list"; // used by settings sections and fields in settings page
	var $url = '';
	var $urlpath = ''; // The path to this plugin - see construct
	
	var $debug ; //WP_DEBUG

	//Class Functions
	/**
	 * PHP 4 Compatible Constructor
	 */
	function xili_xl_bbp_addon(){ $this->__construct(); }

	/**
	 * PHP 5 Constructor
	 */		
	function __construct(){
		
		$this->debug = ( defined ('WP_DEBUG') ) ? WP_DEBUG : false ;
		
		load_plugin_textdomain( 'xili_xl_bbp_addon', false, $this->plugin_folder.'/languages' );

		register_activation_hook( __FILE__, array(&$this,'get_xili_settings') ); // first activation
		
		$this->url = plugins_url(basename(__FILE__), __FILE__);
		$this->urlpath = plugins_url('', __FILE__);	
		
		//Initialize the options
		$this->get_xili_settings();
		//Admin menu
		
		add_action( 'admin_menu', array(&$this, 'admin_menu_link') );
		add_action( 'admin_init', array(&$this, 'admin_init') );
		
		if ( is_admin() ) {
			add_filter ( 'xiliml_manage_column_name', array(&$this,'xiliml_manage_column_name'), 10, 3);
			add_filter ( 'xiliml_language_translated_in_column', array(&$this,'xiliml_language_translated_in_column'), 10, 3);
		}
		//Actions both side
		
		add_action( 'init', array(&$this,'plugin_init') );
		add_action( 'bbp_enqueue_scripts', array(&$this,'bbp_custom_css_enqueue') );
		
		
		// front-end side
		
		add_action( 'xiliml_add_frontend_mofiles', array(&$this,'xiliml_add_frontend_mofiles'), 10 ,2);
		if ( ! is_admin() ) { 
			add_action( 'save_post', array(&$this,'bbp_save_topic_or_reply'), 10, 2 );
			add_action( 'parse_query', array(&$this,'bbpress_parse_query') ); // fixe issues in bbp 2.1
		}
	}

	function plugin_init() {
		$this->get_xili_settings();
	}

	
	/**
	 * Retrieves the plugin options from the database.
	 * @return array
	 */
	function get_xili_settings() {
		if (!$xili_settings = get_option( $this->settings_name )) {
			$xili_settings = array(
			 	'css-theme-folder' => false,
				'version'=> $this->xili_settings_ver  // see on top class
			);
			update_option( $this->settings_name, $xili_settings);
		}
		$this->xili_settings = $xili_settings;
	}
	
	/** change default style - inspired from Jared Atchison **/
	function bbp_custom_css_enqueue(){
		if ( isset( $this->xili_settings['css-theme-folder'] ) && $this->xili_settings['css-theme-folder']  ){
			// Unregister default bbPress CSS
			wp_deregister_style( 'bbp-default-bbpress' );
	
			// Register new CSS file in our active theme directory
			wp_enqueue_style( 'bbp-default-bbpress', get_stylesheet_directory_uri() . '/bbpress.css' );
		}
	}

	
	/** change bbp mo file **/
	function xiliml_add_frontend_mofiles ( $theme_domain, $cur_iso_lang ) { // only called in front-end
	
		unload_textdomain( 'bbpress' );
	
		//error_log ( $theme_domain . '--------------' . $cur_iso_lang );
		load_textdomain( 'bbpress', WP_LANG_DIR . '/bbpress/bbpress-'.$cur_iso_lang.'.mo' );
	}
	
	

	function bbp_save_topic_or_reply ( $post_ID, $post ) {
		global $xili_language;
	//test if topic or reply
		if ( in_array ( $post->post_type, array( bbp_get_topic_post_type(), bbp_get_reply_post_type() ) ) ) {
	// get language of parent forum
			$parent_lang =  $xili_language->get_post_language ( $post->post_parent ) ;
	// set taxonomy to language
			if ( $parent_lang != '') {
				wp_set_object_terms( $post_ID, $parent_lang, TAXONAME );
			}
		}
	}
	
	
	/** 
		* fixe issue in bbPress 2.1
	 */
	function bbpress_parse_query ( $wp_query ) {
		$bbp = bbpress() ;
		if ( isset ( $wp_query->query_vars['post_type' ] ) && version_compare( $bbp->version, '2.2', '<') ) { 
	// announced to be fixed in bbp 2.2 - tracs 1947 - 4216
			if ( is_array ( $wp_query->query_vars['post_type' ] ) ) {
				if ( $wp_query->query_vars['post_type' ] = array( bbp_get_topic_post_type(), bbp_get_reply_post_type() ) ) {
					//error_log (' QUERY '.serialize ( $wp_query->query_vars['post_type' ] ));
					$wp_query->is_home = false ;
				}
			}
		}
	}
	
		
	function xiliml_manage_column_name ( $ends, $cols, $post_type ) {
		if ( in_array ( $post_type, array( bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() ) ) ) {
		//error_log ( '----------->'.$post_type);
		$ends = array( 'author', 'comments', 'date', 'rel', 'visible');
		}
		return $ends;
	}
	
	function xiliml_language_translated_in_column ( $output, $result, $post_type ) {
		
		if ( in_array ( $post_type, array( bbp_get_forum_post_type(), bbp_get_topic_post_type(), bbp_get_reply_post_type() ) ) ) {
			$output = '';		
			if ( $result == '' ) {
				$output .= '.' ;
			} else {
				$output .= __('linked in:', 'xili_xl_bbp_addon') ;
				$output .= '&nbsp;<span class="translated-in">' . $result .'</span>'; 	
			}
		}
		
		return $output;
	}
	
	/**
	 * Adds the options subpanel
	 */
	function admin_menu_link() {
		add_options_page( $this->plugin_name, __($this->display_plugin_name, 'xili_xl_bbp_addon'), 'manage_options', __FILE__, array(&$this,'admin_options_page'));
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
	}
	
	/**
	 * Admin init - section - fields
	 */
	function admin_init() {
		register_setting ($this->settings_name, $this->settings_name, array(&$this,'xili_settings_validate_options') );
		add_settings_section ( $this->settings_list.'_section_1', __('List of options ', 'xili_xl_bbp_addon') , array(&$this,'xili_settings_section_1_draw') , $this->settings_list );
		
		add_settings_field ( $this->settings_list.'_css-theme-folder', __('bbPress default style file in theme', 'xili_xl_bbp_addon'), array(&$this,'xili_settings_field_1_draw'), $this->settings_list, $this->settings_list.'_section_1');
		
	}
	
	function xili_settings_section_1_draw () {
		echo '<p>'. __('This plugin shipped with xili-language package is an addon to activate multilingual features to bbPress with xili-language. Some other options are possible.', 'xili_xl_bbp_addon')  . '</p>';
		
	}
	
	function xili_settings_field_1_draw () {
		// not checked - not saved in settings
		$checked = ( isset ( $this->xili_settings['css-theme-folder'] ) && $this->xili_settings['css-theme-folder'] ) ? "checked='checked'" : "";
		
		echo "<input value = 'true' id='{$this->settings_name}[css-theme-folder]' name='{$this->settings_name}[css-theme-folder]' type='checkbox' {$checked}  />"; 
		
	}
	
	function xili_settings_validate_options ( $input ) {
	
		$valid = $input;
		$valid['version'] = $this->xili_settings_ver ; // because not in input !	
		return $valid;	
	}

	/**
	 * Adds the Settings link to the plugin activate/deactivate page
	 */
	function filter_plugin_actions($links, $file) {
	   $settings_link = '<a href="options-general.php?page=' . $this->plugin_folder .'/'. basename(__FILE__) . '">' . __('Settings') . '</a>';
	   array_unshift( $links, $settings_link ); // before other links

	   return $links;
	}

	/**
	 * Adds settings/options page
	 */
	function admin_options_page() { ?>
		<div class="wrap">
    		<?php screen_icon(); ?>
    		<h2><?php printf(__( '%s settings', 'xili_xl_bbp_addon'), $this->display_plugin_name ); ?></h2>
    		<form action="options.php" method="post" >
    		 	<?php 
				do_settings_sections( $this->settings_list );
    			settings_fields( $this->settings_name ); // hidden fields and referrer and nonce
    			?>
    			<?php submit_button( __('Save Changes'), 'secondary' ); // 'primary' = by default ?>
    		</form>
    		<h4><a href="http://dev.xiligroup.com/<?php echo $this->plugin_name; ?>" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo plugins_url( 'images/'.$this->plugin_name.'-logo-32.jpg', __FILE__ ) ;  ?>" alt="<?php echo $this->display_plugin_name; ?> logo"/>&nbsp;<?php echo $this->display_plugin_name; ?> </a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2012 - v. <?php echo XILIXLBBPADDON_VER; ?></h4>
    	</div>
	
		 			
		<?php
	}
	
} //End Class

// bbPress admin Language (user locale)
function xili_xl_bbp_lang_init ( ) { 
	if ( is_admin() )
		add_filter( 'bbpress_locale', 'xili_bbp_admin_side_locale', 10 );
}

function xili_bbp_admin_side_locale ( $locale = 'en_US') {
	
	$locale = get_user_option( 'user_locale' ); 

		if ( empty( $locale ) ) {
			$locale = ( defined( 'WPLANG' ) ) ? WPLANG : 'en_US';

			if ( is_multisite() ) {
				if ( defined( 'WP_INSTALLING' ) || ( false === $ms_locale = get_option( 'WPLANG' ) ) )
					$ms_locale = get_site_option( 'WPLANG' );

			if ( $ms_locale !== false )
					$locale = $ms_locale;
			}
		}
		return $locale;
}
add_action( 'plugins_loaded', 'xili_xl_bbp_lang_init', 9 ); // 9 = to be registered before bbPress instantiate

// INIT and ERROR

function xili_xl_bbp_addon_init () {  
	if ( function_exists ('bbpress') ) 
		$bbp = bbpress() ;
	if ( class_exists ('xili_language') && version_compare( XILILANGUAGE_VER, '2.8.0', '>')  && class_exists ('bbpress') && version_compare( $bbp->version, '2.1.2', '>=')  ) {
		global $xili_xl_bbp_addon;
		$xili_xl_bbp_addon['main'] = new xili_xl_bbp_addon();
	} else {
		add_action( 'admin_notices', 'xili_xl_bbp_addon_need_xl' );
		return;
	}
	/* - not used yet
	if ( is_admin() ) {
		$plugin_path = dirname(__FILE__) ; //error_log( $plugin_path );
		require ( $plugin_path . '/includes/class-admin.php' );
	    $xili_xl_extended['admin'] = new xili_xl_template_admin();
	}
	*/
}

add_action( 'plugins_loaded', 'xili_xl_bbp_addon_init', 17); // after xili-tidy-tags

function xili_xl_bbp_addon_need_xl() {
		global $wp_version;
		load_plugin_textdomain( 'xili_language_errors', false, 'xili-language/languages' );
		echo '<div id="message" class="error fade"><p>';
		echo '<strong>'.__( 'Installation of both xili-language AND bbPress is not completed.', 'xili_language_errors' ) . '</strong>';
		echo '<br />';
		echo '</p></div>';
} 


?>