<?php
/*
Plugin Name: xili-language
Plugin URI: http://dev.xiligroup.com
Description: This plugin is the first which introduce a new taxonomy - here language - to modify on the fly the translation of the theme depending the language of the post -
Author: MS
Version: 0.090122
Author URI: http://www.xiligroup.com
*/ 

/*multilingue for admin pages and menu*/

load_plugin_textdomain('xili-language',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));


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

/*filters used when querytag is used - see function.php where rules depend from theme*/
function with_lang($join) {
	global $wp_query, $wpdb;	
		if ( '' != $wp_query->query_vars[QUETAG] ) {
			$join .= " LEFT JOIN $wpdb->term_relationships as tr ON ($wpdb->posts.ID = tr.object_id) LEFT JOIN $wpdb->term_taxonomy as tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) ";
	/*echo '---->'.$wp_query->query_vars[QUETAG];*/
		}
	
	return $join;
}

function where_lang($where) {
	global $wp_query , $wpdb;
	if ( '' != $wp_query->query_vars[QUETAG] ) {
		$reqtag = is_term( $wp_query->query_vars[QUETAG], TAXONAME );
		if ( !empty($reqtag) )
					$reqtag = $reqtag['term_id'];
				else
					$reqtag = 0;
		/*echo "***** ".$reqtag;*/			
		$where .= " AND tt.taxonomy = '".TAXONAME."' ";
		$where .= " AND tt.term_id = $reqtag ";
		
	}	
	return $where;
}

add_filter('posts_join', 'with_lang');
add_filter('posts_where', 'where_lang');

/*enable the new query tag associated with new taxonomy*/
function keywords_addQueryVar($vars) {
	$vars[] = QUETAG;
	return $vars ;
}

add_filter('query_vars', 'keywords_addQueryVar');

/* set language when post is saved */

function xili_language_add($post_ID) {
	$sellang = $_POST['xili_language_set'];
	if ("" != $sellang)
		wp_set_object_terms($post_ID, $sellang, TAXONAME);
	
}
/* UI add in sidebar of post admin (write , edit) */

function xili_language_checkboxes() { 
	global $post_ID, $default_lang;
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
			$curlangname = $default_lang; /* when created before plugin */
		endif;
		
	} else {
		$curlangname = $default_lang;
	}
	echo TAXONAME.' : <a href="#xili-lang">'.$curlangname.'</a>' ; /*link to bottom of sidebar*/
?>
	<fieldset id="xili-lang" class="dbx-box">
  <h3 class="dbx-handle"><?php _e('xili-language', 'xili-language') ?></h3>
  <div class="dbx-content">
  
<?php foreach ($listlanguages as $language) { ?> 
<label for="xili_language_check_<?php echo _e($language->name, 'xili-language'); ?>" class="selectit"><input id="xili_language_check_<?php echo $language->slug ; ?>" name="xili_language_set" type="radio" value="<?php echo $language->slug ; ?>"  <?php if($curlangname==$language->name) echo 'checked="checked"' ?> /> <?php _e($language->description, 'xili-language'); ?></label>
  
<?php } /*link to top of sidebar*/?> 
  <br /><a href="#categorydiv">=> <?php _e('Categories') ;?></a>
  
  </div>
  </fieldset>
  
<?php
}
/* UI add in sidebar of post admin (write , edit) */

function xili_language_checkboxes_n() { 
	global $post_ID, $default_lang;
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
			$curlangname = $default_lang; /* when created before plugin */
		endif;
		
	} else {
		$curlangname = $default_lang;
	}
	echo __('Selected language', 'xili-language').' : <strong>'.$curlangname.'</strong><br /><br />' ; /*link to bottom of sidebar*/
?>
	
  
<?php foreach ($listlanguages as $language) { ?> 
<label for="xili_language_check_<?php echo $language->name ; ?>" class="selectit"><input id="xili_language_check_<?php echo $language->slug ; ?>" name="xili_language_set" type="radio" value="<?php echo $language->slug ; ?>"  <?php if($curlangname==$language->name) echo 'checked="checked"' ?> /> <?php echo _e($language->description, 'xili-language'); ?></label>
  
<?php } /*link to top of sidebar*/?> 
  <br /><br /><a href="#">=> <?php _e('Top') ;?></a>
  
  
  
<?php
}

function get_cur_language($post_ID) {

	$ress = wp_get_object_terms($post_ID, 'language');
		/*print_r($ress);*/
		/*Array ( [0] => stdClass Object ( [term_id] => 18 [name] => [slug] => 18 [term_group] => 0 [term_taxonomy_id] => 19 [taxonomy] => language [description] => [parent] => 0 [count] => 1 ) )*/

//print_r($ress);
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

/*List of available languages*/
function xili_language_list($before = '<li>', $after ='</li>') {
	global $curlang; /*slug of post (page or post)*/
	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
	foreach ($listlanguages as $language) {
		$a = $before ."<a href='?".QUETAG."=".$language->slug."' title='".__('Posts selected','xiliphone')." ".__('in '.$language->description,'xiliphone')."'>". __('in '.$language->description,'xiliphone') ."</a>".$after;
	echo $a;
	}	
}

/*language of current post used in loop*/
function xili_post_language($before = '<span class"xili-lang">(', $after =')</span>') {
	global $post;
	$ress = wp_get_object_terms($post->ID, TAXONAME);
	$obj_term = $ress[0];
	if ('' != $obj_term->name) :
			$curlangname = $obj_term->name;
	else :
			$curlangname = __("undefined");
	endif;
	$a = $before . $curlangname .$after;
	echo $a;
}

/* select .mo file */
function set_mofile($curlang) {
// load_theme_textdomain('xiliphone'); - replaced to be flexible -
	if (defined('THEME_TEXTDOMAIN')) {$themetextdomain = THEME_TEXTDOMAIN; } else {$themetextdomain = 'ttd-not-defined';  }
	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false,'slug' => $curlang));
	$filename = $listlanguages[0]->name;
	$filename .= '.mo';
	if ('' != $filename) {
		$mofile = get_template_directory() . "/$filename";	
		load_textdomain($themetextdomain,$mofile);
	}
}

/* wp_head action for theme headers */
function xiliml_language_head() {
	global $curlang,$post,$wp_query;
	if(!is_front_page()) {
		$curlang = get_cur_language($post->ID);
	} else {
		if ( '' != $wp_query->query_vars[QUETAG] ) {
			$curlang = $wp_query->query_vars[QUETAG];	
		} else {
			$curlang = strtolower(WPLANG); /* select here the default language of the site */
		}	
	}
	set_mofile($curlang);
}
add_action('wp_head', 'xiliml_language_head');


/*insert other language of wp_list_categories*/

function xiliml_cat_language($content, $category = null) {
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
if (function_exists('xiliml_infunc_cat_language')){
	add_filter( 'list_cats', 'xiliml_infunc_cat_language',10,2); /* in functions.php of current theme */
} else { 
    add_filter('list_cats', 'xiliml_cat_language',10,2); /* mode 2 : content = name */
}

/*by get meta : create a link list of the corresponding posts in other languages*/
function xiliml_list_links($post_ID,$before = " ", $after = ", ") {
	if (function_exists('xili_infunc_list_links')) return xili_infunc_list_links($post_ID, $before,$after);
	/* default here*/
	global $curlang;
	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
		foreach ($listlanguages as $language) {
			$otherpost = get_post_meta($post_ID, 'lang-'.$language->slug, true);
			if ('' != $otherpost && $language->slug != $curlang ) {
				$output .= $before."<a href='?p=".$otherpost."' >".__($language->description,'xiliphone') ."</a>".$after;
			}	
		}
	if ('' != $output) {_e('This post in',THEME_TEXTDOMAIN); echo $output;}
}


/* the_category() rewritten to keep new features of multilingual (and amp & pbs in link)*/
function xiliml_the_category($post_ID, $separator = ', ' ,$echo = true) {
	if (function_exists('xili_infunc_the_category')) return xili_infunc_the_category($post_ID, $separator,$echo);
	/* default here*/
	global $curlang ; /*set by locale of wpsite*/
	$the_cats_list = wp_get_object_terms($post_ID, 'category');
	$i = 0;
	foreach ($the_cats_list as $the_cat) {
		if ( 0 < $i )
			$thelist .= $separator . ' ';
		 	$the_catlink = '<a href="' . get_category_link($the_cat->term_id) . '" title="' . attribute_escape(apply_filters( 'category_description', $the_cat->description, $the_cat->term_id )) . '" ' . $rel . '>';
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


/*only if $deflang is defined as constant of before query !*/
function xiliml_add_querytag() {
	global $wp_query;
	global $deflang;
	if (!empty($wp_query->query_vars['cat']) && ($deflang != '') ) :
		echo '----';
		if (empty($wp_query->query_vars[QUETAG])) :
			$wp_query->query_vars[QUETAG] = $deflang;/* to force sub select in cat*/
		endif;
	endif;
return;	
}	
/* insert def language in cat */
if (function_exists('xiliml_infunc_add_querytag')){
	add_filter( 'pre_get_posts', 'xiliml_infunc_add_querytag' ); /* in functions.php of current theme */
} else {
	add_action('pre_get_posts', 'xiliml_add_querytag');
}

/*add the language key in links of current pages*/
function xiliml_link_append_lang( $link ) {
	global $curlang; 
	//if ((strpos($link, 'cat=1') === false)) : /*depending theme rules*/
  		if ($curlang) :
  			$link .= '&amp;lang='.$curlang ;
  		endif;
  	//endif;
  return $link;
}
if (function_exists('xiliml_infunc_link_append_lang')){
	add_filter( 'category_link', 'xiliml_infunc_link_append_lang' ); /* in functions.php of current theme */
} else {
	add_filter( 'category_link', 'xiliml_link_append_lang' );
}

/*translate description of cat*/
function xiliml_link_translate_desc( $description, $category=null,$context='') {
	global $curlang;
  	if ($curlang) :
  			$translated_desc = __($description,'xiliphone') ;
  	else :
  			$translated_desc = $description;
  	endif;
 	return $translated_desc;
}
if (function_exists('xiliml_infunc_link_translate_desc')){
	add_filter( 'category_description', 'xiliml_infunc_link_translate_desc' ); /* in functions.php of current theme */
} else {
	add_filter('category_description','xiliml_link_translate_desc');
}
/* end cat description */


//********************************************//
// Actions
//********************************************//

add_action('publish_post', 'xili_language_add');
add_action('admin_menu', 'myplugin_add_custom_box');

function myplugin_add_custom_box() {
	if (function_exists('add_meta_box')) {
 		// 2.5 logic, calling add_meta_box to define the screen
 		add_meta_box('1', 'xili-language', 'xili_language_checkboxes_n', 'page', 'normal');
 		add_meta_box('1', 'xili-language', 'xili_language_checkboxes_n', 'post', 'normal');
	} else {
 		// 2.3 logic, calling add_action( 'dbx_post_advanced' ) or similar
 		add_action ( 'dbx_post_sidebar', 'xili_language_checkboxes') ;
		add_action ( 'dbx_page_sidebar', 'xili_language_checkboxes') ;
	} 
}
add_action('admin_menu', 'xili_add_pages');

//********************************************//
// Administration - settings pages 
//********************************************//

/**/
function language_menu() { 
	$formtitle = __('Add a language','xili-language');
	$submit_text = __('Add &raquo;','xili-language');
	$cancel_text = __('Cancel');
	if (isset($_POST['action'])) $action=$_POST['action'];
	
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
		    
		    $message .= " - ".__('A language to delete. CLICK MENU LANGUAGE TO CANCEL !','xili-language');
		    
		    break;
		    
		case 'deleting';
		    $actiontype = "add";
		    $term = $_POST['language_term_id'];
		    wp_delete_term( $term, TAXONAME, $args);
		    $message .= " - ".__('A language was deleted.','xili-language');
		    break;
		    
		default :
		    $actiontype = "add";
		    $message .= __('Find above the list of languages.','xili-language');
		    
		    
	}
	?>
	<form name="add" id="add" method="post" action="options-general.php?page=language_page">
	<input type="hidden" name="action" value="<?php echo $actiontype ?>" />
	
	<?php if (!isset($action) || $action=='add' || $action=='edited' || $action=='deleting') :?>
	<div class='wrap'>
	
	<h2><?php _e('Language','xili-language') ?> (<a href="#add"><?php _e('Add a language','xili-language') ?></a>)</h2>
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
   
   	</div>
	<?php endif; ?>
	<div class='wrap'>
	<h4><?php _e('Note:','xili-language') ?></h4>
	<p><?php echo $message ?></p>
	</div>
	<?php /* the create - edit - delete form */ ?>
	<div class='wrap'>
	<h2 id="add"><?php _e($formtitle,'xili-language') ?></h2>
	<?php if ($action=='edit' || $action=='delete') :?>
		<input type="hidden" name="language_term_id" value="<?php echo $language->term_id ?>" />
	<?php endif; ?>
	<table class="editform" width="100%" cellspacing="2" cellpadding="5">
		<tr>
			<th width="33%" scope="row" valign="top"><label for="language_name"><?php _e('Name:') ?></label></th>
			<td width="67%"><input name="language_name" id="language_name" type="text" value="<?php echo attribute_escape($language->name); ?>" size="40" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="language_nicename"><?php _e('Language slug:','xili-language') ?></label></th>
			<td><input name="language_nicename" id="language_nicename" type="text" value="<?php echo attribute_escape($language->slug); ?>" size="40" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="language_description"><?php _e('Full name:','xili-language') ?></label></th>
			<td><input name="language_description" id="language_description" size="40" value="<?php echo $language->description; ?>" <?php if($action=='delete') echo 'disabled="disabled"' ?> /></td>
			
		</tr>
	</table>
<p class="submit"><input type="submit" name="submit" value="<?php echo $submit_text ?>" /></p>

	</div>
	<div class='wrap'>
	<h3>© <a href="http://www.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2007-9</h3>
	<p><?php _e('This plugin was developped to test the new taxonomy, terms tables and tags specifications. <br /> Here a new taxonomy was created and used for languages of posts. <br /> New radiobuttons are available in Post (and Page) write and edit pages for by author selection. It is now updated for WP 2.7','xili-language') ?></p>
	</div>
	</form>
<?php	
	}
/*add admin menu and associated page*/
function xili_add_pages() {
	 add_options_page(__('Language','xili-language'), __('Language','xili-language'), 'import', 'language_page', 'language_menu');
}

/* private function for admin page : one line of taxonomy */
	
function xili_lang_row() { 
	
	global $default_lang;	
	/*list of languages*/
	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
	if (empty($listlanguages)) : /*create a default line with the default language (as in config)*/
		$term = $default_lang;
		$args = array( 'alias_of' => '', 'description' => $default_lang, 'parent' => 0, 'slug' =>'');
		wp_insert_term( $term, TAXONAME, $args);
		$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
	endif;
	foreach ($listlanguages as $language) {
		
		$class = ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || " class='alternate'" == $class ) ? '' : " class='alternate'";

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