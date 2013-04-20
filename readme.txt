=== xili-language ===
Contributors: michelwppi, MS dev.xiligroup.com
Donate link: http://dev.xiligroup.com/
Tags: theme,post,plugin,posts,page,category,admin,multilingual, bilingual, taxonomy,dictionary,.mo file,.po file,localization, widget, language, international, i18n, l10n, wpmu, multisite, blogroll, japanese, khmer, rtl, translation-ready, bbpress
Requires at least: 3.4.2
Tested up to: 3.6
Stable tag: 2.8.7
License: GPLv2
xili-language lets you create and manage multilingual WP site in several languages with yours or most famous localizable themes. Ready for CMS design.

== Description ==

**xili-language provides for a bilingual (or multilingual) website an automatic selection of language (.mo) in theme according to the language of current post(s) or page. Theme's behaviour can be fully personalized through settings, hooks and api. Ready for CMS developers and designers.**

* xili-language plugin provides an automatic selection of language in theme according to the language of displayed post, series of posts, page or articles. *If the post is in gaelic, the texts of the theme will be in gaelic if the author checks the post as gaelic and if the theme contains the right .mo file for this target language.*

* A new interface with a list of titles (and links) to help you write articles and their translations.

* To help authoring, current user can choose language of his dashboard.

* xili-language select *on the fly* the multilingual .mo files present in the theme's folder.

* xili-language uses a custom taxonomy to specify language of post, page and custom post.

* xili-language plugin works on Wordpress installation in mono (standalone) or multisite (network) mode.

= Prequisite =
* A project of a website with articles in different languages.
* **A localizable theme** : Every themes with **localization** (or translation-ready like twentyten) can be easily used (and improved) for realtime multilingual sites.
* A tool to translate .po files of the theme and built .mo files (poEdit or better xili-dictionary - *see below* ). 
* see [this page in wiki.xiligroup.org](http://wiki.xiligroup.org/index.php/Xili-language:_Getting_started,_prerequisites).

= What to prepare before and during installation before activating =
* verify that your theme is translation-ready. Collect .po files of theme for target languages.
* if rtl languages are used, verify that theme contains rtl.css file.

= Links and documentation to read before activating =
* Check out the [screenshots](http://wordpress.org/extend/plugins/xili-language/screenshots/) to see it in action and other tabs [here](http://wordpress.org/extend/plugins/xili-language/other_notes/).
* [xili wiki](http://wiki.xiligroup.org/index.php/Xili-language_v2.6:_what%27s_new_with_xili-dictionary_v2.1),
* [multilingual](http://multilingual.wpmu.xilione.com) theme and how to,
* [news and history](http://dev.xiligroup.com/xili-language/),
* [forum](http://dev.xiligroup.com/?post_type=forum) to read experience of users and obtain some support,
* **For webmaster**:  Before to be moved in wiki, [table](http://dev.xiligroup.com/?p=1432) summarizes all the technical features (widgets, template tags, functions and hooks) of this powerful plugin for personalized CMS created by webmaster,
* **For german speaking webmaster** some [documentations and posts](http://2012.wpmu.xilione.com/?lang=de_de) are written by YogieAnamCara of [sensorgrafie](http://www.sensorgrafie.de)

* and finally the source code of the plugin itself if you read php!

= Themes provided as examples =
* Since WordPress 3.0, the default theme named **twentyten** can be used without php coding for a multilingual site [as shown here twentyten-xili](http://multilingual.wpmu.xilione.com).
* Another child theme examples: **twentyeleven** [twentyeleven-xili](http://2011.wpmu.xilione.com/), **twentytwelve** [twentytwelve-xili](http://2012.wpmu.xilione.com/), **twentythirteen** [twentythirteen-xili](http://2013.extend.xiligroup.org/).


**TRILOGY FOR MULTILINGUAL CMS WEBSITE** including [xili-language plugin](http://wordpress.org/extend/plugins/xili-language/)

Please verify that you have installed the latest versions of:

* [xili-dictionary plugin](http://wordpress.org/extend/plugins/xili-dictionary/): With xili-dictionary, it is easier to create or update online, via admin/dashboard UI, the files .mo of each language.
* [xili-tidy-tags plugin](http://wordpress.org/extend/plugins/xili-tidy-tags/): With xili-tidy-tags, it is now possible to display sub-selection (cloud) of **tags** according language and semantic trans-language group (trademark,…). 

= That this plugin does not =
*With around 8000 php lines, xili-language is not everything…*

* xili-language plugin **does not create additional tables in the database**. xili-language simply makes proper use of the taxonomy tables and postmeta table offered by WordPress to define language and link items between them. Because xili-language plugin does not modify deeply the post edit UI, it is possible to use **iPhone / iPod Touch** Wordpress app to prepare and draft the post.

* xili-language plugin does not replace the author or the editor. No automatic translation. Content strategist is the master of the languages, the contents and the navigation inside the website. With xili-dictionary, webmaster can translate the theme's items. For design, the creator is free to choose text or graphic. xili-language does not provide flags (or few as example in child-theme example like [twentytwelve-xili](http://2012.wpmu.xilione.com) )!

= Newbie, WP user, Developer,… =

* **Newbie:** originally built for webmaster and developer, the plugin trilogy progress since 4 years to be more and more plug and play for newbies who can read and spend a little time mainly for translation.

* xili-language is also dedicated for theme's creator or webmaster with knowledges in CMS and WP and having (or not) tools to create .mo language files. Through API (hook), the plugin add automatic tools (or links or filters) for sidebar or top menus. Categories or Archives lists are translated also. 
* xili-language provides also series of functions which can be *hooked* in the functions.php file of the theme that you create i.e. for a cms like multilingual website.

= Licence, donation, services, "as is", ... =
Contrary to popular belief, *GPL doesn't say that everything must be zero-cost*, just that when you receive the software (plugin or theme) that it not restrict your freedoms in how you use it. *Free open source plugin does not mean free services*

* Texts of licence: [GPLv2](http://www.gnu.org/licenses/gpl-2.0.html)
* Donation link via paypal in sidebar of [dev.xiligroup site](http://dev.xiligroup.com/)
* Services : As authors of plugin, dev.xiligroup team is able to provide services (consulting, training, support) with affordable prices for WP multilingual contexts in corporate or commercial websites. 
* **as is** : see no warranty chapter in license GPLv2.

= Version 2.8.7 =
* Last Updated 2013-04-16
* fixes lang_perma if search, fixes IE matching(z8po), add option 'Posts selected in' for language_list title link
* tests with WP 3.6 beta

= Version 2.8.6 =

* Fixes security issues
* Improves searchform
* Continues tests with WP 3.6 alpha and Twenty Thirteen theme

= Version 2.8.5 =
* more option in automatic nav menu insertion
* cleaning sources after test phases (2.8.4.x)
* pre-tests with WP 3.6 alpha and Twenty Thirteen theme

= Version 2.8.4, 2.8.4.1, 2.8.4.2, 2.8.4.3 =

* plugin domain switching improved, cleaning __construct source, fixes
* Fixes clone of medias both on WP 3.4 and WP 3.5
* Add page_for_posts features when static page as front page [see wiki post](http://wiki.xiligroup.org/index.php/Xili-language:_page_for_posts)
* Tests on WP 3.5.1
* fixes (support settings issue)

= Version 2.8.3 =
* Adaptation for new .mo behavior of WP 3.5 - multilingual features in media library maintained as specified before with taxonomy language.
* Pointer only one time
* Tests on WP 3.5: insertion in empty nav menu - improved admin UI - ready for alias and language permalinks (with xili-language premium services)

= News from 1.8.0 to 2.8.2 =

* see [tab and chapters in changelog](http://wordpress.org/extend/plugins/xili-language/changelog/)

= Roadmap =

* Improved documentation for template tags and functions - [started here in xili wiki](http://wiki.xiligroup.org).
* Delivery of a *premium* services kit (with powerful features and attractive fees) packaged with professional training and support.


== Installation ==

READ CAREFULLY ALL THE README AND PREREQUISITES

1. Upload the folder containing `xili-language.php` and language files to the `/wp-content/plugins/` directory,
2. Verify that your theme is international compatible - translatable terms like `_e('the term','mytheme')` and no text hardcoded - and contains .mo and .po files for each target language - (application poEdit and/or plugin [xili-dictionary](http://dev.xiligroup.com/xili-dictionary/) can be used)
3. verify that a domain name is defined in your theme - see note at end list below, 
4. Activate the plugin through the *'Plugins'* menu in WordPress,
5. Go to the dashboard settings tab - languages - and adapt default values if necessary by adding your languages in the catalog. You can set the order (1,2,3...) of the series. (used in `language_attributes()` template_tag).
6. Modify each post by setting (checking) the language in xili-language box at the right of the post editing window before publishing.
7. Others settings and parts (Browser detection, widgets, shortcode, template tags) see below… and examples.

= Additional infos =

1. Before using your own theme, to understand how xili-language works, install the child theme of twentyten shown in this commented [demo site](http://multilingual.wpmu.xilione.com).
2. Child of TwentyTen and Child of Twenty Eleven themes include a navigation menu - [downloadable here](http://multilingual.wpmu.xilione.com/download/) -. In xili-language settings it is possible to insert automatically languages menu in the menu previously set by you.
3. If you are webmaster and want to add lot of personalizations in your theme, read source and visit [expert's corner](http://dev.xiligroup.com/?cat=480&lang=en_us).

= Browser detection for visitors or authors =
To change the language of the frontpage according to the language of the visitor's browser, check the popup in right small box in settings.
To change the default language of a new post according to the language of the author's browser, check the popup in right small box in settings.

= xili-language and widgets =

Three widgets are created to enrich sidebar : list of languages, recent posts and recent comments with sub-selection according current language.

= xili-language and shortcode =

Shortcode to add a link to other language inside content of a post like
`[linkpost lang="fr_FR"]`
is available in a [library](http://dev.xiligroup.com/?p=1111) in complement to xili-language.

= xili-language and template tags =

* xili-language "*translates*" template tags and permits some variations for webmasters:

The most current template tags don't need special work: `wp_list_categories()`, `wp_title()`,...

`wp_get_archives` 
Possible that very special permalinks need to use hook named `xiliml_get_archives_link`. -
Sub selection of archives for `wp_get_archives()` with &lang= (see § below)

`wp_list_pages()` with optional query tag &lang=

* xili-language "*provides*" new template tags (or widgets) to solve issues from WP core or add new features for CMS:

`xili_language_list()` - outside loop (sidebar) display the languages of the site (used also by widget)

`xiliml_the_category()` - replace the_category() template tag of WP Core


improved `xiliml_the_others_posts()` function and theme tag to be used in multilingual category loop and by option (array) to return an array of linked posts in other languages (useful for CMS webmasters) (array of lang  and id ) - the id is easily transformable in permalink with function `get_permalink()` when using this array.

* widget for recent comments that can subselect those for current language. (a function `xiliml_recent_comments()` is also available if you want to create a template tag with resulting objects array).


= Archives selection =

Archives tags is a very complex template tag in his background and not very easy source hookable. So we decided to add few features : by adding query in vars of the function, it will be possible to display a monthly list of archives for a selected language - `wp_get_archives('..your.vars..&lang=fr_fr')` - or the current the theme language - `wp_get_archives('..your.vars..&lang=')` -. The displayed list of links will be translated and link restrited to display only archives of this language.

= text direction =

Examples *for theme's designer* of functions to keep text **direction** of theme and of current post :
(see child theme of twentyten example [in](http://multilingual.wpmu.xilione.com/300/episode-2-creation-of-a-multilingual-website-with-xili-language/))
or twentyeleven child theme using rtl.css.

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
**Caution** : *multilingual theme with both ltr and rtl texts needs a rigourous structure of the css ! See note in version 2.4.*

= XILI-LANGUAGE VERSION >=1.8.0 is not compatible with WP less than 3.0 =

= SPECIAL NOTE FOR XILI-LANGUAGE VERSION >= 1.5.0 and WP 3.0 =

*Nothing to do in functions.php as in former version of WP and xili-language* : only verify that the theme is localizable (translation-ready) and functions.php contains a function `load_theme_textdomain();` and that the theme's folder contains .mo files (in root or a language sub-folder) for each languages of your website. As example "twentyten" default WP theme is compatible with help of a child theme "twentyten-xili" available [here in action and downloadable](http://multilingual.wpmu.xilione.com) .

Plugin is backward compatible for theme of monosite. But if you upgrade xili-language, it is better to restore your theme with default `load_theme_textdomain();`. Delete lines concerned by obsolete constants THEME_TEXTDOMAIN and languages sub-folder THEME_LANGS_FOLDER.

= NOTE FOR THEMES DESIGNER =
If your theme is compatible both for newest (>3.0-apha) and older versions, add some conditional lines.

= NOTE FOR COMMENT FORM IN WP 3.0 =
Today with WP3.0, comments form language is based on default language of admin UI and not on theme's language. xili-language solves this features. So you need to add this terms msgid in the .po of your theme for translation used by xili-language. In latest version, xili-dictionary adds this option to avoid tedious copy and paste !


== Frequently Asked Questions ==

= What about WPMU and the trilogy ? =
[xili-language](http://wordpress.org/extend/plugins/xili-language/), [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/), [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/)
Since WP 3.0-alpha, if multisite is activated, the trilogy is now compatible and will include progressively some improvements dedicaded especially for WPMU context. Future specific docs will be available for registered webmasters.

= How to see post or page ID in dashbord ? =

Now since 1.6.0, IDs are displayed in sidebox UI of post edit window.

= Where can I see websites using this plugin ? =

dev.xiligroup.com [here](http://dev.xiligroup.com/?p=187 "why xili-language ?")
and
www.xiliphone.mobi [here](http://www.xiliphone.mobi "a theme for mobile") also usable with mobile as iPhone.

As multisite demo, visit the working site made with twentyone default theme: [as shown here](http://multilingual.wpmu.xilione.com). This website contains articles to prepare a multilingual cms site. 

And as you can see in [stats](http://wordpress.org/extend/plugins/xili-language/stats/), hundreds of sites use xili-language.

= For commercial websites, is it possible to buy support ? = 
Yes, use contact form [here](http://dev.xiligroup.com/?page_id=10).

= What is gold functions ?, is it possible to buy them ? =
Some gold functions (in xilidev-libraries) are explained [here](http://dev.xiligroup.com/?p=1111) and some belong to pro services for commercial websites.
Yes, use contact form [here](http://dev.xiligroup.com/?page_id=10).

= Support Forum or contact form ? =

Effectively, prefer [forum](http://dev.xiligroup.com/?post_type=forum) to obtain some support.

= Does xiligroup provide free themes ? =

No yet, but a lot of well designed themes like fusion or Arclite are very easily adaptable. 
Only, a child theme of **twentyten** is shipped here for [demo](http://multilingual.wpmu.xilione.com/300/episode-2-creation-of-a-multilingual-website-with-xili-language/).

= Is poEdit mandatory to edit .po and to build .mo file ? =
[xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/) avoids to use poEdit to update .mo files with contents of terms of your database (categories, ...)

= What about plugin admin settings UI localization ? =

It is simple, if you have translated the settings UI of plugin in your mother language, you send us a message through the contact form that contains the link to your site where you have add the .po and .mo files. Don't forget to fill the header of the .po file with your name and email. If all is ok, the files will be added to the xili-language wp plugins repository. Because I am not able to verify the content, you remain responsible of your translation.

= What about languages with 2 letters WPLANG as in wp-config.php like japanese ? =

Before version 1.8.8, it was necessary to change wp-config.php like japanese and set **WPLANG** to ISO : from *ja* to **ja_JA**. Now with 1.8.8, the trilogy is updated, it is not necessary. So very easy for a japanese to transform his site in a multilingual site by adding other language files. For other mother languages, just add the japanese (ja.mo) inside languages sub-folder ot the theme [kept here](http://ja.wordpress.org/).

= What about bookmarks and sub-selection according current language of displayed loop ? =

Since version 1.8.5, xili-language has inside his class filters and actions to permit displaying sub-selection of links and bookmarks.

* case of default widget-links : in xili-language settings, only check link categories where sub-selection is wanted.
* case of template tags : `<?php wp_list_bookmarks( array( 'lang'=>the_curlang() ) ; ?>` here this new arg named *lang* is set to the current language.
 
Visit links list editor settings page and set for each link the language.

= What happens if frontpage is a page ? =

The page as frontpage must have its clones in each other languages. Like for posts, if the user's browser is not in the default language, xili-language will display the page in the corresponding language if set by the editor. [home page of website dev.xiligroup.com](http://dev.xiligroup.com/) uses this feature.

= How to enlarge language list of the dashboard ? =

Since version 2.8, the current user (author) can choose language of his dashboard. To enlarge list of available languages, you must upload the xx_YY.mo files of other localized WP installs in the folder wp-content/languages/. For example, here a list for a trilingual website (english, french, german : fr_FR.mo, de_DE.mo.
See this [codex page](http://codex.wordpress.org/WordPress_in_Your_Language) *about WP in your language* to find kit containing wanted files.

== Screenshots ==
Here a selection of main screenshots.

1. an example of wp-content/themes folder and his languages sub-folder.
2. Source coding extract with 'international' text in 'xiliphone' theme.
3. The plugin settings UI - first tab: the languages list and edit form to add or edit.
4. The plugin settings UI - second tab: Settings of front-end and languages behaviour.
5. The plugin settings UI - third tab: Settings for navigation menus and experts.
6. The language dashboard in post writting UI under the content textarea. For more infos and how to, visit the [wiki website](http://wiki.xiligroup.org/index.php/Xili-language_v2.5#Getting_started_:_linking_posts_with_different_language).
7. List of posts with language column and infos about linked posts. For more infos and how to, visit the [wiki website](http://wiki.xiligroup.org/index.php/Xili-language_v2.5).
8. Dashboard: Posts edit list with language sub-selection, quick edit and bulk edit available.
9. xili-dictionary v.2
10. xili-tidy-tags: Admin Tools UI - see this compatible plugin to group tags according languages.
11. xili-language widget: Admin widgets UI - since 0.9.9.6, "multiple" languages list widget.
12. xili-language: Widget to display recent posts in choosen target language.
13. Blogroll and bookmarks taxonomies and language sub-selection.
14. Since 2.2.0, new xili-language trilogy menu in admin bar menu to group main settings for multilingual website.

* See illustrated presentations in the [wiki](http://wiki.xiligroup.org/).

== Changelog ==

= Version 2.8.7 (2013-04-16) =
* fixes lang_perma if search, 
* fixes IE matching(z8po), add option 'Posts selected in' for language_list title link (used by XD)

= Version 2.8.6 (2013-03-22) =

* Fixes security issues
* Improves searchform
* Continues tests with WP 3.6 alpha and Twenty Thirteen theme

= 2.8.5 (2013-03-13) = 
* more option in automatic nav menu insertion
* cleaning sources after test phases (2.8.4.x)
* pre-tests with WP 3.6 alpha and Twenty Thirteen theme

= 2.8.4.3 (2013-03-03) =
* testing phase before releasing 2.8.5 as current
* plugin domain switching improved, clean __construct source, fixes
= 2.8.4.2 (2013-02-16) =
* media cloning again available in WP 3.5.x, add infos about attached
= 2.8.4.1 (2013-02-03) =
* add page_for_posts features
* fixes get_terms cache at init
* fixes support settings issue
* improved admin UI

= 2.8.3.1 (2013-01-06) = 
* Maintenance release, fixes class exists in bbp addon

= Version 2.8.0, 2.8.1 (2012-09-21) =
* Improvements for bbPress >= 2.1 with multilingual forums. See this [post in wiki](http://wiki.xiligroup.org/index.php/Xili-language:_multilingual_forums_with_bbPress_add-on).
* Dashboard language choosen by each user (if WP .mo locale files are available), 
* Improved preset list of languages
* Fixes

= Version 2.7.0, 2.7.1 (2012-08-20) =
* multilingual features in media library, see [wiki xili about media attached texts](http://wiki.xiligroup.org/index.php/Xili-language:_Media_and_language_of_title_and_caption_or_description)
* fixes - for best results, update xili_dictionary to 2.1.3 and xili_tidy_tags to 1.8.
= Version 2.6.0 to 2.6.3 (2012-07-08)=
* Able to detect and use local files (local-xx_XX.mo) containing translations of local website datas created by xili-dictionary >= 2.1.
* More infos in categories list about translations. Links with xili-dictionary.
* Incorporate news pointer widget to hightlight infos (some need to be dismissed two times !)
* MAJOR UPDATE: See short presentation of new in the [wiki xili](http://wiki.xiligroup.org/index.php/Xili-language_v2.6:_what%27s_new_with_xili-dictionary_v2.1))

= Version 2.5.0 (2012-04-18) =
* A new metabox now contains the list of (now and future) linked translated posts. The new design benefits from the gains of the concept of xili-dictionary 2.0.  
* This box replaces the previous two metaboxes designed at the origin of xili-language. For more info, visit the [wiki website](http://wiki.xiligroup.org).

= Versions 2.4.0, 2.4.4 (2012-03-29) =
* Settings pages are now organized in 4 tabs with more online infos.
* automatic rtl.css adding if present in theme like twentyten or twentyeleven - So supports better arabic, persian, hebraic and other *right to left* languages.
* new way and options to manage dates translation using class wp_locale - before activation: read carefully [this keynote](http://dev.xiligroup.com/?p=2275)
* ready for the new version of xili-dictionary 2 that uses now custom post type to store msg lines.
* compatible with themes with language files in sub-sub-folder of theme.

= 2.3.0, 2.3.2 (2011-11-13) = 
* fixes and avoid notices, fixes support emailing 
* optimized findposts ajax for linked posts
* IMPORTANT: DON'T FORGET TO BACKUP BEFORE UPGRADING.
* ready for multi nav menus [see this post](http://2011.wpmu.xilione.com/?p=160)
* ready for enlarged selection of undefined posts
= 2.2.2, 3 (2011-10-08) = 
* improved code - clean warning - permalink rare issues solved when page switch on front (next)
* fixes - `wp_list_pages` improved for current language subselection (see code)
* improved date formatting options if no *Server Entities Charset* for rare languages like khmer.
* improved search form - findposts ajax added in linked metabox for post and page
= 2.2.0, 1 (2011-07-21) =
* fixes error in navmenu and defaults options of xili_language_list.
* source reviewed, folder reorganized, ready for option with lang inside permalink. Screenshots renewed from WP 3.2 RC
* deep tests with official release of WP 3.2
= 2.1.0, 1 (2011-0628) = 
* fixes uninstall white screen, fixes focus error
* new navigation when singular linked post in xili_language_list, multiple nav menus location, new filter for xili_nav_lang_list see code..
* when a singular (single or page) is displayed, linked posts of other languages are set in xili-language-list links . Previously, it was possible to offer this behaviour by using hook (filter) provided by the plugin. Now, for newbies, it will be easier to link posts according languages with widget.
* for previous users of navigation menus : v2.1.0 is compatible with settings of previous release BUT introduces now a way to choose multiple menu locations - so revisit the settings page to confirm your previous choice or sets to new navigation way including singular links.
= 2.0.0 (2011-04-10) =
* erase old coding remaining for 2.9.x - Improve (progressively) readme...
= 1.9.0, 1 (2011-03-16) =
* fixes in xili widget recent posts - only post-type display by default - input added to add list of type (post,video,…) 
* fixes query_var issues when front-page as page or list of posts (thanks to A B-M)
* Released as current for 3.1

= 1.8.9.1, 1.8.9.3 (2011-01-24) =
* bulk edit in posts list
* add option to adapt nav home menu item

* add `n` in date formatting translation.
* new column in dashboard to see visibility of a language in Languages list - new checkbox in edit and one in widget to subselect only visible langs.
* twentyten-xili child theme : now use version 1.0
* Webmaster : xili_language_list hook has now 5 params - see source.
* Webmaster : to get linked post ID, don't use `get_post_meta` but `xl_get_linked_post_in` function (see lin #4115) (future changes in linking mechanisms)
 
= 1.8.9 (2010-12-12) =
* filter by languages in Posts edit list.
* add filter 'xili_nav_lang_list' to control nav menu automatic insertion by php webmasters.
* add filter 'xili_nav_page_list' to control automatic sub-selection of pages.
* add id and class for separator in nav menu automatic insertion.
* set language available in quick-edit mode of posts list.

= 1.8.6 to 1.8.8 (2010-12-05) =
* complete gettext filters - include optional activation of the 3 widgets. - add use `WPLANG` with 2 chars as *ja* for japanese
* add gettext filter to change domain for visitor side of widget and other plugins.
* optional total uninstall with all datas and options set by xili-language.
* readme rewritten - email metabox at bottom.

= 1.8.5 (2010-11-15) =
* improve automatic languages sub-folder detection and caution message if `load_textdomain()` is missing and not active in functions.php
* repairs oversight about bookmarks taxonomies (blogroll) : now it is possible in widget to sub-select links according language and in template tag `wp_list_bookmarks()`
= 1.8.3 - 1.8.4 (2010-11-07) =
* query for posts with undefined language `lang=*` ( **since 2.3 replaced** by `lang=.` ), improved widget languages list (condition)
* widgets rewritten as widget class extend.
* search form improved
* fixes
= 1.8.2 (2010-10-30) =
* as expected by some webmasters, 'in' before language is not displayed before name in language list.
* better automatic insertion of nav menu for theme with several location. 
* now compatible with child theme - see [Forum](http://dev.xiligroup.com/?forum=xili-language-plugin)
* improve date to strftime format translation.
= 1.8.0 (2010-10-08) =
* now, if checked in settings, a custom post type can be multilingual as post or page type.

= 1.7.0 - 1.7.1 (2010-07-21) =
* some functions are improved through new hooks (front-page selection).
* fixes unexpected rewritting (when permalinks is set) and fixes query of category without languages.
* optional automatic insertion of selection by language of pages in top nav menu (WP 3.0 and twentyten) before list of languages. Possible to adapt parameters as in template-tag ` wp_pages_list()` .
* **For developers:** `xiliml_cur_lang_head` filter is now obsolete and replace by `xiliml_curlang_action_wp` - see code source - the mechanism for frontpage (home recent posts list or page) is changed and don't now use redundant queries.
* **For developers:** if you use `xili_language_list` hook action to create your own list - verify it if you use page as frontpage because 'hlang' querytag is now obsolete.
* **Latest version compatible with WP 2.9.x**

= 1.6.0 - 1.6.1 (2010-06-28) =
* Add new features to manage sticky posts ( [see this post in demo website](http://multilingual.wpmu.xilione.com/) )
* Fixes refresh of THEME_TEXTDOMAIN for old WP 2.9.x
* Improvements mainly for WP 3.0
* more functions to transform without coding site based on famous new twentyten theme. (article later)
* possible to complete top nav menu with languages list for website home selection in two ways.
* new functions for developers/webmasters: `xili_get_listlanguages()`, see source.
* example of language's definition (popup) to add new language.
* Language list widget: list of available options added (hookable also).
* some parts of source rewritten.

= 1.5.2, 3, 4, 5 (2010-05-27) = 
* WP 3.0 (mono or multisite): incorporates automatic detection of theme domain and his new default theme 'twentyten'
* A demo in multisite mode with WP 3.0 and 'twentyten' is [here](http://multilingual.wpmu.xilione.com).
* remains compatible for previous versions WP 2.9.x
* some fixes - see changes log.

= 1.3.x  to 1.4.2a (2010-04-03) =
* Rename two filters for compatibility with filters renamed by WP3.0. Incorporate posts edit UI modifications of WP3.0.
* no unwanted message in homepage when theme-domain is not defined - plugin must be activated AFTER theme domain settings.
* improved template_tags : xiliml_the_category, xiliml_the_other_posts (see source doc)
* Browser's window title now translated for categories (`wp_title()`). Option in post edit UI to auto-search linked posts in other languages - [see this post](http://dev.xiligroup.com/?p=1498).
* New option to adapt the home query according rules defined by chief editor. If home page loop is filled by most recent posts (via index or home.php), formerly, by default xili-language is able to choose the theme's language but not to sub-select the loop (without php coding). Now when checking in Settings *'Modify home query'* - no need to be a php developer.
* New widget for **recent posts** (able to choose language). This new widget solves conflicts or issues occuring when WP default widget is present (contains an *obscur* `wp_reset_query`). Also a choice of language of this list of recent posts is possible - not necessary the same of the current page. And you can install multiple widgets. **Replace WP Recent Posts widget by this one named** - *List of recent posts* -
* New functions to change and restore loop's language query-tag (see functions [table](http://dev.xiligroup.com/?p=1432) ).
* Better dashboard post UI to create linked post (and page): *from one post, it possible to create linked post in another language and the links are prefilled. Just need to save draft to save the links between root and translated posts filled by authors.* [See](http://dev.xiligroup.com/?p=1498)
* fixes lost languages's link when trash or untrash (WP 2.9.1).

= main features improved in previous releases up to 1.3.1 =

* *see readme in [previous versions](http://wordpress.org/extend/plugins/xili-language/download/) to read the changelog chronology*
* …
= 0.9.0 (2009-02-28) = first public release (beta)

© 20130416 - MS - dev.xiligroup.com

== Upgrade Notice ==
Please read the readme.txt before upgrading.
**As usually, don't forget to backup the database before major upgrade or testing no-current version.**
Upgrading can be easily procedeed through WP admin UI or through ftp (delete previous release folder before upgrading via ftp).
Verify you install latest version of trilogy (xili-language, xili-tidy-tags, xili-dictionary).
v2.1.0 is compatible with settings of previous release BUT introduces now a way to choose multiple navmenu locations - so revisit the settings page to confirm your previous choice or sets to new navigation way including singular links.

== More infos ==

= Technical infos =

* REMEMBER : xili-language follows the WordPress story since more than 4 years. Initially designed for webmasters with knowledge in WP, PHP,… step by step the plugin will improved to be more and more plug and play. So don't forget to visit this [demo site](http://multilingual.wpmu.xilione.com), see this [other demo](http://2011.wpmu.xilione.com/) and [Forum](http://dev.xiligroup.com/?forum=xili-language-plugin).

**Prerequisite:**
Verify that your theme is international compatible (translatable terms like `_e('the term','mythemedomaine')` and no displayed texts 'hardcoded' (example in default theme of WP named *twentyten* or *twentyeleven* ).

* Works with WP > 3.0 in mono or multisite. For version 2.9 and less, use release 1.7 or less

**CMS**

* Contains features dedicated to multilingual theme's creators and webmasters. Don't forget to read documented source code.

**Documentation for developers**

A [table](http://dev.xiligroup.com/?p=1432) summarizes all the technical features (widgets, template tags, functions and hooks) of this powerful plugin for personalized CMS created by webmaster.

* Provides infos about **text direction** *ltr* ou *rtl* of languages (arabic, hebraic,...) of theme and of each post in loop
* unique id for category link hook [see expert's corner posts](http://dev.xiligroup.com/?p=1045)
* hooks to define header metas or language attributes in html tag.

**More infos and docs**

* Other posts, articles and more descriptions [here](http://dev.xiligroup.com/xili-language/ "why xili-language ?") and [here in action](http://multilingual.wpmu.xilione.com).
* Visit also [Forum](http://dev.xiligroup.com/?forum=xili-language-plugin) to obtain more support or contribute to others by publishing reports about your experience.

= Compatibility =

xili-language is compatible with the plugin [xili-dictionary](http://dev.xiligroup.com/xili-dictionary/) which is able to deliver .mo files on the fly through the WP admin UI (and .po files translatable by other translators). [xili-dictionary](http://dev.xiligroup.com/xili-dictionary/) used a specific taxonomy without adding tables in WP database. 

xili-language is compatible with the plugin [xili-tidy-tags](http://dev.xiligroup.com/xili-tidy-tags/ ). xili-tidy-tags lets you create multiple group of tags. That way, you can have a tag cloud for tags in English, another cloud for French tags, another for Spanish ones, and so on. You can also use the plugin for more than multilingual blogs. Basically, you can create any group of tags you want.

xili-language is full compatible with the plugin [xilitheme-select](http://wordpress.org/extend/plugins/xilitheme-select/ "xilitheme-select") to be used with iPhone, iPod Touch or other mobiles.

More informations about other plugins in the website [dev.xiligroup.com](http://dev.xiligroup.com/ "xiligroup plugins") or in [WP Repository](http://wordpress.org/extend/plugins/search.php?q=xili&sort=)

*The plugin is frequently updated*. Visit [Other versions](http://wordpress.org/extend/plugins/xili-language/developers/).
See also the [dev.xiligroup Forum](http://dev.xiligroup.com/?forum=xili-language-plugin).

© 2008-2013 - MS - dev.xiligroup.com
