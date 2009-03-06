<?php
/*
Plugin Name: xili-language widget
Plugin URI: http://dev.xiligroup.com/xili-language/
Description: This plugin is a part of xili-language plugin. Add a optional widget to display list of languages in the sidebar.
Author: dev.xiligroup.com - MS
Version: 0.9.7.1
Author URI: http://dev.xiligroup.com
*/

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
		add_action('widgets_init', array(&$this, 'init_widget'));
	}

	function init_widget() {
		if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
			return;
		register_sidebar_widget(array('xili-language_Widget','widgets'),array(&$this, 'widget'));
		register_widget_control(array('xili-language_Widget', 'widgets'), array(&$this, 'widget_options'));
	}

	function widget($args) {
		global $wpdb;

		$WidgetTitle=get_option('xili_language_widget_options');
		extract($args);

		echo $before_widget.$before_title.__($WidgetTitle,THEME_TEXTDOMAIN).$after_title;
		if (function_exists('xili_language_list')) { 
			echo '<ul>';
				xili_language_list();
			echo '</ul>';
			}
		echo $after_widget;
	}

	function widget_options() {
		if ($_POST['xili_language_widget_options']) {
			$option=$_POST['xili_language_widget_options'];
			update_option('xili_language_widget_options',$option);
		}
		$option=get_option('xili_language_widget_options');
		echo '<label for="xili_language_widget_options">'.__('Title').': <input id="xili_language_widget_options" name="xili_language_widget_options" type="text" value="'.$option.'" /></label>';
	}
}
$xili_language_widget= new xili_language_Widget ();
?>