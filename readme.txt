=== xili-language ===
Contributors: MS dev.xiligroup.com
Donate link: http://dev.xiligroup.com/
Tags: theme,post,plugin,posts,page,category,admin,multilingual, bilingual, taxonomy,dictionary,.mo file,.po file,localization,widget,language,international, i18n, l10n
Requires at least: 2.7
Tested up to: 2.9
Stable tag: 1.1.9

xili-language provides for a multilingual website an automatic selection of language (.mo) in theme according to the language of current post(s). 

== Description ==

**xili-language provides for a bilingual (or multilingual) website an automatic selection of language (.mo) in theme according to the language of current post(s).**

* xili-language plugin provides an automatic selection of language in theme according to the language of displayed post, series of posts, page or articles. 
* xili-language select *on the fly* the .mo files present in the theme's folder.  
* Themes with *localization* can be easily transformed for realtime multilingual sites. 
* xili-language is dedicated for theme's creator or webmaster with knowledges in CMS and WP and having (or not) tools to create .mo language files. Through API (hook), the plugin add automatic tools (or links or filters) for sidebar or top menus. Categories or Archives lists are translated also. 
* xili-language provides also series of functions which can be *hooked* in the functions.php file of the theme that you create i.e. for a multilingual cms like website.
* With **xili-tidy-tags** plugin [here](http://wordpress.org/extend/plugins/xili-tidy-tags/), it is now possible to display sub-selection (cloud) of **tags** according language. With [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/) plugin (beta), it is easier to create or update online, via admin UI, the files .mo of each language.

= 1.1.9 =
Intermediate release :
* Optional improving hooking ways to be compatible with l10n cache of Johan's plugin see line 2200. 
* Automatic detection of `THEME_TEXT_DOMAIN` constant and languages (.mo) sub-folder in theme's folder. More infos in Settings UI Special sidebox.
* Fixes title of `wp_get_archives` links with current permalinks. Possible that very special permalinks need to use hook named `xiliml_get_archives_link`.

= 1.1.8 =
This release adds new features dedicated to multilingual theme's creators and webmasters. Some new php functions, a folder to include functions shared by themes (not necessary to put functions in functions.php of the current theme); example with a shortcode to insert link inside content toward another post in a language. A post explaining these improvements can be found [here](http://dev.xiligroup.com/?cat=480&lang=en_us). **Since this release, xili-language is also tested with future wordpress 2.9-rare.**

= summary of main features improved in previous releases up to 1.1 =

* improve `xiliml_the_others_posts()` function and theme tag to be used in multilingual category loop and by option (array) to return an array of linked posts in other languages (useful for CMS webmasters) (array of lang  and id ) - the id is easily transformable in permalink with function `get_permalink()` when using this array.
* New ways to choose default language of front-page, 
* Fix unique id for category link hook [see expert's corner posts](http://dev.xiligroup.com/?p=1045)
* Fix unexpected like tags metabox added by WP 28 ( see [trac #10437](http://core.trac.wordpress.org/ticket/10437) ).
* add option 'typeone' in default languages list as one example for new multiple list widgets.
* link to modify linked posts in post edit dashboard UI
* New widget for recent comments that can subselect those for current language. (a function `xiliml_recent_comments()` is also available if you want to create a template tag with resulting objects array). Sub selection of archives for `wp_get_archives()` with &lang= [see installation notes](http://wordpress.org/extend/plugins/xili-language/installation/). If frontpage is a page, select correlated page according language [see Other notes](http://wordpress.org/extend/plugins/xili-language/other_notes/).
* sub selection of pages for `wp_list_pages()` with &lang=, some fixes (other posts, options when activate,...) and soon [examples](http://dev.xiligroup.com/xili-language/) (how to install selected rss...)
* Provides infos about **text direction** *ltr* ou *rtl* of languages (arabic, hebraic,...) of theme and of each post in loop see note [direction in installation](http://wordpress.org/extend/plugins/xili-language/installation/). Soon more infos and demos for designers if multilingual theme. Some fixes (thanks to Jacob about QuickEdit UI bug when post update).
* In dashboard : for new post, pre-set default language of author according his browser's language.
* W3C xml:lang - multilingual widget
* Counting only published posts and pages, Filter for widget's titles, in admin UI link to posts of one language, compatible with **xili-tidy-tags** plugin to display sub-selection (cloud) of tags according language. - A post [here](http://dev.xiligroup.com/xili-tidy-tags/ "why xili-tidy-tags ?").
* Data model include default and future sub-group and sorting of languages.
* hooks to define header metas or language attributes in html tag.
* Add optional detection of browser language, record undefined state of post,...
* Add a box in post admin edit UI to easily set link to similar posts in other languages (as formerly with custom fields). More docs in php. (see post about hooks in [dev.xiligroup.com](http://dev.xiligroup.com/?p=504) 
* Subfolder for langs in theme (see note in [installation](http://wordpress.org/extend/plugins/xili-language/installation/) - T ag for theme : `the_xili_local_time()` to display date...
* Based on class and oop - New settings UI according to new rules and style of WP 2.7 (meta_boxes, js) - *ONLY USE WITH Wordpress 2.7 and more* - HOOKS (Action, Filter) usable in your functions.php.

xili-dictionary is available [here](http://dev.xiligroup.com/xili-dictionary/ "xili-dictionary posts")

**Prerequisite**
Verify that your theme is international compatible (translatable terms like `_e('the term','mytheme')` and no displayed texts 'hardcoded' (example in theme 'default-i18n' of WP).

**More infos and docs**
… other texts and more descriptions [here](http://dev.xiligroup.com/xili-language/ "why xili-language ?")

**Compatibility**
xili-language is full compatible with the plugin [xilitheme-select](http://wordpress.org/extend/plugins/xilitheme-select/ "xilitheme-select") to be used with iPhone, iPod Touch or other mobiles.

xili-language is compatible with the plugin [xili-dictionary](http://dev.xiligroup.com/?p=312 "why xili-dictionary ?") which is able to deliver .mo files on the fly with the WP admin UI. xili-dictionary used a specific taxonomy without adding tables in WP database. **xili-dictionary plugin is in beta version**. 


More informations on the site [dev.xiligroup.com](http://dev.xiligroup.com/ "xiligroup plugins")

Check out the [screenshots](http://wordpress.org/extend/plugins/xili-language/screenshots/) to see it in action.

== Installation ==

1. Upload the folder containing `xili-language.php` and language files to the `/wp-content/plugins/` directory,
2. Verify that your theme is international compatible - translatable terms like `_e('the term','mytheme')` and no text hardcoded - 
3. define domain name of your theme - see note at end list below, 
4. Activate the plugin through the *'Plugins'* menu in WordPress,
5. Go to the dashboard settings tab - language - and adapt default values if necessary by adding your languages in the catalog. You can set the order (1,2,3...) of the series. (used in `language_attributes()` template_tag).
6. Modify each post by setting (checking) the language flag in xili-language sub-windows at the right of the editing window before publishing.

= SPECIAL NOTE FOR VERSION >= 1.1.9 =

With the cost of 50 lines more, plugin now detect automatically (if theme is good) `the theme_text_domain` and languages (.mo) sub-folder. It not mandatory to declare the two constats (but compatible with previous settings).
Only encapsule the `load_theme_textdomain()` like in that example for a theme named fusion:

`
if (!class_exists('xili_language')) { // class in not (!) present...
   load_theme_textdomain('fusion', get_template_directory() . '/lang');	
}
`

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

= text direction, since 0.9.9 =

Examples *for theme's designer* of functions to keep text **direction** of theme and of current post :

`
<?php 
	$themelangdir = ((class_exists('xili_language')) ? the_cur_lang_dir() : array ()) ; ?>
<div class="inside <?php echo $themelangdir['direction'] ?>">
...

`
example in loop :
`
<?php while (have_posts()) : the_post(); 
$langdir = ((class_exists('xili_language')) ? get_cur_post_lang_dir($post->ID) : array());
?>
      <div class="story <?php echo $langdir['direction'] ?>" >

`
minimal example in css :
`
.rtl {direction: rtl; text-align:right !important; font-size:130% !important;}
.ltr {direction: ltr; text-align:left !important;}

`
**Caution** : *multilingual theme with both ltr and rtl texts needs a rigourous structure of the css !*

= Archives selection =

Archives tags is a very complex template tag in his background and not very easy source hookable. So we decided to add few features : by adding query in vars of the function, it will be possible to display a monthly list of archives for a selected language - `wp_get_archives('..your.vars..&lang=fr_fr')` - or the current the theme language - `wp_get_archives('..your.vars..&lang=')` -. The displayed list of links will be translated and link restrited to display only archives of this language.


= Wordpress 2.9-rare =
Today, xili-language is 'compatible'.

== Frequently Asked Questions ==

= Where can I see websites using this plugin ? =

dev.xiligroup.com [here](http://dev.xiligroup.com/?p=187 "why xili-language ?")
and
www.xiliphone.mobi [here](http://www.xiliphone.mobi "a theme for mobile") also usable with mobile as iPhone.

And as you can see in [stats](http://wordpress.org/extend/plugins/xili-language/stats/), hundreds of sites use xili-language.

= For commercial websites, is it possible to buy support ? = 
Yes, use contact form [here](http://dev.xiligroup.com/?page_id=10).

= Support Forum or contact form ? =

Effectively, prefer [forum](http://forum.dev.xiligroup.com/) to obtain some support.

= Does xiligroup provide free themes ? =

No yet, but a lot of well designed themes like fusion or Arclite are very easily adaptable ( or the author incorporates automatic detection of xili-language as presented [here](http://dev.xiligroup.com/?p=427) ). And [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/) avoids to use poEdit to update .mo files with contents of terms of your database (categories, ...)


== Screenshots ==

1. an example of wp-content/themes folder
2. the admin settings UI
3. the language setting in post writting UI
4. coding extract with 'international' text in 'xiliphone' theme
5. xili-dictionary: Admin Tools UI - list of translated terms 
6. xili-dictionary: Admin Tools UI - functionalities windows
7. xili-language: Admin Tools UI - pre-set default language of author according his browser's language.
8. xili-tidy-tags: Admin Tools UI - see this compatible plugin to group tags according languages
9. xili-language widget : Admin widgets UI - since 0.9.9.6, "multiple" languages list widget
10. xili-language: Special Admin UI sidebox - infos about theme's content for multilingual settings.

== Changelog ==
= 1.1.8 - 1.1.9 = new features for theme's developers - see code lines - Fix title of `wp_get_archives` links with current permalinks.
= 1.1 = improve `xiliml_the_others_posts` function optionally to return an array of linked posts
= 1.0.2 = fix unexpected like tags metabox added by WP 28 in post edit UI - tracs #10437
= 1.0 = 
* New ways to define default language of front-page, 
* also compatible with new recent WP 2.8.
* Some fixes. Unique id for category link hook

= 0.9.9.6 = ready for new multiple widgets - fixed filter by in class
= 0.9.9.5 = php doc enhanced, link to modify linked posts
= 0.9.9.4 = Recent commments, 'Get_archives' translatable, some fixes or improvements...
= 0.9.9.3 = sub selection of pages for `wp_list_pages()` with `&lang=` , some fixes
= 0.9.9 = give dir of lang ltr or rtl, fixes for cat popup in post edit admin UI, fixes quick-edit update  (0.9.9.1 fixes internal `get_cur_language()` that now deliver array. 0.9.9.2 fixe class of metabox has-right-sidebar for 2.8, W3C)
= 0.9.8.3 = (dashboard) for new post, pre-set default language of author according his browser's language.
= 0.9.8.2 = better query (`get_terms_of_groups_lite`) - fixes W3C xml:lang
= 0.9.8.1 = Counting only published posts and pages, add filter for widget's titles, in admin UI link to posts of one language, compatible with xili-tidy-tags plugin.
= 0.9.8 = data model now include future sub-group and sorting of languages.
= 0.9.7.6 = Add new hooks to define header metas or language attributes in html tag...
= 0.9.7.5 = Add detection of browser language, fixes W3C errors, record undefined state of post,...
= 0.9.7.4 = Add a box in post admin edit UI to easily set link to similar posts in other languages.
= 0.9.7.1 = fixes, add subfolder for langs in theme - add new tag for theme : `the_xili_local_time()`
= 0.9.7 = OOP and CLASS coding - New settings UI according to new rules and style of WP 2.7 (`meta_boxes`, js).

= 0.9.6 = New settings UI according to new rules and style of WP 2.7 (meta_boxes, js)

= 0.9.4 = fixes and hooks from plugin to functions defined in functions.php
= 0.9.3 = third public release (beta) some fixes and display language in post/page lists
= 0.9.2 = second public release (beta) ready to include xili-dictionary plugin (tools)
= 0.9.0 = first public release (beta)


© 091109 - MS - dev.xiligroup.com

== More infos ==

= What happens if frontpage is a page ? =

Before publishing an 'how-to', since 0.9.9.4, the plugin incorporates now features that are formerly possible through the hookable functions of xili-language. The page frontpage must have her clones in each other languages. As for posts, if the user's browser is not in the default language, xili-language will display the page in the corresponding language if set by the editor. Be patient, a detailled post will arrive soon... [here](http://dev.xiligroup.com/?p=708#tab-1)

This first beta releases are for theme's creator or designer.

The plugin post is frequently updated [dev.xiligroup.com](http://dev.xiligroup.com/xili-language/ "Why xili-language ?")

See also the [Wordpress plugins forum](http://wordpress.org/tags/xili-language/) and [dev.xiligroup Forum](http://forum.dev.xiligroup.com/).

© 2008-2009 - MS - dev.xiligroup.com
