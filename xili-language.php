<?php
/*
Plugin Name: xili-language
Plugin URI: http://dev.xiligroup.com/xili-language/
Description: This plugin is the first which introduce a new taxonomy - here language - to modify on the fly the translation of the theme depending the language of the post or other blog elements - a way to create a real multi-language site (cms or blog).
Author: dev.xiligroup.com - MS
Version: 0.9.7.4
Author URI: http://dev.xiligroup.com
*/

# updated 090315 - 0.9.7.4 - Ajax in test for post metabox (custom refreshing) - options added in class instantiation. More docs in php. 
# updated 090311 - fix in hooks with default values.
# updated 090307 - add metabox to linked posts in other languages (as custom fields).
# updated 090306 - fix - add new tag the_xili_local_time() for date in theme and more...
# updated 090304 - fix permalink bug in xili_language_list - add add_again_filter() for future uses.
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

define('XILILANGUAGE_VER','0.9.7.4'); /* used in admin UI*/

class xili_language {
	
	var $default_lang; /* language of config.php*/
	var $curlang;
	var $post_ajax = false; /* ajax used in meta box in post edit UI unstable yet */
	var $is_metabox = false; /* meta box in post edit UI - if used don't use custom fields that are not refreshed */
	
	function xili_language($metabox = false, $post_ajax = false) {
		$this->is_metabox = $metabox;
		$this->post_ajax = $post_ajax;
		/*activated when first activation of plug*/
		register_activation_hook(__FILE__,array(&$this,'xili_language_activate'));
	
		/*get current settings - name of taxonomy - name of query-tag*/
		$xili_settings = get_option('xili_language_settings');
		if(empty($xili_settings)) {
			define('TAXONAME','language');
			define('QUETAG','lang');			
		} else {
			define('TAXONAME',$xili_settings['taxonomy']);
			define('QUETAG',$xili_settings['reqtag']);
		}
		/* add new taxonomy in available taxonomies */
		register_taxonomy( TAXONAME, 'post',array('hierarchical' => false, 'update_count_callback' => ''));
		/* default values */
		
		if (''!= WPLANG && strlen(WPLANG)==5) :
			$this->default_lang = WPLANG;
		else:
			$this->default_lang = 'en_US';
		endif;
		define('DEFAULTSLUG', $this->get_default_slug());
		
		add_filter('query_vars', array(&$this,'keywords_addQueryVar'));
		add_filter('posts_join', array(&$this,'with_lang'));
		add_filter('posts_where', array(&$this,'where_lang'));
		
		add_action('wp_head',  array(&$this,'xiliml_language_head'));
		
		
		add_filter('list_cats', array(&$this,'xiliml_cat_language'),10,2); /* mode 2 : content = name */
		add_filter( 'category_link', array(&$this,'xiliml_link_append_lang'));
		add_filter('category_description',array(&$this,'xiliml_link_translate_desc'));
		add_filter('tag_link', array(&$this,'xiliml_taglink_append_lang' ));
		
		add_action('pre_get_posts', array(&$this,'xiliml_modify_querytag'));
		
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

		/* inspired from custax */
		add_action('manage_posts_custom_column', array(&$this,'xili_manage_column'), 10, 2);
		add_filter('manage_edit_columns', array(&$this,'xili_manage_column_name'));

		add_action('manage_pages_custom_column', array(&$this,'xili_manage_column'), 10, 2);
		add_filter('manage_edit-pages_columns', array(&$this,'xili_manage_column_name'));
		
		
		/* new actions for xili-language theme's templates tags */
		
		$this->add_action('xili_language_list','xili_language_list',10,3); /* add third param 0.9.7.4*/
		$this->add_action('xili_post_language','xili_post_language',10,2);
		
		$this->add_action('xiliml_the_other_posts','xiliml_the_other_posts',10,3);
		$this->add_action('xiliml_the_category','xiliml_the_category',10,3);
		$this->add_action('xiliml_langinsearchform','xiliml_langinsearchform',10,2);
	}
	
	function add_action ($action, $function = '', $priority = 10, $accepted_args = 1)
	{
		add_action ($action, array (&$this, $function == '' ? $action : $function), $priority, $accepted_args);
	}
	
	function add_filter ($filter, $function = '', $priority = 10, $accepted_args = 1)
	{
		add_filter ($filter, array (&$this, $function == '' ? $filter : $function), $priority, $accepted_args);
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
 		}
	}
	
	/**
	 * set language when post or page is saved 
	 *
	 * @since 0.9.0
	 * @completed 0.9.7.1 to record postmeta of linked posts in other languages
	 *
	 * @param $post_ID
	 */
	function xili_language_add($post_ID) {
		$sellang = $_POST['xili_language_set'];
		if ("" != $sellang)
			wp_set_object_terms($post_ID, $sellang, TAXONAME);
		
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
	}
	
	/**
	 * Return language of post.
	 *
	 * @since 0.9.0
	 * 
	 *
	 * @param $post_ID.
	 * @return slug of language of post.
	 */
	 
	function get_cur_language($post_ID) {
		$ress = wp_get_object_terms($post_ID, 'language');
		if ($ress) {
			if (is_a($ress, 'WP_Error')){
				echo "Language table not created ! see plug-in admin";
			} else {
				$obj_term = $ress[0];
				return $obj_term->slug;
			}
	 	} else {
	  		return DEFAULTSLUG;	
	 	}		
	}	
	
	function xili_language_activate() {
		$submitted_settings = array(
			    'taxonomy'		=> 'language',
			    'version' 		=> '0.2',
			    'reqtag'		=> 'lang',
		    );
		update_option('xili_language_settings', $submitted_settings);	    
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
	 * can be hooked in functions.php xiliml_cur_lang_head
	 * call by wp_head()
	 * @param 
	 */
	function xiliml_language_head() {
		$this->curlang = $this->xiliml_cur_lang_head();
		$this->set_mofile($this->curlang);
	}
	/**
	 * default rules - set curlang in head according rules 
	 *
	 * @since 0.9.7
	 * @updated 0.9.7.1 - if no posts
	 * 
	 * default filter of xiliml_cur_lang_head
	 * @param $curlang .
	 */
	function xiliml_cur_lang_head () {
		if (has_filter('xiliml_cur_lang_head')) return apply_filters('xiliml_cur_lang_head',''); /* '' warning on some server need one arg by default*/
		/* default */
		global $post,$wp_query;
			if (have_posts()) {
				if(!is_front_page()) { /* every pages */
					$curlang = $this->get_cur_language($post->ID); /* the first post give the current lang*/
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
						$curlang = $wp_query->query_vars[QUETAG];	
					} else {
						$curlang = strtolower(WPLANG); /* select here the default language of the site */
					}	
				}
			} else { /*no posts for instance in category + lang */
			 	if (isset($_GET["lang"])) {
			    		$curlang=$_GET["lang"];
			    } else {
			    		$curlang = strtolower(WPLANG); /* select here the default language of the site */
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
	 * insert other language of wp_list_categories 
	 *
	 * @since 0.9.0
	 * update 0.9.7
	 * can be hooked by filter add_filter('xiliml_cat_language','yourfunction',2,3) in functions.php
	 * call by do_filter list_cats 
	 * @param $content, $category
	 */
	function xiliml_cat_language ($content, $category = null) {
		if (has_filter('xiliml_cat_language')) return apply_filters('xiliml_cat_language',$content, $category,$this->curlang);
		/* default */ 
	            /*set by locale of wpsite*/
	      /*these rules can be changed by using */
	    if (''!= $this->curlang) : /*to detect admin UI*/
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
	 * translate description of categories
	 *
	 * @since 0.9.0
	 * update 0.9.7
	 * can be hooked by filter add_filter('xiliml_link_translate_desc','yourfunction',2,4) in functions.php
	 *
	 *
	 */
	function xiliml_link_translate_desc( $description, $category=null,$context='') {
		if (has_filter('xiliml_link_translate_desc')) return apply_filters('xiliml_link_translate_desc',$description,$category,$context,$this->curlang);
		
		/*default*/
	  	if ($this->curlang) :
	  			$translated_desc = __($description,THEME_TEXTDOMAIN) ;
	  	else :
	  			$translated_desc = $description;
	  	endif;
	 	return $translated_desc;
	}
	/* end cat description */

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
	 */
	function xili_language_checkboxes_n() { 
		global $post_ID, $default_lang,$wp_version;
	/*list of languages*/
		$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
	
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
			$curlangname = "";
		}
		echo __('Selected language', 'xili-language').' : <strong>'.$curlangname.'</strong><br /><br />' ; /*link to bottom of sidebar*/
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
		$listlanguages = get_terms(TAXONAME, array('hide_empty' => false)); ?>
		<table width="100%" cellspacing="4" cellpadding="2">
		<thead>
		<tr ><th><?php _e('Language','xili-language'); ?></th><th align="left"><?php _e('Post ID','xili-language'); ?></th><th align="left"><?php _e('Display','xili-language'); ?></th></tr>
		</thead>
		<tbody id='the-linked' class='list:linked'>
			<?php
			foreach ($listlanguages as $language) {
				$otherpost = get_post_meta($post_ID, QUETAG.'-'.$language->slug, true);?>
				<tr ><th>
				<label for="xili_language_<?php echo QUETAG.'-'.$language->slug ; ?>"><?php _e($language->description,'xili-language') ; ?>&nbsp;</label></th><td align="left"><input id="xili_language_<?php echo QUETAG.'-'.$language->slug ; ?>" name="xili_language_<?php echo QUETAG.'-'.$language->slug ; ?>"  value="<?php echo $otherpost; ?>" size="5" /><input type="hidden" name="xili_language_rec_<?php echo QUETAG.'-'.$language->slug ; ?>" value="<?php echo $otherpost; ?>"/>
				
				<?php
				if ('' != $otherpost && $language->slug != $postlang ) {
					$output = "</td><td><a target='_blank' href='".get_permalink($otherpost)."' >"." ".__($language->description,'xili-language') ."</a></td></tr>";
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
		$formtitle = __('Add a language','xili-language');
		$submit_text = __('Add &raquo;','xili-language');
		$cancel_text = __('Cancel');
		if (isset($_POST['reset'])) {
			$action=$_POST['reset'];
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
			case 'add':
				$term = $_POST['language_name'];
				$args = array( 'alias_of' => '', 'description' => $_POST['language_description'], 'parent' => 0, 'slug' =>$_POST['language_nicename']);
			    wp_insert_term( $term, TAXONAME, $args);
			    $actiontype = "add";
			    $message .= " - ".__('A new language was added.','xili-language');
			     break;
			    
			case 'edit';
			    $actiontype = "edited";
			    //echo $term_id;
			    $language = get_term($term_id,TAXONAME,OBJECT,'edit');
			    $submit_text = __('Update &raquo;');
			    $formtitle = 'Edit language';
			    $message .= " - ".__('Language to update.','xili-language');
			    break;
			    
			case 'edited';
			    $actiontype = "add";
			    $term = $_POST['language_term_id'];
			    
				$args = array( 'alias_of' => '', 'description' => $_POST['language_description'], 'parent' => 0, 'slug' =>$_POST['language_nicename']);
				wp_update_term( $term, TAXONAME, $args);
				$message .= " - ".__('A language was updated.','xili-language');
				
			    break;
			    
			case 'delete';
			    $actiontype = "deleting";
			    $submit_text = __('Delete &raquo;','xili-language');
			    $formtitle = 'Delete language ?';
			    $language = get_term($term_id,TAXONAME,OBJECT,'edit');
			    
			    $message .= " - ".__('A language to delete.','xili-language');
			    
			    break;
			    
			case 'deleting';
			    $actiontype = "add";
			    $term = $_POST['language_term_id'];
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
		/* form datas in array for do_meta_boxes() */
		$data = array('message'=>$message,'messagepost'=>$messagepost,'action'=>$action, 'formtitle'=>$formtitle, 'language'=>$language,'submit_text'=>$submit_text,'cancel_text'=>$cancel_text);
		?>
		
		<div id="xili-language-settings" class="wrap">
			<?php screen_icon('options-general'); ?>
			<h2><?php _e('Language','xili-language') ?></h2>
			<form name="add" id="add" method="post" action="options-general.php?page=language_page">
				<input type="hidden" name="action" value="<?php echo $actiontype ?>" />
				<?php wp_nonce_field('xili-language-settings'); ?>
				<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
		
				<div id="poststuff" class="metabox-holder">
					<div id="side-info-column" class="inner-sidebar">
						<?php do_meta_boxes($this->thehook, 'side', $data); ?>
					</div>
				
					<div id="post-body" class="has-sidebar">
						<div id="post-body-content" class="has-sidebar-content">
					
	   					<?php do_meta_boxes($this->thehook, 'normal', $data); ?>
						</div>
					<h4>xili-language - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2007-9 - v. <?php echo XILILANGUAGE_VER; ?></h4>		
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
	 	
		<p><?php _e('This plugin was developped to test the new taxonomy, terms tables and tags specifications. <br /> Here a new taxonomy was created and used for languages of posts. <br /> New radiobuttons are available in Post (and Page) write and edit pages for selection by author. It is now updated for WP 2.7','xili-language') ?></p>
		<?php
	}	
	
	function on_normal_1_content($data) { 
		extract($data); ?>
		<?php //if (!isset($action) || $action=='add' || $action=='edited' || $action=='deleting') :?>
					<table class="widefat">
						<thead>
						<tr>
						<th scope="col" style="text-align: center"><?php _e('ID') ?></th>
	        			<th scope="col"><?php _e('Name') ?></th>
	        			<th scope="col"><?php _e('Full name:','xili-language') ?></th>
	        			<th scope="col"><?php _e('Language slug:','xili-language') ?></th>
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
		
		<h2 id="add" <?php if ($action=='delete') echo 'style="color:#FF1111;"'; ?>><?php _e($formtitle,'xili-language') ?></h2>
		<?php if ($action=='edit' || $action=='delete') :?>
			<input type="hidden" name="language_term_id" value="<?php echo $language->term_id ?>" />
		<?php endif; ?>
		<table class="editform" width="100%" cellspacing="2" cellpadding="5">
			<tr>
				<th width="33%" scope="row" valign="top" align="right"><label for="language_name"><?php _e('Name:') ?></label>&nbsp;</th>
				<td width="67%"><input name="language_name" id="language_name" type="text" value="<?php echo attribute_escape($language->name); ?>" size="40" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
			</tr>
			<tr>
				<th scope="row" valign="top" align="right"><label for="language_nicename"><?php _e('Language slug:','xili-language') ?></label>&nbsp;</th>
				<td><input name="language_nicename" id="language_nicename" type="text" value="<?php echo attribute_escape($language->slug); ?>" size="40" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
			</tr>
			<tr>
				<th scope="row" valign="top" align="right"><label for="language_description"><?php _e('Full name:','xili-language') ?></label>&nbsp;</th>
				<td><input name="language_description" id="language_description" size="40" value="<?php echo $language->description; ?>" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
				
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
		
		global $default_lang;	
		/*list of languages*/
		$listlanguages = get_terms(TAXONAME, array('hide_empty' => false,'get'=>'all'));
		if (empty($listlanguages)) : /*create two default lines with the default language (as in config)*/
		  	/* language of WP */
			$term = 'en_US';
			$args = array( 'alias_of' => '', 'description' => 'English', 'parent' => 0, 'slug' =>'');
			wp_insert_term( $term, TAXONAME, $args);
		 	$term = $default_lang;
		 	$desc = $default_lang;
		 	if (!defined('WPLANG') || $default_lang == 'en_US' ) {$term = 'fr_FR'; $desc = 'french';}
		 	$args = array( 'alias_of' => '', 'description' => $desc, 'parent' => 0, 'slug' =>'');
			wp_insert_term( $term, TAXONAME, $args);
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
		endif;
		foreach ($listlanguages as $language) {
			
			$class = ((defined('DOING_AJAX') && DOING_AJAX) || " class='alternate'" == $class ) ? '' : " class='alternate'";
	
			$language->count = number_format_i18n( $language->count );
			$posts_count = ( $language->count > 0 ) ? "<a href='edit.php?lang=$language->term_id'>$language->count</a>" : $language->count;	
		
			$edit = "<a href='?action=edit&page=language_page&term_id=".$language->term_id."' >".__( 'Edit' )."</a></td>";	
			/* delete link*/
			$edit .= "<td><a href='?action=delete&page=language_page&term_id=".$language->term_id."' class='delete'>".__( 'Delete' )."</a>";	
			
		$line="<tr id='cat-$language->term_id'$class>
			<th scope='row' style='text-align: center'>$language->term_id</th>
			<td>" .$language->name. "</td>
			<td>$language->description</td>
			<td>$language->slug</td>
			<td align='center'>$language->count</td> 
			<td>$edit</td>\n\t</tr>\n"; /*to complete*/
			echo $line;
		}	
	}
	
	//********************************************//
	// Functions for themes (hookable by add_action() in functions.php - 0.9.7
	//********************************************//
	
	
	/**
	 * List of available languages.
	 *
	 * @since 0.9.0
	 * @updated 0.9.7.4
	 * can be hooked by add_action in functions.php
	 *
	 * @param $before = '<li>', $after ='</li>'.
	 * @return list of languages of site for sidebar list.
	 */
	function xili_language_list($before = '<li>', $after ='</li>',$option='') {
			/* default here*/
		$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
		foreach ($listlanguages as $language) {
			$a = $before ."<a href='".get_bloginfo('siteurl')."/?".QUETAG."=".$language->slug."' title='".__('Posts selected',THEME_TEXTDOMAIN)." ".__('in '.$language->description,THEME_TEXTDOMAIN)."'>". __('in '.$language->description,THEME_TEXTDOMAIN) ."</a>".$after;
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
	function xili_post_language($before = '<span class"xili-lang">(', $after =')</span>') {
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
	 * create a link list of the corresponding posts in other languages
	 *
	 * @since 0.9.0
	 * can be hooked by add_action in functions.php
	 *
	 *
	 */
		
	function xiliml_the_other_posts ($post_ID,$before = " ", $after = ", ") {
		/* default here*/
		$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			foreach ($listlanguages as $language) {
				$otherpost = get_post_meta($post_ID, 'lang-'.$language->slug, true);
				if ('' != $otherpost && $language->slug != $curlang ) {
					$output .= $before."<a href='".get_permalink($otherpost)."' >".__($language->description,THEME_TEXTDOMAIN) ."</a>".$after;
				}	
			}
		if ('' != $output) {_e('This post in',THEME_TEXTDOMAIN); echo $output;}		
	}
	
	/**
	 * the_category() rewritten to keep new features of multilingual (and amp & pbs in link)
	 *
	 * @since 0.9.0
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
			 	$the_catlink = '<a href="' . get_category_link($the_cat->term_id) . '" title="' . __(trim(attribute_escape(apply_filters( 'category_description', $the_cat->description, $the_cat->term_id ))),THEME_TEXTDOMAIN) . '" ' . $rel . '>';
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
	 * $before, $after each line of radio input
	 *
	 * @param $before, $after. 
	 * @return echo the form.
	 */
	function xiliml_langinsearchform ($before='',$after='') {
			/* default here*/
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			foreach ($listlanguages as $language) {
				$a = $before.'<input type="radio" name="'.QUETAG.'" value="'.$language->slug.'" id=s"'.$language->slug.'">'.__($language->description,THEME_TEXTDOMAIN).' </>'.$after;
			echo $a;
			}			
		    echo '<input type="radio" name="alllang" value="yes">'.__('All',THEME_TEXTDOMAIN).'</>';	 // this query alllang is unused -		
	}	
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
 * Return the current date or a date formatted with strftime.
 *
 * @since 0.9.7.1
 * can be used in theme for multilingual date
 * @param format and time (if no time = current date-time)
 * @return the slug of language (used in query).
 */
function the_xili_local_time($format='%B %d, %Y',$time = null) {
	global $xili_language;
	 if ($time == null ) $time = time();
	$curlang = $xili_language->curlang;
	$curlang = substr($curlang,0,3).strtoupper(substr($curlang,-2));
	setlocale(LC_TIME, $curlang); /* work if server is ready */
	return htmlentities(strftime(__($format,THEME_TEXTDOMAIN),$time)); /* entities for some server */
}
/**
 * Return the language of current theme in loop.
 *
 * @since 0.9.7.0
 * useful for functions in functions.php or other plugins
 * 
 * @param ID of the post
 * @return the name of language as ISO code (en_US).
 */
function get_cur_language($post_ID) {
	global $xili_language;
	return $xili_language->get_cur_language($post_ID);
}

/**
 * Activate hooks of plugin in class.
 *
 * @since 0.9.7.4
 * can be used in functions.php for special action
 *
 * @param filter name and 
 * @return the name of language as ISO code (en_US).
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
			remove_filter('category_link', 'xili_languagexiliml_link_append_lang0');
				$catcur = get_category_link($catid); /* unusable */
			add_again_filter('category_link', 'xiliml_link_append_lang');
	return $catcur;
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
 * hook: add_action('xiliml_langinsearchform',10,2) to change its behaviour elsewhere
 * @param html tags 
 * @return echo the list as radio-button
 */	 
function xiliml_langinsearchform ($before='',$after='') { /* list of radio buttons for search form*/
	global $xili_language;
	if ($xili_language->this_has_filter('xiliml_langinsearchform')){ 
		remove_filter('xiliml_langinsearchform','xili_languagexiliml_langinsearchform0'); /*no default from class*/
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
 * hook: add_action('xiliml_the_category',10,3) to change its behaviour elsewhere
 * @param post_id separator echo (true by default) 
 * @return echo (by default) the list as radio-button
 */
function xiliml_the_category($post_ID, $separator = ', ' ,$echo = true) { /* replace the_category() */
	global $xili_language;
	if ($xili_language->this_has_filter('xiliml_the_category')){ 
		remove_filter('xiliml_the_category','xili_languagexiliml_the_category0'); /*no default from class*/
	}
	do_action('xiliml_the_category',$post_ID,$separator,$echo);
}

/**
 * Template Tag - in loop display the link of other posts defined as in other languages
 *
 * @since 0.9.0
 * can be used in theme template in single.php under the title
 * example: if(class_exists('xili_language_form')) xiliml_the_other_posts($post->ID)
 *
 * hook: add_action('xiliml_the_other_posts',10,3) to change its behaviour elsewhere
 * @param post_id before after 
 * @return echo (by default) the list 
 */
function xiliml_the_other_posts ($post_ID,$before = " ", $after = ", ") { /* display the other posts defined as in other lang */
	global $xili_language;
	if ($xili_language->this_has_filter('xiliml_the_other_posts')){ 
		remove_filter('xiliml_the_other_posts','xili_languagexiliml_the_other_posts0'); /*no default from class*/
	}
	do_action('xiliml_the_other_posts',$post_ID,$before, $after);
}

/**
 * Template Tag - in loop display the language of the post
 *
 * @since 0.9.0
 * can be used in theme template in loop under the title
 * example: if(class_exists('xili_language_form')) xili_post_language()
 *
 * hook: add_action('xili_post_language',10,2) to change its behaviour elsewhere
 * @param before after 
 * @return echo (by default) the language 
 */
function xili_post_language($before = '<span class"xili-lang">(', $after =')</span>') { /* post language in loop*/
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
 * hook: add_action('xili_language_list',10,3) to change its behaviour elsewhere
 * @param before after theoption
 * @return echo the list of languages
 */
function xili_language_list($before = '<li>', $after ='</li>', $theoption='') { /* list of languages i.e. in sidebar */
	global $xili_language;
	if ($xili_language->this_has_filter('xili_language_list')){ 
		remove_filter('xili_language_list','xili_languagexili_language_list0'); /*no default from class*/
	}	
	do_action('xili_language_list',$before,$after,$theoption); 
}

/**
 * instantiation of xili_language class
 *
 * @since 0.9.7
 *
 * @param metabox (for other posts in post edit UI - to replace custom fields - beta tests)
 * @param ajax ( true if ajax is activated for post edit admin UI - alpha tests )
 */
$xili_language = new xili_language(true,false); 

?>