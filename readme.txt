=== xili-language ===
Contributors: MS xiligroup
Donate link: http://dev.xiligroup.com/
Tags: theme,post,plugin,posts, page, category, admin,multilingual,taxonomy,dictionary, .mo file, .po file
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: 0.9.7.1

xili-language provides an automatic live selection of language (.mo files) in theme according to the language of current post(s). 

== Description ==

xili-language plugin provides an automatic selection of language in theme according to the language of one post. xili-language use *on the fly* the .mo files present in the theme's folder. It is dedicaced for theme's creator or webmaster with knowledges in CMS and WP and having tools to create .mo language files. The plugin add automatic tools (or links or filters) for sidebar or top menus. Categories lists are translated also. xili-language provides also series of functions which can be *hooked* in the functions.php file of the theme that you create i.e. for a multilingual cms like website.

**NEW: 0.9.7**
Based on class and oop - New settings UI according to new rules and style of WP 2.7 (meta_boxes, js) - *ONLY USE WITH Wordpress 2.7 and more* - WITH NEW HOOKS (Action, Filter) usable in your functions.php.
If you have used old method of hooks as in previous release, now you have the choice of name of your function but you must add `add_filter() or add_action()`. no change in database - beta release -

xili-dictionary alpha is available [here](http://dev.xiligroup.com/?p=312 "why xili-dictionary ?")

**Prerequisite**
Verify that your theme is international compatible (translatable terms like _e('the term','mytheme') and no displayed texts 'hardcoded' (example in theme 'default-i18n' of WP).

**More infos and docs**
… other texts and more descriptions [here](http://dev.xiligroup.com/?p=187 "why xili-language ?")

**Compatibility**
xili-language is full compatible with the plugin [xilitheme-select](http://wordpress.org/extend/plugins/xilitheme-select/ "xilitheme-select") to be used with iPhone, iPod Touch or other mobiles.

xili-language is compatible with the plugin [xili-dictionary](http://dev.xiligroup.com/?p=312 "why xili-dictionary ?") which is able to deliver .mo files on the fly with the WP admin UI. xili-dictionary used a specific taxonomy without adding tables in WP database. **xili-dictionary plugin is in beta version**. 


More informations on the site [dev.xiligroup.com](http://dev.xiligroup.com/ "xili-language plugin")

Check out the [screenshots](http://wordpress.org/extend/plugins/xili-language/screenshots/) to see it in action.

== Installation ==

1. Upload the folder containing `xili-language.php` and language files to the `/wp-content/plugins/` directory,
2. Verify that your theme is international compatible - translatable terms like _e('the term','mytheme') and no text hardcoded - 
3. define name of your theme - see note at end list below, 
4. Activate the plugin through the *'Plugins'* menu in WordPress,
5. Go to the dashboard settings tab - language - and adapt default values if necessary by adding your languages catalog.
6. Modify each post by setting (checking) the language flag in xili-language sub-windows at the end of the editing window before publishing.

= NOTE = 
In the functions php file of the theme : replace by commenting `load_theme_textdomain` line  `//load_theme_textdomain('mythemename'); ` by a *define* `define('THEME_TEXTDOMAIN','mythemename'); //second text must be used in theme texts with _e( , ) or __( , )` where 'mythemename' is `'kubrik'` in default international theme.

== Frequently Asked Questions ==

= Where can I see websites using this plugin ? =

dev.xiligroup.com [here](http://dev.xiligroup.com/?p=187 "why xili-language ?")
and
www.xiliphone.mobi [here](http://www.xiliphone.mobi "a theme for mobile") also usable with mobile as iPhone.

== Screenshots ==

1. an example of wp-content/themes folder
2. the admin settings UI
3. the language setting in post writting UI
4. coding extract with 'international' text in 'xiliphone' theme
5. xili-dictionary : Admin Tools UI - list of translated terms 
6. xili-dictionary : Admin Tools UI - functionalities windows

== More infos ==

This first beta releases are for theme's creator or designer.

The plugin post is frequently updated [dev.xiligroup.com](http://dev.xiligroup.com/?p=187 "Why xili-language ?")

See also the [Wordpress plugins forum](http://wordpress.org/tags/xili-language/).
= 0.9.7.1 = fixes see code script.
= 0.9.7 = OOP and CLASS coding - New settings UI according to new rules and style of WP 2.7 (meta_boxes, js).

= 0.9.6 = New settings UI according to new rules and style of WP 2.7 (meta_boxes, js)

= 0.9.4 = fixes and hooks from plugin to functions defined in functions.php
= 0.9.3 = third public release (beta) some fixes and display language in post/page lists
= 0.9.2 = second public release (beta) ready to include xili-dictionary plugin (tools)
= 0.9.0 = first public release (beta)


© 090228 - MS - dev.xiligroup.com
