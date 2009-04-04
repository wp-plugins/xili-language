=== xili-language ===
Contributors: MS xiligroup
Donate link: http://dev.xiligroup.com/
Tags: theme,post,plugin,posts,page,category,admin,multilingual,taxonomy,dictionary,.mo file,.po file,localization,widget
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: 0.9.8.2

xili-language provides an automatic selection of language (.mo) in theme according to the language of current post(s) for a multilingual website.

== Description ==

xili-language plugin provides an automatic selection of language in theme according to the language of one post. xili-language use *on the fly* the .mo files present in the theme's folder.  Themes with *localization* can be easily transformed in multilingual site. It is dedicaced for theme's creator or webmaster with knowledges in CMS and WP and having tools to create .mo language files. The plugin add automatic tools (or links or filters) for sidebar or top menus. Categories lists are translated also. xili-language provides also series of functions which can be *hooked* in the functions.php file of the theme that you create i.e. for a multilingual cms like website.
With **xili-tidy-tags** plugin [here](http://wordpress.org/extend/plugins/xili-tidy-tags/), it is now possible to display sub-selection (cloud) of **tags** according language.

= NEW: 0.9.8.2 =
better query (`get_terms_of_groups_lite`) - fixes W3C xml:lang - multilingual widget
 
**NEW: 0.9.8.1**
Counting only published posts and pages, add filter for widget's titles, in admin UI link to posts of one language, compatible with **xili-tidy-tags** plugin to display sub-selection (cloud) of tags according language. - A post [here](http://dev.xiligroup.com/xili-tidy-tags/ "why xili-tidy-tags ?").
**NEW: 0.9.8**
Data model now include default and future sub-group and sorting of languages.
Add new hooks to define header metas or language attributes in html tag.
**NEW: 0.9.7.5** 
Add optional detection of browser language, fixes W3C errors, record undefined state of post,...
**NEW: 0.9.7.4**
Add a box in post admin edit UI to easily set link to similar posts in other languages (as formerly with custom fields). More docs in php. (see post about hooks in [dev.xiligroup.com](http://dev.xiligroup.com/?p=504) 
**NEW: 0.9.7.1**
fixes (see code script), add subfolder for langs in theme (see note in [installation](http://wordpress.org/extend/plugins/xili-language/installation/) - add new tag for theme : `the_xili_local_time()` to display date...
**NEW: 0.9.7**
Based on class and oop - New settings UI according to new rules and style of WP 2.7 (meta_boxes, js) - *ONLY USE WITH Wordpress 2.7 and more* - WITH NEW HOOKS (Action, Filter) usable in your functions.php.
If you have used old method of hooks as in previous release, now you have the choice of name of your function but you must add `add_filter() or add_action()`. no change in database - beta release -

xili-dictionary alpha is available [here](http://dev.xiligroup.com/?p=312 "why xili-dictionary ?")

**Prerequisite**
Verify that your theme is international compatible (translatable terms like `_e('the term','mytheme')` and no displayed texts 'hardcoded' (example in theme 'default-i18n' of WP).

**More infos and docs**
… other texts and more descriptions [here](http://dev.xiligroup.com/?p=187 "why xili-language ?")

**Compatibility**
xili-language is full compatible with the plugin [xilitheme-select](http://wordpress.org/extend/plugins/xilitheme-select/ "xilitheme-select") to be used with iPhone, iPod Touch or other mobiles.

xili-language is compatible with the plugin [xili-dictionary](http://dev.xiligroup.com/?p=312 "why xili-dictionary ?") which is able to deliver .mo files on the fly with the WP admin UI. xili-dictionary used a specific taxonomy without adding tables in WP database. **xili-dictionary plugin is in beta version**. 


More informations on the site [dev.xiligroup.com](http://dev.xiligroup.com/ "xili-language plugin")

Check out the [screenshots](http://wordpress.org/extend/plugins/xili-language/screenshots/) to see it in action.

== Installation ==

1. Upload the folder containing `xili-language.php` and language files to the `/wp-content/plugins/` directory,
2. Verify that your theme is international compatible - translatable terms like `_e('the term','mytheme')` and no text hardcoded - 
3. define domain name of your theme - see note at end list below, 
4. Activate the plugin through the *'Plugins'* menu in WordPress,
5. Go to the dashboard settings tab - language - and adapt default values if necessary by adding your languages in the catalog. You can set the order (1,2,3...) of the series. (used in `language_attributes()` template_tag).
6. Modify each post by setting (checking) the language flag in xili-language sub-windows at the right of the editing window before publishing.

= NOTE = 
In the functions php file of the theme : replace by commenting `load_theme_textdomain` line  `//load_theme_textdomain('mythemename'); ` by a *define* `define('THEME_TEXTDOMAIN','mythemename'); //second text must be used in theme texts with _e( , ) or __( , )` where 'mythemename' is `'kubrik'` in default international theme.

Another example with fusion theme that offer localization :

replace

`function init_language(){
	load_theme_textdomain('fusion', get_template_directory() . '/lang');
}`

by

`function init_language(){
	if (class_exists('xili_language')) {
		define('THEME_TEXTDOMAIN','fusion');
		define('THEME_LANGS_FOLDER','/lang');
	} else {
	   load_theme_textdomain('fusion', get_template_directory() . '/lang');	
	}
	
}`

see the [recent post](http://dev.xiligroup.com/?p=427 "Transform a theme with localization").

= Browser detection =
To change the language of the frontpage according to the language of the visitor's browser, check the button in right small box in settings.

= Wordpress 2.8 beta =
Today, xili-language is 'compatible' with 'nightly' of next WP release.

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

= 0.9.8.2 = better query (`get_terms_of_groups_lite`) - fixes W3C xml:lang
= 0.9.8.1 = Counting only published posts and pages, add filter for widget's titles, in admin UI link to posts of one language, compatible with xili-tidy-tags plugin.
= 0.9.8 = data model now include future sub-group and sorting of languages.
= 0.9.7.6 = Add new hooks to define header metas or language attributes in html tag...
= 0.9.7.5 = Add detection of browser language, fixes W3C errors, record undefined state of post,...
= 0.9.7.4 = Add a box in post admin edit UI to easily set link to similar posts in other languages.
= 0.9.7.1 = fixes, add subfolder for langs in theme - add new tag for theme : `the_xili_local_time()`
= 0.9.7 = OOP and CLASS coding - New settings UI according to new rules and style of WP 2.7 (meta_boxes, js).

= 0.9.6 = New settings UI according to new rules and style of WP 2.7 (meta_boxes, js)

= 0.9.4 = fixes and hooks from plugin to functions defined in functions.php
= 0.9.3 = third public release (beta) some fixes and display language in post/page lists
= 0.9.2 = second public release (beta) ready to include xili-dictionary plugin (tools)
= 0.9.0 = first public release (beta)


© 090404 - MS - dev.xiligroup.com
