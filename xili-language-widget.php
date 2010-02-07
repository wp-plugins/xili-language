<?php
/*
Plugin Name: xili-language widget
Plugin URI: http://dev.xiligroup.com/xili-language/
Description: This plugin is a part of xili-language plugin. Add a optional widgets to display list of languages in the sidebar or recent comments. <strong>It now (0.9.9.6) possible to have multiple lists - <a href="widgets.php">Reinstall</a> the widget</strong>
Author: dev.xiligroup.com - MS
Version: 1.2.1
Author URI: http://dev.xiligroup.com
*/

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

class xili_language_Widget {

	function xili_language_Widget() {
		load_plugin_textdomain('xili-language-widget',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
		add_action('widgets_init', array(&$this, 'init_widget'));
	}

	function init_widget() {
		if ( !function_exists('wp_register_sidebar_widget') || !function_exists('wp_register_widget_control') )
			return;
		$widget_ops = array('classname' => 'xili-language_Widget', 'description' => __( "List of available languages by xili-language plugin",'xili-language-widget') );
		wp_register_sidebar_widget('xili-language_Widget',__('List of languages','xili-language-widget'),array(&$this, 'widget'),$widget_ops);
	 	wp_register_widget_control('xili-language_Widget',__('List of languages','xili-language-widget'),array(&$this, 'widget_options'),$widget_ops);
		
	}

	function widget($args) {
		global $wpdb;

		$options = get_option('xili_language_widget_options');
		extract($args);

		echo $before_widget.$before_title.__($options['title'],THEME_TEXTDOMAIN).$after_title;
		if (function_exists('xili_language_list')) { 
			echo '<ul class="xililanguagelist">';
				xili_language_list();
			echo '</ul>';
			}
		echo $after_widget;
	}

	function widget_options() {
		if (isset($_POST['xili_language_widget_submit'])) {
			$options['title'] = strip_tags(stripslashes($_POST["xili_language_widget_options"]));
			update_option('xili_language_widget_options',$options);
		}
		$options=get_option('xili_language_widget_options');
		echo '<label for="xili_language_widget_options">'.__('Title').': <input id="xili_language_widget_options" name="xili_language_widget_options" type="text" value="'.attribute_escape($options['title']).'" /></label>';
		echo '<input type="hidden" id="xili_language_widget_submit" name="xili_language_widget_submit" value="1" />';
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
		$title = empty($options['title']) ? __('Recent Comments',THEME_TEXTDOMAIN) : apply_filters('widget_title', $options['title']);
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
				echo  '<li class="recentcomments">' . sprintf(__('%1$s on %2$s',THEME_TEXTDOMAIN), get_comment_author_link(), '<a href="' . clean_url( get_comment_link($comment->comment_ID) ) . '">' . get_the_title($comment->comment_post_ID) . '</a>') . '</li>';
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
			echo $before_widget.$before_title.__($options[$number]['title'],THEME_TEXTDOMAIN).$after_title;
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
		// other option and list html tags
		echo '<br /><label for="xili_l_widget_theoption-'.$number.'">'.__('Type','xili-language-widget').': <input id="xili_l_widget_theoption-'.$number.'" name="xili-language_Widgets['.$number.'][theoption]" type="text" value="'.$theoption.'" /></label>';
		
		echo '<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend>'.__('HTML tags of list','xili-language-widget').'</legend>';
		
		echo "<label for=\"xili_l_widget_beforelist-".$number."\">".__('before list','xili-language-widget').": <input id=\"xili_l_widget_beforelist-".$number."\" name=\"xili-language_Widgets[".$number."][beforelist]\" type=\"text\" value=\"".$beforelist."\" style=\"width:100%\" /></label><br />";
		echo '<label for="xili_l_widget_beforeline-'.$number.'">'.__('before line','xili-language-widget').': <input id="xili_l_widget_beforeline-'.$number.'" name="xili-language_Widgets['.$number.'][beforeline]" type="text" value="'.$beforeline.'" style="width:100%" /></label><br />';
		echo '<label for="xili_l_widget_afterline-'.$number.'">'.__('after line','xili-language-widget').': <input id="xili_l_widget_afterline-'.$number.'" name="xili-language_Widgets['.$number.'][afterline]" type="text" value="'.$afterline.'" style="width:100%" /></label><br />';
		echo '<label for="xili_l_widget_afterlist-'.$number.'">'.__('after list','xili-language-widget').': <input id="xili_l_widget_afterlist-'.$number.'" name="xili-language_Widgets['.$number.'][afterlist]" type="text" value="'.$afterlist.'" style="width:100%" /></label>';
		
		echo '</fieldset>';
		
		
		//
		echo '<input type="hidden" id="xili_l_widget_submit-'.$number.'" name="xili-language_Widgets['.$number.'][submit]" value="1" />';
		
	} // end options (control)
		
} // end class

//$xili_language_widget =& new xili_language_Widget ();

$xili_recent_comments_widget =& new xili_recent_comments_Widget ();

/* since 0.9.9.6 - multiple widgets available */

$xili_language_widgets =& new xili_language_Widgets ();


?>