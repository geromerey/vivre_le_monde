=== WP Realtime Sitemap ===
Contributors: Rincewind
Donate link: http://goo.gl/mmUuGj
Tags: seo, navigation, site, map, sitemap, sitemaps, posts, pages, custom, post, types, wpmu, wpml, wordpress, multisite, multiuser, bilingual, i18n, l10n, language, multilanguage, multilingual, translation, qtranslate
Requires at least: 3.0
Tested up to: 4.8
Stable tag: 1.5.7

A sitemap plugin to make it easier for your site to show all your pages, posts, archives, categories and tags in an easy to read format.

== Description ==

A sitemap plugin to make it easier for your site to show all your pages, posts, archives, categories and tags in an easy to read format without any need for template modification or HTML/PHP knowledge in a page.

This plugin doesn't create an XML file to be used with any search engine this is outside of what this plugin was designed to do.

== Features ==

1. Order the output anyway you want in the plugin settings page.
2. Order the output of the WP Nav Menu, Pages, Posts, Custom Post Types, Archives, Categories and Tags.
3. Show/hide WP Nav Menu, Pages, Posts, Custom Post Types, Archives, Categories and Tags.
4. Optionally show categories and/or tags as a bullet list, or as a tag cloud.
5. Exclude Pages, Posts, Custom Post Types, Categories and Tags IDs.
6. Limit the amount of posts, custom post types, archives, categories and tags displayed.
7. Change the archive type from the WordPress default.
8. Show/hide Categories and Tags which have no posts associated to them.
9. Show/hide how many posts are in each Archive, Category or Tag.
10. Optionally name the sections different from the default of Pages, Posts, Archives, Categories and Tags.
11. Hierarchical list of pages and categories.
12. Supports I18n for translation.
13. Supports use of the WordPress shortcode for including the sitemap in pages and posts.
14. Supports menus created with the inbuilt WordPress Menu Editor.
15. Works on WordPress Multisite (WPMU) and Multilingual (WPML) blogs.
16. Comes with an uninstaller, if you don't want it anymore just deactivate the plugin and delete it from within WordPress and it will delete all of its settings itself.

I can't think of anything else that I personally would need this plugin to do for my own use, if anyone feels it doesn't meet their requirements, or has any suggestions as to how to make it better then please get in touch with me and I will see what I can do to accommodate your requests.

Please rate this plugin and/or make a [donation](http://goo.gl/mmUuGj "PayPal donation") if you find it useful, thank you.

== Translations ==

WP Realtime Sitemap is available in various languages, some of which are only partially translated if you could help yourself and others by filling in some of the gaps to complete a translation I would personally be very grateful, you can see the translations here https://goo.gl/wwYdW8.

I have included a wp-realtime-sitemap.po file in the translations folder if you would prefer to use a standalone program such as Poedit, if you did choose to do the translation this way please also submit the translations to the http://translate.wordpress.org website also.

== Installation ==

= Instructions for installing via download from WordPress.org =

1. Download and extract the Plugin zip file.
2. Upload the files to `/wp-content/plugins/wp-realtime-sitemap` directory.
3. Activate the plugin through the &quot;Plugins&quot; menu in WordPress.

= Instructions for installing from within your own blog =

1. Login to the admin interface.
2. Click Plugins in the left hand menu navigation.
3. Click on &quot;Add New&quot; next to the Plugins header in the main content area.
4. Enter &quot;WP Realtime Sitemap&quot; (without quotes) in the textbox and click the &quot;Search Plugins&quot; button.
5. In the list of relevant plugins click the &quot;Install&quot; link for &quot;WP Realtime Sitemap&quot; on the right hand side of the page.
6. Click the &quot;Install Now&quot; button on the popup page.
7. Click &quot;Activate Plugin&quot; to finish installation.

= Display the sitemap on a page or post =

1. Click Pages in the left hand menu navigation.
2. Click on &quot;Add New&quot; in the left hand menu navigation or click on &quot;Add New&quot; next to the Pages header in the main content area.
3. Give your page a title I suggest Sitemap, and put `[wp-realtime-sitemap]` into the WYSIWYG box.
4. Now save or update the page and click on the View page link at the top to see your new sitemap.

**Note**: If you already have a page for your sitemap then put the shortcode `[wp-realtime-sitemap]` in this pages WYSIWYG box instead of creating a new page.

Please see the [FAQ](http://goo.gl/QNiRH "FAQ") and [Other Notes](http://goo.gl/bVQBL "Other Notes") tab for further help.

== Frequently Asked Questions ==

= I would like to make a donation how can I do this? =

You can make a Pay Pal donation by clicking [here](http://goo.gl/mmUuGj "PayPal donation"), or click on "Donate to this plugin" in the right hand side box where it says FYI, your donation will be very gratefully received thank you!

= What should I call the page that I add the sitemap to? =

You can call it whatever you like. I would suggest you call it Site Map.

= I have some pages that I need but are to be hidden and not on the sitemap =

My plugin only shows posts and pages that have the status as published, so if you wish to have a post of page be published but not to be shown, change its status to &quot;privately published&quot; and it will disappear off the sitemap, you can do this easily when editing a post/page with the Publish box on the left hand side, I have included a screenshot to show what to set this box to.  You can also exclude by ID now in the settings.

= I cant get the other short code options to work =

These are now depreciated and should no longer be used, only the shortcode `[wp-realtime-sitemap]` should be in your sitemap page now, all other options are now decided by the admin interface for the plugin and this will always be the case moving forward.

= Do I need to add the `<!--wp-realtime-sitemap-->` to a Post or a Page? =

This method is no longer supported, please use the shortcode `[wp-realtime-sitemap]` instead in a page or a post, please see the [Installation](http://goo.gl/bRO8F "Installation") tab for further help.

= Is there a PHP code so I can add it to a PHP template file? =

This method is no longer supported, please use the shortcode `[wp-realtime-sitemap]` instead in a page or a post, please see the [Installation](http://goo.gl/bRO8F "Installation") tab for further help.

== Screenshots ==

1. Settings page in the admin area.
2. Output as displayed on Twenty Ten theme.
3. How to hide a post and/or page off the sitemap using the published privately option in WordPress.

== Examples ==

The shortcode will use the admin interface for all its configurable options the shortcode is now only used for where to display/output the sitemap at.

Show the sitemap: `[wp-realtime-sitemap]`.

Ordering is done within the plugin settings page.

= Old depreciated options for reference only =

The example shortcodes below are for reference only for use using version below v1.5.2, please do not use these past this version as they are unlikely to be included in future versions, and should therefore be treated as depreciated.

Show everything: `[wp-realtime-sitemap show="all"]`.

Show pages: `[wp-realtime-sitemap show="pages"]`.

Show posts: `[wp-realtime-sitemap show="posts"]`.

Show custom post types: `[wp-realtime-sitemap show="custom-posts"]`.

Show archives: `[wp-realtime-sitemap show="archives"]`.

Show categories: `[wp-realtime-sitemap show="categories"]`.

Show tags: `[wp-realtime-sitemap show="tags"]`.

== Changelog ==

= 1.5.7 =
* Updated code to be compatible with PHP 7.

= 1.5.6 =
* Escaped some strings to address security issues.
* Added &quot;suppress_filters => false&quot; to make the plugin WPML compatible.
* Fixed uninstall hook as was incorrectly used.

= 1.5.5 =
* Fixed reported issue of not being able to change &quot;Header Settings&quot;.
* Fixed broken donation link.
* Removed themefuse buttons.
* Changed hook so settings are only removed on uninstall and not on deactivation.

= 1.5.4 =
* Fixed issue with the custom post types.
* Correct the shortcode mentioned in the description.
* Romanian translation by [Luke Tyler](http://www.enjoyprepaid.com).
* Russian translation updated by [Igor Dubilej](http://www.itransition.com).

= 1.5.3 =
* Added &quot;show_promote&quot; to default options.
* Added upgrade procedure as WordPress changed the way the activation hook works, no longer fires on upgrade only activation.
* German translation by [Andreas Breitschopp](http://www.ab-weblog.com/de/).
* Made some minor changes to the plugin readme.txt to make it easier for people to understand how to use the plugin.

= 1.5.2 =
* Fixed issue with variable name for custom post types.
* Added option to show categories with posts in a hierarchy.
* Added option to show menu created with the menu maker in WordPress.
* Dutch translation by [Martien van de Griendt](http://www.vandegriendtwebsites.nl).
* Updated wp-realtime-sitemap.pot, wp-realtime-sitemap.po and the rest of the .po translation files.
* Added option to order the output in admin interface, individual short codes for menu, pages, posts, custom post types, archives, categories and tags are now obsolete, these have been kept in for now for backwards compatibility.
* All shortcodes are now depreciated, please use `[wp-realtime-sitemap]`.  Ordering is done within the plugin settings page.

= 1.5.1 =
* Fixed issue with default settings being set incorrectly.
* Fixed issue where tags tag cloud was showing categories instead.
* Added missing code to be able to change Posts header.
* Updated wp-realtime-sitemap.pot, wp-realtime-sitemap.po and the rest of the .po translation files.

= 1.5 =
* Completely rewritten all of the options in the admin interface.
* Option to exclude pages, posts, custom post types, archives, categories and tags from the output.
* Now able to limit posts, custom post types, archives, categories and tags from the output.
* No option to limit pages as this is currently broken in WordPress.
* More options for sorting that wasn't included previously.
* Option to change the archive type no longer fixed to monthly.
* Removed sorting options from the WordPress shortcode.
* Fixed code so only runs the code for the section chosen not all sections.

= 1.4.8 =
* Added custom post types, if this was something you have been waiting for, or have requested then please consider making a [donation](http://goo.gl/mmUuGj "PayPal donation") thank you!
* Added ability to change the names of the sections from the defaults of Pages, Posts, Archives, Categories and Tags, this is optional if there blank/empty will use the defaults.
* No longer using query_posts to display pages, posts and custom post types, now using get_posts now works correctly with WPMU, WPML.
* Added Screenshots of admin interface, and the output of the plugin.
* Fixed bug where comments and the comment form was showing on the sitemap page, a great big thank you to [eceleste](http://wordpress.org/support/profile/eceleste) for help with this fix.
* Fixed issue where HTML output wasn't valid for posts, due to missing the double quotes around the url, thanks to [GreyIBlackJay](http://wordpress.org/support/profile/greyiblackjay) for spotting this.

= 1.4.7.2 =
* Changed constructor so the localization files are initialized with the plugin.
* Spanish translation by François-Xavier Gonzalez.

= 1.4.7.1 =
* Fixed some duplication errors in the language files.
* Russian translation by [ssvictors](http://wordpress.org/support/profile/ssvictors).

= 1.4.7 =
* Minor fix to the new variable names, some instances where the old ones were still referenced, instead of the new ones.

= 1.4.6 =
* Updated code to be cleaner and easier to understand.
* Used WordPress Settings API for options form, and added validation.
* Updated the localization files, still fully translatable right down to the admin area.

= 1.4.5 =
* Removed database code from admin_init as was being called on every admin page.
* Added post limit to show x number of posts only, currently limited to 9999.

= 1.4.4 =
* Added option to reset database settings back to defaults.
* Fixed code when using `[wp-realtime-sitemap show="all"]` and not correctly showing tags and/or categories as tag clouds or not.
* Changed activation code to better upgrade database settings, and clean up old data from the database that is now no longer needed.
* Brazilian Portuguese translation by Gervasio Antonio.
* Admin interface now fully translatable.

= 1.4.3 =
* Fixed issue where overwriting `sort_column` variable.

= 1.4.2 =
* Fixed minor bug, where content output would be before whatever was put into the WYSIWYG editor, instead of after.
* Wrapped date for posts in a span tag so easier for this to be styled.

= 1.4.1 =
* Minor security update added nonce field to the form, to check request came from your site and not someone else's site who was using the same plugin.

= 1.4 =
* Hot Fix: Removed comment replacement code in favour of shortcodes instead, this was needed to fix an issue on some blogs where PHP memory limit is set to 64MB.
* Added options to choose to have post count and post date output with the sitemap.
* Streamlined options in the database now instead of several rows for the options in the database, there is now only 1.
* Added code to clean up database from the old way to the new way, preserving your current options also.

= 1.3 =
* Hierarchical list of pages and categories.
* Change code for tags to use WordPress inbuilt functions instead.
* Supports I18n for translation.

= 1.2 =
* Updated code, added settings, support and donate links.
* Fixed display bug.

= 1.1 =
* Optionally show categories and tags as a bullet list, or as a tag cloud.
* Hierarchical list of pages.

= 1.0 =
* Initial version.

== Upgrade Notice ==

= 1.5.3 =
Please change your code in the page you have your sitemap on to `[wp-realtime-sitemap]`, and re-save the settings for the plugin.

= 1.4.6 =
Renamed form options for Show post count, Show date, Post limit, as a result of this I do regrettably have to tell you you will need to visit the settings page and submit your settings back into the database for these options, otherwise your sitemap will not display on your site.

= 1.4 =
You will need to change the code you have in your pages/posts to show the sitemap, please see plugin page on WordPress.org for more info.

= 1.1 =
This update now shows pages as a hierarchical list of pages, and gives the option of having categories and tags as an unordered list or tag cloud.