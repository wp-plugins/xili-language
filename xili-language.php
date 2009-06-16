<?php
/*
Plugin Name: xili-language
Plugin URI: http://dev.xiligroup.com/xili-language/
Description: This plugin introduce a new taxonomy - here language - to modify on the fly the translation of the theme depending the language of the post or other blog elements - a way to create a real multi-language site (cms or blog).
Author: dev.xiligroup.com - MS
Version: 1.0
Author URI: http://dev.xiligroup.com
*/

# updated 090615 - 1.0 - Via admin UI, new ways to choose default language of front-page (page, home,...)
# updated 090606 - 0.9.9.6 - ready for 2.8 hooks - ready for multiple languages list widget
# see readme text for these intermediate versions.
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

define('XILILANGUAGE_VER','1.0'); /* used in admin UI*/

class xili_language {
	
	var $default_lang; /* language of config.php*/
	var $curlang;
	var $langstate; /* undefined or not */
	var $browseroption = '';
	var $authorbrowseroption = '';
	var $functions_enable = '';
	var $default_dir = ''; /* undefined or not in WP config '' or rtl or ltr */
	var $curlang_dir = ''; /* undefined or not according array */
	var $rtllanglist = 'ar-he-fa-ur'; /*default-list - can be set after class instantiation*/
	var $post_ajax = false; /* ajax used in meta box in post edit UI unstable yet */
	var $is_metabox = false; /* meta box in post edit UI - if used don't use custom fields that are not refreshed */
	var $xili_settings; /* saved in options */
	var $langs_group_id; /* group ID and Term Taxo ID */
	var $langs_group_tt_id; 
	var $get_archives_called = array(); /* if != '' - insert lang in link */
	var $idx = array(); /* used to identify filter or action set from this class - since 0.9.9.6 */
	
	function xili_language($metabox = false, $post_ajax = false) {
		$this->is_metabox = $metabox;
		$this->post_ajax = $post_ajax;
		/*activated when first activation of plug*/
		register_activation_hook(__FILE__,array(&$this,'xili_language_activate'));
	
		/*get current settings - name of taxonomy - name of query-tag - 0.9.8 new taxonomy taxolangsgroup */
		$this->xili_settings = get_option('xili_language_settings');
		if(empty($this->xili_settings)) {
			$submitted_settings = array(
			    'taxonomy'		=> 'language',
			    'version' 		=> '0.3',
			    'reqtag'		=> 'lang',
			    'browseroption' => '',
			    'authorbrowseroption' => '',
			    'taxolangsgroup' => 'languages_group',
			    'functions_enable' => ''
		    );
			define('TAXONAME','language');
			define('QUETAG','lang');
			define('TAXOLANGSGROUP','languages_group');
			update_option('xili_language_settings', $submitted_settings);	
			$this->xili_settings = get_option('xili_language_settings');		
		} else {
			define('TAXONAME',$this->xili_settings['taxonomy']);
			define('QUETAG',$this->xili_settings['reqtag']);
			$this->browseroption = $this->xili_settings['browseroption'];
			$this->authorbrowseroption = $this->xili_settings['authorbrowseroption'];
			$this->functions_enable = $this->xili_settings['functions_enable'];
			if ($this->xili_settings['version'] == '0.2') {
				$this->xili_settings['taxolangsgroup'] = 'languages_group';
				$this->xili_settings['version'] = '0.3';
				update_option('xili_language_settings', $this->xili_settings);
			}	
			define('TAXOLANGSGROUP',$this->xili_settings['taxolangsgroup']);
		}
		define('XILIFUNCTIONSPATH',WP_PLUGIN_DIR.'/xilidev-libraries'); /* since 1.0 to add xili-libraries */
		/* add new taxonomy in available taxonomies */
		register_taxonomy( TAXONAME, 'post',array('hierarchical' => false, 'update_count_callback' => array(&$this,'_update_post_lang_count')));
		register_taxonomy( TAXOLANGSGROUP, 'term',array('hierarchical' => false, 'update_count_callback' => ''));
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
		//print_r($thegroup[0]);
		/* default values */
		
		if (''!= WPLANG && strlen(WPLANG)==5) :
			$this->default_lang = WPLANG;
		else:
			$this->default_lang = 'en_US';
		endif;
		define('DEFAULTSLUG', $this->get_default_slug());
		if ( $dir = get_bloginfo('text_direction') ) /* if present in blog options @since 0.9.9 */
				$this->default_dir = $dir;
		
		add_filter('query_vars', array(&$this,'keywords_addQueryVar'));
		add_filter('posts_join', array(&$this,'with_lang'));
		add_filter('posts_where', array(&$this,'where_lang'));
		
		add_action('wp', array(&$this,'xiliml_language_head')); 
		/* 'wp' = where theme's language is defined just after query */
		add_filter('language_attributes',  array(&$this,'head_language_attributes'));
		add_action('wp_head', array(&$this,'head_insert_language_metas'),10,2);
 
 	 	add_filter('widget_title', array(&$this,'widget_texts')); /* added 0.9.8.1 */
	 	add_filter('widget_text', array(&$this,'widget_texts'));
		add_filter('list_cats', array(&$this,'xiliml_cat_language'),10,2); /* mode 2 : content = name */
		add_filter('category_link', array(&$this,'xiliml_link_append_lang'));
		add_filter('category_description',array(&$this,'xiliml_link_translate_desc'));
		add_filter('tag_link', array(&$this,'xiliml_taglink_append_lang' ));
		
		add_action('pre_get_posts', array(&$this,'xiliml_modify_querytag'));
		/* filters for archives since 0.9.9.4 */
		add_filter('getarchives_join', array(&$this,'xiliml_getarchives_join'),10,2);
		add_filter('getarchives_where', array(&$this,'xiliml_getarchives_where'),10,2);
		add_filter('get_archives_link', array(&$this,'xiliml_get_archives_link'));
		/* actions for post and page admin UI */
		add_action('save_post', array(&$this,'xili_language_add'));
		//add_action('publish_post', array(&$this,'xili_language_add')); /* only set when published !*/
		add_action('save_page', array(&$this,'xili_language_add'));
		//add_action('publish_page', array(&$this,'xili_language_add'));
		if ($this->post_ajax) {
			add_action( 'wp_ajax_oklinked', array(&$this,'ok_linked') );
			add_action( 'wp_ajax_customrefresh', array(&$this,'custom_refresh') );
		}
		/* admin settings UI*/
		add_action('init', array(&$this, 'init_textdomain'));
		add_filter('plugin_action_links',  array(&$this,'xililang_filter_plugin_actions'), 10, 2);
		
		add_action('admin_menu', array(&$this,'myplugin_add_custom_box'));
		add_action('admin_menu', array(&$this,'xili_add_pages'));
		
		//add_action('admin_print_styles', array(&$this, 'load_styles') );
		//add_action('admin_print_scripts', array(&$this, 'load_scripts') );

		/* inspired from custax */
		add_action('manage_posts_custom_column', array(&$this,'xili_manage_column'), 10, 2);
		add_filter('manage_edit_columns', array(&$this,'xili_manage_column_name'));

		add_action('manage_pages_custom_column', array(&$this,'xili_manage_column'), 10, 2);
		add_filter('manage_edit-pages_columns', array(&$this,'xili_manage_column_name'));
		
		//add_filter('locale',array(&$this,'get_locale_filter'));
		
		/* new actions for xili-language theme's templates tags */
		
		$this->add_action('xili_language_list','xili_language_list',10,3); /* add third param 0.9.7.4*/
		$this->add_action('xili_post_language','xili_post_language',10,2);
		
		$this->add_action('xiliml_the_other_posts','xiliml_the_other_posts',10,3);
		$this->add_action('xiliml_the_category','xiliml_the_category',10,3);
		$this->add_action('xiliml_langinsearchform','xiliml_langinsearchform',10,2);
	}
	
	function get_locale_filter ($locale) {
		//echo $locale;
		//echo $this->curlang;
		//define('XILITEST', '----'.$this->curlang);
		//return $this->curlang;
	}
	
	function load_styles () {
		//wp_admin_css( 'dashboard' );
		//wp_admin_css( 'global' );
		//wp_admin_css( 'press-this' );
		}
		
	function load_scripts () {	
		//wp_enqueue_script('inline-edit-post');	
	}
	
	function add_action ($action, $function = '', $priority = 10, $accepted_args = 1)
	{
		add_action ($action, array (&$this, $function == '' ? $action : $function), $priority, $accepted_args);
		$this->idx[$action] = _wp_filter_build_unique_id($action, array (&$this, $function == '' ? $action : $function), $priority); /* unique id of this filter from object */
		 
	}
	
	function add_filter ($filter, $function = '', $priority = 10, $accepted_args = 1)
	{
		add_filter ($filter, array (&$this, $function == '' ? $filter : $function), $priority, $accepted_args);
		$this->idx[$action] = _wp_filter_build_unique_id($action, array (&$this, $function == '' ? $action : $function), $priority); /* unique id of this filter from object */
	}
			
	/**
	 * More than one filter for the function. 
	 *
	 * @since 0.9.7
	 * 
	 * @param $the_function (string). 
	 * @return true if more than one.
	 */
	function this_has_filter($the_function) {
		global $wp_filter;
		$has = $wp_filter[$the_function];
		//print_r($has);
		$keys = array_keys($has);
		//echo count($has[$keys[0]]);
		if (count($has[$keys[0]]) >= 2) { /*one from class others from functions.php or elsewhere*/
			return true;
		} else {
			return false;
		} 	
	}	

	function myplugin_add_custom_box() {		
 		add_meta_box('xilil-2', __("Page's language",'xili-language'), array(&$this,'xili_language_checkboxes_n'), 'page', 'side','high');
 		add_meta_box('xilil-2', __("Post's language",'xili-language'), array(&$this,'xili_language_checkboxes_n'), 'post', 'side','high');
 		if ($this->is_metabox) {
 			add_meta_box('xilil-1', __('Linked posts','xili-language'), array(&$this,'xili_language_linked_posts'), 'post', 'side','high');
 			add_meta_box('xilil-1', __('Linked pages','xili-language'), array(&$this,'xili_language_linked_posts'), 'page', 'side','high');
 		}
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
	 * set language when post or page is saved 
	 *
	 * @since 0.9.0
	 * @completed 0.9.7.1 to record postmeta of linked posts in other languages
	 * @updated 0.9.7.5 to delete relationship when undefined
	 * @updated 0.9.9 to avoid delete relationship when in quick_edit
	 * @param $post_ID
	 */
	function xili_language_add($post_ID) {
		if (!isset($_POST['_inline_edit'])) { /* to avoid delete relationship when in quick_edit (edit.php) */
			$sellang = $_POST['xili_language_set'];
			if ("" != $sellang) {
				wp_set_object_terms($post_ID, $sellang, TAXONAME);
			} else {
					wp_delete_object_term_relationships( $post_ID, TAXONAME ); 	
			}
			if ($this->is_metabox) {	
				/* the linked posts set by author */	
				$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
				foreach ($listlanguages as $language) {
					$inputid = 'xili_language_'.QUETAG.'-'.$language->slug ;
					$recinputid = 'xili_language_rec_'.QUETAG.'-'.$language->slug ;
					$linkid = $_POST[$inputid];
					$reclinkid = $_POST[$recinputid]; /* hidden previous value */
					$langslug = QUETAG.'-'.$language->slug ;
					if ($reclinkid != $linkid) { /* only if changed value */
						if ((is_numeric($linkid) && $linkid == 0) || '' == $linkid ) {
							delete_post_meta($post_ID, $langslug);
						} elseif (is_numeric($linkid) && $linkid > 0) {
							update_post_meta($post_ID, $langslug, $linkid);	
						}
					}	
				}
			}
		} /* quick edit */		
	}
	
	/**
	 * Return language dir
	 *
	 * @since 0.9.9
	 * @param slug of lang
	 */
	function get_dir_of_cur_language($lang_slug) {
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
	function get_cur_language($post_ID) {
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
				return array('lang'=>$postlang,'direction'=>$postlangdir);
			}
	 	} else {
	 		$this->langstate = false; /* can be used in language attributes for header */
	  		return false;	/* undefined state */
	 	}		
	}
		
	/* first activation of plugin */
	function xili_language_activate() {
		$this->xili_settings = get_option('xili_language_settings');
		if(empty($this->xili_settings)) {
			$this->xili_settings = array(
			    'taxonomy'		=> 'language',
			    'version' 		=> '0.3',
			    'reqtag'		=> 'lang',
			    'browseroption' => '',
			    'authorbrowseroption' => '',
			    'taxolangsgroup' => 'languages_group',
			    'functions_enable' => ''
		    );
		    update_option('xili_language_settings', $this->xili_settings);
		}
	}
	
	/*enable the new query tag associated with new taxonomy*/
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
	
	/*filters used when querytag is used - 
	 *see below and functions.php where rules depend from theme
	 */
	function with_lang($join) {
		global $wp_query, $wpdb;	
		if ( '' != $wp_query->query_vars[QUETAG] ) {
			$join .= " LEFT JOIN $wpdb->term_relationships as tr ON ($wpdb->posts.ID = tr.object_id) LEFT JOIN $wpdb->term_taxonomy as tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) ";
		}
	
	return $join;
	}
	/**
	 * Setup global post data.
	 *
	 * @since 0.9.0
	 * @updated 0.9.4 (OR added) lang=xx_xx,yy_yy,..
	 *
	 * @param object $where.
	 * @return $where.
	 */
	function where_lang($where) {
		global $wp_query , $wpdb;
		$reqtags = array();
		$thereqtags = array();
		if ( '' != $wp_query->query_vars[QUETAG] ) {
		/* one or more lang - no + because only one lang per post now */
			if ( strpos($wp_query->query_vars[QUETAG], ',') !== false ) {
				$langs = preg_split('/[,\s]+/', $wp_query->query_vars[QUETAG]);
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
				$where .= " AND tt.taxonomy = '".TAXONAME."' ";
				$where .= " AND tt.term_id IN ( $wherereqtag )";
			
			} else {
			/* only one lang */
				$wp_query->query_vars[QUETAG] = sanitize_term_field('slug', $wp_query->query_vars[QUETAG], 0, 'post_tag', 'db');
				$reqtag = $wp_query->query_vars[QUETAG];
				$reqtag = is_term( $reqtag, TAXONAME );
				if (''!= $reqtag) {
					$wherereqtag = $reqtag['term_id'];
				} else {
					$wherereqtag = 0;	
				}
				$where .= " AND tt.taxonomy = '".TAXONAME."' ";
				$where .= " AND tt.term_id = $wherereqtag ";
			}
					
		}	
		return $where;
	}

	/* template theme live modification */
	
	/**
	 * wp_head action for theme headers 
	 *
	 * @since 0.9.0
	 * @updated 0.9.9
	 * can be hooked in functions.php xiliml_cur_lang_head
	 * call by wp_head()
	 * @param 
	 */
	function xiliml_language_head() {
		$this->curlang = $this->xiliml_cur_lang_head();
		$this->curlang_dir = $this->get_dir_of_cur_language($this->curlang); /* general dir of the theme */
		$this->set_mofile($this->curlang);
	}
	/**
	 * default rules - set curlang in head according rules 
	 *
	 * @since 0.9.7
	 * @updated 0.9.7.1 - if no posts 0.9.9.1 - 0.9.9.4 
	 * 
	 * default filter of xiliml_cur_lang_head
	 * @return $curlang .
	 */
	function xiliml_cur_lang_head () {
		if (has_filter('xiliml_cur_lang_head')) return apply_filters('xiliml_cur_lang_head',''); /* '' warning on some server need one arg by default*/
		/* default */
		global $post,$wp_query;
			if (have_posts()) {
				if(!is_front_page()) { /* every pages */
					$curlangdir = $this->get_cur_language($post->ID);
					$curlang = $curlangdir['lang']; /* the first post give the current lang*/
					if ($curlangdir == false) $curlang = DEFAULTSLUG; /* can be changed if use hook */
					if (is_page()) {
						if (isset($_GET["loclang"])) {
			    			$curlang=$_GET["loclang"];
			    		/* get var to override the selected lang - ex. in bi-lingual contact*/
						}
					} 
					elseif (is_search() && isset($_GET["lang"])) {
						$curlang=$_GET["lang"]; /*useful when no result*/
					}
				} else { /* front page */
					if ( '' != $wp_query->query_vars[QUETAG] ) {
						$curlang = $wp_query->query_vars[QUETAG];	/* home series type*/
					} else {
						$showpage = get_settings('show_on_front');
						$page_front = get_settings('page_on_front');
						$hcurlang = (isset($_GET["hlang"])) ? $_GET["hlang"] : $this->choice_of_browsing_language() ; 
						$target = get_post_meta($page_front, 'lang-'.$hcurlang, true);
						if ($showpage == "page") {
							if ($target && $target != $post->ID) { /* only if present and diff */
								query_posts('page_id='.$target); 
								if (have_posts()) {
									the_post();
									$curlang = get_cur_language($post->ID);
									rewind_posts();
								} else {
									query_posts('page_id='.$page_front); /* restore */
									$curlang = get_cur_language($page_front);
								}
							} else {
								$curlang = get_cur_language($post->ID);	
							}
						} else { /* home */
							$curlang = $this->choice_of_browsing_language();
						}	
					}	
				}
			} else { /*no posts for instance in category + lang */
			 	if (isset($_GET["lang"])) {
			    		$curlang=$_GET["lang"];
			    } else {
			    		$curlang = $this->choice_of_browsing_language();//strtolower(WPLANG); /* select here the default language of the site */
			   	}
			}	
			return $curlang; /* as in external hook for filter*/
	}
	
	/**
	 *select .mo file 
	 * @since 0.9.0
	 * @updated 0.9.7.1
	 * call by function in wp_head : see xiliml_language_head()
	 * @param $curlang .
	 */
	function set_mofile($curlang) {
	// load_theme_textdomain(THEME_TEXTDOMAIN); - replaced to be flexible -
		if (defined('THEME_TEXTDOMAIN')) {$themetextdomain = THEME_TEXTDOMAIN; } else {$themetextdomain = 'ttd-not-defined';  }
		if (defined('THEME_LANGS_FOLDER')) {$langfolder = '/'.str_replace("/","",THEME_LANGS_FOLDER).'/' ;} else {$langfolder = "/"; } /* added when .mo files are in subfolder of themes */
		$listlanguages = get_terms(TAXONAME, array('hide_empty' => false,'slug' => $curlang));
		$filename = $listlanguages[0]->name;
		$filename .= '.mo';
		if ('' != $filename) {
			$mofile = get_template_directory() .$langfolder."$filename";	
			load_textdomain($themetextdomain,$mofile);
		}
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
	function head_language_attributes($output) {
		/* hook head_language_attributes */
		if (has_filter('head_language_attributes')) return apply_filters('head_language_attributes',$output);
		$attributes = array();
		$output = '';

		if ( $dir = get_bloginfo('text_direction') ) /*use hook for future use */
			$attributes[] = "dir=\"$dir\"";
		if ($this->langstate == true) {	
			$lang = str_replace('_','-',substr($this->curlang,0,3).strtoupper(substr($this->curlang,-2)));
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
		if ( get_option('html_type') == 'text/html')
				$attributes[] = "lang=\"$lang\"";
	
		if ( get_option('html_type') != 'text/html')
			$attributes[] = "xml:lang=\"$lang\"";	

		$output = implode(' ', $attributes);
		return $output;
	}
	
	/**
	 * modify  insert language metas in head (via wp_head)
	 *
	 * @since 0.9.7.6
	 * @updated 0.9.8 
	 * @must be defined in functions.php according general theme design (wp_head) 
	 *   
	 * @param $curlang
	 */
	function head_insert_language_metas($curlang,$undefined=true) {
		$curlang = $this->curlang;
		$undefined = $this->langstate;
			if (has_filter('head_insert_language_metas')) return apply_filters('head_insert_language_metas',$curlang,$undefined);
	}
	
	/**
	 * Translate texts of widgets  
	 *
	 * @since 0.9.8.1
	 * @ return
	 */
	function widget_texts ($value){
		return __($value,THEME_TEXTDOMAIN);
	}
			
	/**
	 * insert other language of wp_list_categories 
	 *
	 * @since 0.9.0
	 * @updated 0.9.8.4
	 * can be hooked by filter add_filter('xiliml_cat_language','yourfunction',2,3) in functions.php
	 * call by do_filter list_cats 
	 * @param $content, $category
	 */
	function xiliml_cat_language ($content, $category = null) {
		if (has_filter('xiliml_cat_language')) return apply_filters('xiliml_cat_language',$content, $category,$this->curlang);
		/* default */ 
	            /*set by locale of wpsite*/
	      /*these rules can be changed by using */
	    if (!is_admin()) : /*to detect admin UI*/
	      	$new_cat_name =  __($category->name,THEME_TEXTDOMAIN); /*visible ??? in dashboard ???*/
	      	if ($new_cat_name != $content) : 
	      		$new_cat_name .= " (". $content .") ";
	      	endif;
	      		 		/* due to default if no translation*/
	    else :
	    	$new_cat_name =  $content;
	    endif; 
	    return $new_cat_name;
	 } 
	
	/**
	 * add the language key in category links of current pages
	 *
	 * @since 0.9.0
	 * update 0.9.7
	 * can be hooked by filter add_filter('xiliml_link_append_lang','yourfunction',2,2) in functions.php
	 * call by do_filter 
	 * @param $content,
	 */
	function xiliml_link_append_lang( $link ) {
		if (has_filter('xiliml_link_append_lang')) return apply_filters('xiliml_link_append_lang',$link,$this->curlang);
		/*default*/
	  		if ($this->curlang) :
	  			$link .= '&amp;'.QUETAG.'='.$this->curlang ;
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
	 * to cancel sub select by lang in cat 1 by default 
	 *
	 * @since 0.9.2
	 * update 0.9.7
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
		if (!defined('XILI_CATS_ALL')) define('XILI_CATS_ALL','1'); /* change in functions.php or use hook in cat 1 by default*/
			$excludecats = explode(",", XILI_CATS_ALL);
			if (!empty($wp_query->query_vars['cat'])) {
				if 	(in_array($wp_query->query_vars['cat'],$excludecats)) {
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
	function xiliml_getarchives_join($join,$r) {
		global $wpdb;
		if (has_filter('xiliml_getarchives_join')) return apply_filters('xiliml_getarchives_join',$join,$r,$this->curlang);
		extract( $r, EXTR_SKIP );
		$this->get_archives_called = $r;
		if (isset($lang)) {
			if ("" == $lang ) { /* used for link */
				$this->get_archives_called['lang'] = $this->curlang;
			} else {
				$this->get_archives_called['lang'] = $lang;
			}
			$join = " INNER JOIN $wpdb->term_relationships as tr ON ($wpdb->posts.ID = tr.object_id) INNER JOIN $wpdb->term_taxonomy as tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) ";
		
		}
		return $join;
		
	}
	function xiliml_getarchives_where($where,$r) {
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
	/* here basic translation - to improve depending theme features : use hook 'xiliml_get_archives_link' */
	function xiliml_get_archives_link($link_html) {
		if (has_filter('xiliml_get_archives_link')) return apply_filters('xiliml_get_archives_link', $link_html,$this->get_archives_called, $this->curlang);
		extract( $this->get_archives_called, EXTR_SKIP );
		if ('' != $lang) {
			$permalink = get_option('permalink_structure');
			$sep = ('' == $permalink) ? "&amp;lang=" : "?lang=";
			if ($format != 'option' && $format != 'link' && $type != 'postbypost' && $type != 'alpha') {
				/* text extract */
				$i = preg_match_all("/'>(.*)<\/a>/Ui", $link_html, $matches,PREG_PATTERN_ORDER);
				$line = $matches[1][0];
				/* link extract */
				$i = preg_match_all("/href='(.*)' title/Ui", $link_html, $matches,PREG_PATTERN_ORDER);
				if ( '' == $type || 'monthly' == $type) {
					if ('' == $permalink) {
						$archivedate = str_replace(get_bloginfo('siteurl').'/?' , "" , $matches[1][0]);
						$r = wp_parse_args( $archivedate, array());
						extract($r, EXTR_SKIP );
						$month = substr($m,-2);
						$year = substr($m,0,4);
					} else {
						$archivedate = str_replace(get_bloginfo('siteurl').'/date/' , "" , $matches[1][0]);
						$month = substr($archivedate,-3,2);
						$year = substr($archivedate,0,4);
					}		
					//echo $month . ' - '. $year ;
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
	function xiliml_link_translate_desc( $description, $category=null,$context='') {
		if (has_filter('xiliml_link_translate_desc')) return apply_filters('xiliml_link_translate_desc',$description,$category,$context,$this->curlang);
		
		/*default*/
	  	if ($this->curlang && ''!= $description) :
	  			$translated_desc = __($description,THEME_TEXTDOMAIN) ;
	  	else :
	  			$translated_desc = $description;
	  	endif;
	 	return $translated_desc;
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
					$preferred_languages[$match[1]] = floatval($match[3]);
					if($match[3]==NULL) $preferred_languages[$match[1]] = 1.0;
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
		if ($this->browseroption != 'browser') return $this->choice_of_home_selected_lang(); /* in settings UI - after filter to hook w/o UI */
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
	
	function match_languages ($listofprefs,$listlanguages) {
		
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
	 * add admin menu and associated pages of admin UI
	 *
	 * @since 0.9.0
	 * @updated 0.9.6 - only for WP 2.7.X - do registering of new meta boxes and JS
	 *
	 */
	function xili_add_pages() {
		 $this->thehook = add_options_page(__('Language','xili-language'), __('Language','xili-language'), 'manage_options', 'language_page', array(&$this,'languages_settings'));
		 add_action('load-'.$this->thehook, array(&$this,'on_load_page'));
		 
	}
	
	function on_load_page() {
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('postbox');
			add_meta_box('xili-language-sidebox-1', __('Message','xili-language'), array(&$this,'on_sidebox_1_content'), $this->thehook , 'side', 'core');
			add_meta_box('xili-language-sidebox-2', __('Info','xili-language'), array(&$this,'on_sidebox_2_content'), $this->thehook , 'side', 'core');
			
	}
	/**
	 * Add action link(s) to plugins page
	 * 
	 * @since 0.9.3
	 * @author MS
	 * @copyright Dion Hulse, http://dd32.id.au/wordpress-plugins/?configure-link and scripts@schloebe.de
	 */
	function xililang_filter_plugin_actions($links, $file){
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
	 */
	function xili_language_checkboxes_n() { 
		global $post_ID,$wp_version;
	/*list of languages*/
		//$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
		$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
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
		
		if(0 != $post_ID){
			$ress = wp_get_object_terms($post_ID, TAXONAME);
			/*print_r($ress);*/
			/*Array ( [0] => stdClass Object ( [term_id] => 18 [name] => [slug] => 18 [term_group] => 0 [term_taxonomy_id] => 19 [taxonomy] => language [description] => [parent] => 0 [count] => 1 ) )*/
			$obj_term = $ress[0];
			if ('' != $obj_term->name) :
				$curlangname = $obj_term->name;
			else :
				$curlangname = ""; /* when created before plugin */
			endif;
			
		} else {
			$curlangname = $defaultlanguage; /* new post */
		}
		echo __('Selected language', 'xili-language').' : <strong>'.$curlangname.'</strong> '.((0 == $post_ID) ? $mention : "").'<br /><br />' ; /*link to bottom of sidebar*/
		foreach ($listlanguages as $language) { ?> 
		<label for="xili_language_check_<?php echo $language->slug ; ?>" class="selectit"><input id="xili_language_check_<?php echo $language->slug ; ?>" name="xili_language_set" type="radio" value="<?php echo $language->slug ; ?>"  <?php if($curlangname==$language->name) echo 'checked="checked"' ?> /> <?php echo _e($language->description, 'xili-language'); ?></label>
	  
		<?php } /*link to top of sidebar*/?> 
		<label for="xili_language_check" class="selectit"><input id="xili_language_check" name="xili_language_set" type="radio" value="" <?php if($curlangname=="") echo 'checked="checked"' ?> /> <?php _e('undefined','xili-language') ?></label><br />
	  	<br /><small>© xili-language</small>
	  	<?php if ($wp_version < '2.7') { ?>
	  	<a href="#">=> <?php _e('Top') ;?></a>
	<?php }
	}
	
	/**
	 * to display the linked posts in post edit UI
	 *
	 * @since 0.9.8
	 * 
	 *
	 */
	function xili_language_linked_posts() { 
		global $post_ID;
		$update_nonce = wp_create_nonce('oklinked');
		//$update_nonce2 = wp_create_nonce('customrefresh');
		$postlang = '';
		//$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
		$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		 ?>
		<table width="100%" cellspacing="4" cellpadding="2">
		<thead>
		<tr ><th><?php _e('Language','xili-language'); ?></th><th align="left"><?php _e('Post ID','xili-language'); ?></th><th align="left"><?php _e('Display','xili-language'); ?></th><th align="left"><?php _e('Edit'); ?></th></tr>
		</thead>
		<tbody id='the-linked' class='list:linked'>
			<?php
			foreach ($listlanguages as $language) {
				$otherpost = get_post_meta($post_ID, QUETAG.'-'.$language->slug, true);?>
				<tr ><th>
				<label for="xili_language_<?php echo QUETAG.'-'.$language->slug ; ?>"><?php _e($language->description,'xili-language') ; ?>&nbsp;</label></th><td align="left"><input id="xili_language_<?php echo QUETAG.'-'.$language->slug ; ?>" name="xili_language_<?php echo QUETAG.'-'.$language->slug ; ?>"  value="<?php echo $otherpost; ?>" size="5" /><input type="hidden" name="xili_language_rec_<?php echo QUETAG.'-'.$language->slug ; ?>" value="<?php echo $otherpost; ?>"/>
				
				<?php
				if ('' != $otherpost && $language->slug != $postlang ) {
					$output = "</td><td><a target='_blank' href='".get_permalink($otherpost)."' >"." ".__($language->description,'xili-language') ."</a></td><td><a target='_blank' href='post.php?action=edit&post=".$otherpost."' >"." ".__('Edit') ."</a></td></tr>";
				} else {
					$output = "</td></tr>";
				}
				echo $output; 
			} ?>	</tbody></table>
			<?php if ($this->post_ajax) { ?>
			<div id='formstatus'></div><span id='loading' class='hidden'><?php _e('Saving...','xili-language') ?></span><span id='loading2' class='hidden'><?php _e('Refreshing...','xili-language') ?></span><div class='submit'>
			<input id='updatelink' name='updatelinked' type='submit' tabindex='6' value='Update' /><small>© xili-language</small></div><?php echo wp_nonce_field( 'oklinked', '_ajax_nonce', true, false );/**/ ?><?php /* echo wp_nonce_field( 'customrefresh', '_ajax_nonce', false, false );*/ ?>
			<script  type='text/javascript'>
			<!--
	
	jQuery(document).ready(function(){
		jQuery('#updatelink').click(function() { //start function when Random button is clicked
			jQuery.ajax({
				type: "post",url: "admin-ajax.php",
				data: {
					action: 'oklinked', 
					<?php
					foreach ($listlanguages as $language) {
						echo "xili_language_".$language->slug.": "."escape( jQuery( '#"."xili_language_".QUETAG."-".$language->slug."' ).val()),";	
					}
					echo "post_id: '".$post_ID."',";	
					?>
					_ajax_nonce: '<?php echo $update_nonce; ?>' 
					},
				beforeSend: function() {jQuery("#loading").fadeIn('fast');jQuery("#formstatus").fadeIn("fast");}, //when link is clicked
				success: function(html){ //so, if data is retrieved, store it in html
					jQuery("#loading").fadeOut('slow');
					jQuery("#formstatus").html( html );
					jQuery.ajax({ // refresh custom fields list
						type: "post",url: "admin-ajax.php",
					 	data: {
					 	action: 'customrefresh',
					 	<?php
							echo "post_id: '".$post_ID."',"; ?>
					 	_ajax_nonce: '<?php echo $update_nonce; ?>' 
					 	},
					 	beforeSend: function() {jQuery("#loading2").fadeIn('fast');},
						success: function(html){
					 	jQuery("#the-list").html( html );
					 	jQuery("#loading2").fadeOut('slow');
					 	}		
			 });
				}
			}); //close jQuery.ajax
			return false;
		})
	})
	-->
	</script><?php }
	}
	
	function custom_refresh() {
		check_ajax_referer( "oklinked" );
		$post_ID = $_POST['post_id'];
		$count = 0;
		$metadata = has_meta($post_ID);
		$list ="";
		$output = '';//<tr><td>Refreshed by xili-language</td></tr>';
		if ($metadata)
			foreach ( $metadata as $entry ) { $list .= _list_meta_row( $entry, $count );}
		$output .= $list;
		echo $output."<!--- end updated by xili-language -->";
		die();
	}
	
	function ok_linked() {
		check_ajax_referer( "oklinked" );
		
		$post_ID = $_POST['post_id'];
				
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			$linked = array ();
			foreach ($listlanguages as $language) {
				$key = $language->slug;
				$linked[$key] = $_POST['xili_language_'.$key];
				$linkid = $linked[$key];
				//$reclinkid = $_POST[$recinputid]; /* hidden previous value */
				$langslug = QUETAG.'-'.$key ;
				//if ($reclinkid != $linkid) { /* only if changed value */
					if ((is_numeric($linkid) && $linkid == 0) || '' == $linkid ) {
						delete_post_meta($post_ID, $langslug);
					} elseif (is_numeric($linkid) && $linkid > 0) {
						update_post_meta($post_ID, $langslug, $linkid);
						$mess .= " ".$key;	
					}
			}
			echo '<p>All is OK '.$post_id.' ('.$mess.')</p>'; // voir bannière //
			die();
		
	}
		
	/**
	 * to display the languages settings admin UI
	 *
	 * @since 0.9.0
	 * @updated 0.9.6 - only for WP 2.7.X - do new meta boxes and JS
	 *
	 */
	function languages_settings() { 
		$formtitle = 'Add a language'; /* translated in form */
		$submit_text = __('Add &raquo;','xili-language');
		$cancel_text = __('Cancel');
		if (isset($_POST['reset'])) {
			$action=$_POST['reset'];
		//$messagepost = $action ;
		} elseif (isset($_POST['updateoptions'])) {
			$action='updateoptions';
		//$messagepost = $action ;
		} elseif (isset($_POST['action'])) {
			$action=$_POST['action'];
		//$messagepost = $action ;
		}
		
		if (isset($_GET['action'])) :
			$action=$_GET['action'];
			$term_id = $_GET['term_id'];
		endif;
		$message = $action ;
		switch($action) {
			case 'updateoptions';
				$this->browseroption = $_POST['xili_language_check_option'];
				$this->authorbrowseroption = $_POST['xili_language_check_option_author'];
				$this->functions_enable = $_POST['xili_language_check_functions_enable'];
				$this->xili_settings['browseroption'] = $this->browseroption;
				$this->xili_settings['authorbrowseroption'] = $this->authorbrowseroption;
				$this->xili_settings['functions_enable'] = $this->functions_enable;
				update_option('xili_language_settings', $this->xili_settings);
				$message .= " - ".__('Option is updated.','xili-language')." (=> ".$this->browseroption.") (".$this->authorbrowseroption.") (".$this->functions_enable.")";

				$actiontype = "add";
				break;
			case 'add':
				$term = $_POST['language_name'];
				$args = array( 'alias_of' => '', 'description' => $_POST['language_description'], 'parent' => 0, 'slug' =>$_POST['language_nicename']);
			    $theids = wp_insert_term( $term, TAXONAME, $args);
			    wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);
			    update_term_order ($theids['term_id'],$this->langs_group_tt_id,$_POST['language_order']);
			    $actiontype = "add";
			    $message .= " - ".__('A new language was added.','xili-language');
			     break;
			    
			case 'edit';
			    $actiontype = "edited";
			    //echo $term_id;
			    //$language = get_term($term_id,TAXONAME,OBJECT,'edit');
			    $language = get_term_and_order ($term_id,$this->langs_group_tt_id,TAXONAME);
			    $submit_text = __('Update &raquo;');
			    $formtitle = 'Edit language';
			    $message .= " - ".__('Language to update.','xili-language');
			    break;
			    
			case 'edited';
			    $actiontype = "add";
			    $term = $_POST['language_term_id'];
			    
				$args = array( 'alias_of' => '', 'description' => $_POST['language_description'], 'parent' => 0, 'slug' =>$_POST['language_nicename']);
				$theids = wp_update_term( $term, TAXONAME, $args);
				wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);
				update_term_order ($theids['term_id'],$this->langs_group_tt_id,$_POST['language_order']);
				$message .= " - ".__('A language was updated.','xili-language');
				
			    break;
			    
			case 'delete';
			    $actiontype = "deleting";
			    $submit_text = __('Delete &raquo;','xili-language');
			    $formtitle = 'Delete language ?';
			    //$language = get_term($term_id,TAXONAME,OBJECT,'edit');
			    $language = get_term_and_order ($term_id,$this->langs_group_tt_id,TAXONAME);
			    $message .= " - ".__('A language to delete.','xili-language');
			    
			    break;
			    
			case 'deleting';
			    $actiontype = "add";
			    $term = $_POST['language_term_id'];
			    wp_delete_object_term_relationships( $term, TAXOLANGSGROUP );
			    wp_delete_term( $term, TAXONAME, $args);
			    $message .= " - ".__('A language was deleted.','xili-language');
			    break; 
			case 'reset';    
			    $actiontype = "add";
			    break;
			default :
			    $actiontype = "add";
			    $message .= __('Find above the list of languages.','xili-language');
			    
			    
		}
		/* register the main boxes always available */
		add_meta_box('xili-language-normal-1', __('List of languages','xili-language'), array(&$this,'on_normal_1_content'), $this->thehook , 'normal', 'core'); 
		add_meta_box('xili-language-normal-2', __('Language','xili-language'), array(&$this,'on_normal_2_content'), $this->thehook , 'normal', 'core');
		add_meta_box('xili-language-sidebox-3', __('Settings','xili-language'), array(&$this,'on_sidebox_3_content'), $this->thehook , 'side', 'core');
		
		/* form datas in array for do_meta_boxes() */
		$data = array('message'=>$message,'messagepost'=>$messagepost,'action'=>$action, 'formtitle'=>$formtitle, 'language'=>$language,'submit_text'=>$submit_text,'cancel_text'=>$cancel_text,'browseroption'=>$this->browseroption, 'authorbrowseroption'=>$this->authorbrowseroption , 'functions_enable'=>$this->functions_enable);
		?>
		
		<div id="xili-language-settings" class="wrap" style="min-width:750px">
			<?php screen_icon('options-general'); ?>
			<h2><?php _e('Language','xili-language') ?></h2>
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
					<h4><a href="http://dev.xiligroup.com/xili-language" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)).'/xililang-logo-32.gif'; ?>" alt="xili-language logo"/>  xili-language</a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2007-9 - v. <?php echo XILILANGUAGE_VER; ?></h4>		
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
			});
			//]]>
		</script> 
	<?php	//end settings div
		}
	
		//* display xililanguage in lists *//
	function xili_manage_column($name, $id) {
			if($name != TAXONAME)
				return;
			$terms = wp_get_object_terms($id, TAXONAME);
			$first = true;
			foreach($terms AS $term) {
				if($first)
					$first = false;
				else
					echo ', ';
				echo '<a href="' . 'options-general.php?page=language_page'.'">'; /* see more precise link ?*/
				echo $term->name;
				echo '</a>';
			}
		}
	function xili_manage_column_name($cols) {
			$ends = array('comments', 'date', 'rel', 'visible');
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


	function init_textdomain() {
	/*multilingual for admin pages and menu*/
		load_plugin_textdomain('xili-language',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
	}
	
	/**
	 * private functions for languages_settings 
	 * @since 0.9.6
	 *
	 * fill the content of the boxes (right side and normal)
	 * 
	 */
	function  on_sidebox_1_content($data) { 
		extract($data);
		?>
	 	<h4><?php _e('Note:','xili-language') ?></h4>
		<p><?php echo $message;?></p>
		<?php
	}
	
	function  on_sidebox_2_content() { ?>
	 	
		<p><?php _e("This plugin was developed with the taxonomies, terms tables and tags specifications. <br /> Here a new taxonomy was created and used for languages of posts. <br /> New radiobuttons are available in Post (and Page) write and edit pages for selection by author. It is updated for WP 2.8 since 1.0",'xili-language') ?></p>
		<?php
	}	
	
	function  on_sidebox_3_content($data) { /* where to choose if browser language preferences is tested or not */
		extract($data);
		$update_nonce = wp_create_nonce('xilimloptions');
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
	
		</fieldset>
		<br /><br />
		<label for="xili_language_check_option_author" class="selectit"><input id="xili_language_check_option_author" name="xili_language_check_option_author" type="checkbox" value="authorbrowser"  <?php if($authorbrowseroption=='authorbrowser') echo 'checked="checked"' ?> /> <?php echo _e('For new post, pre-select by default: browser language of author', 'xili-language'); ?></label>
		<br /><br />
		<?php if (file_exists(XILIFUNCTIONSPATH)) { /* test if folder exists - ready to add functions.php inside - since 1.0 */?>
		<label for="xili_language_check_functions_enable" class="selectit"><input id="xili_language_check_functions_enable" name="xili_language_check_functions_enable" type="checkbox" value="enable"  <?php if($functions_enable =='enable') echo 'checked="checked"' ?> /> <?php echo _e('Enable gold functions', 'xili-language'); ?></label>
		<?php } else {	
		echo '<input type="hidden" name="xili_language_check_functions_enable" value="'.$functions_enable.'" />';
		} ?>		
	 	<div id='formstatus'></div><span id='loading' class='hidden'><?php _e('Updating...','xili-language') ?></span><div class='submit'>
		<input id='updateoptions' name='updateoptions' type='submit' tabindex='6' value="<?php _e('Update','xili-language') ?>" /></div>
		<?php echo wp_nonce_field( 'xilimloptions', '_ajax_nonce', true, false );/**/ ?>
		<div style="clear:both; height:1px"></div><?php
	}
	
	function on_normal_1_content($data) { 
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
	
	function on_normal_2_content($data) { 
		extract($data);
	 /* the create - edit - delete form */ ?>
		
		<h2 id="addlang" <?php if ($action=='delete') echo 'style="color:#FF1111;"'; ?>><?php _e($formtitle,'xili-language') ?></h2>
		<?php if ($action=='edit' || $action=='delete') :?>
			<input type="hidden" name="language_term_id" value="<?php echo $language->term_id ?>" />
		<?php endif; ?>
		<table class="editform" width="100%" cellspacing="2" cellpadding="5">
			<tr>
				<th width="33%" scope="row" valign="top" align="right"><label for="language_name"><?php _e('Name') ?></label>:&nbsp;</th>
				<td width="67%"><input name="language_name" id="language_name" type="text" value="<?php echo attribute_escape($language->name); ?>" size="40" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
			</tr>
			<tr>
				<th scope="row" valign="top" align="right"><label for="language_nicename"><?php _e('Language slug','xili-language') ?></label>:&nbsp;</th>
				<td><input name="language_nicename" id="language_nicename" type="text" value="<?php echo attribute_escape($language->slug); ?>" size="40" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
			</tr>
			<tr>
				<th scope="row" valign="top" align="right"><label for="language_description"><?php _e('Full name','xili-language') ?></label>:&nbsp;</th>
				<td><input name="language_description" id="language_description" size="40" value="<?php echo $language->description; ?>" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
				
			</tr>
			<tr>
				<th scope="row" valign="top" align="right"><label for="language_order"><?php _e('Order','xili-language') ?></label>:&nbsp;</th>
				<td><input name="language_order" id="language_order" size="3" value="<?php echo $language->term_order; ?>" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
				
			</tr>
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
	 * private functions for admin page : the language list
	 * @since 0.9.0
	 *
	 * @update 0.9.5 : two default languages if taxonomy languages is empty
	 * 
	 */
	function xili_lang_row() { 	
		/*list of languages*/
				//$listlanguages = get_terms_with_order($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		
		if (empty($listlanguages)) : /*create two default lines with the default language (as in config)*/
		  	/* language of WP */
			$term = 'en_US';
			$args = array( 'alias_of' => '', 'description' => 'english', 'parent' => 0, 'slug' =>'');
			$theids = wp_insert_term( $term, TAXONAME, $args);
			wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);
		 	$term = $this->default_lang;
		 	$desc = $this->default_lang;
		 	if (!defined('WPLANG') || $this->default_lang == 'en_US' ) {$term = 'fr_FR'; $desc = 'french';}
		 	$args = array( 'alias_of' => '', 'description' => $desc, 'parent' => 0, 'slug' =>'');
		 	$theids = wp_insert_term( $term, TAXONAME, $args);
			wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP); /* create the group */
			$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		endif;
		foreach ($listlanguages as $language) {	
			$class = ((defined('DOING_AJAX') && DOING_AJAX) || " class='alternate'" == $class ) ? '' : " class='alternate'";
			$language->count = number_format_i18n( $language->count );
			$posts_count = ( $language->count > 0 ) ? "<a href='edit.php?lang=$language->slug'>$language->count</a>" : $language->count;	
		
			$edit = "<a href='?action=edit&amp;page=language_page&amp;term_id=".$language->term_id."' >".__( 'Edit' )."</a></td>";	
			/* delete link*/
			$edit .= "<td><a href='?action=delete&amp;page=language_page&amp;term_id=".$language->term_id."' class='delete'>".__( 'Delete' )."</a>";	
			
		$line="<tr id='cat-$language->term_id'$class>
			<th scope='row' style='text-align: center'>$language->term_id</th>
			<td>" .$language->name. "</td>
			<td>$language->description</td>
			<td>$language->slug</td>
			<td>$language->term_order</td>
			<td align='center'>$posts_count</td> 
			<td>$edit</td>\n\t</tr>\n"; /*to complete - 0.9.8.1 count to post*/
			echo $line;
			//print_r($language);
		}	
	}
	
	//********************************************//
	// Functions for themes (hookable by add_action() in functions.php - 0.9.7
	//********************************************//
	
	
	/**
	 * List of available languages.
	 *
	 * @since 0.9.0
	 * @updated 0.9.7.4 - 0.9.8.3 - 0.9.9.6
	 * can be hooked by add_action in functions.php
	 * with : add_action('xili_language_list','my_infunc_language_list',10,3);
	 *
	 * for multiple widgets since 0.9.9.6 : incorporate top option (without flag) but with example rules
	 *
	 * @param $before = '<li>', $after ='</li>'.
	 * @return list of languages of site for sidebar list.
	 */
	function xili_language_list($before = '<li>', $after ='</li>',$option='') {
		$listlanguages = get_terms_of_groups_lite ($this->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
		if ($option == 'typeone') {
			/* the rules : don't display the current lang if set and add link of category if is_category()*/
			if (is_category()) {  
				$catcur = xiliml_get_category_link();
				$currenturl = $catcur.'&amp;'; 
			} else {
		 		$currenturl = get_bloginfo('siteurl').'/?';
			}
			foreach ($listlanguages as $language) {
				if ($language->slug != $this->curlang ) {
					$a .= $before ."<a href='".$currenturl.QUETAG."=".$language->slug."' title='".__('Posts selected',THEME_TEXTDOMAIN)." ".__('in '.$language->description,THEME_TEXTDOMAIN)."'>". __('in '.$language->description,THEME_TEXTDOMAIN) ."</a>".$after;
				}
			}
			echo $a;
		} else {	/* current list */
			foreach ($listlanguages as $language) {
				$a .= $before ."<a href='".get_bloginfo('siteurl')."/?".QUETAG."=".$language->slug."' title='".__('Posts selected',THEME_TEXTDOMAIN)." ".__('in '.$language->description,THEME_TEXTDOMAIN)."'>". __('in '.$language->description,THEME_TEXTDOMAIN) ."</a>".$after;
			}
			echo $a;
		}	
	}
	
	/**
	 *language of current post used in loop
	 * @since 0.9.0
	 * 
	 *
	 * @param $before = '<span class"xili-lang">(', $after =')</span>'.
	 * @return language of post.
	 */
	function xili_post_language($before = '<span class="xili-lang">(', $after =')</span>') {
		global $post;
		$ress = wp_get_object_terms($post->ID, TAXONAME);
		$obj_term = $ress[0];
		if ('' != $obj_term->name) :
				$curlangname = $obj_term->name;
		else :
				$curlangname = __('undefined',THEME_TEXTDOMAIN);
		endif;
		$a = $before . $curlangname .$after.'';  
		echo $a;
	}
		
	/** 
	 * for one post create a link list of the corresponding posts in other languages
	 *
	 * @since 0.9.0
	 * @updated 0.9.9.2 / 3 $separator replace $after, $before contains pre-text to echo a better list.
	 * can be hooked by add_action in functions.php
	 *
	 *
	 */
	function xiliml_the_other_posts ($post_ID, $before = "This post in", $separator = ", ") {
		/* default here*/
			$outputarr = array();
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			foreach ($listlanguages as $language) {
				$otherpost = get_post_meta($post_ID, 'lang-'.$language->slug, true);
				if ('' != $otherpost && $language->slug != $this->curlang ) {
					$outputarr[] = "<a href='".get_permalink($otherpost)."' >".__($language->description,THEME_TEXTDOMAIN) ."</a>";
				}	
			}
			if (!empty($outputarr))
				$output =  (($before !="") ? __($before,THEME_TEXTDOMAIN)." " : "" ).implode ($separator, $outputarr);
			if ('' != $output) { echo $output;}		
	}
	
	/**
	 * the_category() rewritten to keep new features of multilingual (and amp & pbs in link)
	 *
	 * @since 0.9.0
	 * @updated 0.9.9.4
	 * can be hooked by add_action xiliml_the_category in functions.php
	 *
	 */
	function xiliml_the_category($post_ID, $separator = ', ' ,$echo = true) {
		/* default here*/
		$the_cats_list = wp_get_object_terms($post_ID, 'category');
		$i = 0;
		foreach ($the_cats_list as $the_cat) {
			if ( 0 < $i )
				$thelist .= $separator . ' ';
			$desc4title = trim(attribute_escape(apply_filters( 'category_description', $the_cat->description, $the_cat->term_id )));
			
			$title = ('' == $desc4title) ? __($the_cat->name,THEME_TEXTDOMAIN) : $desc4title;
			$the_catlink = '<a href="' . get_category_link($the_cat->term_id) . '" title="' . $title . '" ' . $rel . '>';
			//if ($curlang != DEFAULTSLUG) :
	      	$the_catlink .=  __($the_cat->name,THEME_TEXTDOMAIN).'</a>';;
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
	 * @updated 0.9.9.5
	 *
	 * $before, $after each line of radio input
	 *
	 * @param $before, $after. 
	 * @return echo the form.
	 */
	function xiliml_langinsearchform ($before='',$after='') {
			/* default here*/
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			foreach ($listlanguages as $language) {
				$a = $before.'<input type="radio" name="'.QUETAG.'" value="'.$language->slug.'" id="'.$language->slug.'" />&nbsp;'.__($language->description,THEME_TEXTDOMAIN).' '.$after;
			echo $a;
			}			
		    echo $before.'<input type="radio" name="alllang" value="yes" /> '.__('All',THEME_TEXTDOMAIN).' '.$after;	 // this query alllang is unused -		
	}
	/**
	 * Select latest comments in current lang.
	 *
	 * @since 0.9.9.4
	 * used by widget xili-recent-comments
	 *
	 * $before, $after each line of radio input
	 *
	 * @param $before, $after. 
	 * @return echo the form.
	 */
	function xiliml_recent_comments ($number = 5) {
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
				$comments = $wpdb->get_results($query);
				wp_cache_add( 'xili_language_recent_comments', $comments, 'widget' );
		}
		return $comments;
	}
		
} /* end of class */



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
/* for backward compatibility - soon obsolete - please modify your theme's function.php */
function get_terms_with_order ($group_ids, $taxonomy, $taxonomy_child, $order = 'ASC') {
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
function update_term_order ($object_id,$term_taxonomy_id,$term_order) {
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
function get_term_and_order ($term_id,$group_ttid,$taxonomy) {
	global $wpdb;
	$term = get_term($term_id,$taxonomy,OBJECT,'edit');
	$term->term_order = $wpdb->get_var("SELECT term_order FROM $wpdb->term_relationships WHERE object_id =  $term_id AND term_taxonomy_id = $group_ttid ");
	return $term;
}

/**** Functions using the class ****/
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
	return array('lang'=>$xili_language->curlang, 'direction'=>$xili_language->curlang_dir);
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
 * Return the current date or a date formatted with strftime.
 *
 * @since 0.9.7.1
 * can be used in theme for multilingual date
 * @param format and time (if no time = current date-time)
 * @return the formatted date.
 */
function the_xili_local_time($format='%B %d, %Y',$time = null) {
	global $xili_language;
	if ($time == null ) $time = time();
	$curlang = $xili_language->curlang;
	$curlang = substr($curlang,0,3).strtoupper(substr($curlang,-2));
	setlocale(LC_TIME, $curlang); /* work if server is ready */
	return htmlentities(strftime(__($format,THEME_TEXTDOMAIN),$time),ENT_COMPAT); /* ,'UTF-8' entities for some server */
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
function get_cur_language($post_ID) {
	global $xili_language;
	$langpost = $xili_language->get_cur_language($post_ID);
	return $langpost['lang'];
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
function get_cur_post_lang_dir($post_ID) {
	global $xili_language;
	return $xili_language->get_cur_language($post_ID);
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
	return array('lang'=>$lang,'direction'=>$dir);
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
function add_again_filter($filtername,$filterfunction) {
	global $xili_language;
	$xili_language->add_filter($filtername,$filterfunction);
}

/**
 * Replace get_category_link to bypass hook from xili_language
 *
 * @since 0.9.7.4
 * can be used in functions.php for special action needing permalink
 
 * @param category ID 
 * @return the permalink of passed cat_id.
 */
function xiliml_get_category_link($catid = 0) {
			if ($catid == 0) {
				global $wp_query;
				$catid = $wp_query->query_vars['cat'];
			}
			remove_filter('category_link', $xili_language->idx['xiliml_link_append_lang']);
				$catcur = get_category_link($catid); 
			add_again_filter('category_link', 'xiliml_link_append_lang');
	return $catcur;
}
/* used by xili widget - usable if you need to create your own template tag 
 *
 * @since 0.9.9.4
 * @param quantity of comments
 *
 * @return comments objects...
 */
function xiliml_recent_comments($number = 5) {
	global $xili_language;
	return $xili_language->xiliml_recent_comments($number);
}

	/* 
	 **
	 **
	 * Template Tags for themes (with current do_action tool some are hookable functions) 
	 **
	 **
	 */
	 
/**
 * Template Tag insertable in search form for sub-selection of a language
 *
 * @since 0.9.7
 * can be used in theme template
 * example: if(class_exists('xili_language_form')) xiliml_langinsearchform()
 *
 * hook: add_action('xiliml_langinsearchform','your_xiliml_langinsearchform',10,2) to change its behaviour elsewhere
 * @param html tags 
 * @return echo the list as radio-button
 */	 
function xiliml_langinsearchform ($before='',$after='') { /* list of radio buttons for search form*/
	global $xili_language;
	if ($xili_language->this_has_filter('xiliml_langinsearchform')){ 
		remove_filter('xiliml_langinsearchform',$xili_language->idx['xiliml_langinsearchform']); /*no default from class*/
	}
	do_action('xiliml_langinsearchform',$before,$after);	
}

/**
 * Template Tag - replace the_category() tag of WP Core
 *
 * @since 0.9.0
 * can be used in theme template in each post in loop
 * example: if(class_exists('xili_language_form')) xiliml_the_category($post->ID)
 *
 * hook: add_action('xiliml_the_category','your_xiliml_the_category',10,3) to change its behaviour elsewhere
 * @param post_id separator echo (true by default) 
 * @return echo (by default) the list of cats with comma separated...
 */
function xiliml_the_category($post_ID, $separator = ', ' ,$echo = true) { /* replace the_category() */
	global $xili_language;
	if ($xili_language->this_has_filter('xiliml_the_category')){ 
		remove_filter('xiliml_the_category',$xili_language->idx['xiliml_the_category']); /*no default from class*/
	}
	do_action('xiliml_the_category',$post_ID,$separator,$echo);
}

/**
 * Template Tag - in loop display the link of other posts defined as in other languages
 *
 * @since 0.9.0
 * @updated 0.9.9.2
 * can be used in theme template in single.php under the title
 * example: if(class_exists('xili_language_form')) xiliml_the_other_posts($post->ID)
 *
 * hook: add_action('xiliml_the_other_posts','your_xiliml_the_other_posts',10,3) to change its behaviour elsewhere
 * @param post_id before separator 
 * @return echo (by default) the list 
 */
function xiliml_the_other_posts ($post_ID,$before = "This post in", $separator = ", ") { /* display the other posts defined as in other lang */
	global $xili_language;
	if ($xili_language->this_has_filter('xiliml_the_other_posts')){ 
		remove_filter('xiliml_the_other_posts',$xili_language->idx['xiliml_the_other_posts']); /*no default from class*/
	}
	do_action('xiliml_the_other_posts',$post_ID, $before, $separator);
}

/**
 * Template Tag - in loop display the language of the post
 *
 * @since 0.9.0
 * can be used in theme template in loop under the title
 * example: if(class_exists('xili_language_form')) xili_post_language()
 *
 * hook: add_action('xili_post_language','your_xili_post_language',10,2) to change its behaviour elsewhere
 * @param before after 
 * @return echo (by default) the language 
 */
function xili_post_language($before = '<span class="xili-lang">(', $after =')</span>') { /* post language in loop*/
	do_action('xili_post_language',$before, $after);
}

/**
 * Template Tag - outside loop (sidebar) display the languages of the site (used also by widget)
 *
 * @since 0.9.0
 * @updated 0.9.7.4
 * can be used in theme template in sidebar menu or header menu
 * example: if(class_exists('xili_language_form')) xili_language_list()
 * theoption param is used to define type of display according places (sidebar / header) in theme (see dev.xiligroup.com)
 *
 * hook: add_action('xili_language_list','your_xili_language_list',10,3) to change its behaviour elsewhere
 * @param before after theoption
 * @return echo the list of languages
 */
function xili_language_list($before = '<li>', $after ='</li>', $theoption='') { /* list of languages i.e. in sidebar */
	global $xili_language;
	if ($xili_language->this_has_filter('xili_language_list')){ 
		remove_filter('xili_language_list',$xili_language->idx['xili_language_list']); /*no default from class*/
	}	
	do_action('xili_language_list',$before,$after,$theoption); 
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
function ex_pages_by_lang ($pages, $r) {
	if (isset($r['lang']) && !empty($pages) && function_exists('get_cur_post_lang_dir')) {
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
			} elseif ($post_lang_dir['lang'] == $r['lang'] ) {
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
 * instantiation of xili_language class
 *
 * @since 0.9.7 - 0.9.9.4 =& for vintage server with php4 !!!!
 *
 * @param metabox (for other posts in post edit UI - to replace custom fields - beta tests)
 * @param ajax ( true if ajax is activated for post edit admin UI - alpha tests )
 */
$xili_language =& new xili_language(true,false); 

/**
 * Enable to add functions and filters that are not in theme's functions.php
 * These filters are common even if you change default theme...
 * Place your functions.php in folder plugins/xilidev-libraries/
 * if you have a filter in this file, avoid to have similar one in functions.php of the theme !!!
 * 
 * (for tests, uncheck 'enable gold functions' in settings UI)
 * @since 1.0
 *
 */
if ($xili_language->functions_enable !='' && file_exists(XILIFUNCTIONSPATH . '/functions.php') )
			include(XILIFUNCTIONSPATH . '/functions.php');

?>