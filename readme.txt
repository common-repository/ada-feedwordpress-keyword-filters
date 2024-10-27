=== Ada FeedWordPress Keyword Filters ===
Contributors: skcsknathan001
Donate link: https://www.paypal.com/paypalme/adadaacom
Plugin URI: https://adadaa.net/1uthavi/ada-feedwordpress-keyword-filters/
Author: CAPitalZ
Author URI: https://adadaa.news/
Tags: feedwordpress, keyword, filter, ada, adadaa
Requires at least: 3.1
Tested up to: 6.6
Stable tag: 2024.0210
License: GPL

Filters posts syndicated through FeedWordPress by keywords.

== Description ==

This is a plugin for the FeedWordPress.

You can do complicated keyword filters using AND, OR, and NOT logics.  Plugin will look for user entered keywords in post_title, and post_content


== Installation ==

WordPress automatic installation is fully supported and recommended.

If you want to manually install

1. Upload `ada-feedwordpress-keyword-filters` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

You should see Ada Keyword Filters section in FeedWordPress's Posts & Links section.

== Upgrade Notice ==

= 2022.1229 =
Fixed php debug function errors.

= 2022.0923 =
Improved performance & design changes updated

= 2021.0311 =
Fixed php deprecated functions.


= 2012.0430 =
Fixed empty author creation when those posts were filtered out.
Speed boost, as the posts were filtered at the initial stage of syndication.

= 2012.0216 =
Initial release

== Frequently Asked Questions ==

= What happens when I have only NOT logics =

When you only use *NOT logics [OR NOT, AND NOT], the syndicated content will be included if those words are not found.

= What happens when there are NOT logics and other logics [OR, AND]? =

When there are NOT logics [OR NOT, AND NOT] along with other logics [OR, AND], then NOT logics take precedence. If NOT logic's keyword is found within the syndicated content, all the other rules are ignored and the content is not included.

== Screenshots ==

1. Adding new keyword filters

== Usage ==

It is not disabled out of the box and you should add keywords by clicking the Add a new keyword filter. You can add as many keyword filters as you want using one or all of the logics [OR, AND, or NOT]

You can add keywords for individual feeds as well.  When you select Posts under individual feeds on the Syndication menu of FeedWordPress, you'll have the same options to add keywords.

* Keywords are *not* case sensitive. Enter comma seperated list of words along with your selection of logics.

* Do not leave extra space between words - unless you want it.  The exact spacing will be matched.
