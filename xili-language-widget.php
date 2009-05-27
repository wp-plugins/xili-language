<?php
/*
Plugin Name: xili-language widget
Plugin URI: http://dev.xiligroup.com/xili-language/
Description: This plugin is a part of xili-language plugin. Add a optional widgets to display list of languages in the sidebar or recent comments.
Author: dev.xiligroup.com - MS
Version: 0.9.9.5
Author URI: http://dev.xiligroup.com
*/

# 090518 - new widget for recent comments
# 090404 - new registering way.
# 090325 - better options record.

/*  thanks to http://blog.zen-dreams.com/ tutorial

	Copyright 2009  dev.xiligroup.com

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
$xili_language_widget =& new xili_language_Widget ();

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
$xili_recent_comments_widget =& new xili_recent_comments_Widget ();


?>