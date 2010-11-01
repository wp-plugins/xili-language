<?php
/*
Plugin Name: xili-language widget
Plugin URI: http://dev.xiligroup.com/xili-language/
Description: This plugin is a part of xili-language plugin. Add optional widgets to display list of languages in the sidebar or recent comments and recents posts selected according current language. xili-language plugin must be activated!
Author: dev.xiligroup.com - MS
Version: 1.8.3
Author URI: http://dev.xiligroup.com
License: GPLv2
*/

# 101101 - 1.8.3 - languages list and recent comments rewritten as extended class of WP_Widget
#
# 101026 - 1.8.1 - fixes : add a missing ending tag in options list in widget xili-language list
# 100713 - 1.7.0 - add a querytag to be compatible with new mechanism (join+where) of xili-language
# 100602 - 1.6.0 - add list of options in widget - hook possible if hook in languages_list
# 100416 - change theme_domain constant for multisite (WP3)
# 100219 - add new widget recent posts if WP >= 2.8.0
# 090606 - xili-language list widget is now multiple and more features

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

/***** new class since 1.8.3 *****/

/**
 * Recent_Comments widget class
 *
 * @since 1.8.3
 */
class xili_WP_Widget_Recent_Comments extends WP_Widget {
	
	function xili_WP_Widget_Recent_Comments() {
		$widget_ops = array('classname' => 'xili_widget_recent_comments', 'description' => __( 'The most recent comments by xili-language' ) );
		$this->WP_Widget('xili-recent-comments', __('Recent Comments list'), $widget_ops);
		$this->alt_option_name = 'xili_widget_recent_comments';

		if ( is_active_widget(false, false, $this->id_base) )
			add_action( 'wp_head', array(&$this, 'recent_comments_style') );

		add_action( 'comment_post', array(&$this, 'flush_widget_cache') );
		add_action( 'transition_comment_status', array(&$this, 'flush_widget_cache') );
	}
	
	function recent_comments_style() { ?>
	<style type="text/css">.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>
<?php
	}

	function flush_widget_cache() {
		wp_cache_delete('xili_widget_recent_comments', 'widget');
	}
	
	function widget( $args, $instance ) {
		global $comments, $comment;

		$cache = wp_cache_get('xili_widget_recent_comments', 'widget');

		if ( ! is_array( $cache ) )
			$cache = array();

		if ( isset( $cache[$args['widget_id']] ) ) {
			echo $cache[$args['widget_id']];
			return;
		}

 		extract($args, EXTR_SKIP);
 		$output = '';
 		$title = apply_filters('widget_title', empty($instance['title']) ? __('Recent Comments') : $instance['title']);

		if ( ! $number = (int) $instance['number'] )
 			$number = 5;
 		else if ( $number < 1 )
 			$number = 1;
		/* if xili-language plugin is activated */
		if (function_exists('xiliml_recent_comments')) {
			$comments = xiliml_recent_comments($number);
		} else {
			$comments = get_comments( array( 'number' => $number, 'status' => 'approve' ) );
		}
		$output .= $before_widget;
		if ( $title )
			$output .= $before_title . $title . $after_title;

		$output .= '<ul id="recentcomments">';
		if ( $comments ) {
			foreach ( (array) $comments as $comment) {
				$output .=  '<li class="recentcomments">' . /* translators: comments widget: 1: comment author, 2: post link */ sprintf(_x('%1$s on %2$s', the_theme_domain()), get_comment_author_link(), '<a href="' . esc_url( get_comment_link($comment->comment_ID) ) . '">' . get_the_title($comment->comment_post_ID) . '</a>') . '</li>';
			}
 		}
		$output .= '</ul>';
		$output .= $after_widget;

		echo $output;
		$cache[$args['widget_id']] = $output;
		wp_cache_set('xili_widget_recent_comments', $cache, 'widget');
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['xili_widget_recent_comments']) )
			delete_option('xili_widget_recent_comments');

		return $instance;
	}

	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = isset($instance['number']) ? absint($instance['number']) : 5;
		
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of comments to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
		<p><small>© xili-language</small></p>
<?php
	}

}

/**
 * xili-language list widget class
 *
 * @since 1.8.3
 * rewritten
 */
class xili_language_Widgets extends WP_Widget {
	
	function xili_language_Widgets() {
		load_plugin_textdomain('xili-language-widget',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
		$widget_ops = array('classname' => 'xili-language_Widgets', 'description' => __( "List of available languages by xili-language plugin",'xili-language-widget' ) );
		$this->WP_Widget('xili_language_widgets', __("List of languages", 'xili-language-widget'), $widget_ops);
		$this->alt_option_name = 'xili_language_widgets_options';
	}
	
	function widget( $args, $instance ) {
		
		extract($args, EXTR_SKIP);
		
 		$output = '';
 		$output .= $before_widget;
 		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( $title )
			$output .= $before_title . $title . $after_title;
 		
		if (function_exists('xili_language_list')) { 
			$output .= $instance['beforelist'];
			$output .= xili_language_list($instance['beforeline'], $instance['afterline'], $instance['theoption'], false);
			$output .= $instance['afterlist'];
		}
		$output .= $after_widget;
		echo $output;
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['beforelist'] = stripslashes($new_instance['beforelist']);
		$instance['beforeline'] = stripslashes($new_instance['beforeline']);
		$instance['afterline'] = stripslashes($new_instance['afterline']);
		$instance['afterlist'] = stripslashes($new_instance['afterlist']);
		$instance['theoption'] = strip_tags(stripslashes($new_instance['theoption']));
		
		return $instance;
	}
	
	function form( $instance ) {
		global $xili_language;
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$beforelist = isset($instance['beforelist']) ? htmlentities(stripslashes($instance['beforelist'])) : "<ul class='xililanguagelist'>";
		$beforeline =  isset($instance['beforeline']) ? htmlentities(stripslashes($instance['beforeline'])) : '<li>';
		$afterline =  isset($instance['afterline']) ? htmlentities(stripslashes($instance['afterline'])): '</li>';
		$afterlist =  isset($instance['afterlist']) ? htmlentities(stripslashes($instance['afterlist'])) : '</ul>';
		$theoption =  isset($instance['theoption']) ? stripslashes($instance['theoption']) : '' ;
		
	?>
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
	<?php
	
	if ( class_exists('xili_language') ) {
		if ( $xili_language->this_has_filter('xili_language_list')) // one external action
			$xili_language->langs_list_options = array();
		if (has_filter('xili_language_list_options')) {	// is list of options described
			do_action('xili_language_list_options', $theoption); // update the list of external action
		}
	}
	if ( class_exists('xili_language') && isset($xili_language->langs_list_options) && $xili_language->langs_list_options != array()) {
		echo '<br /><label for="'.$this->get_field_id('theoption').'">'.__('Type','xili-language-widget').':';
		echo '<select name="'.$this->get_field_name('theoption').'" id="'.$this->get_field_id('theoption').'">';
		foreach ($xili_language->langs_list_options as $typeoption) {
			$selectedoption = ($theoption == $typeoption[0]) ? 'selected = "selected"':'';
			echo '<option value="'.$typeoption[0].'" '.$selectedoption.' >'.$typeoption[1].'</option>';
		}
		echo '</select></label>';
	} else {
			echo '<br /><label for="'.$this->get_field_id('theoption').'">'.__('Type','xili-language-widget').': <input id="'.$this->get_field_id('theoption').'" name="'.$this->get_field_name('theoption').'" type="text" value="'.$theoption.'" /></label>';	
	}
	
	?>
	<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php _e('HTML tags of list','xili-language-widget'); ?></legend>
	<label for="<?php echo $this->get_field_id('beforelist'); ?>"><?php _e('before list','xili-language-widget'); ?></label>:
	<input class="widefat" id="<?php echo $this->get_field_id('beforelist'); ?>" name="<?php echo $this->get_field_name('beforelist'); ?>" type="text" value="<?php echo $beforelist; ?>" />
	
	<label for="<?php echo $this->get_field_id('beforeline'); ?>"><?php _e('before line','xili-language-widget'); ?></label>:
	<input class="widefat" id="<?php echo $this->get_field_id('beforeline'); ?>" name="<?php echo $this->get_field_name('beforeline'); ?>" type="text" value="<?php echo $beforeline; ?>" />
	
	<label for="<?php echo $this->get_field_id('afterline'); ?>"><?php _e('after line','xili-language-widget'); ?></label>:
	<input class="widefat" id="<?php echo $this->get_field_id('afterline'); ?>" name="<?php echo $this->get_field_name('afterline'); ?>" type="text" value="<?php echo $afterline; ?>" />
	
	<label for="<?php echo $this->get_field_id('afterlist'); ?>"><?php _e('after list','xili-language-widget'); ?></label>:
	<input class="widefat" id="<?php echo $this->get_field_id('afterlist'); ?>" name="<?php echo $this->get_field_name('afterlist'); ?>" type="text" value="<?php echo $afterlist; ?>" />
	</fieldset>
	<p><small>© xili-language</small></p>
	<?php
	}
}

/**
 * Widgets registration after classes rewritten
 */
if ( $wp_version >= '2.8.0') {
	function add_new_widgets() {
 		register_widget('xili_Widget_Recent_Posts'); // since 1.3.2
 		register_widget('xili_WP_Widget_Recent_Comments'); // since 1.8.3 
 		register_widget('xili_language_Widgets'); // since 1.8.3 
	}
	add_action('widgets_init','add_new_widgets');
}
?>