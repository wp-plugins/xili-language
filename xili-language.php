<?php
/*
Plugin Name: xili-language
Plugin URI: http://dev.xiligroup.com/xili-language/
Description: This plugin modify on the fly the translation of the theme depending the language of the post or other blog elements - a way to create a real multilanguage site (cms or blog). Numerous template tags and three widgets are included. It introduce a new taxonomy - here language - to describe posts and pages. To complete with tags, use also xili-tidy-tags plugin. To include and set translation of .mo files use xili-dictionary plugin.
Author: dev.xiligroup.com - MS
Version: 2.1.1
Author URI: http://dev.xiligroup.com
License: GPLv2
*/
# updated 110628 - 2.1.1 - fixes focus error
# updated 110620 - 2.1.1 - fixes uninstall error 
# updated 110503 - 2.1.0 - new list types when singular linked post in xili_language_list, multiple location options for nav menu
# updated 110410 - 2.0.0 - erase old coding remaining for 2.9.x - Improve (slowly) readme...
# updated 110316 - 1.9.1 - fixes in xili widget recent posts - only post-type display by default - input added to add list of type (post,video,…) - fixes query_var issues when front-page as page
# updated 110226 - 1.9.0 - release as current for WP 3.1
# updated 110124 - 1.8.9.3 - add actions for bulk edit, prepare language taxonomy for permalink options
# updated 110113 - 1.8.9.2 - add option to adapt nav home menu item
# updated 101213 - 1.8.9.1 - fixes issues for date format and LC UTF-8 (japanese) and other new features: see ChangeLog
# updated 101212 - 1.8.9 - quick-edit, add filters 'xili_nav_lang_list' & 'xili_nav_page_list' and class for separator and more...
# updated 101205 - 1.8.8 - complete gettext filters - include optional activation of the 3 widgets. - add use ja for japanese
# updated 101125 - 1.8.7 - add gettext filter to change domain for visitor side of widget and other plugins
# updated 101123 - 1.8.6 - readme rewritten - email metabox at bottom
# updated 101115 - 1.8.5 - improve automatic languages sub-folder detection and caution message if `load_textdomain()` is missing in functions.php
#						 - repairs oversight about bookmarks taxonomies : now it is possible in widget to sub-select bookmarks according language.
# updated 101107 - 1.8.4 - query with undefined lang *, (changed XILI_CATS_ALL), widget languages list with display condition, fixes if classic home
# updated 101101 - 1.8.3 - widgets updated as extended class
# updated 101030 - 1.8.2 - search form improved - fixes
# updated 101028 - 1.8.1 - can choose the nav menu to insert automatically languages list - child theme compatibility - better date to strftime
# updated 101008 - 1.8.0 - setting added to automatic adjontion of multilingual custom post type
# updated 100721 - 1.7.1 - new messages in admin ui, multilingual pages in nav menu
# updated 100713 - 1.7.0 - new front-page mechanisms, ready for other post_types, better queries join, fixes category query
# updated 100628 - 1.6.1 - Add sticky_posts IDs array translation (Jacob's suggestion) - fixes refresh theme_textdomain for old WP 2.9.x)
# updated 100621 - 1.6.0 - DB queries reducing: xili_get_listlanguages() = list of language objects in settings (updated only when changed) - In WP 3.0, possible to complete top nav menu with lang list and more... see readme.txt
# updated 100527 - 1.5.5 - add filters for comment form live translation (themedomain). Display info if list not set. Fixes linked post/page creation issue in WP3.
# updated 100502 - 1.5.4 - fixes widget title translation issue - recover previous behaviour
# updated 100429 - 1.5.3 - fixes default_slug - both for wp and wpmu - thanks ju-ju.com
# updated 100416 - 1.5.2 - for multisite - wpmu, ready to download .mo in uploads of current site - the_theme_domain() replace constant for wpmu
# updated 100407 - 1.5.1 - include some minor modifications to be WPMU 3.0 compatible (beta tests)
# updated 100404 - 1.5.0 - incorporate automatic detection of theme domain for WP 3.0
# updated 100403 - 1.4.2a - few modification for compatibility with latest WP 3.0-alpha - delete unwanted message (wp)
# updated 100301 - 1.4.2 - improved template_tags : xiliml_the_category, xiliml_the_other_posts
# see readme text for these intermediate versions for WP 2.x. or visit other versions.
# updated 090228 - Class and OOP - see 0.9.7 in comments of functions below - only for WP 2.7.x

# This plugin is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This plugin is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this plugin; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

define('XILILANGUAGE_VER','2.1.1'); /* used in admin UI*/

class xili_language {
	
	var $default_lang; /* language of config.php*/
	var $default_slug; /* slug of language of config.php since 1.5.3 wpmu*/
	var $curlang;
	var $thetextdomain = ""; /* since 1.5.2 - used if multiple */
	var $langstate; /* undefined or not */
	var $browseroption = '';
	var $authorbrowseroption = '';
	var $functions_enable = '';
	var $default_dir = ''; /* undefined or not in WP config '' or rtl or ltr */
	var $curlang_dir = ''; /* undefined or not according array */
	var $rtllanglist = 'ar-he-fa-ur'; /*default-list - can be set after class instantiation*/
	var $xili_settings; /* saved in options */
	var $langs_group_id; /* group ID and Term Taxo ID */
	var $langs_group_tt_id; 
	var $get_archives_called = array(); /* if != '' - insert lang in link */
	var $idx = array(); /* used to identify filter or action set from this class - since 0.9.9.6 */
	var $theme_locale = false; /* to control locale hook */
	var $ossep = "/"; /* for recursive file search in xamp */
	var $current_lang_query_tag = ""; /* since 1.3.0 */
	var $temp_lang_query_tag = "";
	var $domaindetectmsg = "";
	var $langs_list_options = array( array('','default'), array('typeone','Type #1'), array('navmenu', 'Nav Menu' ) , array('navmenu-1', 'Nav Menu #1' ) ); // type of languages list see options in xili_language_list or navmenu
	var $comment_form_labels = array ( // since 1.6.0 for comment_form 
		'name' => 'Name',
		'email' => 'Email',
		'website' => 'Website',
		'comment' => 'Comment',
		'youmustbe' => 'You must be <a href="%s">logged in</a> to post a comment.',
		'loggedinas' => 'Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>',
		'emailnotpublished' => 'Your email address will not be published.',
		'requiredmarked' => ' Required fields are marked <span class="required">*</span>',
		'youmayuse' => 'You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes:',
		'leavereply' => 'Leave a Reply',
		'replyto' => 'Leave a Reply to %s',
		'cancelreply' => 'Cancel reply',
		'postcomment' => 'Post Comment'
		);
	var $sticky_keep_original = false ; // since 1.6.1 see translate_sticky_posts_ID function 
	var $xl_recent_posts = false ;
	var $ltd = false ; // load_textdomain detected 1.8.5
	var $arraydomains = array();
	var $show = false ;
	
	function xili_language( $locale_method = false, $show = false ) {
		global $wp_version;
		
		$this->locale_method = $locale_method; /* added for compatibility with cache plugin from johan */
		$this->show = $show;
		/* activated when first activation of plug */
		register_activation_hook(__FILE__,array(&$this,'xili_language_activate'));
		$this->ossep = strtoupper(substr(PHP_OS,0,3)=='WIN')?'\\':'/';	
			
		/** 
		 * get current settings
		 */
		$this->xili_settings = get_option('xili_language_settings');
		if( false === $this->xili_settings ) { //1.9.1
			$this->initial_settings ();
			update_option('xili_language_settings', $this->xili_settings);				
		} else {
			if ($this->xili_settings['version'] == '0.2' || $this->xili_settings['version'] == '0.3') { 
				$this->xili_settings['taxolangsgroup'] = 'languages_group';
				$this->xili_settings['homelang'] = '';
				$this->xili_settings['version'] = '0.4';
			}
			if ($this->xili_settings['version'] == '0.4') { /* 1.6.0 */
				$this->xili_settings['langs_list_status'] = '';
				$this->xili_settings['in_nav_menu'] = '';
				$this->xili_settings['version'] = '1.0';
				update_option('xili_language_settings', $this->xili_settings);	
			}
			if ($this->xili_settings['version'] == '1.0') { /* 1.7.1 */
				$this->xili_settings['page_in_nav_menu'] = '';
				$this->xili_settings['args_page_in_nav_menu'] = '';
				$this->xili_settings['version'] = '1.1';
				update_option('xili_language_settings', $this->xili_settings);	
			}
			if ($this->xili_settings['version'] == '1.1') { /* 1.8.0 */	
				$this->xili_settings['multilingual_custom_post'] = array();
				$this->xili_settings['version'] = '1.2';
				update_option('xili_language_settings', $this->xili_settings);
			}
			if ($this->xili_settings['version'] == '1.2') { /* 1.8.1 */	
				$this->xili_settings['langs_in_root_theme'] = 'root'; // if child theme: user can choose
				$this->xili_settings['version'] = '1.3';
				update_option('xili_language_settings', $this->xili_settings);
			
			}
			if ($this->xili_settings['version'] == '1.3') { /* 1.8.7 */	
				$this->xili_settings['domains'] = array( 'all' => 'disable', 'default' => 'disable' ); // no default domain to theme domain
				$this->xili_settings['version'] = '1.4';
				update_option('xili_language_settings', $this->xili_settings);
			}
			if ($this->xili_settings['version'] == '1.4') { /* 1.8.7 */	
				$this->xili_settings['widget'] = 'enable';
				$this->xili_settings['version'] = '1.5';
				update_option('xili_language_settings', $this->xili_settings);
			}
			if ($this->xili_settings['version'] == '1.5') { /* 1.8.8 */	
				$this->xili_settings['delete_settings'] = '';
				$this->xili_settings['version'] = '1.6';
				update_option('xili_language_settings', $this->xili_settings);
			}
			if ($this->xili_settings['version'] == '1.6') { /* 1.8.9.1 */	
				$this->xili_settings['allcategories_lang'] = 'browser';
				$this->xili_settings['lang_features'] = array();
				$this->xili_settings['version'] = '1.7';
				update_option('xili_language_settings', $this->xili_settings);
			}
			if ($this->xili_settings['version'] == '1.7') { /* 1.8.9.2 */	
				$this->xili_settings['home_item_nav_menu'] = '';
				$this->xili_settings['version'] = '1.8';
				update_option('xili_language_settings', $this->xili_settings);
			}
			if ( ! isset ( $this->xili_settings['version'] ) || $this->xili_settings['version'] != '1.8') { // repair
				$this->initial_settings ();
				update_option('xili_language_settings', $this->xili_settings);
			}
		}
		define('TAXONAME',$this->xili_settings['taxonomy']);
		define('QUETAG',$this->xili_settings['reqtag']); // 'lang'
		define('TAXOLANGSGROUP',$this->xili_settings['taxolangsgroup']);
		define('XILIFUNCTIONSPATH',WP_PLUGIN_DIR.'/xilidev-libraries'); /* since 1.0 to add xili-libraries */
		$this->browseroption = $this->xili_settings['browseroption'];
		$this->authorbrowseroption = $this->xili_settings['authorbrowseroption'];
		$this->functions_enable = $this->xili_settings['functions_enable'];
		$this->thetextdomain = $this->xili_settings['theme_domain']; /* since 1.5.2 */
		$this->langsliststatus = $this->xili_settings['langs_list_status'];  /* 1.6.0 */
		
		if (function_exists('is_child_theme') && is_child_theme() ) { // 1.8.1 - 1.8.5
			if ( $this->xili_settings['langs_in_root_theme'] == 'root' ) {
				$this->get_template_directory = get_template_directory();
			} else {
				$this->get_template_directory = get_stylesheet_directory();
			}
		} else {
			$this->get_template_directory = get_template_directory();
		}
	
		add_filter('query_vars', array(&$this,'keywords_addQueryVar'));
		add_filter('posts_join', array(&$this,'posts_join_with_lang'), 10, 2 );
		add_filter('posts_where', array(&$this,'posts_where_lang'), 10, 2 );
		
		add_action('wp', array(&$this,'xiliml_language_wp')); 
		/* 'wp' = where theme's language is defined just after query */
		if ($this->locale_method)
			add_filter('locale', array(&$this,'xiliml_setlocale'), 10);
		/* to be compatible with l10n cache from Johan since 1.1.9 */
		add_filter('language_attributes',  array(&$this,'head_language_attributes'));
		add_action('wp_head', array(&$this,'head_insert_language_metas'),10,2);
 
 	 	add_filter('widget_title', array(&$this,'one_text')); /* added 0.9.8.1 */
	 	add_filter('widget_text', array(&$this,'one_text'));
		add_filter('list_cats', array(&$this,'xiliml_cat_language'), 10, 2 ); /* mode 2 : content = name */
		add_filter('link_category', array(&$this,'one_text')); // 1.6.0 for wp_list_bookmarks (forgotten)
		
		add_filter('category_link', array(&$this,'xiliml_link_append_lang'), 10, 2 );
		$filter = 'category_link';
		$function = 'xiliml_link_append_lang';
		$this->idx['xiliml_link_append_lang'] = _wp_filter_build_unique_id($filter, array (&$this, $function == '' ? $filter : $function), 10, 2); 
		/* unique id of this filter from object fixed 1.0.1 */
		add_filter('category_description',array(&$this,'xiliml_link_translate_desc'));
		add_filter('single_cat_title',array(&$this,'xiliml_single_cat_title_translate')); /* 1.4.1 wp_title() */
		add_filter('tag_link', array(&$this,'xiliml_taglink_append_lang' ));
		add_filter('bloginfo', array(&$this,'xiliml_bloginfo'),10,2); /* since 1.6.0 - description - date - time */
		add_action('pre_get_posts', array(&$this,'xiliml_modify_querytag'));
		/* filters for archives since 0.9.9.4 */
		add_filter('getarchives_join', array(&$this,'xiliml_getarchives_join'),10,2);
		add_filter('getarchives_where', array(&$this,'xiliml_getarchives_where'),10,2);
		add_filter('get_archives_link', array(&$this,'xiliml_get_archives_link'));
		
		add_filter('option_sticky_posts', array(&$this,'translate_sticky_posts_ID')); /* 1.6.1 */
		add_filter('option_page_on_front', array(&$this,'translate_page_on_front_ID')); /* 1.7.0 */
		
		/* actions for post and page admin UI */
		add_action('save_post', array(&$this,'xili_language_add'));
		//add_action('publish_post', array(&$this,'xili_language_add')); /* only set when published !*/
		add_action('save_page', array(&$this,'xili_language_add'));
		//add_action('publish_page', array(&$this,'xili_language_add'));
		
		// quick edit languages in list - 1.8.9 
		add_action('quick_edit_custom_box', array(&$this,'languages_custom_box'), 10, 2);
		add_action('bulk_edit_custom_box', array(&$this,'hidden_languages_custom_box'), 10, 2); // 1.8.9.3
		add_action('admin_head-edit.php', array(&$this,'quick_edit_add_script'));
		
		/* bookmarks and widget_links  1.8.5  #2500 */
		add_filter( 'widget_links_args', array( &$this, 'widget_links_args_and_lang' ), 10, 1 ); // in class WP_Widget_Links (default-widgets.php)
		
		add_filter('get_bookmarks', array( &$this, 'the_get_bookmarks_lang' ), 10, 2); // only active if 'lang' in wp_list_bookmarks()
		
		/* admin settings UI and taxonomies */
		add_action('init', array(&$this,'init_textdomain'));
		add_action('init', array(&$this,'set_when_plugin_loaded'));
		add_action('init', array(&$this,'add_link_taxonomy'),12); // 1.8.5
		add_filter('plugin_action_links',  array(&$this,'xililang_filter_plugin_actions'), 10, 2);
		
		add_action('admin_menu', array(&$this, 'myplugin_add_custom_box'));
		add_action('admin_menu', array(&$this, 'xili_add_pages'));
		/* special to detect theme changing since 1.1.9 */
		add_action('switch_theme', array(&$this,'theme_switched'));
		/* inspired from custax */
		add_action('manage_posts_custom_column', array(&$this,'xili_manage_column'), 10, 2);
		//
		if (!is_admin()) {
			add_filter('the_category', array(&$this,'xl_get_the_category_list'), 10, 2); /* 1.7.0 */
			add_filter('gettext', array(&$this,'change_plugin_domain'), 10, 3); /* 1.8.7 */
			add_filter('gettext_with_context', array(&$this,'change_plugin_domain_with_context'), 10, 4); /* 1.8.8 */
			add_filter('ngettext', array(&$this,'change_plugin_domain_plural'), 10, 5);
			add_filter('ngettext_with_context', array(&$this,'change_plugin_domain_plural_with_context'), 10, 6);
		}
		
		add_filter( 'manage_posts_columns', array(&$this,'xili_manage_column_name'));
		add_filter( 'manage_pages_columns', array(&$this,'xili_manage_column_name'));
		add_filter( 'override_load_textdomain', array(&$this,'xiliml_override_load'),10,3); // since 1.5.0
		add_filter( 'theme_locale', array(&$this,'xiliml_theme_locale'),10,2);
		// since 1.5.5
		add_filter( 'comment_form_default_fields', array(&$this,'xili_comment_form_default_fields'));
		add_filter( 'comment_form_defaults', array(&$this,'xili_comment_form_defaults'));
		// for wpmu
		
		// for wp nav menu
		add_filter('the_title', array(&$this,'wp_nav_title_text'),10,2);
		if ('' != $this->xili_settings['in_nav_menu'])
				add_filter('wp_nav_menu_items', 'xili_nav_lang_list',10,2);
		if ('' != $this->xili_settings['page_in_nav_menu'])
				add_filter('wp_nav_menu_items', 'xili_nav_page_list',9,2); // before lang's links - 1.7.1
		if ('' != $this->xili_settings['home_item_nav_menu'])
				add_filter('walker_nav_menu_start_el', 'xili_nav_page_home_item',10,4); // add lang if - 1.8.9.2
		//display contextual help
		add_action( 'contextual_help', array(&$this,'add_help_text'), 10, 3 ); /* 1.7.0 */
		// sub-select in admin/edit.php 1.8.9
		add_action( 'restrict_manage_posts', array(&$this,'restrict_manage_languages_posts') );		
		
		add_action('manage_pages_custom_column', array(&$this,'xili_manage_column'), 10, 2);
		
		add_filter( 'manage_link-manager_columns', array(&$this,'xili_manage_link_column_name') ); // 1.8.5
		add_action( 'manage_link_custom_column', array(&$this,'manage_link_lang_column'),10,2);
		// set or update term for this link taxonomy
		add_action( 'edit_link', array(&$this,'edit_link_set_lang') );
		add_action( 'add_link', array(&$this,'edit_link_set_lang') );

		// since 1.8.8
		if ( $this->xili_settings['widget'] == 'enable' )
			add_action( 'widgets_init', array(&$this,'add_new_widgets') );
	
		
		/* new actions for xili-language theme's templates tags */
		
		$this->add_action('xili_language_list','xili_language_list',10,5); /* add third param 0.9.7.4 - 4th 1.6.0*/
		$this->add_action('xili_post_language','xili_post_language',10,2);
		
		$this->add_action('xiliml_the_other_posts','xiliml_the_other_posts',10,4); /* add a param 1.1 */
		$this->add_action('xiliml_the_category','xiliml_the_category',10,3);
		$this->add_filter('xiliml_langinsearchform','xiliml_langinsearchform',10,3); // 1.8.2 action to filter
		
				
	}
	
	/** * first activation or empty settings */
	function initial_settings () {
		$this->xili_settings = array(
			    'taxonomy'		=> 'language',
			    'version' 		=> '1.7',
			    'reqtag'		=> 'lang',
			    'browseroption' => '',
			    'authorbrowseroption' => '',
			    'taxolangsgroup' => 'languages_group',
			    'functions_enable' => '',
			    'langs_folder' => '',
			    'theme_domain' => '',
			    'homelang' => '',
			    'langs_list_status' => '',
			    'in_nav_menu' => '',
			    'page_in_nav_menu' => '',
				'args_page_in_nav_menu' => '',
				'multilingual_custom_post' => array(),
				'langs_in_root_theme' => '',
				'domains' => array( 'all' => 'disable', 'default' => 'disable' ),  // no default domain to theme domain 1.8.7
				'widget' => 'enable',
				'delete_settings' => '', //1.8.8 for uninstall
				'allcategories_lang' => 'browser', // 1.8.9.1
				'lang_features' => array() ,
				'home_item_nav_menu' => '' // 1.8.9.2
		    );
	}
	/* first activation of plugin */
	function xili_language_activate() {
		$this->xili_settings = get_option('xili_language_settings');
		if(empty($this->xili_settings)) {
			$this->initial_settings ();
		    update_option('xili_language_settings', $this->xili_settings);
		}
	}
	
	function add_action ( $action, $function = '', $priority = 10, $accepted_args = 1 )
	{
		add_action ($action, array (&$this, $function == '' ? $action : $function), $priority, $accepted_args);
		$this->idx[$action] = _wp_filter_build_unique_id($action, array (&$this, $function == '' ? $action : $function), $priority); /* unique id of this filter from object */		 
	}
	
	function add_filter ( $filter, $function = '', $priority = 10, $accepted_args = 1 )
	{
		add_filter ($filter, array (&$this, $function == '' ? $filter : $function), $priority, $accepted_args);
		$this->idx[$filter] = _wp_filter_build_unique_id($filter, array (&$this, $function == '' ? $filter : $function), $priority); /* unique id of this filter from object fixed 1.0.1 */
	}
		
	/**
	 * for wpmu 
	 * register functions must be called by init
	 *
	 * @since 1.5.1
	 *
	 */
	function set_when_plugin_loaded () {
		/** add new taxonomy in available taxonomies 
		 * 1.0.2 - add label false as http://core.trac.wordpress.org/ticket/10437 
		 * 			to avoid metabox as tag displayed 
		 * 'label' => __('Post Langs','xili-language'), , 'query_var' => 'lang' )
		 */
		register_taxonomy( TAXONAME, 'post', array('hierarchical' => false, 'label' => false, 'rewrite' => false , 'update_count_callback' => array(&$this, '_update_post_lang_count'), 'show_ui' => false, '_builtin' => false));
		register_taxonomy( TAXOLANGSGROUP, 'term', array('hierarchical' => false, 'update_count_callback' => '', 'show_ui' => false, 'label'=>false, 'rewrite' => false, '_builtin' => false ));
		$thegroup = get_terms(TAXOLANGSGROUP, array('hide_empty' => false,'slug' => 'the-langs-group'));
		if (!$thegroup) { /* update langs group 0.9.8 */
			$args = array( 'alias_of' => '', 'description' => 'the group of languages', 'parent' => 0, 'slug' =>'the-langs-group');
			wp_insert_term( 'the-langs-group', TAXOLANGSGROUP, $args); /* create and link to existing langs */
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			foreach($listlanguages as $language) {
				wp_set_object_terms($language->term_id, 'the-langs-group', TAXOLANGSGROUP);
			}
			$thegroup = get_terms(TAXOLANGSGROUP, array('hide_empty' => false,'slug' => 'the-langs-group'));
		}
		$this->langs_group_id = $thegroup[0]->term_id;
		$this->langs_group_tt_id = $thegroup[0]->term_taxonomy_id;
		/* here because taxonomy is registered : since 1.5.3 */
		/* default values */
		if ( ''!= WPLANG && ( strlen( WPLANG )==5 || strlen( WPLANG ) == 2 ) ) : // for japanese
			$this->default_lang = WPLANG;
		else:
			$this->default_lang = 'en_US';
		endif;
		$this->default_slug = $this->get_default_slug(); /*no constant for wpmu */
		
		if (!is_multisite() && !defined('DEFAULTSLUG')) define('DEFAULTSLUG',$this->default_slug); /* for backward compatibility */ 
		
			
		if ( $dir = get_bloginfo('text_direction') ) /* if present in blog options @since 0.9.9 */
			$this->default_dir = $dir;
			
			// 1.8.4
		if ( isset($this->xili_settings['available_langs']) ) {
			$this->available_langs = $this->xili_settings['available_langs']; 
		} else {
			$this->available_langs = $this->get_lang_ids() ;
			$this->xili_settings['available_langs'] = $this->available_langs;
			update_option('xili_language_settings', $this->xili_settings);
		}
		

	}
	
	/**
	 * Get list language Objects - designed and used to avoid query by using settings
	 *
	 * @since 1.6.0
	 * @param $force to avoid buffer
	 * @return array of objects
	 */
	function get_listlanguages( $force = false ) { 
		if ($this->xili_settings['langs_list_status'] != "set" || $force === true) {
	 		$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC'); 
	 		if ($listlanguages) {
	 			$this->xili_settings['languages_list'] = $listlanguages;
	 			$this->xili_settings['langs_list_status'] = "set";
	 			update_option('xili_language_settings', $this->xili_settings);
	 		}
	 		return $listlanguages;
		} else {
			return $this->xili_settings['languages_list'];
		}
	}
	
	/**
	 * Get list language IDs
	 *
	 * @since 1.8.4
	 */
	function get_lang_ids() {
		 
		$lang_ids = array() ;
		$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		foreach ( $listlanguages as $lang) {
			$lang_ids[] = $lang->term_id;
		}
		return $lang_ids ;
	}
			
	/**
	 * More than one filter for the function. 
	 *
	 * @since 0.9.7
	 * 
	 * @param $the_function (string). 
	 * @return true if more than one.
	 */
	function this_has_filter( $the_function ) {
		global $wp_filter;
		$has = $wp_filter[$the_function];
		//print_r($has);
		if ( ! is_array( $has ) ) return false; // avoid php warning 2.1.0
		$keys = array_keys($has);
		//echo count($has[$keys[0]]);
		if (count($has[$keys[0]]) >= 2) { /*one from class others from functions.php or elsewhere*/
			return true;
		} else {
			return false;
		} 	
	}
		
	/**
	 * @updated 1.8.0 - add custom post type
	 */
	function myplugin_add_custom_box() {
				
 		add_meta_box('xilil-2', __("Page's language",'xili-language'), array(&$this,'xili_language_checkboxes'), 'page', 'side','high');
 		add_meta_box('xilil-2', __("Post's language",'xili-language'), array(&$this,'xili_language_checkboxes'), 'post', 'side','high');
 		add_meta_box('xilil-1', __('Linked posts','xili-language'), array(&$this,'xili_language_linked_posts'), 'post', 'side','high');
 		add_meta_box('xilil-1', __('Linked pages','xili-language'), array(&$this,'xili_language_linked_posts'), 'page', 'side','high');
 		
 		$custompoststype = $this->xili_settings['multilingual_custom_post'] ;
 		if ( $custompoststype != array()) {
 			//print_r($custompoststype);
 			foreach ( $custompoststype as $key => $customtype ) {
 				if ($customtype['multilingual'] == 'enable') {
					$plural_name = $customtype['name'] ;  
	 				$singular_name = $customtype['singular_name'] ;
	 				add_meta_box('xilil-2', sprintf(__("%s's language",'xili-language'), $singular_name), array(&$this,'xili_language_checkboxes'), $key , 'side','high');
	 				add_meta_box('xilil-1', sprintf(__('Linked %s', 'xili-language'), $plural_name), array(&$this,'xili_language_linked_posts'), $key, 'side','high');
 				}	
 				
 			}
 		};
 		add_action( 'add_meta_boxes_link', array(&$this,'new_box') );
	}
	
	/**
	 * Will update term count based on posts AND pages.
	 *  
	 * @access private from register taxonomy etc...
	 * @since 0.9.8.1
	 * @uses $wpdb
	 *
	 * @param array $terms List of Term taxonomy IDs
	 */
	function _update_post_lang_count( $terms ) {
		global $wpdb;
		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status = 'publish' AND term_taxonomy_id = %d", $term ) );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
		}
	}
	
	/**
	 * set language when post or page is saved or changed 
	 *
	 * @since 0.9.0
	 * @completed 0.9.7.1 to record postmeta of linked posts in other languages
	 * @updated 0.9.7.5 to delete relationship when undefined
	 * @updated 0.9.9 to avoid delete relationship when in quick_edit
	 * @updated 1.3.0 to avoid delete relationship when trashing - 1.4.1 - create post-meta xl-search-linked
	 * @updated 1.8.9.3 for bulk edit...
	 * @param $post_ID
	 */
	function xili_language_add( $post_ID ) { 
		
		if ( isset($_POST['_inline_edit']) ) { /* when in quick_edit (edit.php) */
			
			$sellang = $_POST['xlpop'];
			if ( "" != $sellang ) {
				wp_set_object_terms( $post_ID, $sellang, TAXONAME );
			} else {
				if ( $_GET['action'] != 'trash' && $_GET['action'] != 'untrash' )
						wp_delete_object_term_relationships( $post_ID, TAXONAME ); 	
			}
			
		} else if ( isset( $_GET['bulk_edit']) ) { // bulk_edit 
				 	
		 	$sellang = $_GET['xlpop'];
			if ( "-1" != $sellang && "*" != $sellang) {
				//error_log("append lang to ".$post_ID);
				wp_set_object_terms( $post_ID, $sellang, TAXONAME );
			} else if ( "*" == $sellang )  {
				if ( $_GET['action'] != 'trash' && $_GET['action'] != 'untrash' )
						wp_delete_object_term_relationships( $post_ID, TAXONAME ); 	
			}
		 	
		} else {	
		
			$sellang = $_POST['xili_language_set'];
			if ("" != $sellang) {
				wp_set_object_terms($post_ID, $sellang, TAXONAME);
			} else {
				if ($_GET['action'] != 'trash' && $_GET['action'] != 'untrash')
						wp_delete_object_term_relationships( $post_ID, TAXONAME ); 	
			}
				
			/* the linked posts set by author in postmeta */	
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			foreach ($listlanguages as $language) {
				$inputid = 'xili_language_'.QUETAG.'-'.$language->slug ;
				$recinputid = 'xili_language_rec_'.QUETAG.'-'.$language->slug ;
				$linkid = $_POST[$inputid];
				$reclinkid = $_POST[$recinputid]; /* hidden previous value */
				$langslug = QUETAG.'-'.$language->slug ;
				
				if ($reclinkid != $linkid) { /* only if changed value or created since 1.3.0 */			
					if ((is_numeric($linkid) && $linkid == 0) || '' == $linkid ) {
						delete_post_meta($post_ID, $langslug);
					} elseif (is_numeric($linkid) && $linkid > 0) {
						update_post_meta($post_ID, $langslug, $linkid);
						if ($reclinkid == "-1")	update_post_meta($linkid, QUETAG.'-'.$sellang, $post_ID); 
					}
				}	
			}
			
			if ('' != $_POST['xili_language_search_lang']) {
				update_post_meta($post_ID, '_xl-search-linked', $_POST['xili_language_search_lang']);
			} else {
				if ($_GET['action'] != 'trash' && $_GET['action'] != 'untrash')
					delete_post_meta($post_ID, '_xl-search-linked');
			}
		}	
	}
	
	/**
	 * Return language dir
	 *
	 * @since 0.9.9
	 * @param slug of lang
	 */
	function get_dir_of_cur_language( $lang_slug ) {
		$rtlarray = explode ('-',$this->rtllanglist);			
		$dir = (in_array(substr(strtolower($lang_slug),0,2),$rtlarray)) ? 'rtl' : 'ltr';
		return $dir;
	}
	
	/**
	 * Return language of post.
	 *
	 * @since 0.9.0
	 * @updated 0.9.7.6, 0.9.9
	 *
	 * @param $post_ID.
	 * @return slug of language of post or false if var langstate is false.
	 */
	function get_cur_language( $post_ID ) {
		$ress = wp_get_object_terms($post_ID, TAXONAME);
		if ($ress) {
			if (is_a($ress, 'WP_Error')){
				echo "Language table not created ! see plug-in admin";
				$this->langstate = false;
			} else {
				$obj_term = $ress[0];
				$this->langstate = true;
				$postlang = $obj_term->slug;
				$postlangdir = $this->get_dir_of_cur_language($postlang);
				return array(QUETAG=>$postlang,'direction'=>$postlangdir);
			}
	 	} else {
	 		$this->langstate = false; /* can be used in language attributes for header */
	  		return false;	/* undefined state */
	 	}		
	}
		
	/** enable the new query tag associated with new taxonomy */
	function keywords_addQueryVar($vars) {
		$vars[] = QUETAG;
	return $vars ;
	}

	function get_default_slug() {
		$listlanguages = get_terms(TAXONAME, array('hide_empty' => false)); 
		$default_slug = 'en_us';
		foreach ($listlanguages as $language) {
			if ($language->name == $this->default_lang ) return $language->slug;
		}
		return $default_slug ;
	}
	
	/** 
	 * Query join filter used when querytag is used or home 
	 *  
	 * @updated 1.7.0 modify page on front and home query
	 * @updated 1.8.4 * to select psots with undefined lang
	 *
	 *
	 */
	function posts_join_with_lang( $join, $query_object = null ) {
		global $wpdb;
		
		$showpage = get_option('show_on_front');
		$insert_join = false ;	
		if ( '' != $query_object->query_vars[QUETAG] ) {
			if ( '*' == $query_object->query_vars[QUETAG] ) {	// undefined lang
				$insert_join = false ;
			} elseif ($query_object->query_vars['caller_get_posts']) {
				if ($query_object->query_vars['xlrp']) $insert_join = true ; // called by xili recent posts
			} else {
				$a = $query_object->query_vars['page_id']; //echo 'a='.$a;
				$b = get_option('page_on_front'); //echo 'b='.$b;
				
				if ( !($query_object->is_home && 'page' == $showpage ) ) { // join if no front-page and other page
					if ( $a == $b && $a !='' ) { 
						// 1.8.1 - two pages in results when language is selected at front !!
						// 1.8.4 - home as home + lang
						$insert_join = false ;
					} else {
						$insert_join = true ; // as in cat or home+lang
					}	
				} elseif ( $query_object->is_home && 'page' != $showpage ) { // home and lang 
					$insert_join = true ;
				} else {
					$insert_join = true ;
				} 
			}
			
		} else { // join if home and modify home according rule and not recent posts
			if (!$query_object->query_vars['caller_get_posts']) { // not called by core recent posts
					//if ($query_object->query_vars['xlrp'] && ''!= $query_object->query_vars[QUETAG]) $insert_join = true ; // called by xili recent posts
			//} else {
				if ( $query_object->is_home && 'page' != $showpage  && $this->xili_settings['homelang'] == 'modify') {
					$insert_join = true ;
				} 
			}
		}
		if ( $insert_join ) 
			$join .= " LEFT JOIN $wpdb->term_relationships as xtr ON ($wpdb->posts.ID = xtr.object_id) LEFT JOIN $wpdb->term_taxonomy as xtt ON (xtr.term_taxonomy_id = xtt.term_taxonomy_id) ";
		return $join;
	}
	
	/**
	 * Modify the query including lang or home
	 *
	 * @since 0.9.0
	 * @updated 0.9.4 (OR added) lang=xx_xx,yy_yy,..
	 * @updated 1.7.0 modify page on front and home query
	 *
	 * @param object $where.
	 * @return $where.
	 */
	function posts_where_lang( $where, $query_object = null ) {
		global $wpdb;
		
		$reqtags = array();
		$thereqtags = array();
		$showpage = get_option('show_on_front');
		//print_r($query_object);
		if ( '*' == $query_object->query_vars[QUETAG] ) { // undefined 
		
			$lang_string = implode( ", ", $this->available_langs );
			$where .= " AND $wpdb->posts.ID NOT IN ( SELECT xtr.object_id FROM $wpdb->term_relationships AS xtr INNER JOIN $wpdb->term_taxonomy AS xtt ON xtr.term_taxonomy_id = xtt.term_taxonomy_id WHERE xtt.taxonomy = '".TAXONAME."' AND xtt.term_id IN ($lang_string) )";
		
		} elseif ( '' != $query_object->query_vars[QUETAG] ) {
			$do_it = false;
			if ($query_object->query_vars['caller_get_posts']) { 
				if ($query_object->query_vars['xlrp']) $do_it = true;
			} else { 
				if (!($query_object->is_home && 'page' == $showpage)) {
					$do_it = true; // all but not home	
					
				}
			}
			
			if ($do_it) {
				if ( strpos($query_object->query_vars[QUETAG], ',') !== false ) {
					$langs = preg_split('/[,\s]+/', $query_object->query_vars[QUETAG]);
					foreach ( (array) $langs as $lang ) {
						$lang = sanitize_term_field('slug', $lang, 0, 'post_tag', 'db');
						$reqtags[]= $lang;
					}
					
					foreach ($reqtags as $reqtag){
						$reqtagt = is_term( $reqtag, TAXONAME );
						if ($reqtagt)
							$thereqtags[] = $reqtagt['term_id']; 
					}
					
					$wherereqtag = implode(", ", $thereqtags);
					
						$where .= " AND xtt.taxonomy = '".TAXONAME."' ";
						$where .= " AND xtt.term_id IN ( $wherereqtag )";
					
				
				} else {
				/* only one lang */
					$query_object->query_vars[QUETAG] = sanitize_term_field('slug', $query_object->query_vars[QUETAG], 0, 'post_tag', 'db');
					$reqtag = $query_object->query_vars[QUETAG];
					$reqtag = is_term( $reqtag, TAXONAME );
					if (''!= $reqtag) {
						$wherereqtag = $reqtag['term_id'];
					} else {
						$wherereqtag = 0;	
					}
					
						$where .= " AND xtt.taxonomy = '".TAXONAME."' ";
						$where .= " AND xtt.term_id = $wherereqtag ";
					
				}
				
			
				
			} else { // is_home and page
			
				if ( $query_object->is_home && 'page' == $showpage ) { 
					$query_object->is_home = false ; // renew the values because the query contains lang=
					$query_object->is_page = true ;
					$query_object->is_singular = true ;
					$query_object->query = array();
					$query_object->query_vars['page_id'] = get_option('page_on_front'); // new filtered value 
					$query_object->query_vars['p'] = $query_object->query_vars['page_id'];
					
					$where = str_replace ("'post'","'page'",$where); // post_type = 
					$where .= " AND {$wpdb->posts}.ID = " . $query_object->query_vars['page_id'];
				}
			}
		
			/* no querytag - no called by get_posts*/		
		}  else {
			If ( !$query_object->query_vars['caller_get_posts'] && $query_object->is_home && 'page' != $showpage && $this->xili_settings['homelang'] == 'modify' )  {
				$curlang = $this->choice_of_browsing_language();
				$reqtag = is_term( $curlang, TAXONAME );
				if (''!= $reqtag) {
					$wherereqtag = $reqtag['term_id'];
				} else {
					$wherereqtag = 0;	
				}
				$where .= " AND xtt.taxonomy = '".TAXONAME."' ";
				$where .= " AND xtt.term_id = $wherereqtag ";	
			} //else {
				//echo '-----***';
			//}
						
		}	
		return $where;
	}

	/******** template theme live modifications ********/
	
	/**
	 * wp action for theme at end of query  
	 *
	 * @since 0.9.0
	 * @updated 1.1.9, 1.4.2a
	 * call by wp hook	   
	 *   
	 */
	function xiliml_language_wp() {
		$this->curlang = $this->get_curlang_action_wp(); // see hooks in that function 
		$this->curlang_dir = $this->get_dir_of_cur_language($this->curlang); /* general dir of the theme */
		if ($this->locale_method) {
			$this->xiliml_load_theme_textdomain ($this->thetextdomain); /* new method for cache compatibility - tests */
		} else {
			$this->set_mofile($this->curlang);
		}
	}
	
	/**
	 * 'theme_locale' filter to detect theme and don't load theme_domain in functions.php
	 *
	 * @since 1.5.0
	 *
	 * call by 'theme_locale' filter
	 */
	function xiliml_theme_locale ( $locale, $domain ) {
		$this->thetextdomain = $domain;
		 
		return 'wx_YZ'; // dummy local
	}
	
	/**
	 * 'override_load_textdomain' filter to avoid dummy load
	 *
	 * @since 1.5.0
	 * @updated 1.8.1 - 1.8.5
	 *
	 */
	function xiliml_override_load ( $falseval, $domain, $mofile ) { 
		if ( $this->show ) {
			if ( !in_array( $domain , $this->arraydomains ) )
				$this->arraydomains[] = $domain;
		}
	   	if (false === strpos ($mofile ,'wx_YZ.mo')) {//
	   		return false;
	   	} else {
	   		if ( str_replace( get_stylesheet_directory(), '', $mofile ) == $mofile ) { // no effect
	   			$this->get_template_directory = get_template_directory();
	   			$this->xili_settings['langs_in_root_theme'] = 'root';
	   		} else {
	   			$this->get_template_directory = get_stylesheet_directory(); // a load is in child
	   			$this->xili_settings['langs_in_root_theme'] = '';
	   			
	   		}
	   		$this->ltd = true ;
	   		
	   		$langs_folder = str_replace('/wx_YZ.mo','',str_replace( $this->get_template_directory, '', $mofile ));
	   		// in wp3 the detection is only done here (end user side by theme domain) so updated is mandatory for xili-dico
	   		if ( $this->xili_settings['langs_folder'] != $langs_folder ) { 
		 		$this->xili_settings['langs_folder'] = $langs_folder ;
		 		update_option( 'xili_language_settings', $this->xili_settings );
		 	}
	   		return true;
	   	}	
	}
	
	/**
	 *
	 * Introduced only in visitors side (not in admin) to change domain of plugin or other
	 * gettext filter from function translate() in wp-includes/I10n.php
	 * 
	 * @since 1.8.7 - 1.8.8
	 */
	function change_plugin_domain ($translation, $text, $domain ) {
		
		$domain = $this->switching_domain ( $domain );
		
		$translations = &get_translations_for_domain( $domain );
		return $translations->translate( $text );
	}
	function change_plugin_domain_with_context ($translation, $text, $context, $domain ) {
		
		$domain = $this->switching_domain ( $domain );
		 
		$translations = &get_translations_for_domain( $domain );
		return $translations->translate( $text, $context );
	}
	function change_plugin_domain_plural ($translation, $single, $plural, $number, $domain ) {
		
		$domain = $this->switching_domain ( $domain );
		 
		$translations = &get_translations_for_domain( $domain );
		$translation = $translations->translate_plural( $single, $plural, $number );
		return $translation ;
	}
	function change_plugin_domain_plural_with_context ($translation, $single, $plural, $number, $context, $domain ) {
		
		$domain = $this->switching_domain ( $domain );
		 
		$translations = &get_translations_for_domain( $domain );
		$translation = $translations->translate_plural( $single, $plural, $number, $context );
		return $translation ;
	}
	/** * domain switching */
	function switching_domain ( $domain ) {
		$ok = false ;
		if ( $domain != $this->thetextdomain ) {
			if ( in_array ( $domain, array_keys( $this->xili_settings['domains'] ) ) ) {
				if ( $this->xili_settings['domains'][$domain] == 'enable' ) $ok = true ;
			} else {
				if ( $this->xili_settings['domains']['all'] == 'enable' ) $ok = true ;
			}
			if ( $ok ) 
				$domain = $this->thetextdomain ;
		} 
		return $domain;
	}
	
	/**
	 * locale hook when load_theme_textdomain is present in functions.php
	 *
	 * @since 1.1.9
	 *
	 * call by locale hook
	 */
	function xiliml_setlocale ( $locale ) {
		if ($this->theme_locale === true) {
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false,'slug' => $this->curlang));
			return $listlanguages[0]->name;
		} else {
			return $locale; 	
		}		
	}
	
	/**
	 * locale hook when load_theme_textdomain is present in functions.php
	 *
	 * @since 1.1.9
	 *
	 * call by locale hook
	 */
	function xiliml_load_theme_textdomain ( $domain ) {
		$this->theme_locale = true;
		$langfolder = $this->xili_settings['langs_folder'];
		$langfolder = '/'.str_replace("/","",$langfolder); /* please no lang folder in sub-subfolder */
		$langfolder = ($langfolder == "/") ? "" : $langfolder;
		load_theme_textdomain($domain, $this->get_template_directory . $langfolder);
		$this->theme_locale = false;		
	}
	
	/**
	 * select .mo file 
	 * @since 0.9.0
	 * @updated 0.9.7.1 - 1.1.9 - 1.5.2 wpmu - 1.8.9.1 (domain select)
	 * call by function xiliml_language_wp()
	 * @param $curlang .
	 */
	function set_mofile( $curlang ) {
		// load_theme_textdomain(THEME_TEXTDOMAIN); - replaced to be flexible -
		if ( ""!=$this->thetextdomain ) { $themetextdomain = $this->thetextdomain; } else { $themetextdomain = 'ttd-not-defined'; }
		$langfolder = $this->xili_settings['langs_folder'];
		$langfolder = '/'.str_replace("/","",$langfolder); /* please no lang folder in sub-subfolder */
		$langfolder = ($langfolder == "/") ? "" : $langfolder;
		
		$filename = '';
		if ( '' != $curlang ) {
			$listlanguages = $this->get_listlanguages(); // 1.8.9.1
			foreach ( $listlanguages as $language ) {
				if ( $language->slug ==  $curlang ) {
					$filename = $language->name;
					continue ;
				}
			}
		}
			
		if ( '' != $filename ) {
			$filename .= '.mo'; 
			$mofile = $this->get_template_directory .$langfolder."/$filename";	
			load_textdomain( $themetextdomain, $mofile );
			if ( is_multisite() ) { /* complete theme's language with db structure languages (cats, desc,…) */
				if (($uploads = wp_upload_dir()) && false === $uploads['error'] ) {
					$wpmu_curdir = $uploads['basedir']."/languages";
					load_textdomain( $themetextdomain, $wpmu_curdir."/$filename" );
				}
			}
		}
	}
	
	/**
	 * default rules - set curlang for wp action 
	 *
	 * @since 1.7.0 - new mechanism for front-page
	 * @updated 1.8.9.1 - better category case
	 * 
	 * replace xiliml_cur_lang_head (0.9.7 -> 1.6.1)
	 * @return $curlang
	 */
	function get_curlang_action_wp () {
		if ( has_filter( 'xiliml_curlang_action_wp' ) ) return apply_filters( 'xiliml_curlang_action_wp', '' ); /* '' warning on some server need one arg by default*/
		/* default */
		global $post, $wp_query ;
		if ( have_posts() ) {
			if ( !is_front_page() ) { /* every pages */ 
				$curlangdir = $this->get_cur_language($post->ID); 
				$curlang = $curlangdir[QUETAG]; /* the first post give the current lang*/ 
				if ( $curlangdir == false ) $curlang = $this->choice_of_browsing_language(); //$this->default_slug; /* no more constant - wpmu - can be changed if use hook */
				if ( is_page() ) {
					if ( isset( $_GET["loclang"] ) ) {
		    			$curlang = $_GET["loclang"];
		    		/* get var to override the selected lang - ex. in bi-lingual contact*/
					}
				} elseif ( is_search() && isset( $_GET[QUETAG] ) ) {
					$curlang = $_GET[QUETAG]; /*useful when no result*/
				} elseif ( is_category() ) {
					if ( isset( $_GET[QUETAG] ) ) {
						$curlang = $_GET[QUETAG];
					} else {
						$curlang = $this->choice_of_categories_all_languages( $curlang ) ;  //$this->choice_of_browsing_language(); // again as defined 1.8.9.1
					}
				}
			} else { /* front page - switch between lang (and post/page) is done in query posts_where_lang filter see above */
				if ( '' != $wp_query->query_vars[QUETAG] ) {
					$curlang = $wp_query->query_vars[QUETAG];	/* home series type*/ 
				} else {
					$showpage = get_option('show_on_front');
					if ( $showpage == "page" ) {
						$page_front = get_option('page_on_front');
						$curlang = get_cur_language($page_front); 
					} else { /* home.php - 1.3.2 */
						$curlang = $this->choice_of_browsing_language();  
					}	
				}	
			}
		} else { /*no posts for instance in category + lang */
		 	if ( isset($_GET[QUETAG]) ) {
		    		$curlang=$_GET[QUETAG];
		    } else {
		    		$curlang = $this->choice_of_browsing_language(); /* select here the default language of the site */
		   	}   	
		} 	
		return $curlang; /* as in external hook for filter*/
	}
	
	/**
	 * modify  language_attributes() output
	 *
	 * @since 0.9.7.6
	 *  
	 * The - language_attributes() -  template tag is use in header of theme file in html tag 
	 *   
	 * @param $output
	 */
	function head_language_attributes( $output ) {
		/* hook head_language_attributes */
		if ( has_filter('head_language_attributes') ) return apply_filters('head_language_attributes',$output);
		$attributes = array();
		$output = '';

		if ( $dir = get_bloginfo('text_direction') ) /*use hook for future use */
			$attributes[] = "dir=\"$dir\"";
		if ( $this->langstate == true ) {	
			if (strlen($this->curlang) == 5) {
				$lang = str_replace('_','-',substr($this->curlang,0,3).strtoupper(substr($this->curlang,-2))); 
			} else {
				$lang = $this->curlang; //1.5.2 for ja as commented in 200909
			}
		} else {
			//use hook if you decide to display limited list of languages for use by instance in frontpage 
			$listlang = array();
			//$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
			foreach ($listlanguages as $language) {
				$listlang[] = str_replace('_','-',$language->name);
			}	
			$lang = $listlang[0]; // implode(', ',$listlang); // not w3c compatible
		}
		if ( get_option('html_type') == 'text/html' )
				$attributes[] = "lang=\"$lang\"";
	
		if ( get_option('html_type') != 'text/html' )
			$attributes[] = "xml:lang=\"$lang\"";	

		$output = implode(' ', $attributes);
		return $output;
	}
	
	/**
	 * modify  insert language metas in head (via wp_head)
	 *
	 * @since 0.9.7.6
	 * @updated 1.1.8 
	 * @must be defined in functions.php according general theme design (wp_head) 
	 *   
	 * @param $curlang
	 */
	function head_insert_language_metas( $curlang, $undefined = true ) {
		$curlang = $this->curlang; 
		$undefined = $this->langstate;
		echo "<!-- multilingual website powered with xili-language v. ".XILILANGUAGE_VER." WP plugin of dev.xiligroup.com -->\n";
			if (has_filter('head_insert_language_metas')) return apply_filters('head_insert_language_metas',$curlang,$undefined);
	}
	
	/**
	 * Translate texts of widgets or other simple text... 
	 *
	 * @updated 1.6.0 - Old name widget_texts
	 * @since 0.9.8.1 
	 * @ return
	 */
	function one_text ( $value ){
		if ('' != $value)
			return __($value, $this->thetextdomain);
		else
			return $value;
	}
	
	/**
	 * Translate title of wp nav menu  
	 *
	 * @since 1.6.0
	 * @ return
	 */
	function wp_nav_title_text ( $value = "", $itemID = 0 ){
		if ('' != $value)
			return __($value,$this->thetextdomain);
		else
			return $value;
	}
	
	/**
	 * Add filters of texts of comment form  - because default text are linked with wp language (and not theme)
	 *
	 * @since 1.5.5
	 * @ return arrays with themetextdomain
	 */
	function xili_comment_form_default_fields ( $fields ) {
		$commenter = wp_get_current_commenter();

		$req = get_option( 'require_name_email' );
		$aria_req = ( $req ? " aria-required='true'" : '' );
		$fields =  array(
			'author' => '<p class="comment-form-author">' . '<label for="author">' . __( $this->comment_form_labels['name'], $this->thetextdomain ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) .
			            '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>',
			'email'  => '<p class="comment-form-email"><label for="email">' . __( $this->comment_form_labels['email'], $this->thetextdomain ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) .
			            '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>',
			'url'    => '<p class="comment-form-url"><label for="url">' . __( $this->comment_form_labels['website'], $this->thetextdomain ) . '</label>' .
			            '<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p>',
		);
		return $fields;
	}
	function xili_comment_form_defaults ( $defaults ) {
		global $user_identity, $post;
		$req = get_option( 'require_name_email' );
		$xilidefaults = array(
		
		'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . _x( $this->comment_form_labels['comment'], $this->thetextdomain ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>',
		'must_log_in'          => '<p class="must-log-in">' .  sprintf( __( $this->comment_form_labels['youmustbe'], $this->thetextdomain ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $post->ID ) ) ) ) . '</p>',
		'logged_in_as'         => '<p class="logged-in-as">' . sprintf( __( $this->comment_form_labels['loggedinas'], $this->thetextdomain ), admin_url( 'profile.php' ), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post->ID ) ) ) ) . '</p>',
		'comment_notes_before' => '<p class="comment-notes">' . __( $this->comment_form_labels['emailnotpublished'], $this->thetextdomain ) . ( $req ? __( $this->comment_form_labels['requiredmarked'], $this->thetextdomain ) : '' ) . '</p>',
		'comment_notes_after'  => '<dl class="form-allowed-tags"><dt>' . __( $this->comment_form_labels['youmayuse'], $this->thetextdomain ) . '</dt> <dd><code>' . allowed_tags() . '</code></dd></dl>',
		'id_form'              => 'commentform',
		'id_submit'            => 'submit',
		'title_reply'          => __( $this->comment_form_labels['leavereply'], $this->thetextdomain ),
		'title_reply_to'       => __( $this->comment_form_labels['replyto'], $this->thetextdomain ),
		'cancel_reply_link'    => __( $this->comment_form_labels['cancelreply'], $this->thetextdomain ),
		'label_submit'         => __( $this->comment_form_labels['postcomment'], $this->thetextdomain ),
		);
		$args = wp_parse_args( $xilidefaults, $defaults);
		return $args;
	}
			
	/**
	 * insert other language of wp_list_categories 
	 *
	 * @since 0.9.0
	 * @updated 0.9.8.4 - 1.4.1 = no original term in ()
	 * can be hooked by filter add_filter('xiliml_cat_language','yourfunction',2,3) in functions.php
	 * call by do_filter list_cats 
	 * @param $content, $category
	 */
	function xiliml_cat_language ( $content, $category = null ) {
		if (has_filter('xiliml_cat_language')) return apply_filters('xiliml_cat_language',$content, $category,$this->curlang);
	    $new_cat_name = (!is_admin()) ? __($category->name,$this->thetextdomain) : $content ;  /*to detect admin UI*/
	    return $new_cat_name;
	 } 
	
	/**
	 * add the language key in category links of current pages
	 *
	 * @since 0.9.0
	 * update 0.9.7 1.5.1
	 * can be hooked by filter add_filter('xiliml_link_append_lang','yourfunction',10,2) in functions.php
	 * call by do_filter 
	 * @param $content,
	 */
	function xiliml_link_append_lang( $link, $category_id = 0 ) {
		if (has_filter('xiliml_link_append_lang')) return apply_filters('xiliml_link_append_lang',$link, $category_id, $this->curlang);
		/*default*/
			//echo $category_id;
	  		if ($this->curlang) :
	  			//$link .= '&amp;'.QUETAG.'='.$this->curlang ;
	  			$permalink = get_option('permalink_structure');
				$sep = ('' == $permalink) ? "&amp;".QUETAG."=" : "?".QUETAG."=";
	  			$link .= $sep.$this->curlang ; // wpmu
	  		endif;
	  	
	  return $link;
	}
	
	/**
	 * Setup global post data.
	 *
	 * @since 0.9.4
	 * update 0.9.7
	 * can be hooked by filter add_filter('xiliml_taglink_append_lang','yourfunction',2,3) in functions.php
	 *
	 * @param $taglink, $tag_id.
	 * @return $taglink.
	 */
	function xiliml_taglink_append_lang ( $taglink, $tag_id=null ) {
		if (has_filter('xiliml_taglink_append_lang')) return apply_filters('xiliml_taglink_append_lang',$taglink,$tag_id,$this->curlang);
		/* no yet default */
		/* global $curlang; 
		
	  		if ($curlang) :
	  			$taglink .= '&amp;'.QUETAG.'='.$curlang ;
	  		endif;
	  	
	  	*/
	 return $taglink;
	} 
	
	/**
	 * Setup global post data.
	 *
	 * @since 1.6.0 
	 * can be hooked by filter add_filter('xiliml_bloginfo','yourfunction',10,3) in functions.php
	 *
	 * @param $output, $show.
	 * @return translated $output.
	 */ 
	 function xiliml_bloginfo ( $output, $show ) {
	 	if (has_filter('xiliml_bloginfo')) return apply_filters('xiliml_bloginfo',$output, $show, $this->curlang);
	 	$info_enabled = array('name', 'blogname', 'description');
	 	if (in_array($show, $info_enabled)) {
	 		return __($output, $this->thetextdomain);
	 	} else {
	 		return $output;
	 	}
	 }
	 
	/**
	 * to cancel sub select by lang in cat 1 by default 
	 *
	 * @since 0.9.2
	 * update 0.9.7 - 1.8.4
	 * can be hooked by filter add_filter('xiliml_modify_querytag','yourfunction') in functions.php
	 *
	 *
	 */
	function xiliml_modify_querytag() {
		if (has_filter('xiliml_modify_querytag')) {
			apply_filters('xiliml_modify_querytag','');
		} else {	
			/*default*/
			global $wp_query;
			if ( defined('XILI_CATS_ALL') && !empty($wp_query->query_vars['cat']) )  { /* change in functions.php or use hook in cat 1 by default*/
				$excludecats = explode(",", XILI_CATS_ALL);
				if 	( $excludecats != array() && in_array($wp_query->query_vars['cat'],$excludecats) ) {
					$wp_query->query_vars[QUETAG] = "";	/* to cancel sub select */
				}
			}
		}	
	}
		
	/**
	 * filters for wp_get_archives 
	 *
	 * @since 0.9.2
	 * @params $join or $where and template params
	 *
	 */
	function xiliml_getarchives_join( $join, $r ) {
		global $wpdb;
		if (has_filter('xiliml_getarchives_join')) return apply_filters('xiliml_getarchives_join',$join,$r,$this->curlang);
		extract( $r, EXTR_SKIP );
		$this->get_archives_called = $r;
		if (isset($lang)) {
			if ("" == $lang ) { /* used for link */
				$this->get_archives_called[QUETAG] = $this->curlang;
			} else {
				$this->get_archives_called[QUETAG] = $lang;
			}
			$join = " INNER JOIN $wpdb->term_relationships as tr ON ($wpdb->posts.ID = tr.object_id) INNER JOIN $wpdb->term_taxonomy as tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) ";
		
		}
		return $join;
		
	}
	
	function xiliml_getarchives_where( $where, $r ) {
		global $wpdb;
		if (has_filter('xiliml_getarchives_where')) return apply_filters('xiliml_getarchives_where',$where,$r,$this->curlang);
		extract( $r, EXTR_SKIP );
		if (isset($lang)) {
			if ("" == $lang ) {
				$curlang = $this->curlang;
			} else {
				$curlang = $lang;
			}
			$reqtag = is_term( $curlang, TAXONAME );
				if (''!= $reqtag) {
					$wherereqtag = $reqtag['term_id'];
				} else {
					$wherereqtag = 0;	
				}
				$where .= " AND tt.taxonomy = '".TAXONAME."' ";
				$where .= " AND tt.term_id = $wherereqtag ";
		}		
		return $where;
	}
	
	/** *here basic translation - to improve depending theme features : use hook 'xiliml_get_archives_link' */
	function xiliml_get_archives_link( $link_html ) {
		if (has_filter('xiliml_link_translate_desc')) return apply_filters('xiliml_get_archives_link', $link_html,$this->get_archives_called, $this->curlang);
		extract( $this->get_archives_called, EXTR_SKIP );
		if ('' != $lang) {
			$permalink = get_option('permalink_structure');
			$sep = ('' == $permalink) ? "&amp;".QUETAG."=" : "?".QUETAG."=";
			if ($format != 'option' && $format != 'link' && $type != 'postbypost' && $type != 'alpha') {
				/* text extract */
				$i = preg_match_all("/'>(.*)<\/a>/Ui", $link_html, $matches,PREG_PATTERN_ORDER);
				$line = $matches[1][0];
				/* link extract */
				$i = preg_match_all("/href='(.*)' title/Ui", $link_html, $matches,PREG_PATTERN_ORDER);
				if ( '' == $type || 'monthly' == $type) {
					if ('' == $permalink) {
						$archivedate = str_replace(get_bloginfo('url').'/?' , "" , $matches[1][0]);
						$r = wp_parse_args( $archivedate, array());
						extract($r, EXTR_SKIP );
						$month = substr($m,-2);
						$year = substr($m,0,4);
					} else {
						/* Due to prevents post ID and date permalinks from overlapping using /date/ v 1.1.9 
						 * no / at end for "numeric" permalink giving /archives/date/2009/04
						 */
						$thelink = $matches[1][0];
						$i = preg_match_all("/\/([0-9]{4})\/([0-9]{2})/Ui", $thelink, $results,PREG_PATTERN_ORDER);
						if ($i) { //print_r($results);
							$month = $results[2][0];
							$year = $results[1][0];
						}
					}	
					$time = strtotime($month.'/1/'.$year);
					$line2print = the_xili_local_time('%B %Y',$time); /* use server local*/
					$link_html = str_replace($line , $line2print , $link_html);
				}	
				$link_html = str_replace("' titl" , $sep.$lang."' titl" , $link_html);	
			} elseif ($format == 'option') {
				/* need improve with regex */
				$link_html = str_replace("'>" , $sep.$lang."'>" , $link_html);
			}
		}	
		return $link_html;
	}
 
	 /**
	 * translate description of categories
	 *
	 * @since 0.9.0
	 * update 0.9.7 - 0.9.9.4
	 * can be hooked by filter add_filter('xiliml_link_translate_desc','yourfunction',2,4) in functions.php
	 *
	 *
	 */
	function xiliml_link_translate_desc( $description, $category=null, $context='' ) {
		if (has_filter('xiliml_link_translate_desc')) return apply_filters('xiliml_link_translate_desc',$description,$category,$context,$this->curlang);
	  	$translated_desc = ($this->curlang && ''!= $description) ? __($description, $this->thetextdomain) : $description ;
	 	return $translated_desc;
	}
	
	/**
	 * filters for wp_title() translation - single_cat_title - 
	 * since 1.4.1
	 *
	 */
	function xiliml_single_cat_title_translate ( $cat_name ) {
		if (has_filter('xiliml_single_cat_title_translate')) return apply_filters('xiliml_single_cat_title_translate',$cat_name);
		$translated = ($this->curlang && ''!= $cat_name) ? __($cat_name,$this->thetextdomain) : $cat_name;
	 	return $translated;	
	}
		
	/**
	 * Return the list of preferred languages for displaying pages (see in firefox prefs)
	 * thanks to php.net comments HTTP_ACCEPT_LANGUAGE
	 * @since 0.9.7.5
	 * 
	 * @return array (non sorted)
	 */
	function the_preferred_languages() {	 
		$preferred_languages = array();
			if(preg_match_all("#([^;,]+)(;[^,0-9]*([0-9\.]+)[^,]*)?#i",$_SERVER["HTTP_ACCEPT_LANGUAGE"], $matches, PREG_SET_ORDER)) {
				foreach($matches as $match) {
					if(isset($match[3])) {
						$preferred_languages[$match[1]] = floatval($match[3]);
					} else {
						$preferred_languages[$match[1]] = 1.0; 
					}
				}
				return $preferred_languages;	
			} else {
				return false;	
			}	
	}
	/**
	 * Return the lang defined by admin UI if no browser
	 *
	 * @since 1.0
	 *
	 */	
	function choice_of_home_selected_lang() {
		if ($this->browseroption == 'browser') {
			return choice_of_browsing_language();
		} elseif ($this->browseroption != '') { /* slug of the lang*/
			return $this->browseroption;
		} else {	
	 		return strtolower($this->default_lang);
		}
	}
	
	/**
	 * Return the list of preferred languages for displaying pages (see in firefox prefs)
	 * thanks to php.net comments HTTP_ACCEPT_LANGUAGE
	 * @since 0.9.7.5
	 * @update 0.9.9.4
	 * @return array (non sorted)
	 */
	function choice_of_browsing_language() {
		if (has_filter('choice_of_browsing_language')) return apply_filters('choice_of_browsing_language');
		if ( $this->browseroption != 'browser' ) return $this->choice_of_home_selected_lang(); /* in settings UI - after filter to hook w/o UI */
		$listofprefs = $this->the_preferred_languages();
		if (is_array($listofprefs)) {
			arsort($listofprefs, SORT_NUMERIC);
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			$sitelanguage = $this->match_languages ($listofprefs,$listlanguages);
			if ($sitelanguage) return $sitelanguage->slug;
			return strtolower($this->default_lang);
		} else {
			return strtolower($this->default_lang);
		}
	}
	
	function match_languages ( $listofprefs, $listlanguages ) {
		
			foreach($listofprefs as $browserlanguage => $priority) {
					/* match root languages to give similar in site  - first : five chars langs*/
					foreach($listlanguages as $sitelanguage) {
						if ($sitelanguage->slug == str_replace('-','_',$browserlanguage)) return $sitelanguage;
					}
			}
			foreach($listofprefs as $browserlanguage => $priority) {
					/* match root languages to give similar in site  - second : two first chars langs*/
					foreach($listlanguages as $sitelanguage) {
						if (str_replace('-','_',$browserlanguage) == substr($sitelanguage->slug,0,2)) return $sitelanguage;
					}	
			}
	}
	
	/**
	 * Choice of language when is_category and all languages
	 *
	 * @since 1.8.9.1
	 * called by get_curlang_action_wp
	 *
	 */
	function choice_of_categories_all_languages( $curlang ) {
		$choice = $this->xili_settings['allcategories_lang'];
		if ( $choice == "browser" ) {
			return $this->choice_of_browsing_language();
		} elseif ( $choice == "firstpost" ) {
			return $curlang ;
		} elseif ( $choice == "" ) {
			if ( function_exists('xl_choice_of_categories_all_languages') ) {
				return xl_choice_of_categories_all_languages () ;
			} else {
				return ''; // return without mo
			}
		}
		return $choice;
	}
	
	/**
	 * to encapsulate future method
	 *
	 * @since 1.8.9.1
	 * @param post_ID and lang slug
	 */
	function linked_post_in ( $fromID, $lang_slug ) {
		return get_post_meta( $fromID, QUETAG.'-'.$lang_slug, true ); // will be soon changed
	}
	
	/**
	 * if possible, translate the array of ID of sticky posts
	 *
	 * @since 1.6.1
	 * called by hook option_sticky_posts
	 *
	 */
	function translate_sticky_posts_ID( $original_array ) {
		if (!is_admin() && is_home() ) { // because impossible to register the value in admin UI - 
		// and because tracs http://core.trac.wordpress.org/ticket/14115
			if ($original_array != array()) {
				$translated_array = array();
				if (isset($_GET[QUETAG])) {  // $this_curlang is not yet set
					$curlang = $_GET[QUETAG]; 
				} else {
					$curlang = $this->choice_of_browsing_language(); // rule defined in admin UI
				}
				foreach ($original_array as $id) {
					$langpost = $this->get_cur_language($id); 
					$post_lang = $langpost[QUETAG];
					if ($post_lang != $curlang) { // only if necessary
						$trans_id = $this-> linked_post_in( $id, $curlang ) ; // get_post_meta($id, 'lang-'.$curlang, true);
						if ( '' != $trans_id ) { 
							$translated_array[] = $trans_id;
						} else {
							if ( $this->sticky_keep_original === true ) $translated_array[] = $id; 
							// set by webmaster  in theme functions
							// $xili_language->sticky_keep_original = true;
						}
					} else {
						$translated_array[] = $id;
					}
				}
				return $translated_array;
			} else {
				return $original_array;
			} 
		} else {
			return $original_array;
		}		
	}
	
	/**
	 * if possible, translate the ID of front_page post
	 *
	 * @since 1.7.0
	 * called by hook option_page_on_front
	 *
	 */
	function translate_page_on_front_ID ( $original_id ) {
		$showpage = get_option('show_on_front');
		if ( !is_admin() && "page" == $showpage) {
			if (isset($_GET[QUETAG])) {  // $this_curlang is not yet set
				$curlang = $_GET[QUETAG]; 
			} else {
				$curlang = $this->choice_of_browsing_language(); // rule defined in admin UI
			}
			
			$trans_id = $this-> linked_post_in( $original_id, $curlang ) ; // get_post_meta($original_id, 'lang-'.$curlang, true);
			//echo " ----".$original_id.'==== '.$trans_id ;
			if ( '' != $trans_id ) { 
				return $trans_id;
			} else {
				return $original_id;	
			}
		} else {
			return $original_id;
		}
	}
	
	
	/********************************** ADMIN UI ***********************************/
			
	/**
	 * add admin menu and associated pages of admin UI
	 *
	 * @since 0.9.0
	 * @updated 0.9.6 - only for WP 2.7.X - do registering of new meta boxes and JS
	 *
	 */
	function xili_add_pages() {
		/* browser title and menu title */
		 $this->thehook = add_options_page(__('xili-language plugin','xili-language'), __('Languages ©xili','xili-language'), 'manage_options', 'language_page', array(&$this,'languages_settings'));
		 add_action('load-'.$this->thehook, array(&$this,'on_load_page'));
	}
	
	function on_load_page() {
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('postbox');
			add_meta_box('xili-language-sidebox-1', __('Message','xili-language'), array(&$this,'on_sidebox_1_content'), $this->thehook , 'side', 'core');
			add_meta_box('xili-language-sidebox-2', __('Info','xili-language'), array(&$this,'on_sidebox_2_content'), $this->thehook , 'side', 'core');
			add_meta_box('xili-language-sidebox-4', __('Special','xili-language'), array(&$this,'on_sidebox_4_content'), $this->thehook , 'side', 'core');
			add_meta_box('xili-language-sidebox-mail', __('Mail & Support','xili-language'), array(&$this,'on_sidebox_mail_content'), $this->thehook , 'normal', 'low');
			if ( !is_multisite() )
			  add_meta_box('xili-language-sidebox-uninstall', __('Uninstall Options','xili-language'), array(&$this,'on_sidebox_uninstall_content'), $this->thehook , 'side', 'low');
	}

	/**
	 * Add action link(s) to plugins page
	 * 
	 * @since 0.9.3
	 * @author MS
	 * @copyright Dion Hulse, http://dd32.id.au/wordpress-plugins/?configure-link and scripts@schloebe.de
	 */
	function xililang_filter_plugin_actions( $links, $file ){
		static $this_plugin;
		if( !$this_plugin ) $this_plugin = plugin_basename(__FILE__);
		if( $file == $this_plugin ){
			$settings_link = '<a href="options-general.php?page=language_page">' . __('Settings') . '</a>';
			$links = array_merge( array($settings_link), $links); // before other links
		}
		return $links;
	}
	
	/* UI added in sidebar of post admin (write , edit)
	 *
	 * @since 0.9.0
	 * @updated 0.9.5 : add a no-lang radio - again in top of sidebar admin post's UI
	 * @updated 0.9.8.3 : if new post and checked in settings : default language = author's browser's language !
	 * @updated 1.3.0
	 * @updated 1.5.5, 1.7.0 post-type
	 */
	function xili_language_checkboxes() { 
		global $post_ID,$post,$wp_version ;
		//$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
		$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		if ($listlanguages) { //1.5.5
			if ($this->authorbrowseroption == 'authorbrowser') { // setting = select language of author's browser
				$listofprefs = $this->the_preferred_languages();
				if (is_array($listofprefs)) {
					arsort($listofprefs, SORT_NUMERIC);
					$sitelanguage = $this->match_languages ($listofprefs,$listlanguages);
					if ($sitelanguage) {
						$defaultlanguage = $sitelanguage->name;
					} else {
						$defaultlanguage = "";
					}	
					$mention = '('.__('Browser language', 'xili-language').')';
				} else {
					$defaultlanguage = ""; /* undefined */
				}	
			} else {
				$defaultlanguage = ""; /* undefined */
				$mention = "";
			}	
			$test = ($post->post_status == 'auto-draft') ? false : true ; //echo '---'.$post->post_status;	
			if ($test === true){
				$ress = wp_get_object_terms($post_ID, TAXONAME);
				$theid = '['.$post_ID.'] '; //1.5.5
				/*Array ( [0] => stdClass Object ( [term_id] => 18 [name] => [slug] => 18 [term_group] => 0 [term_taxonomy_id] => 19 [taxonomy] => language [description] => [parent] => 0 [count] => 1 ) )*/
				$obj_term = $ress[0];
				if ('' != $obj_term->name) :
					$curlangname = $obj_term->name;
				else :
					$curlangname = ""; /* when created before plugin */
				endif;
				
			} else {
				if (isset($_GET['xltgt_lang'])) {
					$curlangname = $_GET['xltgt_lang']; /* since 1.3.0 */
					$mention = '<br />('.sprintf(__('From other %s', 'xili-language'), $post->post_type).': '.$_GET['xlfrom_id'].'&nbsp;'.$_GET['xlfrom_lang'].')';
				} else {
					$curlangname = $defaultlanguage; /* new post */
				}
			}
			echo $theid.__('Selected language', 'xili-language').' : <strong>'.$curlangname.'</strong> '.(($test===false) ? $mention : "").'<br /><br /><div style="line-height:1.7em;">' ;
			$l = 2; /*link to bottom of sidebar*/
			foreach ($listlanguages as $language) { $l++ ; 
			if ( $l % 3 == 0 && $l != 3) { echo '<br />'; } 
			?> 
			<label style="border:solid 1px grey; margin:1px 0px; padding:3px 2px; width:79px; display:inline-block; " for="xili_language_check_<?php echo $language->slug ; ?>" class="selectit"><input id="xili_language_check_<?php echo $language->slug ; ?>" name="xili_language_set" type="radio" value="<?php echo $language->slug ; ?>"  <?php if($curlangname==$language->name) echo 'checked="checked"' ?> />&nbsp;<?php echo _e($language->description, 'xili-language'); ?></label>
			<?php  
			} /*link to top of sidebar*/?> 
			<br /><label style="border:solid 1px grey; margin:1px 0px; padding:3px 2px; width:167px; display:inline-block;" for="xili_language_check" class="selectit"><input id="xili_language_check" name="xili_language_set" type="radio" value="" <?php if($curlangname=="") echo 'checked="checked"' ?> />&nbsp;<?php _e('undefined','xili-language') ?></label><br /></div>
		  	<br /><small>© xili-language <?php echo XILILANGUAGE_VER; ?></small>
	<?php } else { 
		$settings_link = '<a href="options-general.php?page=language_page">' . __('Settings') . '</a>';
		echo '<p>'.__('The list of languages is not defined. Go to: ','xili-language').$settings_link.'</p>';
	}
	}
	
	/**
	 * to display the linked posts in post edit UI
	 *
	 * @since 0.9.8
	 * @updated 1.3.0, 1.7.0 post-type
	 *
	 */
	function xili_language_linked_posts() { 
		global $post_ID, $post, $wp_version;
		$postlang = '';
		$test = ($post->post_status == 'auto-draft') ? false : true ; 	
		if ($test === true){
			$ress = wp_get_object_terms($post_ID, TAXONAME);
			$obj_term = $ress[0];
			$postlang = ('' != $obj_term->slug) ? $obj_term->slug : ""; /* when created before plugin */
		} else {
			$postlang = ""; /* new post */
		}
		//$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
		$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		$post_type = $post->post_type ;
		?>
			<p><em><?php printf(__('ID of %ss in other languages:','xili-language'), $post_type); ?></em></p>
		<?php $theid = $post_type.' ID';
			$autosearchmess = sprintf(__('to auto search linked %ss. (read docs)','xili-language'), $post_type) ;
		?>
		<table width="100%" cellspacing="4" cellpadding="2">
		<thead>
		<tr ><th><?php _e('Language','xili-language'); ?></th><th align="left"><?php _e($theid,'xili-language'); ?></th><th align="left"><?php _e('Display','xili-language'); ?></th><th align="left"><?php _e('Edit'); ?></th></tr>
		</thead>
		<tbody id='the-linked' class='list:linked'>
			<?php
			
			$test = ($post->post_status == 'auto-draft') ? false : true ; 
		
		if ($test === true) $autosearch = get_post_meta($post_ID,'_xl-search-linked',true);
			foreach ($listlanguages as $language) {
				$output = "";
				$otherpost = "";
				$line = true;
				if ($language->slug == $postlang ) {
					if ($post->post_status == 'publish' || $post->post_status == 'pending') {
						$line = false;
					} else {
						$line = true;
					}	
				} 
				if ($line) {
					
					$test = ($post->post_status == 'auto-draft') ? false : true ; 
		
					if ($test === true) {
						$otherpost = $this->linked_post_in( $post_ID, $language->slug ) ; //get_post_meta($post_ID, QUETAG.'-'.$language->slug, true);
						$otherpostr = $otherpost; 
						/* since 1.3.0 - 1.4.1 */
						if ('' != $autosearch && "" != $postlang) { 
							$source_ID = $this->search_pinger_post($post_ID,QUETAG.'-'.$postlang,$language->term_id, $post_type);
						 	if (0 != $source_ID) { 
						 		$otherpost = $source_ID;
						 		$otherpostr = "-1"; /* to be refreshed */
						 	}	
						}
					} else { /* since 1.3.0 */
						if (isset($_GET['xlfrom_id'])) {
							if ($_GET['xlfrom_lang'] == $language->slug) {
								$otherpost = $_GET['xlfrom_id'];
								$otherpostr = "-1";
							} else {
								/* pre-fill linked posts from source post */
								$otherpost = $this->linked_post_in( $_GET['xlfrom_id'], $language->slug ) ; //get_post_meta($_GET['xlfrom_id'], QUETAG.'-'.$language->slug, true);
								$otherpostr = "-1";
							}
						}
					}
				
				?>
				<tr ><th>
				<label for="xili_language_<?php echo QUETAG.'-'.$language->slug ; ?>"><?php _e($language->description,'xili-language') ; ?>&nbsp;</label></th><td align="left"><input id="xili_language_<?php echo QUETAG.'-'.$language->slug ; ?>" name="xili_language_<?php echo QUETAG.'-'.$language->slug ; ?>"  value="<?php echo $otherpost; ?>" size="5" /><input type="hidden" name="xili_language_rec_<?php echo QUETAG.'-'.$language->slug ; ?>" value="<?php echo $otherpostr; ?>"/>
				
				<?php
				if ('' != $otherpost  ) {
					$output = "</td><td><a target='_blank' href='".get_permalink($otherpost)."' >"." ".__($language->description,'xili-language') ."</a></td><td><a target='_blank' href='post.php?action=edit&post=".$otherpost."' >"." ".__('Edit') ."</a></td></tr>";
				} else {
					$output = $this->newlinkedpost($postlang, $language->name); /* if possible */
				}
			}
				echo $output; 
			} 
			/* since 1.4.1 */
			
			if ($test === true && "" != $postlang) {
			?>	
			<tr ><th><?php _e('Check','xili-language'); ?></th><td><input id="xili_language_search_lang" name="xili_language_search_lang" type="checkbox" value="searchlinked" <?php if('' != $autosearch) echo 'checked="checked"' ?> /></td><td colspan = 2 ><small><?php echo$autosearchmess ; ?></small></td></tr>
			<?php } ?>
				</tbody></table>
			<br /><small>© xili-language</small> 
	<?php }
	
	/**
	 * to create a linked post in target language
	 *
	 * @since 1.3.0
	 * @updated 1.3.1 - Add New specific for pages - 1.5.5 - new post and post_type=page - 1.7.0 post_type
	 *
	 */
	function newlinkedpost( $postlang = "" , $targetlang = "" ) {
		global $post;
		
		$whatnew = 'post';
		$type = ($post->post_type == 'post') ? '' : 'post_type='.$post->post_type.'&';
		
		if ($post->post_status == 'publish' || $post->post_status == 'pending' || $post->post_status == 'draft') {
			if ( $postlang != strtolower($targetlang) ) {
			return "</td><td><small>".__('*','xili-language')."</small></td><td><a href='".$whatnew."-new.php?".$type."xlfrom_id=".$post->ID."&xlfrom_lang=".$postlang."&xltgt_lang=".$targetlang."' target='_blank' >".__('Add New')."</a></td><tr>";
			}
		} else {
			return "</td></tr>";
		}
	}
	
	/**
	 * to research post linking current post in the language
	 * @since 1.4.1
	 * @params ID of post, lang of this post as meta_key, lang of searched pinger post, type of post (page)
	 *
	 */
	function search_pinger_post ( $targetID, $langmeta, $pingerlang, $posttype ) {
		global $wpdb;
		
		$query = "SELECT ID FROM $wpdb->posts as pp LEFT JOIN $wpdb->postmeta as pm ON (pp.ID = pm.post_id) LEFT JOIN $wpdb->term_relationships as tr ON (pp.ID = tr.object_id) LEFT JOIN $wpdb->term_taxonomy as tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) WHERE 1=1 AND pm.meta_key = '$langmeta' AND pm.meta_value = '$targetID' AND pp.post_type = '$posttype' AND (pp.post_status IN ('publish','private','draft','pending')) AND tt.taxonomy = 'language' AND tt.term_id = $pingerlang ORDER BY pp.post_date DESC LIMIT 0, 1";
		//echo $query;
		$r = @$wpdb->get_col($query);
		//print_r($r);
		if (!empty($r)) {
			$id = $r[0]; /* if multiple take the most recent */
		} else {
		 	$id = 0;	
		}
//echo '->'.$id.$langmeta.$targetID.'</br>'; 
		return $id;
	}
	
	/** 
	 * display languages column in Posts/Pages list
	 *
	 * @updated 1.8.9
	 */
	function xili_manage_column( $name, $id ) {
		if($name != TAXONAME)
			return;
		$terms = wp_get_object_terms($id, TAXONAME);
		$first = true;
		foreach( $terms AS $term ) {
			if($first)
				$first = false;
			else
				echo ', ';
			if ( current_user_can ('activate_plugins') ) {	
				echo '<a href="' . 'options-general.php?page=language_page'.'" title="'.__('See list of languages…','xili-language').'" >'; /* see more precise link ?*/
				echo $term->name;
			} else {
				echo $term->name;
			}
			echo '</a><input type="hidden" id="'.QUETAG.'-'.$id.'" value="'.$term->slug.'" >'; // for Quick-Edit - 1.8.9
		}
	}
	function xili_manage_column_name( $cols ) {
		$ends = array('comments', 'date', 'rel', 'visible');
		$end = array();
		foreach( $cols AS $k=>$v ) {
			if(in_array($k, $ends)) {
				$end[$k] = $v;
				unset($cols[$k]);
			}
		}
		$cols[TAXONAME] = __('Language','xili-language');
		$cols = array_merge($cols, $end);
		return $cols;
	}

	
	/**
	 * Insert popup in quickedit at end (filter quick_edit_custom_box - template.php)
	 *
	 * @since 1.8.9
	 *
	 * hook with only two params - no $post_id - populated by.. || $type != 'post' || $type != 'page'
	 * style="margin:5px 0 2px 7px; padding:12px 6px; border:1px solid #ccc; width:100px; float:right;"
	 */
	function languages_custom_box ( $col, $type ) {
		
		if( $col != TAXONAME ) { 
         return;
    	}
    	$listlanguages = $this->get_listlanguages();
    	$margintop = ($type == 'page' ) ? '-40px' : '0';
		?>
		
		<fieldset style="background: #E4EAF8; margin: <?php echo $margintop; ?> 20px 2px 7px; padding:0 5px 2px; border:1px solid #ccc; width:100px; float:right;" ><legend><em><?php _e('Language','xili-language') ?></em></legend>
			<select name="xlpop" id="xlpop">
			<option value=""> <?php _e('undefined','xili-language') ?> </option>
			<?php foreach ($listlanguages as $language)  {
				echo '<option value="'.$language->slug.'">'.__($language->description, 'xili-language').'</option>';
			// no preset values now (see below)	
			}
			?>
			</select>
		</fieldset>
		
		
	<?php
	}
	
	/**
	 * workaround for insert value popup in quickedit
	 *
	 * @since 1.8.9
	 * keep value in hidden input in column see xili_manage_column
	 * setTimeout mandatory for popup DOM - adapted from http://nerdlife.net/boston-wordpress-meetup-example-code/
	 *
	 */
	function quick_edit_add_script () {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function() {
    			jQuery('a.editinline').live('click', function() {
       			var id = inlineEditPost.getId(this);
        		if ( jQuery('#<?php echo QUETAG ?>-' + id ).length ) {
        			var val = jQuery('#<?php echo QUETAG ?>-' + id ).val();
        			 
        			 setTimeout( 'jQuery("#xlpop").val("'+val+'")',.1);
        			 
        		}
    			});
    			
			});
		</script>
		<?php 
	}
	
	/**
	 * Add Languages selector in edit.php edit after Category Selector (hook: restrict_manage_posts)
	 *
	 * @since 1.8.9
	 *
	 */
	function restrict_manage_languages_posts () {
		$listlanguages = $this->get_listlanguages();
		?>
		<select name="<?php echo QUETAG ?>" id="<?php echo QUETAG ?>" class='postform'>
			<option value=""> <?php _e('View all languages','xili-language') ?> </option>
			
			<option value="*" <?php echo ( $_GET[QUETAG] == "*" ) ? "selected=selected" : "" ; ?> > <?php _e('Without language','xili-language') ?> </option>
			
			<?php foreach ($listlanguages as $language)  {
				$selected = ( $language->slug == $_GET[QUETAG] ) ? "selected=selected" : "" ;
				echo '<option value="'.$language->slug.'" '.$selected.' >'.__($language->description, 'xili-language').'</option>';
			}
			?>
			</select>
		<?php
	}
	/**
	 * Insert popup in BULK quickedit at end (filter bulk_edit_custom_box - template.php)
	 *
	 * @since 1.8.9.3
	 *
	 * hook with only two params - no $post_id - populated by.. || $type != 'post' || $type != 'page'
	 * 
	 */
	 function hidden_languages_custom_box ( $col, $type ) { 
		if( $col != TAXONAME ) {
         return;
    	} 
    	$listlanguages = $this->get_listlanguages();
    	$margintop = ($type == 'page' ) ? '-40px' : '0';
		?>
		
		<label class="alignright">
			<span class="title"><?php _e( 'Language','xili-language' ); ?></span>
			<select name="xlpop" id="xlpop">
			<option value="-1"> <?php _e('&mdash; No Change &mdash;') ?> </option>
			<option value="*"> <?php _e('undefined','xili-language') ?> </option>
			<?php foreach ($listlanguages as $language)  {
				echo '<option value="'.$language->slug.'">'.__($language->description, 'xili-language').'</option>';
			// no preset values now (see below)	
			}
			?>
			</select>
		</label>
	<?php
	}
	
	/**
	 * List custom post types
	 *
	 * @since 1.8.0
	 *
	 */
	function get_custom_desc() {
		$types = get_post_types(array('show_ui'=>1));
		if ( count($types) > 2 ) {
			$thecheck = array() ;
			foreach ( $types as $type) {
				if ( $type != 'page' && $type != 'post' ) {
					$custom = get_post_type_object ($type);
					$clabels = $custom->labels;
					$thecustoms[$type] = array ('name' => $custom->label, 'singular_name' => $clabels->singular_name, 'multilingual'=>''  ) ;
				}
			}
			return $thecustoms ;
		}
	}
	
	/**
	 *
	 * @since 1.8.8
	 *
	 *
	 */
	function multilingual_links_erase ( $lang_term_id ) {
		$languages = $this -> get_listlanguages();
		
		foreach ($languages as $language ) {
			if ( $language->term_id == $lang_term_id ) { 
				$lang_slug = $language->slug ;
				continue ;
			}
		}
		foreach ($languages as $language ) {
			// for other languages as this - delete postmeta linked to post of erased posts
			if ( $language->term_id != $lang_term_id) {
				$post_IDs = get_objects_in_term( array( $language->term_id ), array( TAXONAME ) );
				foreach ( $post_IDs as $post_ID ) {
						delete_post_meta( $post_ID, QUETAG.'-'.$lang_slug ) ;
				}			
			}
		}
		// posts
		$post_IDs = get_objects_in_term( array( $lang_term_id ), array( TAXONAME ) );
		foreach ( $post_IDs as $post_ID ) {
			// delete relationships posts
	 		wp_delete_object_term_relationships( $post_ID, TAXONAME );
		}
		// links of blogroll
		$links = get_objects_in_term( array( $lang_term_id ), array( 'link_'.TAXONAME ) );
		foreach ( $links as $link ) {	   
			wp_delete_object_term_relationships( $link, 'link_'.TAXONAME );
		}
	}
			
	/**
	 * to display the languages settings admin UI
	 *
	 * @since 0.9.0
	 * @updated 0.9.6 - only for WP 2.7.X - do new meta boxes and JS
	 *
	 */
	function languages_settings() { 
		global $wp_version ; // 1.8.5
		$formtitle = 'Add a language'; /* translated in form */
		$submit_text = __('Add &raquo;','xili-language');
		$cancel_text = __('Cancel');
		$action = '';
		$language = '';
		$msg = 0 ; /* 1.7.1 */
		if (isset($_POST['reset'])) {
			$action =$_POST['reset'];
		} elseif ( isset($_POST['updateoptions']) || isset($_POST['innavenable']) ) {
			$action ='updateoptions';
		} elseif ( isset($_POST['updateundefined'])) {
			$action ='updateundefined';
		} elseif ( isset($_POST['menuadditems'])) {
			$action ='menuadditems';	
		} elseif ( isset($_POST['sendmail']) ) { //1.8.5
			$action = 'sendmail' ; 
		} elseif ( isset($_POST['uninstalloption']) ) { //1.8.8
			$action = 'uninstalloption' ; 
		} elseif ( isset($_POST['action'])) {
			$action=$_POST['action'];
		} 
		
		if (isset($_GET['action'])) :
			$action=$_GET['action'];
			$term_id = $_GET['term_id'];
		endif;
		$message = $action ;
		//echo $action . '----';
		switch( $action ) {
			case 'updateundefined';
				check_admin_referer( 'xilimloptions' );
				$targetlang = $_POST['xili_language_toset'];
				$fromcats = $_POST['from_categories'];
				if (""!= $targetlang) {
					$q = xiliml_setlang_of_undefined_posts ($targetlang, $fromcats, 50);
					$message .= " _ $q ".__('posts are set in:','xili-language')." ".$targetlang." ".__("category")." =[$fromcats]";
				} else {
					$q = xiliml_setlang_of_undefined_posts ($targetlang, $fromcats, 50);
					$message .= " _ around $q ".__('posts are undefined in','xili-language')." ".__("category")."  = [$fromcats]";	
				}
				$actiontype = "reset";
				break;
			case 'menuadditems';	
				check_admin_referer( 'xilimloptions' );
				$this->xili_settings['navmenu_check_option2'] = $_POST['xili_navmenu_check_option2']; // 1.8.1
				
				// function below sidebox_4
				$result = $this->add_list_of_language_links_in_wp_menu($this->xili_settings['navmenu_check_option2']);
				$optionmessage .= ' - '. __('Go to Nav-menus in Themes to validate changes','xili-language').' ('.$result.')';
				$message .= $optionmessage ;
				$msg = 6;
				$actiontype = "reset";
				break;
			case 'updateoptions';
				check_admin_referer( 'xilimloptions' );
				$this->browseroption = $_POST['xili_language_check_option'];
				$this->authorbrowseroption = $_POST['xili_language_check_option_author'];
				$this->functions_enable = $_POST['xili_language_check_functions_enable'];
				$this->xili_settings['browseroption'] = $this->browseroption;
				$this->xili_settings['allcategories_lang'] = $_POST['allcategories_lang']; // 1.8.9.1
				$this->xili_settings['authorbrowseroption'] = $this->authorbrowseroption;
				$this->xili_settings['functions_enable'] = $this->functions_enable;
				$this->xili_settings['widget'] = $_POST['xili_language_widgetenable']; //1.8.8 
				if ( current_theme_supports( 'menus' ) ) {
					$menu_locations =  get_nav_menu_locations(); 
					$selected_menu_locations = array();
					foreach ($menu_locations as $menu_location => $location_id) {
						if ( $_POST['xili_navmenu_check_option_'.$location_id] == 'enable' ) {
							$selected_menu_locations[$menu_location]['navenable'] = 'enable';
							$selected_menu_locations[$menu_location]['navtype'] = $_POST['xili_navmenu_check_optiontype_'.$location_id]; //0.9.1
						}
					}
					$this->xili_settings['navmenu_check_options'] = $selected_menu_locations; // 2.1.0
					
					$this->xili_settings['in_nav_menu'] = $_POST['list_in_nav_enable']; // 1.6.0
					$this->xili_settings['page_in_nav_menu'] = $_POST['page_in_nav_enable']; // 1.7.1
					$this->xili_settings['args_page_in_nav_menu'] = $_POST['args_page_in_nav']; // 1.7.1
					$this->xili_settings['navmenu_check_option'] = $_POST['xili_navmenu_check_option'];
					$this->xili_settings['navmenu_check_optionp'] = $_POST['xili_navmenu_check_optionp'];
					$this->xili_settings['home_item_nav_menu'] = $_POST['xili_home_item_nav_menu']; // 1.8.9.2 
				// 1.8.1
				}
				$this->xili_settings['homelang'] = $_POST['xili_language_home_lang']; // 1.3.2 
				/* since 1.8.0 */
				$types = get_post_types(array('show_ui'=>1));
				if ( count($types) > 2 ) {
					$desc_customs = $this->get_custom_desc() ;
					foreach ( $desc_customs as $type => $desc_custom) {
						$desc_customs[$type]['multilingual'] = $_POST['xili_language_multilingual_custom_'.$type]; 
					}
					$this->xili_settings['multilingual_custom_post'] = $desc_customs ;
				} else {
					$this->xili_settings['multilingual_custom_post'] = array() ;	
				}
				/* widget settings */
				if ( current_theme_supports( 'widgets' ) ) {
					$link_cats = get_terms( 'link_category');
					$this->xili_settings['link_categories_settings']['all'] = ( $_POST['xili_language_link_cat_all'] == 'enable'  ) ? true : false ; 
					foreach ( $link_cats as $link_cat ) {
						$this->xili_settings['link_categories_settings']['category'][$link_cat->term_id] = (( $_POST['xili_language_link_cat_'.$link_cat->term_id] == 'enable'  ) ? true : false );
					}
				}
				/* domains switching settings 1.8.7 */
				foreach ( $this->xili_settings['domains'] as $domain => $state ) {
					if ( $_POST['xili_language_domains_'.$domain] == 'enable' ) {
						$this->xili_settings['domains'][$domain] = 'enable';
					} else {
						$this->xili_settings['domains'][$domain] = '';
					}
				}
				
				/* UPDATE OPTIONS */
				update_option('xili_language_settings', $this->xili_settings);
				/* messages */
				$optionmessage = " - ".sprintf(__("Options are updated: home language = %s, For Author language of a new post = %s, xilidev functions = %s, Automatic Nav Menu = %s, Selection of pages in Nav Menu = %s",'xili-language'), $this->browseroption, $this->authorbrowseroption, $this->functions_enable, $this->xili_settings['in_nav_menu'], $this->xili_settings['page_in_nav_menu']);
				$message .= $optionmessage ;
				$msg = 6;
				$this->insert_gold_functions (); 
				$actiontype = "reset";
				break;
				
			case 'sendmail'; // 1.8.5
				check_admin_referer( 'xilimloptions' ); 
				$this->xili_settings['url'] = $_POST['urlenable'];
				$this->xili_settings['theme'] = $_POST['themeenable'];
				$this->xili_settings['wplang'] = $_POST['wplangenable'];
				$this->xili_settings['version'] = $_POST['versionenable'];
				$this->xili_settings['xiliplug'] = $_POST['xiliplugenable'];
				update_option('xili_language_settings', $this->xili_settings);
				$contextual_arr = array();
				if ( $this->xili_settings['url'] == 'enable' ) $contextual_arr[] = "url=[".get_bloginfo ('home')."]" ;
				if ( isset($_POST['onlocalhost']) ) $contextual_arr[] = "url=local" ;
				if ( $this->xili_settings['theme'] == 'enable' ) $contextual_arr[] = "theme=[".get_option ('stylesheet')."]" ;
				if ( $this->xili_settings['wplang'] == 'enable' ) $contextual_arr[] = "WPLANG=[".WPLANG."]" ;
				if ( $this->xili_settings['version'] == 'enable' ) $contextual_arr[] = "WP version=[".$wp_version."]" ;
				if ( $this->xili_settings['xiliplug'] == 'enable' ) $contextual_arr[] = "xiliplugins=[". $this->check_other_xili_plugins() ."]" ;
				$contextual_arr[] = $_POST['webmestre']; // 1.9.1
				
				$headers = 'From: xili-language plugin page <' . get_bloginfo ('admin_email').'>' . "\r\n" ;
	   			if ( '' != $_POST['ccmail'] ) $headers .= 'Cc: <'.$_POST['ccmail'].'>' . "\r\n";
	   			$headers .= "\\";
	   			$message = "Message sent by: ".get_bloginfo ('admin_email')."\n\n" ;
	   			$message .= "Subject: ".$_POST['subject']."\n\n" ;
	   			$message .= "Topic: ".$_POST['thema']."\n\n" ;
	   			$message .= "Content: ".$_POST['mailcontent']."\n\n" ;
	   			$message .= "Checked contextual infos: ". implode ( ' ** ', $contextual_arr ) ."\n\n" ;
	   			$message .= "This message was sent by webmaster in xili-language plugin settings page.\n\n";
	   			$message .= "\n\n"; 
	   			$result = wp_mail('contact@xiligroup.com', $_POST['thema'].' from xili-language v.'.XILILANGUAGE_VER.' plugin settings page.' , $message, $headers );
				$message = __('Email sent.','xili-language');
				$msg = 7;
				$emessage = sprintf( __( 'Thanks for your email. A copy was sent to %s (%s)','xili-language' ), $_POST['ccmail'], $result ) ;
				break;
			
			case 'uninstalloption' ; // 1.8.8 see Uninstall Options metabox in sidebar
				$this->xili_settings['delete_settings'] = $_POST['delete_settings'];
				update_option('xili_language_settings', $this->xili_settings);
				break;
				
			case 'add';
				check_admin_referer( 'xilimloptions' );
				$term = $_POST['language_name'];
				if ("" != $term ) {
					$slug = $_POST['language_nicename'];
					$args = array( 'alias_of' => '', 'description' => $_POST['language_description'], 'parent' => 0, 'slug' =>$slug );
				    $res = wp_insert_term( $term, TAXONAME, $args);
					if ( is_wp_error($res) ) {
						$theids = get_term($term, TAXONAME);
					} else { 
						$theids = $res;
					}
					wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);
				    update_term_order ($theids['term_id'],$this->langs_group_tt_id,$_POST['language_order']);
				    
				    $this->xili_settings['langs_list_status'] = "added"; // 1.6.0 
				    $lang_ids = $this->get_lang_ids();				
				    $this->available_langs = $lang_ids ;
				    $this->xili_settings['available_langs'] = $this->available_langs;
				    $this->xili_settings['lang_features'][$slug]['hidden'] = $_POST['language_hidden'];
				    $this->xili_settings['lang_features'][$slug]['charset'] = $_POST['language_charset'];
					update_option('xili_language_settings', $this->xili_settings);
				    $actiontype = "add";
				    $message .= " - ".__('A new language was added.','xili-language');
				    $msg = 5;
				}
			    break;
			    
			case 'edit';
				
			    $actiontype = "edited";
			    $language = get_term_and_order ($term_id,$this->langs_group_tt_id,TAXONAME);
			    $submit_text = __('Update &raquo;');
			    $formtitle = 'Edit language';
			    $message .= " - ".__('Language to update.','xili-language');
			    $msg = 3;
			    break;
			    
			case 'edited';
				check_admin_referer( 'xilimloptions' );
			    $actiontype = "add";
			    $term = $_POST['language_term_id'];
			    $slug = $_POST['language_nicename'];
				$args = array( 'alias_of' => '', 'description' => $_POST['language_description'], 'parent' => 0, 'slug' => $slug);
				$theids = wp_update_term( $term, TAXONAME, $args);
				if ( !is_wp_error($theids) ) {
					wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);
					update_term_order ($theids['term_id'],$this->langs_group_tt_id,$_POST['language_order']);
					$this->xili_settings['langs_list_status'] = "edited"; // 1.6.0 
					$this->xili_settings['lang_features'][$slug]['hidden'] = $_POST['language_hidden'];
				    $this->xili_settings['lang_features'][$slug]['charset'] = $_POST['language_charset'];
					update_option('xili_language_settings', $this->xili_settings);
					$message .= " - ".__('A language was updated.','xili-language');
					$msg = 4 ;
				} else {
					$msg = 8 ;
				}
			    break;
			    
			case 'delete';
			    $actiontype = "deleting";
			    $submit_text = __('Delete &raquo;','xili-language');
			    $formtitle = 'Delete language ?'; 
			    $language = get_term_and_order ($term_id,$this->langs_group_tt_id,TAXONAME);
			    $message .= " - ".__('A language to delete.','xili-language');
			    $msg = 1;
			    break;
			    
			case 'deleting';
				check_admin_referer( 'xilimloptions' );
			    $actiontype = "add";
			    $term_id = $_POST['language_term_id'];
			    $slug = $_POST['language_nicename'];
			    if ( $_POST['multilingual_links_erase'] == 'erase' ) {
			    	$this->multilingual_links_erase ( $term_id ); // as in uninstall.php - 1.8.8
			    }
			    
			    wp_delete_object_term_relationships( $term_id, TAXOLANGSGROUP ); // degrouping
			    wp_delete_term( $term_id, TAXONAME, $args);
			    
			    $this->xili_settings['langs_list_status'] = "deleted"; // 1.6.0 
			    $lang_ids = $this->get_lang_ids();				
				$this->available_langs = $lang_ids ;
				$this->xili_settings['available_langs'] = $this->available_langs;
				unset ( $this->xili_settings['lang_features'][$slug] );
				update_option('xili_language_settings', $this->xili_settings);
			    $message .= " - ".__('A language was deleted.','xili-language');
			    $msg = 2;
			    break; 
			case 'reset';    
			    $actiontype = "add";
			    break;
			default :
			    $actiontype = "add";
			    $message .= __('Find above the list of languages.','xili-language');
			    
			    
		}
		/* register the main boxes always available */
		add_meta_box('xili-language-normal-1', __('List of languages','xili-language'), array(&$this,'on_normal_1_content'), $this->thehook , 'normal', 'high'); 
		add_meta_box('xili-language-normal-2', __('Language','xili-language'), array(&$this,'on_normal_2_content'), $this->thehook , 'normal', 'high');
		add_meta_box('xili-language-sidebox-3', __('Settings','xili-language'), array(&$this,'on_sidebox_3_content'), $this->thehook , 'side', 'high');
		
		
		$themessages[1] = __('A language to delete.','xili-language');
		$themessages[2] = __('A language was deleted.','xili-language');
		$themessages[3] = __('Language to update.','xili-language');
		$themessages[4] = __('A language was updated.','xili-language');
		$themessages[5] = __('A new language was added.','xili-language');
		$themessages[6] = $optionmessage ;
		$themessages[7] = __('Email sent.','xili-language');
		$themessages[8] = __('Error when updating.','xili-language');
		
		/* form datas in array for do_meta_boxes() */
		$data = array(
			'message'=>$message, 'action'=>$action, 'formtitle'=>$formtitle, 'language'=>$language,'submit_text'=>$submit_text,'cancel_text'=>$cancel_text,'browseroption'=>$this->browseroption, 'authorbrowseroption'=>$this->authorbrowseroption , 'functions_enable'=>$this->functions_enable, 'customs_enable' => $this->customs_enable,
			'list_in_nav_enable' => $this->xili_settings['in_nav_menu'],
			'page_in_nav_enable' => $this->xili_settings['page_in_nav_menu'],
			'args_page_in_nav' => $this->xili_settings['args_page_in_nav_menu'], 'emessage'=>$emessage,
			'language_features' => $this->xili_settings['lang_features'][$language->slug]
		);
		?>
		
		<div id="xili-language-settings" class="wrap" style="min-width:750px">
			<?php screen_icon('options-general'); ?>
			<h2><?php _e('Languages','xili-language') ?></h2>
			<?php if (0!= $msg ) { ?>
			<div id="message" class="updated fade"><p><?php echo $themessages[$msg]; ?></p></div>
			<?php } ?>
			<form name="add" id="add" method="post" action="options-general.php?page=language_page">
				<input type="hidden" name="action" value="<?php echo $actiontype ?>" />
				<?php wp_nonce_field('xili-language-settings'); ?>
				<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); 
				/* 0.9.9.2 add has-right-sidebar for next wp 2.8*/ ?>
				<div id="poststuff" class="metabox-holder has-right-sidebar">
					<div id="side-info-column" class="inner-sidebar">
						<?php do_meta_boxes($this->thehook, 'side', $data); ?>
					</div>
				
					<div id="post-body" class="has-sidebar has-right-sidebar">
						<div id="post-body-content" class="has-sidebar-content" style="min-width:360px">
					
	   					<?php do_meta_boxes($this->thehook, 'normal', $data); ?>
						</div>
					<h4><a href="http://dev.xiligroup.com/xili-language" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)).'/xililang-logo-32.png'; ?>" alt="xili-language logo"/>  xili-language</a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2007-2011 - v. <?php echo XILILANGUAGE_VER; ?></h4>		
					</div>
				</div>
		</form>
		</div>
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->thehook; ?>');
				// for examples list
				$('#language_name_list').change(function() {
                 	var x = $(this).val();
      				$('#language_name').val(x);
      				var x = $(this).val();
      				x = x.toLowerCase();
      				$('#language_nicename').val(x);
      				var v = $('#language_name_list option:selected').text();
      				v = v.substring(0,v.indexOf(" (",0));
      				$('#language_description').val(v);
    			});
			});
			//]]>
		</script> 
	<?php	//end settings div
		}
	
		
	/**
	 * Set language plugin 
	 * 
	 *
	 * @updated 1.1.9
	 * also include automatic search of domain and lang subfolder in current theme
	 */
	function init_textdomain() {
	/*multilingual for admin pages and menu*/
		load_plugin_textdomain('xili-language',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
		
		/* in wp3 multisite - don't use constant - for backward compatibility keep it in mono*/			
		if ( '' != $this->thetextdomain ) { 
			if (!is_multisite() && !defined('THEME_TEXTDOMAIN') ) define('THEME_TEXTDOMAIN',$this->thetextdomain); // for backward compatibility;
			if (is_admin()) {
				$this->xili_settings['theme_domain'] = $this->thetextdomain;
				update_option('xili_language_settings', $this->xili_settings);
			}
		} else {
			$this->domaindetectmsg = __('no load_theme_textdomain in functions.php','xili-language');
		}
		
	}
	
	function searchpath( $path, $filename ) {
		$this->xili_settings['langs_folder'] = str_replace($this->get_template_directory,'',$path);
	}
	
	/**
	 * Reset values when theme was changed... updated by previous function
	 * @since 1.1.9
	 */ 
	function theme_switched ( $theme ) {
		$this->xili_settings['langs_folder'] ="";
		$this->xili_settings['theme_domain'] =""; /* to force future search in new theme */
		update_option('xili_language_settings', $this->xili_settings);
	}
	
	/**
	 * private functions for languages_settings 
	 * @since 0.9.6
	 *
	 * fill the content of the boxes (right side and normal)
	 * 
	 */
	function  on_sidebox_1_content( $data ) { 
		extract($data);
		?>
	 	<h4><?php _e('Note:','xili-language') ?></h4>
		<p><?php echo $message;?></p>
		<?php
	}
	
	/** 
	 * info box 
	 */
	function  on_sidebox_2_content() { ?>
	 	
		<p><?php _e("This plugin was developed with the taxonomies, terms tables and tags specifications. <br /> Here a new taxonomy was created and used for languages of posts and pages. <br /> New radiobuttons are available in Post (and Page) write and edit admin pages for selection by author and also in Quick Edit mode of Posts list. It is updated for WP 3.1 since 1.8.8 version.",'xili-language') ?></p>
		<?php
	}	
	
	/** *where to choose if browser language preferences is tested or not */
	function  on_sidebox_3_content( $data ) { 
		extract( $data );
		/* 1.0 browser - default - languages */
		?>
		<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo _e('Select language of the home page', 'xili-language'); ?></legend>
			<select name="xili_language_check_option" id="xili_language_check_option" style="width:100%;">
				<?php  if ($browseroption == 'browser')
						$checked = 'selected = "selected"';
						else 
						$checked = '';
				?>
				<option value="" ><?php _e('Software defined','xili-language'); ?></option>
				<option value="browser" <?php echo $checked; ?> ><?php _e("Language of visitor's browser",'xili-language'); ?></option>
				<?php $listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
			foreach ($listlanguages as $language) {
				if ($browseroption == $language->slug) 
						$checked = 'selected = "selected"';
					else 
						$checked = '';
				echo '<option value="'.$language->slug.'" '.$checked.' >'.__($language->description,'xili-language').'</option>';
			}
				?>
			</select>
			<?php  if ('page' != get_option('show_on_front')) { ?>
				<br /> &nbsp;&nbsp;<label for="xili_language_home_lang"><?php _e('Modify home query','xili-language') ?> <input id="xili_language_home_lang" name="xili_language_home_lang" type="checkbox" value="modify" <?php if($this->xili_settings['homelang'] == 'modify') echo 'checked="checked"' ?> /></label>
				<?php }  ?>
		</fieldset>
		<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo _e("Theme's language when categories in 'all'", 'xili-language'); ?></legend>
			<label for="allcategories_lang" >
			<select name="allcategories_lang" id="allcategories_lang" style="width:100%;">
				<?php $allcategories_lang = $this->xili_settings['allcategories_lang']; ?>
				<option value="" ><?php _e('Software defined','xili-language'); ?></option>
				<option value="browser" <?php echo  ( ($allcategories_lang == 'browser') ? 'selected = "selected"' : '' ) ; ?> ><?php _e("Language of visitor's browser",'xili-language'); ?></option>
				<option value="firstpost" <?php echo  ( ($allcategories_lang == 'firstpost') ? 'selected = "selected"' : '' ) ; ?> ><?php _e("Language of first post in loop",'xili-language'); ?></option>
				<?php $listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
				foreach ($listlanguages as $language) {
					if ($allcategories_lang == $language->slug) 
						$checked = 'selected = "selected"';
					else 
						$checked = '';
				echo '<option value="'.$language->slug.'" '.$checked.' >'.__($language->description,'xili-language').'</option>';
			}
				?>
			</select></label>
	
		</fieldset>
		<br /><br />
		<label for="xili_language_check_option_author" class="selectit"><input id="xili_language_check_option_author" name="xili_language_check_option_author" type="checkbox" value="authorbrowser"  <?php if($authorbrowseroption=='authorbrowser') echo 'checked="checked"' ?> /> <?php echo _e('For new post, pre-select by default: browser language of author', 'xili-language'); ?></label>
		<br /><br />
		<?php if ( file_exists(XILIFUNCTIONSPATH) ) { /* test if folder exists - ready to add functions.php inside - since 1.0 */?>
		<label for="xili_language_check_functions_enable" class="selectit"><input id="xili_language_check_functions_enable" name="xili_language_check_functions_enable" type="checkbox" value="enable"  <?php if($functions_enable =='enable') echo 'checked="checked"' ?> /> <?php echo _e('Enable gold functions', 'xili-language'); ?></label>&nbsp;&nbsp;
		<?php } else {	
		echo '<input type="hidden" name="xili_language_check_functions_enable" value="'.$functions_enable.'" />';
		} 
		?>
		<br /><br />
		<?php //print_r (get_post_type_object( 'book' ) ); 
		$types = get_post_types(array('show_ui'=>1));
		
		if ( count($types) > 2 ) {
			$thecheck = array() ;
			$thecustoms = $this->get_custom_desc() ;
			foreach ( $thecustoms as $type => $thecustom) { 
					$thecheck[] = $type ;
			}
			$clabel = implode(', ', $thecheck);
		?>
		<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo _e('Multilingual custom posts', 'xili-language'); ?></legend>
		<?php ( count($thecheck) == 1 ) ? printf(__('One custom post (%s) is available.','xili-language'), $clabel ) : printf(__('More than one custom post (%s) are available.','xili-language'), $clabel );
		?>
		<br /><?php echo _e('Check the custom to enable multilanguage features.', 'xili-language'); ?><br /><br />
		<?php 
		$customs_options = $this->xili_settings['multilingual_custom_post'];
		foreach ( $thecustoms as $type => $thecustom) { 
			$customs_enable = ( isset($customs_options[$type]) ) ? $customs_options[$type]['multilingual'] : '';	
		?>	
		<label for="xili_language_multilingual_custom_<?php echo $type; ?>" class="selectit"><input id="xili_language_multilingual_custom_<?php echo $type; ?>" name="xili_language_multilingual_custom_<?php echo $type; ?>" type="checkbox" value="enable"  <?php if($customs_enable =='enable') echo 'checked="checked"' ?> /> <?php echo $thecustom['singular_name']; ?></label><br />
		<?php } ?>
		
		</fieldset>	
		<?php }
		if ( current_theme_supports( 'widgets' ) ) {  // 1.8.8 ?>
			<br /><label for="xili_language_widgetenable" class="selectit"><input id="xili_language_widgetenable" name="xili_language_widgetenable" type="checkbox" value="enable"  <?php if($this->xili_settings['widget'] =='enable') echo 'checked="checked"' ?> /> <?php echo _e('Enable widgets', 'xili-language'); ?></label>
		<?php } else { 
			echo '<br /><small>'.__('Current theme has no widgets support.','xili-language').'</small>';		
			echo '<input type="hidden" name="xili_language_widgetenable" value="'.$this->xili_settings['widget'].'" />';
		}  
		if ( current_theme_supports( 'widgets' ) ) {  // theme widget enable
			$link_cats = get_terms( 'link_category');
			$cat_settings = $this->xili_settings['link_categories_settings'];
			?>	
			<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo _e('Bookmarks widget settings', 'xili-language'); ?></legend>
			<?php echo _e("Check the bookmark's categories where to enable multilanguage features.", "xili-language"); ?><br /><br />
			
			<label for="xili_language_link_cat_all" class="selectit"><input id="xili_language_link_cat_all" name="xili_language_link_cat_all" type="checkbox" value="enable"  <?php if( $cat_settings['all'] == 'enable') echo 'checked="checked"' ?> /> <?php _e('All Links'); ?></label><br />
			<?php
			foreach ( $link_cats as $link_cat ) {
				
			?>
			<label for="xili_language_link_cat_<?php echo intval($link_cat->term_id) ?>" class="selectit"><input id="xili_language_link_cat_<?php echo intval($link_cat->term_id) ?>" name="xili_language_link_cat_<?php echo intval($link_cat->term_id) ?>" type="checkbox" value="enable"  <?php echo ( $cat_settings['category'][$link_cat->term_id]  == 'enable' ? ' checked="checked"' : '' ) ?> /> <?php echo $link_cat->name ; ?></label><br />
			
			<?php	
			}
			?>
			
			
			</fieldset>
		<?php } ?>
		<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo _e('Translation domains settings', 'xili-language'); ?></legend>
			<?php echo _e("For experts in multilingual CMS: Check to modify domains switching.", "xili-language"); ?><br /><br />
			<?php
			foreach ( $this->xili_settings['domains'] as $domain => $state ) {
				$domaininlist = ( $domain == 'all' ) ? __( 'Switch all domains excepted default WP','xili-language' ) : $domain ;
			?>
			<label for="xili_language_domains_<?php echo $domain ; ?>" class="selectit"><input id="xili_language_domains_<?php echo $domain ; ?>" name="xili_language_domains_<?php echo $domain ; ?>" type="checkbox" value="enable"  <?php echo ( $state  == 'enable' ? ' checked="checked"' : '' ) ?> /> <?php echo $domaininlist ; ?></label><br />
			
			<?php	
			}
			if ( $this->show ) print_r( $this->arraydomains ) ;
			 ?>
		</fieldset>
	 	<div id='formstatus'></div><span id='loading' class='hidden'><?php _e('Updating...','xili-language') ?></span>
	 	<div class='submit'>
		<input id='updateoptions' name='updateoptions' type='submit' tabindex='6' value="<?php _e('Update','xili-language') ?>" /></div>
		<?php wp_nonce_field('xilimloptions'); ?>
		<div style="clear:both; height:1px"></div><?php
	}
	
	/** *Actions box *menu *gold options */
	function  on_sidebox_4_content( $data ) { 
		extract($data);
		$template_directory = $this->get_template_directory;
		if ( function_exists('is_child_theme') && is_child_theme() ) { // 1.8.1 and WP 3.0
			$theme_name = get_option("stylesheet").' '.__('child of','xili-language').' '.get_option("template"); 
		} else {
			$theme_name = get_option("template"); 
		}
	 	?>
	 	<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo __("Theme's informations:",'xili-language'); ?></legend>
	 	<strong><?php echo ' - '.$theme_name.' -'; ?></strong>
	 	<p><?php 
	 		if ("" != $this->thetextdomain) {
	 			echo __('theme_domain:','xili-language').' '.$this->thetextdomain.'<br />'.__('as function like:','xili-language').'<i> _e(\'-->\',\''.$this->thetextdomain.'\');</i>'; }
	 		else {
	 			echo '<span style="color:red">'; _e('Theme domain NOT defined','xili-language');echo '</span>';
	 			if (''!=$this->domaindetectmsg) echo '<br /><span style="color:red">'; echo $this->domaindetectmsg.'</span>';
	 		} ?><br />
	 	<?php echo __("Languages sub-folder:",'xili-language').' '.$this->xili_settings['langs_folder']; ?><br />
	 	<?php _e('Available MO files:','xili-language'); echo '<br />';
	 	if ( file_exists( $template_directory ) ) // when theme was unavailable
	 		$this->find_files($template_directory, "/.mo$/", array(&$this,"available_mo_files")) ;
	 	
	 	if ( $this->ltd === false )	echo '<br /><span style="color:red">'.__('CAUTION: no load_theme_textdomain() in functions.php - review the content of file in the current theme or choose another canonical theme.','xili-language').'</span>'; 
	 		
	 		?>
	 	</p>
	 	</fieldset>
	 	<br />
	 	<?php if ( current_theme_supports( 'menus' ) ) { ?>
	 	<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo __("Nav menu: Home links in each language",'xili-language'); ?></legend>
	 		<?php $menu_locations =  get_nav_menu_locations(); echo $this->xili_settings['navmenu_check_option'];
		 	$selected_menu_locations = $this->xili_settings['navmenu_check_options'];
		 	if ( is_array( $menu_locations ) ) {
		 	?>
		 	<p><?php _e('Choose location(s) of nav menu(s) where languages list will be automatically inserted:','xili_language_ms'); ?></p>
		 	<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;">
			<?php
			if ( $this->this_has_filter('xl_language_list') ) {	// is list of options described
				$this->langs_list_options = array();
				do_action('xili_language_list_options', $theoption); // update the list of external action
			}	
			foreach ( $menu_locations as $menu_location => $location_id ) { 
				$locations_enable = ( isset($selected_menu_locations[$menu_location]) ) ? $selected_menu_locations[$menu_location]['navenable'] : '';
				if ( $locations_enable == 'enable' || ( !isset($this->xili_settings['navmenu_check_options']) &&$this->xili_settings['navmenu_check_option'] ==  $menu_location ) )
						$checked = 'checked="checked"'; // ascendant compatibility ( !isset($this->xili_settings['navmenu_check_options']) && 
					else 
						$checked = '';
				?>
				<label for="xili_navmenu_check_option_<?php echo $location_id; ?>" class="selectit"><input id="xili_navmenu_check_option_<?php echo $location_id; ?>" name="xili_navmenu_check_option_<?php echo $location_id; ?>" type="checkbox" value="enable"  <?php echo $checked; ?> /> <?php echo $menu_location; ?></label> - 
				<label for="xili_navmenu_check_optiontype_<?php echo $location_id; ?>"><?php _e('Type','xili_language_ms' ) ?>:
				<select name="xili_navmenu_check_optiontype_<?php echo $location_id; ?>" id="xili_navmenu_check_optiontype_<?php echo $location_id; ?>">
				<?php
				if ( $this->langs_list_options == array() ) {
						echo '<option value="" >default</option>';
				} else {
					
					foreach ($this->langs_list_options as $typeoption) {
						if ( false !== strpos( $typeoption[0], 'navmenu' ) ) {
							$seltypeoption = $this->xili_settings['navmenu_check_options'][$menu_location]['navtype'];
							$selectedoption = ($seltypeoption == $typeoption[0]) ? 'selected = "selected"':'';
							echo '<option value="'.$typeoption[0].'" '.$selectedoption.' >'.$typeoption[1].'</option>';
						}
					}
				}
				
				?>
				
				</select></label><br />
				<?php // focus
			} 
				?>
			</fieldset>
			<br />	<br />
	 		<label for="list_in_nav_enable" class="selectit"><input id="list_in_nav_enable" name="list_in_nav_enable" type="checkbox" value="enable"  <?php if($list_in_nav_enable =='enable') echo 'checked="checked"' ?> /> <?php echo _e('Add list in nav menu', 'xili-language'); ?></label><br />
	 		
	 		<br />
	 		<label for="xili_home_item_nav_menu" class="selectit"><input id="xili_home_item_nav_menu" name="xili_home_item_nav_menu" type="checkbox" value="modify"  <?php if($this->xili_settings['home_item_nav_menu'] =='modify') echo 'checked="checked"' ?> /> <?php echo _e('Menu Home item with lang.', 'xili-language'); ?></label><br />
	 		
	 		<hr /><br />
	 		
	 		
	 		<p><?php _e('Choose location of nav menu where sub-selection of pages list will be automatically inserted according current displayed language:','xili-language'); ?></p>
	 		<select name="xili_navmenu_check_optionp" id="xili_navmenu_check_optionp" style="width:100%;">
			<?php	
			foreach ($menu_locations as $menu_location => $location_id) {
				if ($this->xili_settings['navmenu_check_optionp'] == $menu_location ) 
						$checked = 'selected = "selected"';
					else 
						$checked = '';
				
				echo '<option value="'.$menu_location.'" '.$checked.' >'.$menu_location.'</option>';
			} 
				?>
			</select> 
			<br />	<br />
	 		<label for="page_in_nav_enable" class="selectit"><input id="page_in_nav_enable" name="page_in_nav_enable" type="checkbox" value="enable"  <?php if($page_in_nav_enable =='enable') echo 'checked="checked"' ?> /> <?php echo _e('Add selection of pages in nav menu', 'xili-language'); ?></label><br /><br />
	 		
	 		<label for="args_page_in_nav" class="selectit"><?php echo _e('Args', 'xili-language'); ?> : <input id="args_page_in_nav" name="args_page_in_nav" type="text" value="<?php echo $args_page_in_nav ?>"  /> </label>
		 	<div class="submit"><input  id='innavenable' name='innavenable' type='submit' value="<?php _e('Update','xili-language') ?>" /></div>
		 	<?php } else {
		 		printf (__("This theme doesn't contain active Nav Menu. List of languages cannot be automatically added.","xili-language"));
		 		echo '<br />';printf (__("See <a href=\"%s\" title=\"Menu Items definition\">Appearance Menus activation</a> settings.","xili-language"), "nav-menus.php");
		 	} ?>
		</fieldset>
	 	<br /> 	
	 	
	 	<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo __("Theme's nav menu items settings:",'xili-language'); ?></legend>
		 	<p><?php
		 	if ( $menu_locations ) {
		 	$loc_count = count( $menu_locations );
		 	printf (__("This theme (%s) contains %d Nav Menu(s).",'xili-language'), $theme_name, $loc_count); ?>
		 	<p><?php _e('Choose nav menu where languages list will be manually inserted:','xili-language'); ?></p>
		 	<select name="xili_navmenu_check_option2" id="xili_navmenu_check_option2" style="width:100%;">
			<?php	
			foreach ($menu_locations as $menu_location => $location_id) {
				if ($this->xili_settings['navmenu_check_option2'] == $menu_location ) 
						$checked = 'selected = "selected"';
					else 
						$checked = '';
				
				echo '<option value="'.$menu_location.'" '.$checked.' >'.$menu_location.'</option>';
			}
				?>
			</select>
			<br />	<br />
		 	<?php
		 	echo '<br />';printf (__("See <a href=\"%s\" title=\"Menu Items definition\">Appearance Menus</a> settings.","xili-language"), "nav-menus.php");
		 	if($list_in_nav_enable =='enable') {
		 			echo '<br /><span style="color:red">'; _e('Be aware that list is already automatically inserted (see above) !','xili-language'); echo '</span>'; }
		 	echo '<br />'; _e('Do you want to add list of language links at the end ?','xili-language'); echo '<br />';
		 	?>
		 	</p>
			<div class="submit"><input  id='menuadditems' name='menuadditems' type='submit' value="<?php _e('Add menu items','xili-language') ?>" /></div>
		 	
		 	<?php } else {
		 		printf (__("This theme doesn't contain active Nav Menu.","xili-language"));
		 		echo '<br />';printf (__("See <a href=\"%s\" title=\"Menu Items definition\">Appearance Menus</a> settings.","xili-language"), "nav-menus.php");
		 	} ?>
	 	</fieldset>
	 	<?php } ?>
		<p><?php _e("Special Gold Actions",'xili-language') ?></p>
		
		<?php
		//echo  '---'.$this->functions_enable;
		if ($this->functions_enable !='' && function_exists('xiliml_setlang_of_undefined_posts')) {
			xiliml_special_UI_undefined_posts ($this->langs_group_id);
		}
	}
	
	/**
	 * to add links in current menu of twentyten
	 *
	 * 
	 *
	 */
	function add_list_of_language_links_in_wp_menu ($location) {
		$defaultarray = array(
			'menu-item-type' => 'custom',
			'menu-item-title' => '',
			'menu-item-url' => '',
			'menu-item-description' => '',
			'menu-item-status' => 'publish');
		$url = get_bloginfo('url') ;
		$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		$langdesc_array = array();
		foreach ($listlanguages as $language){
			$langdesc_array[] = $language->description;
		}
		/* detect menu inside location */
		$menu_locations =  get_nav_menu_locations();
		$menuid = $menu_locations[$location];
		$menuitem = wp_get_nav_menu_object($menuid);
		$items = get_objects_in_term( $menuitem->term_id, 'nav_menu' );
		$nothere = true;
		if ( ! empty( $items ) ) {
			$founditems = wp_get_nav_menu_items($menuid); //try to see if a previous insert was done
			foreach ($founditems as $item) {
		 		if ($item->title =='|' || in_array($item->title, $langdesc_array)) {
		 			$nothere = false;
		 			break;
		 		}
		 	}
		}
		if ($nothere == true) {
			/* add separator */
				$defaultarray['menu-item-title'] = '|';
				$defaultarray['menu-item-url'] = $url.'/#';
				wp_update_nav_menu_item($menuid,0,$defaultarray);
			foreach ($listlanguages as $language){
				$defaultarray['menu-item-title'] = $language->description ;
				$defaultarray['menu-item-url'] = $url.'/?lang='.$language->slug ;
				wp_update_nav_menu_item($menuid,0,$defaultarray);
			}
			return __("language items added","xili-language");
		} else {
			return __("seems to be set","xili-language");
		}
	}
	
	/** * main setting window * the list */
	function on_normal_1_content( $data ) { 
		extract($data); ?>
		<?php //if (!isset($action) || $action=='add' || $action=='edited' || $action=='deleting') :?>
					<table class="widefat">
						<thead>
						<tr>
						<th scope="col" style="text-align: center"><?php _e('ID') ?></th>
	        			<th scope="col"><?php _e('Name','xili-language') ?></th>
	        			<th scope="col"><?php _e('Full name','xili-language') ?></th>
	        			<th scope="col"><?php _e('Language slug','xili-language') ?></th>
	        			<th scope="col"><?php _e('Order','xili-language') ?></th>
	        			<th scope="col"><?php _e('Vis.','xili-language') ?></th>
	        			<th scope="col" width="90" style="text-align: center"><?php _e('Posts') ?></th>
	        			<th colspan="2" style="text-align: center"><?php _e('Action') ?></th>
						</tr>
						</thead>
						<tbody id="the-list">
							<?php $this->xili_lang_row(); /* the lines */?>
						</tbody>
					</table>
					<?php if ($action=='edit' || $action=='delete') :?>
					<p>(<a href="?action=add&page=language_page"><?php _e('Add a language','xili-language') ?></a>)</p>
	   				<?php endif; ?>	
	<?php	
	}	
	
	/** 
	 * form to create or edit one language 
	 */
	function on_normal_2_content( $data ) { 
		extract($data);
	 /* the create - edit - delete form */ ?>
		
		<h2 id="addlang" <?php if ($action=='delete') echo 'style="color:#FF1111;"'; ?>><?php _e($formtitle,'xili-language') ?></h2>
		<?php if ($action=='edit' || $action=='delete') :?>
			<input type="hidden" name="language_term_id" value="<?php echo $language->term_id ?>" />
			
		<?php endif; ?>
		<?php if ( $action=='delete') :?>
			
			<input type="hidden" name="language_nicename" value="<?php echo $language->slug ?>" />
		<?php endif; ?>
		<table class="editform" width="100%" cellspacing="2" cellpadding="5">
			<tr>
				<th width="33%" scope="row" valign="middle" align="right"><label for="language_name_list"><?php _e('Examples') ?></label>:&nbsp;</th>
				<td width="67%"><select name="language_name_list" id="language_name_list">
					<?php $this->example_langs_list($language->name, $action);  ?>
				</select>&nbsp;<small> <a href="http://www.gnu.org/software/hello/manual/gettext/Usual-Language-Codes.html#Usual-Language-Codes" target="_blank"><?php _e('ISO Language-Codes','xili-language'); ?></a></small>&nbsp;_&nbsp;<small><a href="http://www.gnu.org/software/hello/manual/gettext/Country-Codes.html#Country-Codes" target="_blank"><?php _e('ISO Country-Codes','xili-language'); ?></a></small><br />&nbsp;</td>		
			</tr>
			<tr>
				<th scope="row" valign="middle" align="right"><label for="language_name"><?php _e('Name') ?></label>:&nbsp;</th>
				<td ><input name="language_name" id="language_name" type="text" value="<?php echo attribute_escape($language->name); ?>" size="10" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
			</tr>
			<tr>
				<th scope="row" valign="middle" align="right"><label for="language_nicename"><?php _e('Language slug','xili-language') ?></label>:&nbsp;</th>
				<td><input name="language_nicename" id="language_nicename" type="text" value="<?php echo attribute_escape($language->slug); ?>" size="10" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
			</tr>
			<tr>
				<th scope="row" valign="middle" align="right"><label for="language_description"><?php _e('Full name','xili-language') ?></label>:&nbsp;</th>
				<td><input name="language_description" id="language_description" size="20" type="text" value="<?php echo $language->description; ?>" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
				
			</tr>
			<tr>
				<th scope="row" valign="middle" align="right"><label for="language_order"><?php _e('Order','xili-language') ?></label>:&nbsp;</th>
				<td><input name="language_order" id="language_order" size="3" type="text" value="<?php echo $language->term_order; ?>" <?php if($action=='delete') echo 'disabled="disabled"' ?> />&nbsp;&nbsp;&nbsp;<small>
					<label for="language_hidden"><?php _e('hidden','xili-language') ?>&nbsp;<input name="language_hidden" id="language_hidden" type="checkbox" value="hidden" <?php if($action=='delete') echo 'disabled="disabled"' ?> <?php if($language_features['hidden']=='hidden') echo 'checked="checked"' ?> /></label>&nbsp;&nbsp;
					<label for="language_charset"><?php _e('Server Entities Charset:','xili-language') ?>&nbsp;<input name="language_charset" id="language_charset" type="text" value="<?php echo $language_features['charset'] ?>" size="25" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></label></small>
				
				</td>
			</tr>
			<?php if ( $action=='delete' ) :?>
			<tr>
				<th scope="row" valign="top" align="right"><label for="multilingual_links_erase"><span style="color:red" ><?php _e('Erase multilingual features of concerned posts by this language being erased !','xili-language') ?></span</label>&nbsp;:&nbsp;</th>
				<td><input name="multilingual_links_erase" id="multilingual_links_erase" type="checkbox" value="erase" /></td>
				
			</tr>
			<?php endif; ?>
			<tr>
			<th><p class="submit"><input type="submit" name="reset" value="<?php echo $cancel_text ?>" /></p></th>
			<td>
			<p class="submit"><input type="submit" name="submit" value="<?php echo $submit_text ?>" /></p>
			</td>
			</tr>
		</table>
	<?php	
	}
	
	/**
	 * private functions for admin page : the language example list
	 * @since 1.6.0
	 */
	function example_langs_list($language_name, $state) {
		$examples_list = array('en_US' => 'english', 'fr_FR' => 'french', 'es_ES' => 'spanish', 'de_DE' => 'german',
		'it_IT' => 'italian', 'pt_PT'=>'portuguese', 'ru_RU'=>'russian', 
		'zh_CN' => 'chinese', 'ja' => 'japanese', 'ar_AR' => 'arabic');
		/* reduce list according present languages in today list */
		if ($state != 'delete' && $state != 'edit') {
			$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
			foreach ($listlanguages as $language) {
			 	if ( array_key_exists($language->name, $examples_list))  unset ($examples_list[$language->name]);	
			}
		}
		//
		echo '<option value="">'.__('Choose…','xili-language').'</option>';
		foreach($examples_list AS $key=>$value) {
			$selected = (''!=$language_name && $language_name == $key) ? 'selected=selected' : '';
			echo '<option value="'.$key.'" '.$selected.'>'.$value.' ('.$key.')</option>';
		}
	}
	
	/**
	 * private functions for admin page : the language list
	 * @since 0.9.0
	 *
	 * @update 0.9.5 : two default languages if taxonomy languages is empty
	 * @update 1.8.8 : fixes slug of defaults
	 * @update 1.8.9.1 : visible = *
	 * 
	 */
	function xili_lang_row() { 	
		/*list of languages*/
		$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		if (empty($listlanguages)) { /*create two default lines with the default language (as in config)*/
		  	/* language of WP */
			$term = 'en_US';
			$args = array( 'alias_of' => '', 'description' => 'english', 'parent' => 0, 'slug' =>'en_us');
			$res = wp_insert_term( $term, TAXONAME, $args);
			if (is_wp_error($res)) {
				$theids = get_term($term, TAXONAME);
			} else { 
				$theids = $res;
			}
			wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);
			
		 	$term = $this->default_lang;
		 	$desc = $this->default_lang; 
		 	if (!defined('WPLANG') || $this->default_lang == 'en_US' || $this->default_lang == '' ) {
		 		$term = 'fr_FR'; $desc = 'french'; $slug = 'fr_fr' ;
		 	}
		 	$args = array( 'alias_of' => '', 'description' => $desc, 'parent' => 0, 'slug' => $slug);
		 	$res = wp_insert_term( $term, TAXONAME, $args);
			if (is_wp_error($res)) {
				$theids = get_term($term, TAXONAME);
			} else { 
				$theids = $res;
			}
			wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);
			$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		}
		$class = null;
		
		// 1.8.9.1 - update language_features
		
		$update_features = ( $this->xili_settings['lang_features'] == array() ) ? true : false ;
		
		foreach ($listlanguages as $language) {	
			if ( $update_features ) { // create default values
				$slug = $language->slug;
				$this->xili_settings['lang_features'][$slug]['hidden']= "";
				$this->xili_settings['lang_features'][$slug]['charset']= "";
			}
			$class = ((defined('DOING_AJAX') && DOING_AJAX) || " class='alternate'" == $class ) ? '' : " class='alternate'";
			$language->count = number_format_i18n( $language->count );
			$posts_count = ( $language->count > 0 ) ? "<a href='edit.php?lang=$language->slug'>$language->count</a>" : $language->count;	
		
			$edit = "<a href='?action=edit&amp;page=language_page&amp;term_id=".$language->term_id."' >".__( 'Edit' )."</a></td>";	
			/* delete link*/
			$edit .= "<td><a href='?action=delete&amp;page=language_page&amp;term_id=".$language->term_id."' class='delete'>".__( 'Delete' )."</a>";	
		$h = ( $this->xili_settings['lang_features'][$language->slug]['hidden'] == 'hidden') ? "&nbsp;" : "*";
		$h .= ( $this->xili_settings['lang_features'][$language->slug]['charset'] != '') ? "&nbsp;+" : "";	
		$line="<tr id='cat-$language->term_id'$class>
			<th scope='row' style='text-align: center'>$language->term_id</th>
			<td>" .$language->name. "</td>
			<td>$language->description</td>
			<td>$language->slug</td>
			<td>$language->term_order</td>
			<td align='left'>$h</td>
			<td align='center'>$posts_count</td> 
			<td>$edit</td>\n\t</tr>\n"; /*to complete - 0.9.8.1 count to post*/
			echo $line;
			//print_r($language);
		}
		if ( $update_features ) update_option('xili_language_settings', $this->xili_settings);	
	}
	
	/**
	 * ***** Functions to improve bookmarks language filtering *****
	 * 
	 */
	
	/**
	 * Filter to widget_links parameter
	 * @ since 1.8.5
	 */
	function widget_links_args_and_lang ($widget_args) {
		
		$cur_lang = $this->curlang;
		// rules depending category and settings in xili-language
		$cat_settings = $this->xili_settings['link_categories_settings'] ; //array ( 'all'=> true, 'category' => array ( '2' => false , '811' => true ) );
		$sub_select = false; 
		if ( $widget_args['category'] ) {
			$sub_select = $cat_settings['category'][$widget_args['category']] ;
		} else {
			$sub_select = $cat_settings['all'];
			// if ( $sub_select ) $widget_args['categorize'] = 0;
		}
		
		if ( $sub_select ) {	
			$linklang = is_term($cur_lang,'link_'.TAXONAME) ;
			$linklang_ever = is_term('ev_er','link_'.TAXONAME) ; // the dummy lang - shown ever with selected language
			if ( $cur_lang && $linklang ) {
				if ( $widget_args['category'] ) {
					$cat = get_term( $widget_args['category'], 'link_category' );
					$catname = apply_filters( "link_category", $cat->name );
				}	
				$the_link_ids = array ();
				
				$the_link_ids_cat = get_objects_in_term( array( $widget_args['category'] ), 'link_category' ) ; //print_r($the_link_ids_cat);
				$the_link_ids = get_objects_in_term( array( $linklang['term_id'], $linklang_ever['term_id'] ), 'link_'.TAXONAME ) ; // lang + ever
				$the_link_ids_all = array_intersect ($the_link_ids , $the_link_ids_cat ); //print_r($the_link_ids_all);
				if ( $widget_args['category'] ) $widget_args['categorize'] = 0; // no sub list in one cat asked
				$widget_args['include'] = implode (',' , $the_link_ids_all );
				$widget_args['category'] = ''; // because implode of intersect
				$widget_args['title_li'] = $catname ;
				
			}
		}	
		return $widget_args ;
	}
	
	/**
	 * only active if 'lang' in template tag wp_list_bookmarks()
	 *
	 * as :  wp_list_bookmarks( array( 'lang'=>the_curlang() ) ) to display only in current language  
	 *
	 * don't interfere with widget_links filter
	 *
	 * @ since 1.8.5
	 */
	function the_get_bookmarks_lang ($links_list, $args) {
		if ( isset( $args[QUETAG] ) ) {
			// get links in selected lang
			$linklang = is_term($args[QUETAG],'link_'.TAXONAME) ;
			$linklang_ever = is_term('ev_er','link_'.TAXONAME) ; // the dummy lang - shown ever with selected language
			//global $the_link_ids;
			$this->the_link_ids = get_objects_in_term( array( $linklang['term_id'], $linklang_ever['term_id'] ), 'link_'.TAXONAME ) ;
			
			return array_filter ( $links_list , array(&$this,'_filtering_links') ) ;
			
		}
		return $links_list;
	}
	function _filtering_links ($link) {
		//global $the_link_ids;
		if ( in_array( $link->link_id , $this->the_link_ids ) ) return true ;
	}


	/**
	 * Action and filter to add language column in link-manager page
	 * @ since 1.8.5
	 */
	function manage_link_lang_column ( $column_name, $link_id ) {
		
		if($column_name != TAXONAME)
					return;
		$ress = wp_get_object_terms($link_id, 'link_'.TAXONAME);
		$obj_term = $ress[0];
		echo $obj_term->name ;
	}
	
	
	function xili_manage_link_column_name ( $cols ) {
				$ends = array('rel', 'visible', 'rating'); // insert language before rel
				$end = array();
				foreach($cols AS $k=>$v) {
					if(in_array($k, $ends)) {
						$end[$k] = $v;
						unset($cols[$k]);
					}
				}
				$cols[TAXONAME] = __('Language','xili-language');
				$cols = array_merge($cols, $end);
				return $cols;
	}


	/**
	 * Box, action and function to set language in edit-link-form
	 * @ since 1.8.5
	 */
	function new_box () {
		add_meta_box('linklanguagediv', __("Link's language","xili-language"), array(&$this,'link_language_meta_box'), 'link', 'side', 'core');
	}
	

	function link_language_meta_box( $link) {
		
		$ress = wp_get_object_terms($link->link_id, 'link_'.TAXONAME);  
					$theid = '['.$link->link_id.'] ';  
					
					$obj_term = $ress[0];
					if ('' != $obj_term->name) :
						$curlangname = $obj_term->name;
					else :
						$curlangname = ""; /* when created before plugin */
					endif;
		
		
		echo '<h4>'.__('Check the language for this link','xili-language').'</h4><div style="line-height:1.7em;">';
		// built the check series with saved check if edit
		$listlanguages = get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' );
		$l = 2;
		foreach ($listlanguages as $language) { 
			if ( $l % 3 == 0 && $l != 3) { echo '<br />'; }
			?> 
		
				<label style="border:solid 1px grey; margin:1px 0px; padding:3px 2px; width:79px; display:inline-block; " for="xili_language_check_<?php echo $language->slug ; ?>" class="selectit"><input id="xili_language_check_<?php echo $language->slug ; ?>" name="xili_language_set" type="radio" value="<?php echo $language->slug ; ?>"  <?php if( $curlangname==$language->name ) echo 'checked="checked"' ?> /> <?php echo _e($language->description, 'xili-language'); ?></label>
			  
				<?php } /*link to top of sidebar*/?> 
				<br /><label style="border:solid 1px grey; margin:1px 0px; padding:3px 2px; width:79px; display:inline-block; " for="xili_language_check" class="selectit"><input id="xili_language_check_ever" name="xili_language_set" type="radio" value="ev_er" <?php if($curlangname=="ev_er") echo 'checked="checked"' ?> /> <?php _e('Ever','xili-language') ?></label>
				<label style="border:solid 1px grey; margin:1px 0px; padding:3px 2px; width:79px; display:inline-block; " for="xili_language_check" class="selectit"><input id="xili_language_check" name="xili_language_set" type="radio" value="" <?php if($curlangname=="") echo 'checked="checked"' ?> /> <?php _e('undefined','xili-language') ?></label><br /></div>
			  	<br /><small>© xili-language <?php echo XILILANGUAGE_VER; ?></small>
		<?php
		
	}
	
		
	/**
	 * To edit language when submit in edit-link-form
	 * @ since 1.8.5
	 */
	function edit_link_set_lang ( $link_id ) {
		// create relationships with link_language taxonomy
				$sellang = $_POST['xili_language_set'];
				// test if exist in link taxinomy or create it
				$linklang = is_term($sellang,'link_'.TAXONAME);
				if ( !$linklang ) {
					$lang = is_term($sellang,TAXONAME);
					$lang_term = get_term($lang[ 'term_id' ], TAXONAME );
					wp_insert_term( $lang_term -> name, 'link_'.TAXONAME , array ( 'alias_of' => '', 'description' => $lang_term -> description, 'parent' => 0, 'slug' => $lang_term->slug )  );
				}
				
				if ("" != $sellang) {
					wp_set_object_terms($link_id, $sellang, 'link_'.TAXONAME);
				} else {
					wp_delete_object_term_relationships( $link_id, 'link_'.TAXONAME ); 	
				}
	}

	/**
	 * Register link language taxonomy
	 * @ since 1.8.5
	 */
	function add_link_taxonomy () {
		register_taxonomy( 'link_'.TAXONAME, 'link', array('hierarchical' => false, 'label' => false, 'rewrite' => false, 'update_count_callback' =>  array(&$this,'_update_link_lang_count'), 'show_ui' => false, '_builtin' => false ));
		
	}
	// count update
	function _update_link_lang_count( $terms ) {
		//
		global $wpdb;
			foreach ( (array) $terms as $term ) {
				$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->links WHERE $wpdb->links.link_id = $wpdb->term_relationships.object_id AND term_taxonomy_id = %d", $term ) );
				$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
			}
	}


	/** end of language for bookmarks * @ since 1.8.5 **/

	/**
 	* now active in same file as class xili_language
 	* Widgets registration after classes rewritten
 	*
 	* @since 1.8.8
 	*/
	
	function add_new_widgets() {
		load_plugin_textdomain('xili-language-widget',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__))); // 1.8.8.1 fixes translation and red messages
 		register_widget('xili_Widget_Recent_Posts'); // since 1.3.2
 		register_widget('xili_WP_Widget_Recent_Comments'); // since 1.8.3 
 		register_widget('xili_language_Widgets'); // since 1.8.3 
	}
	
	
	/**
	 * Contextual help
	 *
	 * @since 1.7.0
	 */
	 function add_help_text( $contextual_help, $screen_id, $screen ) { 
	  // $contextual_help = var_dump($screen); // use this to help determine $screen->id
	  //echo $contextual_help;
	  //echo $screen_id;
	  //print_r($screen);
	  if ('settings_page_language_page' == $screen->id ) {
	    $contextual_help =
	      '<p>' . __('Things to remember to set xili-language:','xili-language') . '</p>' .
	      '<ul>' .
	      '<li>' . __('Verify that the theme is localizable (like kubrick, fusion or twentyten).','xili-language') . '</li>' .
	      '<li>' . __('Define the list of targeted languages.','xili-language') . '</li>' .
	      '<li>' . __('Prepare .po and .mo files for each language with poEdit or xili-dictionary plugin.','xili-language') . '</li>' .
	      '<li>' . __('If your website contains custom post type: check those which need to be multilingual. xili-language will add automatically edit meta boxes.','xili-language') . '</li>' .
	      '</ul>' .
	      
	      '<p><strong>' . __('For more information:') . '</strong></p>' .
	      '<p>' . __('<a href="http://dev.xiligroup.com/xili-language" target="_blank">Xili-language Plugin Documentation</a>','xili-language') . '</p>' .
	      '<p>' . __('<a href="http://codex.wordpress.org/" target="_blank">WordPress Documentation</a>','xili-language') . '</p>' .
	      '<p>' . __('<a href="http://forum2.dev.xiligroup.com/" target="_blank">Support Forums</a>','xili-language') . '</p>' ;
	  }
	  return $contextual_help;
	}

	function check_other_xili_plugins () {
		$list = array();
		//if ( class_exists( 'xili_language' ) ) $list[] = 'xili-language' ;
		if ( class_exists( 'xili_tidy_tags' ) ) $list[] = 'xili-tidy-tags' ;
		if ( class_exists( 'xili_dictionary' ) ) $list[] = 'xili-dictionary' ;
		if ( class_exists( 'xilithemeselector' ) ) $list[] = 'xilitheme-select' ;
		if ( function_exists( 'insert_a_floom' ) ) $list[] = 'xili-floom-slideshow' ;
		if ( class_exists( 'xili_postinpost' ) ) $list[] = 'xili-postinpost' ;
		return implode (', ',$list) ;
	}
	
	function on_sidebox_mail_content ( $data ) {
		extract( $data );
		global $wp_version ;
		if ( '' != $emessage ) { ?>
	 		<h4><?php _e('Note:','xili-language') ?></h4>
			<p><strong><?php echo $emessage;?></strong></p>
		<?php } ?>
		<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo _e('Mail to dev.xiligroup', 'xili-language'); ?></legend>
		<label for="ccmail"><?php _e('Cc:','xili-language'); ?>
		<input class="widefat" id="ccmail" name="ccmail" type="text" value="<?php bloginfo ('admin_email') ; ?>" /></label><br /><br />
		<?php if ( false === strpos( get_bloginfo ('home'), 'local' ) ){ ?>
			<label for="urlenable">
				<input type="checkbox" id="urlenable" name="urlenable" value="enable" <?php if( $this->xili_settings['url']=='enable') echo 'checked="checked"' ?> />&nbsp;<?php bloginfo ('home') ; ?>
			</label><br />
		<?php } else { ?>
			<input type="hidden" name="onlocalhost" id="onlocalhost" value="localhost" />
		<?php } ?>
		<label for="themeenable">
			<input type="checkbox" id="themeenable" name="themeenable" value="enable" <?php if( $this->xili_settings['theme']=='enable') echo 'checked="checked"' ?> />&nbsp;<?php echo "Theme name= ".get_option ('stylesheet') ; ?>
		</label><br />
		<?php if (''!= WPLANG ) {?>
		<label for="wplangenable">
			<input type="checkbox" id="wplangenable" name="wplangenable" value="enable" <?php if( $this->xili_settings['wplang']=='enable') echo 'checked="checked"' ?> />&nbsp;<?php echo "WPLANG= ".WPLANG ; ?>
		</label><br />
		<?php } ?>
		<label for="versionenable">
			<input type="checkbox" id="versionenable" name="versionenable" value="enable" <?php if( $this->xili_settings['version']=='enable') echo 'checked="checked"' ?> />&nbsp;<?php echo "WP version: ".$wp_version ; ?>
		</label><br /><br />
		<?php $list = $this->check_other_xili_plugins();
		if (''!= $list ) {?>
		<label for="xiliplugenable">
			<input type="checkbox" id="xiliplugenable" name="xiliplugenable" value="enable" <?php if( $this->xili_settings['xiliplug']=='enable') echo 'checked="checked"' ?> />&nbsp;<?php echo "Other xili plugins = ".$list ; ?>
		</label><br /><br />
		<?php } ?>
		<label for="webmestre"><?php _e('Type of webmaster:','xili-language'); ?>
		<select name="webmestre" id="webmestre" style="width:100%;">
			<option value="?" ><?php _e('Define your experience as webmaster…','xili-language'); ?></option>
			<option value="newbie" ><?php _e('Newbie in WP','xili-language'); ?></option>
			<option value="wp-php" ><?php _e('Good knowledge in WP and few in php','xili-language'); ?></option>
			<option value="wp-php-dev" ><?php _e('Good knowledge in WP, CMS and good in php','xili-language'); ?></option>
			<option value="wp-plugin-theme" ><?php _e('WP theme and /or plugin developper','xili-language'); ?></option>
		</select>
		</label><br /><br />
		<label for="subject"><?php _e('Subject:','xili-language'); ?>
		<input class="widefat" id="subject" name="subject" type="text" value="" /></label>
		<select name="thema" id="thema" style="width:100%;">
			<option value="" ><?php _e('Choose topic...','xili-language'); ?></option>
			<option value="Message" ><?php _e('Message','xili-language'); ?></option>
			<option value="Question" ><?php _e('Question','xili-language'); ?></option>
			<option value="Encouragement" ><?php _e('Encouragement','xili-language'); ?></option>
			<option value="Support need" ><?php _e('Support need','xili-language'); ?></option>
		</select>
		<textarea class="widefat" rows="5" cols="20" id="mailcontent" name="mailcontent"><?php _e('Your message here…','xili-language'); ?></textarea>
		</fieldset>
		<p>
		<?php _e('Before send the mail, check the infos to be sent and complete textarea. A copy (Cc:) is sent to webmaster email (modify it if needed).','xili-language'); ?>
		</p>
		<div class='submit'>
		<input id='sendmail' name='sendmail' type='submit' tabindex='6' value="<?php _e('Send email','xili-language') ?>" /></div>
		<?php //wp_nonce_field('xili-postinpost-sendmail'); ?>
		<div style="clear:both; height:1px"></div>
		<?php
	}
	
	/**
	 * If checked, functions in uninstall.php will be fired when deleting the plugin via plugins list.
	 *
	 * @since 1.8.8
	 */
	function on_sidebox_uninstall_content ( $data ) {
		extract( $data );
	?>
	<p style="color:red;"><?php _e('CAUTION: When checking below, after deactivating xili-language plugin, if delete it through plugins list, ALL the xili-language datas in database will be definitively ERASED !!! (only multilingual features)','xili-language') ?></p>
	<label for="delete_settings">
			<input type="checkbox" id="delete_settings" name="delete_settings" value="delete" <?php if( $this->xili_settings['delete_settings']=='delete') echo 'checked="checked"' ?> />&nbsp;<?php _e("Delete DB plugin's datas",'xili-language') ; ?>
	</label>
	<div class='submit'>
		<input id='uninstalloption' name='uninstalloption' type='submit' tabindex='6' value="<?php _e('Update','xili-language') ?>" /></div>
	<?php
	}
	
	//********************************************//
	// Functions for themes (hookable by add_action() in functions.php - 0.9.7
	//********************************************//

	/**
	 * List of available languages.
	 *
	 * @since 0.9.0
	 * @updated 0.9.7.4 - 0.9.8.3 - 0.9.9.6 - 1.5.5 (add class current-lang in <a>)
	 * @updated 1.6.0 - new option for nav menu hook and echoing 4th param - better permalink
	 * @updated 1.8.1 - delete 'in' prefix in list - class if LI
	 * can be hooked by add_action in functions.php
	 * with : add_action('xili_language_list','my_infunc_language_list',10,4);
	 *
	 * for multiple widgets since 0.9.9.6, 1.6.0 : incorporate options
	 *
	 * @param $before = '<li>', $after ='</li>'.
	 * @return list of languages of site for sidebar list.
	 */
	function xili_language_list( $before = '<li>', $after ='</li>', $option='', $echo = true, $hidden = false ) {
		global $post;
		$before_class = false ;
		if ( substr($before,-2) == '.>' ) { // tips to add dynamic class in before
			$before_class = true ;
			$before = str_replace('.>','>',$before);
		}
		$listlanguages = $this->get_listlanguages();
		$a = ''; // 1.6.0
		if ($option == 'typeone') {
			/* the rules : don't display the current lang if set and add link of category if is_category()*/
			if (is_category()) {  
				$catcur = xiliml_get_category_link();
				$permalink = get_option('permalink_structure'); /* 1.6.0 */
				$sep = ('' == $permalink) ? "&amp;" : "?" ;
				$currenturl = $catcur.$sep;
			} else {
		 		$currenturl = get_bloginfo('url').'/?';
			}
			foreach ($listlanguages as $language) {
				$display = ( $hidden && ( $this->xili_settings['lang_features'][$language->slug]['hidden'] == 'hidden' ) ) ? false : true ;
				if ($language->slug != $this->curlang   && $display ) {
					$beforee = ( $before_class && $before == '<li>' ) ? '<li class="lang-'.$language->slug.'" >': $before;
					$class = " class='lang-".$language->slug."'";
					$a .= $beforee ."<a{$class} href='".$currenturl.QUETAG."=".$language->slug."' title='".__('Posts selected',$this->thetextdomain)." ".__('in '.$language->description,$this->thetextdomain)."'>". __($language->description,$this->thetextdomain) ."</a>".$after;
				}
			}
		
		} elseif ($option == 'typeonenew') { // 2.1.0
			/* the rules : don't display the current lang if set and add link of category if is_category()*/
			if (is_category()) {  
				$catcur = xiliml_get_category_link();
				$permalink = get_option('permalink_structure'); /* 1.6.0 */
				$sep = ('' == $permalink) ? "&amp;" : "?" ;
				$currenturl = $catcur.$sep;
			} else {
		 		$currenturl = get_bloginfo('url').'/?';
			}
			foreach ($listlanguages as $language) {
				$display = ( $hidden && ( $this->xili_settings['lang_features'][$language->slug]['hidden'] == 'hidden' ) ) ? false : true ;
				if ($language->slug != $this->curlang   && $display ) {
					$beforee = ( $before_class && $before == '<li>' ) ? '<li class="lang-'.$language->slug.'" >': $before;
					$class = " class='lang-".$language->slug."'";
					
					if ( ( is_single() || is_page() ) && !is_front_page() ) {	
						$thelink = $this->link_of_linked_post ( $post->ID, $language->slug ) ;
						$title = sprintf (__('Current post in %s',$this->thetextdomain), __($language->description,$this->thetextdomain) ) ;
					} else {
						$thelink = $currenturl . QUETAG . "=" . $language->slug ;
						$title = sprintf (__('Posts selected in %s',$this->thetextdomain), __($language->description,$this->thetextdomain) ) ;
					}
			
					$a .= $beforee ."<a{$class} href='".$thelink."' title='".$title."' >". __($language->description,$this->thetextdomain) ."</a>".$after;
				}
			}
		} elseif ($option == 'navmenu')  {	/* current list in nav menu 1.6.0 */
			foreach ($listlanguages as $language) { 
				$display = ( $hidden && ( $this->xili_settings['lang_features'][$language->slug]['hidden'] == 'hidden' ) ) ? false : true ;
				if ( $display ) { 
					if ($language->slug != $this->curlang ) {
						$class = " class='menu-item menu-item-type-custom lang-".$language->slug."'";
					} else {
						$class = " class='menu-item menu-item-type-custom lang-".$language->slug." current-lang current-menu-item'";
					}
					$beforee = (substr($before,-1) == '>') ? str_replace('>',' '.$class.' >' , $before ) : $before ;
					$a .= $beforee ."<a href='".get_bloginfo('url')."/?".QUETAG."=".$language->slug."' title='".__('Posts selected',$this->thetextdomain)." ".__('in '.$language->description,$this->thetextdomain)."'>". __($language->description,$this->thetextdomain) ."</a>".$after;
				}
			}
			
		} elseif ($option == 'navmenu-1')  {	// 2.1.0
			if (is_category()) {  
						$catcur = xiliml_get_category_link();
						$permalink = get_option('permalink_structure'); /* 1.6.0 */
						$sep = ('' == $permalink) ? "&amp;" : "?" ;
						$currenturl = $catcur.$sep;
					} else {
		 				$currenturl = get_bloginfo('url').'/?';
					}
			
			foreach ($listlanguages as $language) { 
				$display = ( $hidden && ( $this->xili_settings['lang_features'][$language->slug]['hidden'] == 'hidden' ) ) ? false : true ;
				if ( $display ) { 
					
					if ($language->slug != $this->curlang ) {
						$class = " class='menu-item menu-item-type-custom lang-".$language->slug."'";
					} else {
						$class = " class='menu-item menu-item-type-custom lang-".$language->slug." current-lang current-menu-item'";
					}
					
					if ( ( is_single() || is_page() ) && !is_front_page() ) {	
						$thelink = $this->link_of_linked_post ( $post->ID, $language->slug ) ;
						$title = sprintf (__('Current post in %s',$this->thetextdomain), __($language->description,$this->thetextdomain) ) ;
					} else {
						$thelink = $currenturl . QUETAG . "=" . $language->slug ;
						$title = sprintf (__('Posts selected in %s',$this->thetextdomain), __($language->description,$this->thetextdomain) ) ;
					}
					
					$beforee = (substr($before,-1) == '>') ? str_replace('>',' '.$class.' >' , $before ) : $before ;
					$a .= $beforee ."<a href='".$thelink."' title='".$title."'>". __($language->description,$this->thetextdomain) ."</a>".$after;
				}
			}
			
		} else {	/* current list */
			foreach ($listlanguages as $language) {
				$display = ( $hidden && ( $this->xili_settings['lang_features'][$language->slug]['hidden'] == 'hidden' ) ) ? false : true ;
				if ( $display ) {
					if ( $language->slug != $this->curlang ) {
						$class = " class='lang-".$language->slug."'";
					} else {
						$class = " class='lang-".$language->slug." current-lang'";
					}
					$beforee = ( $before_class && $before == '<li>' ) ? '<li class="lang-'.$language->slug.'" >': $before;
					$a .= $beforee ."<a{$class} href='".get_bloginfo('url')."/?".QUETAG."=".$language->slug."' title='".__('Posts selected',$this->thetextdomain)." ".__('in '.$language->description,$this->thetextdomain)."'>". __($language->description,$this->thetextdomain) ."</a>".$after;
				}
			}
		}
		if ($echo) 
				echo $a;
			else
				return $a;	
	}
	
	/**
	 *
	 *
	 * @since 2.1.0
	 */
	function link_of_linked_post ( $fromID, $lang_slug ) { 
	 	$targetpost = $this->linked_post_in ( $fromID, $lang_slug ) ;
	 	if ( $targetpost ) {
	 		return get_permalink($targetpost);
	 	} else {
	 	    return get_bloginfo('url')."/?".QUETAG."=".$lang_slug;
	 	}
	}
	
	/**
	 * For widget - the list of options above
	 * @since 1.6.0
	 */
	function xili_language_list_options () {
		$this->langs_list_options = array( array('','default'), array('typeone','Type n°1'), array('typeonenew','Type for single') );
	}
	
	
	/**
	 * language of current post used in loop
	 * @since 0.9.0
	 * 
	 *
	 * @param $before = '<span class"xili-lang">(', $after =')</span>'.
	 * @return language of post.
	 */
	function xili_post_language( $before = '<span class="xili-lang">(', $after =')</span>' ) {
		global $post;
		$ress = wp_get_object_terms($post->ID, TAXONAME);
		$obj_term = $ress[0];
		if ('' != $obj_term->name) :
				$curlangname = $obj_term->name;
		else :
				$curlangname = __('undefined',$this->thetextdomain);
		endif;
		$a = $before . $curlangname .$after.'';  
		echo $a;
	}
		
	/** 
	 * for one post create a link list of the corresponding posts in other languages
	 *
	 * @since 0.9.0
	 * @updated 0.9.9.2 / 3 $separator replace $after, $before contains pre-text to echo a better list.
	 * @updated 1.1 - see hookable same name function outside class
	 * can be hooked by add_action in functions.php
	 *
	 *
	 */
	function xiliml_the_other_posts ( $post_ID, $before = "This post in", $separator = ", ", $type = "display" ) {
		/* default here*/
			$outputarr = array();
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			$langpost = $this->get_cur_language($post_ID); // to be used in multilingual loop since 1.1
			$post_lang = $langpost[QUETAG];
			foreach ($listlanguages as $language) {
				$otherpost = $this->linked_post_in( $post_ID, $language->slug ) ; //get_post_meta($post_ID, 'lang-'.$language->slug, true);
				
				if ($type == "display") {
					if ('' != $otherpost && $language->slug != $post_lang ) {
						$outputarr[] = "<a href='".get_permalink($otherpost)."' >".__($language->description,$this->thetextdomain) ."</a>";
					}
				} elseif ($type == "array") { // here don't exclude cur lang
					if ('' != $otherpost)
						$outputarr[$language->slug] = $otherpost;
				}
			}
			if ($type == "display") {
				if (!empty($outputarr))
					$output =  (($before !="") ? __($before,$this->thetextdomain)." " : "" ).implode ($separator, $outputarr);
				if ('' != $output) { echo $output;}	
			} elseif ($type == "array") {
				if (!empty($outputarr)) {
					$outputarr[$post_ID] = $post_lang; 
					// add a key with curid to give his lang (empty if undefined)
					return $outputarr;
				} else {
					return false;	
				}
			}	
	}
	
	/**
	 * the_category() rewritten to keep new features of multilingual (and amp & pbs in link)
	 *
	 * @since 0.9.0
	 * @updated 0.9.9.4 
	 * can be hooked by add_action xiliml_the_category in functions.php
	 *
	 */
	function xiliml_the_category( $post_ID, $separator = ', ' ,$echo = true ) {
		/* default here*/
		$the_cats_list = wp_get_object_terms($post_ID, 'category');
		$i = 0;
		foreach ($the_cats_list as $the_cat) {
			if ( 0 < $i )
				$thelist .= $separator . ' ';
			$desc4title = trim(attribute_escape(apply_filters( 'category_description', $the_cat->description, $the_cat->term_id )));
			
			$title = ('' == $desc4title) ? __($the_cat->name,$this->thetextdomain) : $desc4title;
			$the_catlink = '<a href="' . get_category_link($the_cat->term_id) . '" title="' . $title . '" ' . $rel . '>';
			//if ($curlang != DEFAULTSLUG) :
	      	$the_catlink .=  __($the_cat->name,$this->thetextdomain).'</a>';;
	      	//else :
	      		 //$the_catlink .=  $the_cat->name.'</a>';;
	      	//endif;
			$thelist .= $the_catlink;
			++$i;
		}
		if ($echo) :
			echo $thelist;
			return true;
		else :
			return $thelist;
		endif;	
	}	
	
	/**
	 * Add list of languages in radio input - for search form.
	 *
	 * @since 0.9.7
	 * can be hooked by add_action in functions.php
	 *
	 * @updated 0.9.9.5, 1.8.2
	 *
	 * $before, $after each line of radio input
	 *
	 * @param $before, $after. 
	 * @return echo the form.
	 */
	function xiliml_langinsearchform ( $before='', $after='', $echo = true ) {
			/* default here*/
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			$a = '';
			foreach ($listlanguages as $language) {
				$a .= $before.'<input type="radio" name="'.QUETAG.'" value="'.$language->slug.'" id="'.$language->slug.'" />&nbsp;'.__($language->description,$this->thetextdomain).' '.$after;
			}			
		    $a .= $before.'<input type="radio" name="alllang" value="yes" /> '.__('All',$this->thetextdomain).' '.$after;	 // this query alllang is unused -
		    if ( $echo )  
		    	echo $a;
		    else
		    	return $a;
		    		
	}
	
	/**
	 * Select latest comments in current lang.
	 *
	 * @since 0.9.9.4
	 * used by widget xili-recent-comments
	 *
	 * @param $number. 
	 * @return $comments.
	 */
	function xiliml_recent_comments ( $number = 5 ) {
		global $comments, $wpdb ;
		if ( !$comments = wp_cache_get( 'xili_language_recent_comments', 'widget' ) ) {
				$join = "";
				$where = "";
				$reqtag = is_term( $this->curlang, TAXONAME );
					if (''!= $reqtag) {
						$wherereqtag = $reqtag['term_id'];
						$join = " LEFT JOIN $wpdb->term_relationships as tr ON ($wpdb->comments.comment_post_ID = tr.object_id) LEFT JOIN $wpdb->term_taxonomy as tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) ";
					    $where = " AND tt.taxonomy = '".TAXONAME."' ";
					    $where .= " AND tt.term_id = $wherereqtag ";
					}
				$query = "SELECT * FROM $wpdb->comments".$join." WHERE comment_approved = '1' ".$where." ORDER BY comment_date_gmt DESC LIMIT $number"; 
				//echo $query;
				$comments = $wpdb->get_results($query);
				wp_cache_add( 'xili_language_recent_comments', $comments, 'widget' );
		}
		return $comments;
	}
	
	/**
	 * Recursive search of files in a path
	 * @since 1.1.9 
	 * @update 1.2.1 - 1.8.5
	 *
	 */
	 function find_files( $path, $pattern, $callback ) {
 		//$path = rtrim(str_replace("\\", "/", $path), '/') . '/';
 		
		  $matches = Array();
		  $entries = Array();
		  $dir = dir($path);
		  
		  while (false !== ($entry = $dir->read())) {
		    $entries[] = $entry;
		  }
		  $dir->close();
		  foreach ($entries as $entry) {
		    $fullname = $path .$this->ossep. $entry;
		    if ($entry != '.' && $entry != '..' && is_dir($fullname)) {
		      $this->find_files($fullname, $pattern, $callback);
		    } else if (is_file($fullname) && preg_match($pattern, $entry)) {
		      call_user_func($callback, $path , $entry);
		    }
		  }
 		
	}
	/**
	 * display lines of files in special sidebox
	 * @since 1.1.9
	 * 
	 * @updated 1.8.8
	 */
	function available_mo_files( $path , $filename ) {
  		$shortfilename = str_replace(".mo","",$filename );
  		$message = ( ( strlen($shortfilename)!=5 && strlen($shortfilename) != 2 ) || ( false === strpos( $shortfilename, '_' ) && strlen($shortfilename)==5 ) ) ? '<span style="color:red;">'.__('Uncommon filename','xili-language').'</span>' : '';
  		echo $shortfilename. " (".$this->ossep.str_replace($this->ossep,"",str_replace($this->get_template_directory,'',$path)).") ".$message."<br />";
	}
	
	/**
 	* Enable to add functions and filters that are not in theme's functions.php
 	* These filters are common even if you change default theme...
 	* Place your functions.php in folder plugins/xilidev-libraries/
 	* if you have a filter in this file, avoid to have similar one in functions.php of the theme !!!
	*
 	*/
	function insert_gold_functions () {
		if ($this->functions_enable !='' && file_exists(XILIFUNCTIONSPATH . '/functions.php') )
			include_once (XILIFUNCTIONSPATH . '/functions.php');
	}
	
	/**
	 * Retrieve category list in either HTML list or custom format - as in category-template - rewritten for multilingual
	 *
	 * @since 1.7.0
	 *
	 * @param string $separator Optional, default is empty string. Separator for between the categories.
	 * @param string $parents Optional. How to display the parents.
	 * no third param because call by end filter
	 * @return string
	 */
	function xl_get_the_category_list( $thelist, $separator = '', $parents='' ) {
		global $wp_rewrite;
		$categories = get_the_category( $post_id );
		//if ( !is_object_in_taxonomy( get_post_type( $post_id ), 'category' ) )
			//return apply_filters( 'the_category', '', $separator, $parents );
	
		if ( empty( $categories ) ) {
			return  __( 'Uncategorized', $this->thetextdomain ) ; // fixed - avoid a previous recursive filter with custom @since 1.8.0
		}
		$rel = ( is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) ? 'rel="category tag"' : 'rel="category"';
	
		$thelist = '';
		if ( '' == $separator ) {
			$thelist .= '<ul class="post-categories">';
			foreach ( $categories as $category ) {
				$thelist .= "\n\t<li>";
				switch ( strtolower( $parents ) ) {
					case 'multiple':
						if ( $category->parent )
							$thelist .= get_category_parents( $category->parent, true, $separator );
						$thelist .= '<a href="' . get_category_link( $category->term_id ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s", $this->thetextdomain ), __($category->name, $this->thetextdomain) ) ) . '" ' . $rel . '>' . __($category->name, $this->thetextdomain).'</a></li>';
						break;
					case 'single':
						$thelist .= '<a href="' . get_category_link( $category->term_id ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s", $this->thetextdomain ), __($category->name, $this->thetextdomain) ) ) . '" ' . $rel . '>';
						if ( $category->parent )
							$thelist .= get_category_parents( $category->parent, false, $separator );
						$thelist .= __($category->name, $this->thetextdomain).'</a></li>';
						break;
					case '':
					default:
						$thelist .= '<a href="' . get_category_link( $category->term_id ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s", $this->thetextdomain ), __($category->name, $this->thetextdomain) ) ) . '" ' . $rel . '>' . __($category->cat_name, $this->thetextdomain).'</a></li>';
				}
			}
			$thelist .= '</ul>';
		} else {
			$i = 0;
			foreach ( $categories as $category ) {
				if ( 0 < $i )
					$thelist .= $separator;
				switch ( strtolower( $parents ) ) {
					case 'multiple':
						if ( $category->parent )
							$thelist .= get_category_parents( $category->parent, true, $separator );
						$thelist .= '<a href="' . get_category_link( $category->term_id ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s",$this->thetextdomain ), __($category->name, $this->thetextdomain) ) ) . '" ' . $rel . '>' . __($category->name, $this->thetextdomain).'</a>';
						break;
					case 'single':
						$thelist .= '<a href="' . get_category_link( $category->term_id ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s",$this->thetextdomain ), __($category->name, $this->thetextdomain) ) ) . '" ' . $rel . '>';
						if ( $category->parent )
							$thelist .= get_category_parents( $category->parent, false, $separator );
						$thelist .= __($category->name, $this->thetextdomain)."</a>";
						break;
					case '':
					default:
						$thelist .= '<a href="' . get_category_link( $category->term_id ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s",$this->thetextdomain ), __($category->name, $this->thetextdomain) ) ) . '" ' . $rel . '>' . __($category->name, $this->thetextdomain).'</a>';
				}
				++$i;
			}
		}
		return $thelist;
	}


			
} /* end of xili-language class */


/**** Functions that improve taxinomy.php ****/

/**
 * get terms and add order in term's series that are in a taxonomy 
 * (not in class for general use)
 *
 * @since 0.9.8.2 - full version is in xili-tidy-tags
 * @uses $wpdb
 */
function get_terms_of_groups_lite ($group_ids, $taxonomy, $taxonomy_child, $order = '') {
	global $wpdb;
	if ( !is_array($group_ids) )
		$group_ids = array($group_ids);
	$group_ids = array_map('intval', $group_ids);
	$group_ids = implode(', ', $group_ids);
	$theorderby = '';
	
	// lite release
	if ($order == 'ASC' || $order == 'DESC') $theorderby = ' ORDER BY tr.term_order '.$order ;
		
	$query = "SELECT t.*, tt2.term_taxonomy_id, tt2.description,tt2.parent, tt2.count, tt2.taxonomy, tr.term_order FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->terms AS t ON t.term_id = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt2 ON tt2.term_id = tr.object_id WHERE tt.taxonomy IN ('".$taxonomy."') AND tt2.taxonomy = '".$taxonomy_child."' AND tt.term_id IN (".$group_ids.") ".$theorderby;
	
	$listterms = $wpdb->get_results($query);
	if ( ! $listterms )
		return array();

	return $listterms;
}

/** * for backward compatibility - soon obsolete - please modify your theme's function.php */
function get_terms_with_order ( $group_ids, $taxonomy, $taxonomy_child, $order = 'ASC' ) {
	return get_terms_of_groups_lite ($group_ids, $taxonomy, $taxonomy_child, $order);
}	

/**
 * function that improve taxinomy.php 
 * @since 0.9.8
 *
 * update term order in relationships (for terms of langs group defined by his taxonomy_id)
 *
 * @param $object_id, $taxonomy_id, $term_order
 * 
 */
function update_term_order ( $object_id,$term_taxonomy_id,$term_order ) {
	global $wpdb;
	$wpdb->update( $wpdb->term_relationships, compact( 'term_order' ), array( 'term_taxonomy_id' => $term_taxonomy_id,'object_id' => $object_id ) );
}

/**
 * function that improve taxinomy.php 
 * @since 0.9.8
 *
 * get one term and order of it in relationships
 *
 * @param term_id and $group_ttid (taxonomy id of group)
 * @return object with term_order
 */
function get_term_and_order ( $term_id, $group_ttid, $taxonomy ) {
	global $wpdb;
	$term = get_term($term_id,$taxonomy,OBJECT,'edit');
	$term->term_order = $wpdb->get_var("SELECT term_order FROM $wpdb->term_relationships WHERE object_id =  $term_id AND term_taxonomy_id = $group_ttid ");
	return $term;
}

/**** Functions using the class ****/

/**
 * function to progressively replace the previous constant THEME_TEXTDOMAIN only usable in mono site
 *
 * @since 1.5.2
 */
function the_theme_domain() {
	global $xili_language;
	return $xili_language->thetextdomain;	
} 


/**
 * Return the current language of theme.
 *
 * @since 0.9.7
 * use for other function elsewhere
 *
 * @return the slug of language (used in query).
 */
function the_curlang() {
	global $xili_language;
	return $xili_language->curlang;
}

/**
 * Return the current language and dir of theme.
 *
 * @since 0.9.9
 * use for other function elsewhere
 *
 * @return array with slug of language (used in query) and dir (ltr or rtl).
 */
function the_cur_lang_dir() {
	global $xili_language;
	return array( QUETAG=>$xili_language->curlang, 'direction'=>$xili_language->curlang_dir );
}

/**
 * Return the current group of languages
 *
 * @since 0.9.8.3
 */
function the_cur_langs_group_id() {
	global $xili_language;
	return $xili_language->langs_group_id;
} 

/**
 * Return the feature of language
 * Used by xili_language_list hook to hidden checked language
 *
 * @since 1.8.9.1
 */
function xl_lang_features ( $slug, $param ) {
	global $xili_language;
	return $xili_language->xili_settings['lang_features'][$slug][$param];
}

/**
 * Return the current date or a date formatted with strftime.
 *
 * @since 0.9.7.1
 * @updated 1.6.0 - timezone offset - http://core.trac.wordpress.org/ticket/11672
 * can be used in theme for multilingual date
 * @param format and time (if no time = current date-time)
 * @return the formatted date.
 */
function the_xili_local_time( $format='%B %d, %Y',$time = null ) {
	global $xili_language;
	if ($time == null ) {
		$time = current_time('timestamp'); //to get the Unix timestamp with a timezone offset - 
	}
	$curslug = $xili_language->curlang;
	$curlang = ( strlen($curslug) == 5 ) ? substr($curslug,0,3).strtoupper(substr($curslug,-2)) : $curslug ;
	setlocale(LC_TIME, $curlang); /* work if server is ready */
	$charset = ( $xili_language->xili_settings['lang_features'][$curslug]['charset'] != '' ) ? $xili_language->xili_settings['lang_features'][$curslug]['charset'] : "" ; // 1.8.9.1
	if ( "" != $charset  ) {
	  return htmlentities( strftime(__( $format, the_theme_domain() ),$time), ENT_COMPAT, $charset ); /* ,'UTF-8' entities for some server */
	} else {
	 	return htmlentities( strftime(__( $format, the_theme_domain() ),$time), ENT_COMPAT );
	}
}

/**
 * Return the current date or a date formatted with strftime according get_option php date format.
 *
 * @since 1.6.0, 1.8.9.1
 * 
 * can be used in theme for multilingual date 
 * @param format and time (if no time = current date-time)
 * @return the formatted date.
 */
function the_xili_wp_local_time( $wp_format='F j, Y', $time = null ) {
	global $xili_language;
	if ($time == null ) {
		$time = current_time('timestamp'); //to get the Unix timestamp with a timezone offset - 
	}
	$curslug = $xili_language->curlang;
	$curlang = ( strlen($curslug) == 5 ) ? substr($curslug,0,3).strtoupper(substr($curslug,-2)) : $curslug ;
	setlocale(LC_TIME, $curlang); /* work if server is ready */
	$format = xiliml_php2loc_time_format_translator (__($wp_format, the_theme_domain())); /* translated by theme mo*/
	
	$charset = ( $xili_language->xili_settings['lang_features'][$curslug]['charset'] != '' ) ? $xili_language->xili_settings['lang_features'][$curslug]['charset'] : "" ; // 1.8.9.1
	if ( "" != $charset  ) {
		return htmlentities(strftime($format, $time),ENT_COMPAT, $charset  ); /* ,'UTF-8' entities for some server - ja char */
	} else {
		return htmlentities(strftime($format, $time),ENT_COMPAT  );
	}
}

/**
 * Return translated format from php time to loc time used in strftime.
 *
 * @since 1.6.0 
 * @updated 1.8.1 - add T -> %z, e -> %Z - 1.8.7 T -> %Z (stephen)
 * @ 1.8.9.1 - add n -> %m (japanese)
 * (was formerly in xilidev-libraries)
 * can be used in theme for multilingual date 
 * @param phpformat
 * @return locale format.
 */
function xiliml_php2loc_time_format_translator ( $phpformat = 'm/d/Y H:i' ) {
	/* order left to right to avoid over replacing DON'T MODIFY */
	$phpformchar = array('A' ,'a' ,'D' ,'l' ,'g' ,'d' ,'e' ,'j' ,'z' ,'T' ,'N' ,'w ','W' ,'M' ,'F' ,'h' ,'M ','m' ,'y' ,'Y' ,'H' ,'G' ,'i' ,'S' ,'s' ,'O','n');
	/* doc here: http://fr2.php.net/manual/en/function.date.php */
	$locformchar = array('%p','%P','%a','%A','%l','%d','%Z','%e','%j','%Z','%U','%w','%W','%b','%B','%I', '%h', '%m','%y','%Y','%H','%l','%M', '','%S','%z','%m');
	/* doc here: http://fr.php.net/manual/en/function.strftime.php */
	
   if ('' == $phpformat) $phpformat = 'm/d/Y H:i';
   // use to detect escape char that illustrate date or hour... \h or \m
   	$ars = explode('\\', $phpformat ); $i=0;
	if ($ars[0] == $phpformat) {
		$locform = str_replace($phpformchar, $locformchar,$phpformat);
	} else {
		foreach ($ars as $a) {
			if (""!= $a) {
			$locform = $locform.((0 == $i) ? str_replace($phpformchar, $locformchar,$a) : substr($a,0,1).str_replace($phpformchar, $locformchar, substr($a,1)) );
			}
			$i++;
		}
	//echo $s;
	}
   	return $locform ; //strtr( $phpformat, array_combine($phpformchar, $locformchar) );
   //str_replace($phpformchar, $locformchar, $phpformat);
}

/**
 * Return the language of current post in loop.
 *
 * @since 0.9.7.0
 * @updated 0.9.9
 * useful for functions in functions.php or other plugins
 * 
 * @param ID of the post
 * @return the name of language as ISO code (en_US).
 */
function get_cur_language( $post_ID ) {
	global $xili_language;
	$langpost = $xili_language->get_cur_language($post_ID);
	return $langpost[QUETAG];
}

/**
 * Return the lang and dir of language of current post in loop.
 *
 * @since 0.9.9
 * useful for functions in functions.php or other plugins
 * 
 * @param ID of the post
 * @return array two params : lang and direction of lang (ltr or rtl).
 */
function get_cur_post_lang_dir( $post_ID ) {
	global $xili_language;
	return $xili_language->get_cur_language($post_ID);
}

/**
 * Return languages objects in taxinomy. Useful for hooks as in functions.php of theme
 *
 * @since 1.6.0
 * @param $force to avoid buffer
 */
 function xili_get_listlanguages( $force = false ) {
 	global $xili_language;
 	return $xili_language->get_listlanguages($force);
 }

/**
 * Return language object of a post.
 *
 * @since 1.1.8
 * useful for functions in functions.php or other plugins
 * 
 * @param ID of the post
 * @return false or object with params as in current term (->description = full name of lang, ->count = number of posts in this language,...
 */
function xiliml_get_lang_object_of_post( $post_ID ) {
	
	$ress = wp_get_object_terms( $post_ID, TAXONAME ); /* lang of target post */
	if ($ress == array()) {
		return false;
	} else {
		return $ress[0];
	}
}

/**
 * Return the language of current browser.
 *
 * @since 0.9.7.6
 * @updated 0.9.9
 * useful for functions in functions.php or other plugins
 * 
 * @param no
 * @return the best choice.
 */
function choice_of_browsing_language() {
	global $xili_language;
	return $xili_language->choice_of_browsing_language();
}

/**
 * Return the lang and dir of current browser.
 *
 * @since 0.9.9
 * useful for functions in functions.php or other plugins
 * 
 * @param no
 * @return array of the best choice lang and his dir.
 */
function choice_of_browsing_lang_dir() {
	global $xili_language;
	$lang = $xili_language->choice_of_browsing_language();
	$dir = $xili_language->get_dir_of_cur_language($lang);
	return array(QUETAG=>$lang,'direction'=>$dir);
}

/**
 * Activate hooks of plugin in class.
 *
 * @since 0.9.7.4
 * can be used in functions.php for special action
 *
 * @param filter name and function
 * 
 */
function add_again_filter( $filtername, $filterfunction ) {
	global $xili_language;
	$xili_language->add_filter($filtername,$filterfunction);
}

/**
 * Replace get_category_link to bypass hook from xili_language
 *
 * @since 0.9.7.4
 * @updated 1.0.1
 * can be used in functions.php for special action needing permalink
 
 * @param category ID 
 * @return the permalink of passed cat_id.
 */
function xiliml_get_category_link( $catid = 0 ) {
			global $xili_language;
			if ($catid == 0) {
				global $wp_query;
				$catid = $wp_query->query_vars['cat'];
			}
			remove_filter('category_link', $xili_language->idx['xiliml_link_append_lang']);
				$catcur = get_category_link($catid); 
			add_again_filter('category_link', 'xiliml_link_append_lang');
	return $catcur;
}

/** used by xili widget - usable if you need to create your own template tag 
 *
 * @since 0.9.9.4
 * @param quantity of comments
 *
 * @return comments objects...
 */
function xiliml_recent_comments( $number = 5 ) {
	global $xili_language;
	return $xili_language->xiliml_recent_comments($number);
}

/**
 * Return full object of a language
 * @since 1.1.8
 * @param name (fr_FR) or slug (fr_fr)
 * @return false or full language object (example ->description = full as set in admin UI)
 */
function xiliml_get_language( $lang_nameorslug="" ) {
	$language = is_term( $lang_nameorslug, TAXONAME );
	if ($language) {
		return get_term($language['term_id'],TAXONAME,OBJECT,'edit');
	} else {
		return false;	
	}
}
 
 	/* 
	 **
	 * Template Tags for themes (with current do_action tool some are hookable functions) 
	 **
	 */
	 
/**
 * Template Tag insertable in search form for sub-selection of a language
 *
 * @since 0.9.7
 * @updated 1.8.2
 * can be used in theme template
 * example: if(class_exists('xili_language')) xiliml_langinsearchform()
 *
 * hook: add_action('xiliml_langinsearchform','your_xiliml_langinsearchform',10,2) to change its behaviour elsewhere
 * @param html tags 
 * @return echo the list as radio-button
 */	 
function xiliml_langinsearchform ( $before='', $after='', $echo = true ) { /* list of radio buttons for search form*/
	global $xili_language;
	if ($xili_language->this_has_filter('xiliml_langinsearchform')){ 
		remove_filter('xiliml_langinsearchform',$xili_language->idx['xiliml_langinsearchform']); /*no default from class*/
	}
	if ( $echo ) {
		echo apply_filters( 'xiliml_langinsearchform', $before, $after, $echo);	
	} else {
		return apply_filters( 'xiliml_langinsearchform', $before, $after, $echo);
	}
}

/**
 * Template Tag - replace the_category() tag of WP Core
 *
 * @since 0.9.0
 * @updated 1.4.2 - default value to post_ID
 * can be used in theme template in each post in loop
 * example: if(class_exists('xili_language')) xiliml_the_category($post->ID)
 *
 * hook: add_action('xiliml_the_category','your_xiliml_the_category',10,3) to change its behaviour elsewhere
 * @param post_id separator echo (true by default) 
 * @return echo (by default) the list of cats with comma separated...
 */
function xiliml_the_category( $post_ID = 0, $separator = ', ' , $echo = true ) { /* replace the_category() */
	global $xili_language, $post;
	if ($post_ID == 0) $post_ID = $post->ID;
	if ($xili_language->this_has_filter('xiliml_the_category')){ 
		remove_filter('xiliml_the_category',$xili_language->idx['xiliml_the_category']); /*no default from class*/
	}
	do_action('xiliml_the_category',$post_ID,$separator,$echo);
}

/**
 * Template Tag - in loop display the link of other posts defined as in other languages
 *
 * @since 0.9.0
 * @updated 0.9.9.2, 1.1 (can return an array of lang + id)
 * @updated 1.4.2 - default value to post_ID
 * can be used in theme template in single.php under the title
 * example: if(class_exists('xili_language')) xiliml_the_other_posts($post->ID)
 *
 * hook: add_action('xiliml_the_other_posts','your_xiliml_the_other_posts',10,3) to change its behaviour elsewhere
 * @param post_id, before, separator, type (echo, array).
 * @return echo (by default) the list 
 */
function xiliml_the_other_posts ( $post_ID = 0, $before = "This post in", $separator = ", ", $type = "display" ) { /* display the other posts defined as in other lang */
	global $xili_language, $post;
	if ($post_ID == 0) $post_ID = $post->ID;
	if ($xili_language->this_has_filter('xiliml_the_other_posts')){ 
		remove_filter('xiliml_the_other_posts',$xili_language->idx['xiliml_the_other_posts']); /*no default from class*/
	}
	return apply_filters('xiliml_the_other_posts',$post_ID, $before, $separator,$type);
}

/**
 * Template Tag - in loop display the language of the post
 *
 * @since 0.9.0
 * can be used in theme template in loop under the title
 * example: if(class_exists('xili_language')) xili_post_language()
 *
 * hook: add_action('xili_post_language','your_xili_post_language',10,2) to change its behaviour elsewhere
 * @param before after 
 * @return echo (by default) the language 
 */
function xili_post_language( $before = '<span class="xili-lang">(', $after =')</span>' ) { /* post language in loop*/
	do_action( 'xili_post_language',$before, $after );
}

/**
 * Template Tag - outside loop (sidebar) display the languages of the site (used also by widget)
 *
 * @since 0.9.0
 * @updated 0.9.7.4
 * @udpated 1.8.9.1 
 * can be used in theme template in sidebar menu or header menu
 * example: if(class_exists('xili_language')) xili_language_list()
 * theoption param is used to define type of display according places (sidebar / header) in theme (see dev.xiligroup.com)
 *
 * hook: add_action('xili_language_list','your_xili_language_list',10,5) to change its behaviour elsewhere
 * @param before after theoption
 * @return echo the list of languages
 * @hidden don't list hidden languages
 */
function xili_language_list( $before = '<li>', $after ='</li>', $theoption='', $echo = true, $hidden = false ) { 
	
	global $xili_language;
	if ($xili_language->this_has_filter('xili_language_list')){ 
		remove_filter('xili_language_list',$xili_language->idx['xili_language_list']); /*no default from class*/
	}	
	return apply_filters('xili_language_list', $before, $after, $theoption, $echo, $hidden ); 
}

/** 
 * function to get id, link or permalink of linked post in target lang
 * to replace get_post_meta($post->ID, 'lang-'.$language->slug, true) soon obsolete
 * @since 1.8.9.1
 *
 * @updated 2.1.0 - permalink as 
 */
function xl_get_linked_post_in ( $fromID, $lang, $info = 'id' ) {
	global $xili_language;
	
	$language = xiliml_get_language( $lang ); /* test if lang is available */
	
	if ( $language !== false ) {
		$otherpost = get_post_meta( $fromID, QUETAG.'-'.$language->slug, true ); // will be soon changed
		if ( $info == 'permalinknav') return  $xili_language ->link_of_linked_post ( $fromID, $language->slug );
	
		if ( $otherpost ) { 
			switch ( $info ) {
				case 'id';
					$output = $otherpost;
					break;
				case 'link';
					$post = get_post($otherpost);
			        if ( isset($post->post_type) ){
			         	if ( 'post' == $post->post_type ) {
							$output = home_url('?p=' . $otherpost);
			         	} elseif ( 'post' == $post->post_type ) {
			         		$output = home_url('?page_id=' . $otherpost);
			         	}
			        }
					break;
				case 'permalink';
					$output = get_permalink( $otherpost );
					break;	
			}
		} else {
			switch ( $info ) {
				case 'id';
					$output = 0; // false
					break;
				case 'link';
					$output = '#';
					break;
				case 'permalink';
					$output = '#';
					break;	
			}
			
		}
		return $output;
	}
}

/** 
 * Insert automatically some languages items at end in menu
 * @since 1.6.0
 * @updated 1.7.1 - add optionally wp_page_list result
 * @updated 1.8.1 - choose good menu location
 * @updated 1.8.9 - add filter (example: add_filter ('xili_nav_lang_list', 'my_xili_nav_lang_list', 10, 3);)
 *					 and class for separator  ( example: li.menu-separator a {display:none;})
 *
 * updated 2.1.0 - for multiple navmenu locations - CAUTION new filter: xili_nav_lang_lists (with s at end)
 */
function xili_nav_lang_list( $items, $args ) {
	global $xili_language;
	
	if ( isset ( $xili_language->xili_settings['navmenu_check_options'] ) ) {
		$navmenu_check_options = $xili_language->xili_settings['navmenu_check_options']; 
		
		if ( has_filter( 'xili_nav_lang_lists' ) ) return apply_filters ( 'xili_nav_lang_lists',  $items, $args, $navmenu_check_options );
		
		if ( isset($navmenu_check_options[$args->theme_location]) && $navmenu_check_options[$args->theme_location]['navenable'] == 'enable' ) { 
			
			$navmenu = ( '' != $navmenu_check_options[$args->theme_location]['navtype'] ) ? $navmenu_check_options[$args->theme_location]['navtype'] : "navmenu-1";
			
			$end = xili_language_list( '<li>', '</li>', $navmenu, false, true ) ; // don't display hidden languages
			
	   		return $items.'<li class="menu-item menu-separator" ><a>|</a></li>'.$end; // class for display none... 
	   	
		} else {
			return $items;
		}
		
	} else { // if settings not updated since updated
		$navmenu_check_option = $xili_language->xili_settings['navmenu_check_option'];
		if ( has_filter( 'xili_nav_lang_list' ) ) return apply_filters ( 'xili_nav_lang_list',  $items, $args, $navmenu_check_option );
		if ( $navmenu_check_option == $args->theme_location ) { 
		
			$end = xili_language_list( '<li>', '</li>', 'navmenu', false, true ) ; // don't display hidden languages 1.8.9.1
			
   			return $items.'<li class="menu-item menu-separator" ><a>|</a></li>'.$end; // class for display none... 1.8.9 no ID for instantiations
   	
		} else {
			return $items;
		}
	}
}

/** 
 * Insert automatically some pages items at end in menu
 * @since 1.7.1 - add optionally wp_page_list result
 * @updated 1.8.1 - choose good menu location
 * @updated 1.8.9 - add filter (example: add_filter ('xili_nav_page_list', 'my_xili_nav_page_list', 10, 3);)
 *
 */ 
function xili_nav_page_list( $items, $args ) {
	global $xili_language;
	$navmenu_check_option = $xili_language->xili_settings['navmenu_check_optionp'];
	if ( has_filter( 'xili_nav_page_list' ) ) return apply_filters ( 'xili_nav_page_list',  $items, $args, $navmenu_check_option );
	if ( $navmenu_check_option == $args->theme_location ) {
		$pagelist = ''; 
		$pagelist_args = $xili_language->xili_settings['args_page_in_nav_menu'].'&';
		// sub-selection
		$pagelist = wp_list_pages($pagelist_args."title_li=&echo=0&".QUETAG."=".$xili_language->curlang);
	
   		return $items.$pagelist;
   	} else {
		return $items;
	}
}

/** 
 * modify automatically home page item in nav menu - exemple for twentyten child menu
 * @since 1.8.9.2 
 *  
 * filter (example: add_filter ('xili_nav_page_home_item', 'my_xili_nav_page_home_item', 10, 5);)
 *
 */ 
function xili_nav_page_home_item( $item_output, $item, $depth, $args ) {
	global $xili_language;
	$homemenu_check_option = $xili_language->xili_settings['home_item_nav_menu'];
	if ( has_filter( 'xili_nav_page_home_item' ) ) return apply_filters ( 'xili_nav_page_home_item',  $item_output, $item, $depth, $args, $navmenu_check_option );
	
	if ( $item->url == get_option('siteurl').'/'  ) { // page or list
		$curlang = $xili_language->curlang ;
		
		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . __( esc_attr( $item->attr_title ), $xili_language->thetextdomain ) .'"' : ''; //
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'?'.QUETAG.'='.$curlang.'"' : ''; //

		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;
		return $item_output;
   	} else {
		return $item_output;
	}
}


/*
 * sub selection of pages for wp_list_pages()
 * @ since 090504 - exemple of new function add here or addable in functions.php
 * © xiligroup.dev
 *
 * only called if xili-language plugin is active and query tag 'lang' is in wp_list_pages template tag
 *
 * example 1 : wp_list_pages('title_li=&lang='.the_curlang() ); will display only pages of current lang
 *
 * example 2 : wp_list_pages('title_li=&setlang=0&lang='.the_curlang() ); will display pages of current lang AND pages with lang undefined (polyglot pages).
 *
 */
function ex_pages_by_lang ( $pages, $r ) {
	if (isset($r[QUETAG]) && !empty($pages) && function_exists('get_cur_post_lang_dir')) {
		$keepundefined = null;
		if (isset($r['setlang'])) { 
			if ($r['setlang'] == 0 || $r['setlang'] == 'false') $keepundefined = false;
			if ($r['setlang'] == 1 || $r['setlang'] == 'true') $keepundefined = true;
		}	
		$resultingpages = array();
		foreach ($pages as $page) {
			$post_lang_dir = get_cur_post_lang_dir($page->ID);
			if ($post_lang_dir === $keepundefined) {
					$resultingpages[] = $page;
			} elseif ($post_lang_dir[QUETAG] == $r[QUETAG] ) {
					$resultingpages[] = $page;
			}
		}
		return $resultingpages;	
	} else {	
	 	return $pages;
	}
}
add_filter('get_pages','ex_pages_by_lang',10,2);

/**
 * functions to change and restore loop's query tag
 * (useful for sidebar widget - see functions table)
 * @since 1.3.0
 * @param  lang to modify query_tag - 
 *
 */
function xiliml_force_loop_lang ( $lang_query_tag ){
	global $xili_language, $wp_query;
	$xili_language->temp_lang_query_tag = $wp_query->query_vars[QUETAG];
	$wp_query->query_vars[QUETAG] = $lang_query_tag;
	$xili_language->current_lang_query_tag = $wp_query->query_vars[QUETAG];
}

function xiliml_restore_loop_lang (){
	global $xili_language, $wp_query;
	$wp_query->query_vars[QUETAG] = $xili_language->temp_lang_query_tag;
	$xili_language->current_lang_query_tag = $wp_query->query_vars[QUETAG];
}

/**
 * functions to permit lang query tag
 * (useful for WP_Query)
 * @since 1.3.2
 * example: add_action('parse_query','xiliml_add_lang_to_parsed_query');
 *		$r = new WP_Query($thequery);
 *		remove_filter('parse_query','xiliml_add_lang_to_parsed_query'); 
 * used by class xili_Widget_Recent_Posts
 */
function xiliml_add_lang_to_parsed_query ($theclass = array()) {
		global $wp_query;
		$query = $theclass->query;
		if (is_array($query)) {
			$r = $query;
		} else {
			parse_str($query, $r); 	
		}
		if (array_key_exists(QUETAG,$r)) $wp_query->query_vars[QUETAG] = $r[QUETAG];
}

/******* functions and filter added for new default theme named twentyten (since WP 3.0) ********/

/** * in twentyten theme: display the time of current post when mouse is on date */
function xiliml_get_the_translated_time($thetime, $format = '') {
	$theformat = (''== $format) ? get_option('time_format') : $format ;
	return the_xili_wp_local_time($theformat,strtotime(xiliml_get_the_time('m/d/Y H:i')));
}
add_filter('get_the_time','xiliml_get_the_translated_time',10,3);

/** * Clone w/o filter */
function xiliml_get_the_time( $d = '', $post = null ) {
	$post = get_post($post);

	if ( '' == $d )
		$the_time = get_post_time(get_option('time_format'), false, $post, true);
	else
		$the_time = get_post_time($d, false, $post, true);
	return $the_time; /* without filter */
}

/** * in twentyten theme: display the date of current post */
function xiliml_get_translated_date($thedate, $format = '') {
	$theformat = (''== $format) ? get_option('date_format') : $format ;
	return the_xili_wp_local_time($theformat,strtotime(xiliml_get_the_date('m/d/Y H:i')));
}
add_filter('get_the_date','xiliml_get_translated_date',10,2);

/** * Clone w/o filter */
function xiliml_get_the_date( $d = '' ) {
	global $post;
	$the_date = '';

	if ( '' == $d )
		$the_date .= mysql2date(get_option('date_format'), $post->post_date);
	else
		$the_date .= mysql2date($d, $post->post_date);

	return $the_date; /* without filter */
}

/** * filter for template tag: get_comment_date() */
function xiliml3_comment_date($comment_time, $format = '') {
  $theformat = (''== $format) ? get_option('date_format') : $format ;
  return the_xili_wp_local_time($theformat, strtotime(get_comment_time ('m/d/Y H:i'))); 
  /* impossible to use get_comment_date as it is itself filtered*/
}
add_filter('get_comment_date','xiliml3_comment_date',10,2);


require_once (dirname(__FILE__) . '/xili-language-widgets.php');


/**
 * instantiation of xili_language class
 *
 * @since 1.8.8 to verify that WP 3.0 is installed
 * @updated for 1.8.9
 *
 */

if ( version_compare($wp_version, '3.0-alpha', '<') && XILILANGUAGE_VER > '1.8.9' ) {
	add_action( 'admin_notices', 'xili_language_need_30' );
	
	return;
} else {
	
	/**
 	 * instantiation of xili_language class
 	 *
 	 * @since 0.9.7 - 0.9.9.4 =& for vintage server with php4 !!!! - 1.1.9
 	 *
 	 * 
 	 * @param locale_method (true for cache compatibility... in current tests with johan.eenfeldt@kostdoktorn.se)
 	 * @param future version
 	 */
	
	$xili_language =& new xili_language( false , false ); 
	
	/**
	 * Enable to add functions and filters that are not in theme's functions.php
	 * These filters are common even if you change default theme...
	 * Place your functions.php in folder plugins/xilidev-libraries/
	 * if you have a filter in this file, avoid to have similar one in functions.php of the theme !!!
	 * 
	 * (for tests, check / uncheck 'enable gold functions' in settings UI)
	 * @since 1.0
	 * @updated 1.4.0
	 */
	$xili_language->insert_gold_functions ();
}

/** * errors messages */
function xili_language_need_30() {
		global $wp_version;
		load_plugin_textdomain( 'xili_language_errors',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)) );
		echo '<div id="message" class="error fade"><p>';
		echo '<strong>'.__('Installation of xili-language is not completed.', 'xili_language_errors' ) . '</strong>';
		echo '<br />';
		printf( __('This xili-language version (%s) need WordPress Version more than 3.0 alpha; installed release is %s.', 'xili_language_errors' ), XILILANGUAGE_VER , $wp_version) ;
		echo '<br />';
		_e('Upgrade WordPress Version to 3.x or use xili-language version less than 1.7.', 'xili_language_errors' );
		echo '</p></div>';
}

?>