<?php
/*
Plugin Name: xili-language widget
Plugin URI: http://dev.xiligroup.com/xili-language/
Description: This plugin is a part of xili-language plugin. Add a optional widget to display list of languages in the sidebar.
Author: dev.xiligroup.com - MS
Version: 0.9.8.2
Author URI: http://dev.xiligroup.com
*/

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
$xili_language_widget= new xili_language_Widget ();
?>