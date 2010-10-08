<?php
/*
Plugin Name: xili-language widget
Plugin URI: http://dev.xiligroup.com/xili-language/
Description: This plugin is a part of xili-language plugin. Add a optional widgets to display list of languages in the sidebar or recent comments and recents posts (since 2.8.0)
Author: dev.xiligroup.com - MS
Version: 1.8.0
Author URI: http://dev.xiligroup.com
*/

# 100713 - 1.7.0 - add a querytag to be compatible with new mechanism (join+where) of xili-language
# 100602 - 1.6.0 - add list of options in widget - hook possible if hook in languages_list
# 100416 - change theme_domain constant for multisite (WP3)
# 100219 - add new widget recent posts if WP >= 2.8.0
# 090606 - xili-language list widget is now multiple and more features
# 090518 - new widget for recent comments
# 090404 - new registering way.
# 090325 - better options record.

/*  thanks to http://blog.zen-dreams.com/ tutorial

	Copyright 2009-10  dev.xiligroup.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Recent_Posts widget class
 * rewritten from default WP widget to suppress wp_reset_query and add sub-selection by language (current or forced) 
 * @since 1.4.0 
 */
class xili_Widget_Recent_Posts extends WP_Widget {

	function xili_Widget_Recent_Posts() {
		$widget_ops = array('classname' => 'xili_widget_recent_entries', 'description' => __( "The most recent posts on your blog by xili-language",'xili-language-widget') );
		$this->WP_Widget('xili-recent-posts', __('List of recent posts','xili-language-widget'), $widget_ops);
		$this->alt_option_name = 'xili_widget_recent_entries';

		add_action( 'save_post', array(&$this, 'flush_widget_cache') );
		add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
		add_action( 'switch_theme', array(&$this, 'flush_widget_cache') );
	}

	function widget($args, $instance) {
		$cache = wp_cache_get('xili_widget_recent_posts', 'widget');

		if ( !is_array($cache) )
			$cache = array();

		if ( isset($cache[$args['widget_id']]) ) {
			echo $cache[$args['widget_id']];
			return;
		}

		ob_start();
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Recent Posts') : $instance['title']);
		if ( !$number = (int) $instance['number'] )
			$number = 10;
		else if ( $number < 1 )
			$number = 1;
		else if ( $number > 15 )
			$number = 15;
			
		$the_lang =	$instance['the_lang'];
		
		if (class_exists('xili_language')) {
			global $wp_query;
			$tmp_query = $wp_query->query_vars[QUETAG] ; $wp_query->query_vars[QUETAG] = "";
			if ($the_lang == '') 
				$thequery = array('showposts' => $number, 'nopaging' => 0, 'post_status' => 'publish', 'caller_get_posts' => 1);
			else if ($the_lang == '*')	
			 	$thequery = array ('xlrp' => 1, 'showposts' => $number, 'nopaging' => 0, 'post_status' => 'publish',  'caller_get_posts' => 1, QUETAG => the_curlang()); 
			else
				$thequery = array ('xlrp' => 1, 'showposts' => $number, 'nopaging' => 0, 'post_status' => 'publish',  'caller_get_posts' => 1, QUETAG => $the_lang); 	
			//echo '===='.the_curlang();
			add_action('parse_query','xiliml_add_lang_to_parsed_query');
			$r = new WP_Query($thequery);
			remove_filter('parse_query','xiliml_add_lang_to_parsed_query');
			$wp_query->query_vars[QUETAG] = $tmp_query;
		} else {
			$thequery = array('showposts' => $number, 'nopaging' => 0, 'post_status' => 'publish', 'caller_get_posts' => 1);
			$r = new WP_Query($thequery);
		}
		
		if ($r->have_posts()) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul>
		<?php  while ($r->have_posts()) : $r->the_post(); ?>
		<li><a href="<?php the_permalink() ?>" title="<?php echo esc_attr(get_the_title() ? get_the_title() : get_the_ID()); ?>"><?php if ( get_the_title() ) the_title(); else the_ID(); ?> </a></li>
		<?php endwhile; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
			//wp_reset_query();  // Restore global post data stomped by the_post().
		endif;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_add('xili_widget_recent_posts', $cache, 'widget');
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['the_lang'] = strtolower($new_instance['the_lang']);
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['xili_widget_recent_entries']) )
			delete_option('xili_widget_recent_entries');

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete('xili_widget_recent_posts', 'widget');
	}

	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$the_lang = isset($instance['the_lang']) ? strtolower($instance['the_lang']) : '';
		if ( !isset($instance['number']) || !$number = (int) $instance['number'] )
			$number = 5;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
		
		<?php if (class_exists('xili_language')) { global $xili_language; ?>
		<p>
			<label for="<?php echo $this->get_field_id('the_lang'); ?>"><?php _e('Language:','xili-language-widget'); ?></label>
			<select name="<?php echo $this->get_field_name('the_lang'); ?>" id="<?php echo $this->get_field_id('the_lang'); ?>" class="widefat">
				<option value=""<?php selected( $instance['the_lang'], '' ); ?>><?php _e('All languages','xili-language-widget'); ?></option>
				<option value="*"<?php selected( $instance['the_lang'], '*' ); ?>><?php _e('Current language','xili-language-widget'); ?></option>
				<?php $listlanguages = get_terms_of_groups_lite ($xili_language->langs_group_id,TAXOLANGSGROUP,TAXONAME,'ASC');
					foreach ($listlanguages as $language) { ?>
					<option value="<?php echo $language->slug ?>"<?php selected( $instance['the_lang'], $language->slug ); ?>><?php _e($language->description,'xili-language-widget'); ?></option>	
						
					<?php } /* end */
				?>
			</select>
		</p>
		<?php } ?>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /><br />
		<small><?php _e('(at most 15)'); ?></small></p>
		<p><small>© xili-language</small></p>
<?php
	}
}


/*
 * Adapted Recent Comments Widget (original in widget.php is not multilingual ready)
 *
 *@since 0.9.9.4
 *
 *
 */
 
class xili_recent_comments_Widget {
	
	function xili_recent_comments_Widget() {
		load_plugin_textdomain('xili-language-widget',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
		add_action('widgets_init', array(&$this, 'init_widget'));
	}
	
	function init_widget() {
		if ( !function_exists('wp_register_sidebar_widget') || !function_exists('wp_register_widget_control') )
			return;
		$widget_ops = array('classname' => 'xili-language_recent_comments_Widget', 'description' => __( "List of recent comments by xili-language plugin",'xili-language-widget') );
		wp_register_sidebar_widget('xili-language_recent_comments_Widget',__('List of recent comments','xili-language-widget'),array(&$this, 'widget'),$widget_ops);
	 	wp_register_widget_control('xili-language_recent_comments_Widget',__('List of recent comments','xili-language-widget'),array(&$this, 'widget_options'),$widget_ops);
		
	}
	
	function widget($args) {
		global $comments, $comment;
		extract($args, EXTR_SKIP);
		$options = get_option('xili_language_recent_comments');
		$title = empty($options['title']) ? __('Recent Comments',the_theme_domain()) : apply_filters('widget_title', $options['title']);
		if ( !$number = (int) $options['number'] )
			$number = 5;
		else if ( $number < 1 )
			$number = 1;
		else if ( $number > 15 )
			$number = 15;
	/* if xili-language plugin is activated */
		if (function_exists('xiliml_recent_comments'))
			$comments = xiliml_recent_comments($number);
	/* */	
		echo $before_widget; ?>
				<?php echo $before_title . $title . $after_title; ?>
				<ul id="recentcomments"><?php
				if ( $comments ) : foreach ( (array) $comments as $comment) :
				echo  '<li class="recentcomments">' . sprintf(__('%1$s on %2$s',the_theme_domain()), get_comment_author_link(), '<a href="' . clean_url( get_comment_link($comment->comment_ID) ) . '">' . get_the_title($comment->comment_post_ID) . '</a>') . '</li>';
				endforeach; endif;?></ul>
			<?php echo $after_widget;
	}
	
	function widget_options() {
		$options = $newoptions = get_option('xili_language_recent_comments');
		if ( isset($_POST["xl_recent-comments-submit"]) ) {
			$newoptions['title'] = strip_tags(stripslashes($_POST["xl_recent-comments-title"]));
			$newoptions['number'] = (int) $_POST["xl_recent-comments-number"];
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('xili_language_recent_comments', $options);
			wp_delete_recent_comments_cache();
		}
		$title = attribute_escape($options['title']);
		if ( !$number = (int) $options['number'] )
			$number = 5;
		?>
				<p><label for="xl_recent-comments-title"><?php _e('Title:'); ?> <input class="widefat" id="xl_recent-comments-title" name="xl_recent-comments-title" type="text" value="<?php echo $title; ?>" /></label></p>
				<p>
					<label for="xl_recent-comments-number"><?php _e('Number of comments to show:'); ?> <input style="width: 25px; text-align: center;" id="xl_recent-comments-number" name="xl_recent-comments-number" type="text" value="<?php echo $number; ?>" /></label>
					<br />
					<small><?php _e('(at most 15)'); ?></small>
				</p>
				<input type="hidden" id="xl_recent-comments-submit" name="xl_recent-comments-submit" value="1" />
		<?php
		}	
}

/*
 * class for multiple xili-language widgets
 * @since 0.9.9.6  
 *
 */
class xili_language_Widgets {

	function xili_language_Widgets () {
		load_plugin_textdomain('xili-language-widget',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
		add_action('widgets_init', array(&$this, 'init_widget'));
	}

	function init_widget() {
		if ( !function_exists('wp_register_sidebar_widget') || !function_exists('wp_register_widget_control') )
			return;
		if (!$options = get_option('xili_language_widgets_options'))
			$options = array();
		$widget_ops = array('classname' => 'xili-language_Widgets', 'description' => __( "List of available languages by xili-language plugin",'xili-language-widget') );
		$control_ops = array('id_base' => 'xili_language_widgets');
		$name = __('Languages list','xili-language-widget');
		
		$id = false;
		foreach ( (array) array_keys($options) as $o ) {
			$id = "xili_language_widgets-$o"; // Never never never translate an id
			wp_register_sidebar_widget($id, $name, array(&$this, 'widget'), $widget_ops, array( 'number' => $o ));
			wp_register_widget_control($id, $name, array(&$this, 'widget_options'), $control_ops, array( 'number' => $o ));
		}

		// If there are none, we register the widget's existance with a generic template
		if ( !$id ) {
			wp_register_sidebar_widget( 'xili_language_widgets-1', $name, array(&$this, 'widget'), $widget_ops, array( 'number' => -1 ) );
			wp_register_widget_control( 'xili_language_widgets-1', $name, array(&$this, 'widget_options'), $control_ops, array( 'number' => -1 ) );
			
		}
		
	}

	function widget($args, $widget_args = 1) {
		global $wpdb;

		$options = get_option('xili_language_widgets_options');
		extract($args);
		
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		if ( !isset($options[$number]) )
			return;
		if 	("" != $options[$number]['title']) {
			echo $before_widget.$before_title.__($options[$number]['title'],the_theme_domain()).$after_title;
		} else {
			echo $before_widget.$before_title.$aftertitle;
		}
		
		if (isset($options[$number]['beforelist'])) {
			$beforelist = stripslashes($options[$number]['beforelist']);
			$afterlist = stripslashes($options[$number]['afterlist']);
		} else {
			$beforelist = "<ul class='xililanguagelist'>";
			$afterlist = '</ul>';
		}
		if (isset($options[$number]['beforeline'])) {
			$beforeline = stripslashes($options[$number]['beforeline']);
			$afterline = stripslashes($options[$number]['afterline']);
		} else {
			$beforeline = '<li>';
			$afterline = '</li>';
		}
		$theoption = $options[$number]['theoption'];
		
		if (function_exists('xili_language_list')) { 
			echo $beforelist;
				xili_language_list($beforeline, $afterline, $theoption);
			echo $afterlist;
			}
		echo $after_widget;
	}

	function widget_options($widget_args) {
		global $xili_language;
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
		
		$options = get_option('xili_language_widgets_options');
		if (!is_array($options)) 
			$options = array();
		
		if ( !$updated && !empty($_POST['sidebar']) ) {
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();
		
			foreach ( (array) $this_sidebar as $_widget_id ) {
				
				if ( 'xili-language_widgets' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "xili-language_widgets-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
					  	unset($options[$widget_number]);
				}
			}
		
			foreach ( (array) $_POST['xili-language_Widgets'] as $widget_number => $widget_text ) {
				if (isset($widget_text['submit'])) {
					$options[$widget_number]['title'] = strip_tags(stripslashes($widget_text['title']));
					$options[$widget_number]['beforelist'] = $widget_text['beforelist'] ;
					$options[$widget_number]['beforeline'] = $widget_text['beforeline'] ;
					$options[$widget_number]['afterline'] = $widget_text['afterline'] ;
					$options[$widget_number]['afterlist'] = $widget_text['afterlist'] ;
					$options[$widget_number]['theoption'] = strip_tags(stripslashes($widget_text['theoption'])) ;
					
				}
			}
				update_option('xili_language_widgets_options',$options);
				$updated = true;
		}
			
			
		$options = get_option('xili_language_widgets_options');
		
		if ( -1 == $number ) {
			$title = '';
			$number = '%i%';
			
			$beforelist = "<ul class='xililanguagelist'>";
			$afterlist = '</ul>';
			$beforeline = '<li>';
			$afterline = '</li>';
			$theoption = '';
			
		} else {
			$title = attribute_escape($options[$number]['title']);
			$beforelist = htmlentities(stripslashes($options[$number]['beforelist']));
			$beforeline =  htmlentities(stripslashes($options[$number]['beforeline']));
			$afterline =  htmlentities(stripslashes($options[$number]['afterline']));
			$afterlist =  htmlentities(stripslashes($options[$number]['afterlist']));
			$theoption =  stripslashes($options[$number]['theoption']);
		}
		
		echo '<label for="xili_l_widget_title-'.$number.'">'.__('Title').': <input id="xili_l_widget_title-'.$number.'" name="xili-language_Widgets['.$number.'][title]" type="text" value="'.$title.'" /></label>';
		// other options since 1.6.0 
		
		if ($xili_language->this_has_filter('xili_language_list')) // one external action
			$xili_language->langs_list_options = array();
		if (has_filter('xili_language_list_options')) {	// is list of options described
			do_action('xili_language_list_options',$theoption);
		}
		if (isset($xili_language->langs_list_options) && $xili_language->langs_list_options != array()) {
			echo '<br /><label for="xili_l_widget_theoption-'.$number.'">'.__('Type','xili-language-widget').':';
			echo '<select name="xili-language_Widgets['.$number.'][theoption]" id="xili_l_widget_theoption-'.$number.'">';
			foreach ($xili_language->langs_list_options as $typeoption) {
				$selectedoption = ($theoption == $typeoption[0]) ? 'selected = "selected"':'';
				echo '<option value="'.$typeoption[0].'" '.$selectedoption.' >'.$typeoption[1].'</option>';
			}
			echo '</select>';		
		} else {
			echo '<br /><label for="xili_l_widget_theoption-'.$number.'">'.__('Type','xili-language-widget').': <input id="xili_l_widget_theoption-'.$number.'" name="xili-language_Widgets['.$number.'][theoption]" type="text" value="'.$theoption.'" /></label>';	
		}
		
		//list html tags
		echo '<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend>'.__('HTML tags of list','xili-language-widget').'</legend>';
		
		echo "<label for=\"xili_l_widget_beforelist-".$number."\">".__('before list','xili-language-widget').": <input id=\"xili_l_widget_beforelist-".$number."\" name=\"xili-language_Widgets[".$number."][beforelist]\" type=\"text\" value=\"".$beforelist."\" style=\"width:100%\" /></label><br />";
		echo '<label for="xili_l_widget_beforeline-'.$number.'">'.__('before line','xili-language-widget').': <input id="xili_l_widget_beforeline-'.$number.'" name="xili-language_Widgets['.$number.'][beforeline]" type="text" value="'.$beforeline.'" style="width:100%" /></label><br />';
		echo '<label for="xili_l_widget_afterline-'.$number.'">'.__('after line','xili-language-widget').': <input id="xili_l_widget_afterline-'.$number.'" name="xili-language_Widgets['.$number.'][afterline]" type="text" value="'.$afterline.'" style="width:100%" /></label><br />';
		echo '<label for="xili_l_widget_afterlist-'.$number.'">'.__('after list','xili-language-widget').': <input id="xili_l_widget_afterlist-'.$number.'" name="xili-language_Widgets['.$number.'][afterlist]" type="text" value="'.$afterlist.'" style="width:100%" /></label>';
		
		echo '</fieldset>';
		
		echo '<p><small>© xili-language</small></p>';
		//
		echo '<input type="hidden" id="xili_l_widget_submit-'.$number.'" name="xili-language_Widgets['.$number.'][submit]" value="1" />';
		
	} // end options (control)
		
} // end class



$xili_recent_comments_widget =& new xili_recent_comments_Widget ();

/* since 0.9.9.6 - multiple widgets available */

$xili_language_widgets =& new xili_language_Widgets ();

/* since 1.3.2 - multiple recent posts widgets available */

if ( $wp_version >= '2.8.0') {
	function add_new_widgets() {
 		register_widget('xili_Widget_Recent_Posts');
	}
	add_action('widgets_init','add_new_widgets');
}
?>