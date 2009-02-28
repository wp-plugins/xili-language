<?php
/*
Plugin Name: xili-language
Plugin URI: http://dev.xiligroup.com/xili-language/
Description: This plugin is the first which introduce a new taxonomy - here language - to modify on the fly the translation of the theme depending the language of the post or other blog elements - a way to create a real multi-language site (cms or blog).
Author: dev.xiligroup.com - MS
Version: 0.9.6DEV
Author URI: http://dev.xiligroup.com
*/

# updated 090228 - see 0.9.6 in comments of functions below - only for WP 2.7.x
# updated 090226 - see 0.9.5 in comments of functions below
# updated 090221 - more than one lang in query - better hooks for function placed in functions.php - some fixes 
# updated 090215 - add language in posts (and pages) list.
# updated 090208 - fix forgotten theme_domain 
# updated 090205 - fix page publish

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

/*multilingual for admin pages and menu*/

load_plugin_textdomain('xili-language',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));

define('XILILANGUAGE_VER','0.9.6');

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

add_filter('plugin_action_links', 'xililang_filter_plugin_actions', 10, 2);



function xili_language_activate() {
	$submitted_settings = array(
			    'taxonomy'		=> 'language',
			    'version' 		=> '0.2',
			    'reqtag'		=> 'lang',
		    );
	update_option('xili_language_settings', $submitted_settings);	    
	}

/*activated when first activation of plug*/
register_activation_hook(__FILE__,'xili_language_activate');

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

/*enable the new query tag associated with new taxonomy*/
function keywords_addQueryVar($vars) {
	$vars[] = QUETAG;
	return $vars ;
}
add_filter('query_vars', 'keywords_addQueryVar');


/* default values */
global $default_lang;
if (''!= WPLANG && strlen(WPLANG)==5) :
	$default_lang = WPLANG;
else:
	$default_lang = 'en_US';
endif;

function get_default_slug() {
	global $default_lang;
	
	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
	$default_slug = 'en_us';
	foreach ($listlanguages as $language) {
		if ($language->name == $default_lang ) return $language->slug;
	}
	return $default_slug ;
}

define('DEFAULTSLUG', get_default_slug());

/*filters used when querytag is used - 
 *see below and functions.php where rules depend from theme
 */
function with_lang($join) {
	global $wp_query, $wpdb;	
		if ( '' != $wp_query->query_vars[QUETAG] ) {
			$join .= " LEFT JOIN $wpdb->term_relationships as tr ON ($wpdb->posts.ID = tr.object_id) LEFT JOIN $wpdb->term_taxonomy as tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) ";
	/*echo '---->'.$wp_query->query_vars[QUETAG];*/
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

add_filter('posts_join', 'with_lang');
add_filter('posts_where', 'where_lang');


/* set language when post is saved */

function xili_language_add($post_ID) {
	$sellang = $_POST['xili_language_set'];
	if ("" != $sellang)
		wp_set_object_terms($post_ID, $sellang, TAXONAME);
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
		/*print_r($ress);*/
		/*Array ( [0] => stdClass Object ( [term_id] => 18 [name] => [slug] => 18 [term_group] => 0 [term_taxonomy_id] => 19 [taxonomy] => language [description] => [parent] => 0 [count] => 1 ) )*/
	
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


//********************************************//
// Functions for themes
//********************************************//


/**
 * List of available languages.
 *
 * @since 0.9.0
 * 
 *
 * @param $before = '<li>', $after ='</li>'.
 * @return list of languages of site for sidebar list.
 */
function xili_language_list($before = '<li>', $after ='</li>') {
	if (function_exists('xiliml_infunc_language_list')) return xiliml_infunc_language_list($before,$after);
	/* default here*/
	global $curlang; /*slug of post (page or post)*/
	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
	foreach ($listlanguages as $language) {
		$a = $before ."<a href='?".QUETAG."=".$language->slug."' title='".__('Posts selected',THEME_TEXTDOMAIN)." ".__('in '.$language->description,THEME_TEXTDOMAIN)."'>". __('in '.$language->description,THEME_TEXTDOMAIN) ."</a>".$after;
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
	$a = $before . $curlangname .$after;
	echo $a;
}

/**
 *select .mo file 
 * @since 0.9.0
 * 
 * call by function in wp_head : see xiliml_language_head()
 * @param $curlang .
 */
function set_mofile($curlang) {
// load_theme_textdomain(THEME_TEXTDOMAIN); - replaced to be flexible -
	if (defined('THEME_TEXTDOMAIN')) {$themetextdomain = THEME_TEXTDOMAIN; } else {$themetextdomain = 'ttd-not-defined';  }
	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false,'slug' => $curlang));
	$filename = $listlanguages[0]->name;
	$filename .= '.mo';
	if ('' != $filename) {
		$mofile = get_template_directory() . "/$filename";	
		load_textdomain($themetextdomain,$mofile);
	}
}

/**
 * wp_head action for theme headers 
 *
 * @since 0.9.0
 * can be hooked by xiliml_infunc_language_head in functions.php
 * call by wp_head()
 * @param $curlang .
 */
function xiliml_language_head() {
	if (function_exists('xiliml_infunc_language_head')) return xiliml_infunc_language_head();
	/* default here*/
	global $curlang,$post,$wp_query;
	if(!is_front_page()) { /* every pages */
		$curlang = get_cur_language($post->ID); /* the first post give the current lang*/
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
	set_mofile($curlang);
}

add_action('wp_head', 'xiliml_language_head');

/**
 * insert other language of wp_list_categories 
 *
 * @since 0.9.0
 * can be hooked by xiliml_infunc_cat_language in functions.php
 * call by do_filter list_cats 
 * @param $curlang .
 */
 
function xiliml_cat_language($content, $category = null) {
	if (function_exists('xiliml_infunc_cat_language')) 	return xiliml_infunc_cat_language ($content, $category);
	/* default */
      global $curlang ; 
            /*set by locale of wpsite*/
      /*these rules can be changed*/
         if (''!= $curlang) : /*to detect admin UI*/
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
 
add_filter('list_cats', 'xiliml_cat_language',10,2); /* mode 2 : content = name */


/** 
 * create a link list of the corresponding posts in other languages
 *
 * @since 0.9.0
 * can be hooked by xiliml_infunc_the_other_posts in functions.php
 *
 *
 */
	
function xiliml_the_other_posts ($post_ID,$before = " ", $after = ", ") {
	if (function_exists('xiliml_infunc_the_other_posts')) return xiliml_infunc_the_other_posts($post_ID, $before,$after);
	/* default here*/
	global $curlang;
	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
		foreach ($listlanguages as $language) {
			$otherpost = get_post_meta($post_ID, 'lang-'.$language->slug, true);
			if ('' != $otherpost && $language->slug != $curlang ) {
				$output .= $before."<a href='".get_permalink($otherpost)."' >".__($language->description,THEME_TEXTDOMAIN) ."</a>".$after;
			}	
		}
	if ('' != $output) {_e('This post in',THEME_TEXTDOMAIN); echo $output;}
}
function xiliml_list_links ($post_ID,$before = " ", $after = ", ") {
	return xiliml_the_other_posts ($post_ID, $before,$after); /*for compatibility */
}

/**
 * the_category() rewritten to keep new features of multilingual (and amp & pbs in link)
 *
 * @since 0.9.0
 * can be hooked by xili_infunc_the_category in functions.php
 *
 *
 */
function xiliml_the_category($post_ID, $separator = ', ' ,$echo = true) {
	if (function_exists('xili_infunc_the_category')) return xili_infunc_the_category($post_ID, $separator,$echo);
	/* default here*/
	global $curlang ; /*set by locale of wpsite*/
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
 * to cancel sub select by lang in cat 1 by default 
 *
 * @since 0.9.2
 * can be hooked by xiliml_infunc_modify_querytag in functions.php
 *
 */
function xiliml_modify_querytag() {
	if (function_exists('xiliml_infunc_modify_querytag')) return xiliml_infunc_modify_querytag();
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
add_action('pre_get_posts', 'xiliml_modify_querytag');


/**
 * add the language key in category links of current pages
 *
 */
function xiliml_link_append_lang( $link ) {
	if (function_exists('xiliml_infunc_link_append_lang')) return xiliml_infunc_link_append_lang($link);
	global $curlang; 
	//if ((strpos($link, 'cat=1') === false)) : /*depending theme rules*/
  		if ($curlang) :
  			$link .= '&amp;'.QUETAG.'='.$curlang ;
  		endif;
  	//endif;
  return $link;
}
add_filter( 'category_link', 'xiliml_link_append_lang' );

/**
 * Add list of languages in radio input - for search form.
 *
 * @since 0.9.4
 * can be hooked by xiliml_infunc_langinsearchform in functions.php
 *
 * $before, $after each line of radio input
 *
 * @param $before, $after. 
 * @return echo the form.
 */
function xiliml_langinsearchform ($before='',$after='') {
	if (function_exists('xiliml_infunc_langinsearchform')) return xiliml_infunc_langinsearchform($before,$after);
	/* default here*/
	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
	foreach ($listlanguages as $language) {
		$a = $before.'<input type="radio" name="'.QUETAG.'" value="'.$language->slug.'" id=s"'.$language->slug.'">'.__($language->description,THEME_TEXTDOMAIN).' </>'.$after;
	echo $a;
	}
	
    echo '<input type="radio" name="alllang" value="yes">'.__('All',THEME_TEXTDOMAIN).'</>';	 // this query alllang is unused -
}

/**
 * Setup global post data.
 *
 * @since 0.9.4
 * can be hooked by xiliml_infunc_taglink_append_lang in functions.php
 *
 * @param $taglink, $tag_id.
 * @return $taglink.
 */
function xiliml_taglink_append_lang ( $taglink, $tag_id=null ) {
	if (function_exists('xiliml_infunc_taglink_append_lang')) return xiliml_infunc_taglink_append_lang($taglink);
	/* no yet default */
	/* global $curlang; 
	
  		if ($curlang) :
  			$taglink .= '&amp;'.QUETAG.'='.$curlang ;
  		endif;
  	
  	*/
 return $taglink;
} 
add_filter( 'tag_link', 'xiliml_taglink_append_lang' ); 
 


/**
 * translate description of cat
 *
 */
function xiliml_link_translate_desc( $description, $category=null,$context='') {
	if (function_exists('xiliml_infunc_link_translate_desc')) return xiliml_infunc_link_translate_desc( $description, $category,$context);
	/*default*/
	global $curlang;
  	if ($curlang) :
  			$translated_desc = __($description,THEME_TEXTDOMAIN) ;
  	else :
  			$translated_desc = $description;
  	endif;
 	return $translated_desc;
}
add_filter('category_description','xiliml_link_translate_desc');

/* end cat description */


//********************************************//
// Actions
//********************************************//

//add_action('save_post', 'xili_language_add');
add_action('publish_post', 'xili_language_add'); /* only set when published !*/
//add_action('save_page', 'xili_language_add');
add_action('publish_page', 'xili_language_add');

add_action('admin_menu', 'myplugin_add_custom_box');

function myplugin_add_custom_box() {
	if (function_exists('add_meta_box')) {
 		// 2.5 logic, calling add_meta_box to define the screen - side again in 2.7 //
 		add_meta_box('xilil', 'xili-language', 'xili_language_checkboxes_n', 'page', 'side','high');
 		add_meta_box('xilil', 'xili-language', 'xili_language_checkboxes_n', 'post', 'side','high');
	}
}
add_action('admin_menu', 'xili_add_pages');


/**** Authors and ADMIN ***/

/* inspired from custax */
add_action('manage_posts_custom_column', 'xili_manage_column', 10, 2);
add_filter('manage_edit_columns', 'xili_manage_column_name');

add_action('manage_pages_custom_column', 'xili_manage_column', 10, 2);
add_filter('manage_edit-pages_columns', 'xili_manage_column_name');



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
	<label for="xili_language_check_<?php echo $language->name ; ?>" class="selectit"><input id="xili_language_check_<?php echo $language->slug ; ?>" name="xili_language_set" type="radio" value="<?php echo $language->slug ; ?>"  <?php if($curlangname==$language->name) echo 'checked="checked"' ?> /> <?php echo _e($language->description, 'xili-language'); ?></label>
  
	<?php } /*link to top of sidebar*/?> 
	<label for="xili_language_check"class="selectit"><input id="xili_language_check" name="xili_language_set" type="radio" value="" <?php if($curlangname=="") echo 'checked="checked"' ?> /> <?php _e('undefined','xili-language') ?></label><br />
  	<br />
  	<br />
  	<?php if ($wp_version < '2.7') { ?>
  	<a href="#">=> <?php _e('Top') ;?></a>
<?php }
}




//********************************************//
// Administration - settings pages 
//********************************************//
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
/**
 * to display the languages settings admin UI
 *
 * @since 0.9.0
 * @updated 0.9.6 - only for WP 2.7.X - do new meta boxes and JS
 *
 */
function languages_settings() { 
	global $thehook;
	
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
	add_meta_box('xili-language-normal-1', __('List of languages','xili-language'), 'on_normal_1_content', $thehook , 'normal', 'core');
	add_meta_box('xili-language-normal-2', __('Language','xili-language'), 'on_normal_2_content', $thehook , 'normal', 'core');
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
					<?php do_meta_boxes($thehook, 'side', $data); ?>
				</div>
			
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
				
   					<?php do_meta_boxes($thehook, 'normal', $data); ?>
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
			postboxes.add_postbox_toggles('<?php echo $thehook; ?>');
		});
		//]]>
	</script> 
<?php	//end settings div
	}
	
/**
 * add admin menu and associated pages of admin UI
 *
 * @since 0.9.0
 * @updated 0.9.6 - only for WP 2.7.X - do registering of new meta boxes and JS
 *
 */
function xili_add_pages() {
	global $thehook;
	 $thehook = add_options_page(__('Language','xili-language'), __('Language','xili-language'), 'manage_options', 'language_page', 'languages_settings');
	 add_action('load-'.$thehook, 'on_load_page');
	 
}

function on_load_page() {
	global $thehook;
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
		add_meta_box('xili-language-sidebox-1', __('Message','xili-language'), 'on_sidebox_1_content', $thehook , 'side', 'core');
		add_meta_box('xili-language-sidebox-2', __('Info','xili-language'), 'on_sidebox_2_content', $thehook , 'side', 'core');
		
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
						<?php xili_lang_row(); /* the lines */?>
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
?>