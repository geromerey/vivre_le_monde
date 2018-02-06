=== Weekly Schedule ===
Contributors: jackdewey
Donate link: http://ylefebvre.ca/wordpress-plugins/weekly-schedule/
Tags: schedule, events, grid, weekly, multiple, tooltip, jQuery
Requires at least: 2.8
Tested up to: 4.9.1
Stable tag: trunk

The purpose of this plugin is to allow users to create a schedule of weekly events and display that schedule on a page in a table form. Users can style events using stylesheets based on their category and can assign information to items that will be displayed in a tooltip.

== Description ==

The purpose of this plugin is to allow users to create one or more schedules of weekly events and display these schedule on one or more pages as tables. Users can style their schedules using stylesheets based on the category of items and can assign information to items that will be displayed in a tooltip.

You can see a demonstration of the output of the plugin using a single schedule [here](http://ylefebvre.ca/2009-2010-tv-schedule/).

== Installation ==

1. Download the plugin and unzip it.
1. Upload the weekly-schedule folder to the /wp-content/plugins/ directory of your web site.
1. Activate the plugin in the Wordpress Admin.
1. Using the Configuration Panel for the plugin, create schedule categories and items
1. To see your schedule, in the Wordpress Admin, create a new page containing the following code:<br/>
   [weekly-schedule schedule=1]<br />
   where the schedule number will change based on the number of schedules defined.

== Changelog ==

= 3.1 =
* Added new option to make table first row sticky (Will always stay at top of table as it scroll by)

= 3.0.2 =
* Fixed issue with day list not working after recent updates

= 3.0.1 =
* Modifications to allow HTML to be placed in item title (e.g. img tag)

= 3.0 =
* Added new parameter cats to all shortcode to be able to filter categories to be displayed

= 2.9.9 =
* Corrected table creation code to set name and description as UTF-8 fields

= 2.9.8 =
* Reverting changes from 2.9.7. Will bring back down the road.

= 2.9.7 =
* Update way in which qtip library is loaded to be more friendly to do_shortcode function call

= 2.9.6 =
* Small class addition to horizontal mode for styling

= 2.9.5 =
* Fixed problem with category class on table data items

= 2.9.4 =
* Added uninstall routine to remove all options and tables related to Weekly Schedule

= 2.9.3 =
* Updated version of qtip plugin used to diplay schedule item tooltips

= 2.9.2 =
* Corrected bug with widget option to only show current and future items

= 2.9.1 =
* Enhanced output to add now-playing css class to currently playing items in schedule and widget views
* Added row reset to delete all items button
* Added new option in widget only to show items that are playing or still to play on current day

= 2.9 =
* Added new importer to be able to batch import schedule items
* Added new button to delete all items in a schedule

= 2.8.6 =
* Modified widget to respect 12/24 hour display setting from schedule definition
* Removed PHP warnings

= 2.8.5 =
* Added support for network installations

= 2.8.4 =
* Added option to select user access level required to editor weekly schedule content (can only be configured by admin)

= 2.8.3 =
* Corrected PHP warnings

= 2.8.2 =
* Fixed problems with permissions when trying to edit schedule categories and items

= 2.8.1 =
* Added missing icon for menu

= 2.8 =
* Added stylesheet editor to avoid having to always restore the plugin's stylesheet after upgrades

= 2.7.5 =
* Added 24-hour display mode with colon instead of "h" character

= 2.7.4 =
* Fixed bug with link output in weekly schedule grid

= 2.7.2 =
* Changed way that Admin page URL is built to work on https configurations

= 2.7.1 =
* Fix to background color issue introduced in version 2.7

= 2.7 =
* Added ability to specify category background color in admin interface
* Added option to leave item name blank
* Both new functionalities contributed by Daniel R. Baleato

= 2.6.1 =
* Fixed problem with new shortcode [daily-weekly-schedule] to properly accept parameters

= 2.6 =
* Added shortcode [daily-weekly-schedule] to display current day's items in a post / page. Similar to widget
* Added ability to specify item background color and title font color

= 2.5 =
* Added a new widget to the plugin to be able to display the day's items in a site's sidebar easily (Thanks to Philip Kirwan for initial coding)

= 2.4 =
* Updated qtip tooltip plugin to version 2.0 RC

= 2.3.2 =
* Made fixes to accept event names with single quotes in their name

= 2.3.1 =
* Fix check when you change time division to only check items under current schedule for conflicts

= 2.3 =
* Increase size of day name field from 12 to 64 characters

= 2.2.5 =
* Add option to specify the link target (window) where links will be opened. Was previously hard-coded to new window.

= 2.2.4 =
* Added option to add stylesheet and scripts to front page header

= 2.2.3 =
* Minor change fixes

= 2.2.2 =
* fixed problem preventing popup description display in IE 6/7

= 2.2.1 =
* Added support for 2-hour and 3-hour time divisions

= 2.2 =
* Updated qTip plugin for compatibility with Wordpress 3.0

= 2.1.2 =
* Fixed: Could not save general settings for schedules other than #1.

= 2.1.1 =
* Fixed: Times not showing well when listing schedule items

= 2.1 =
* Added: Ability to set time division to 15 minutes (Thanks to Matt Bryers for suggestion and initial ground work)

= 2.0.2 =
* Added reference links at top of admin page

= 2.0.1 =
* Added extra styles to work with times up to 4 hours in vertical mode

= 2.0 =
* New Feature: Added ability to define and display multiple schedules on a Wordpress page

= 1.1.8 =
* Fixed: 12:30pm was showing as 0:30pm.
* Tested with Wordpress 3.0 Beta 1

= 1.1.7 =
* Only load stylesheets and scripts if necessary

= 1.1.6 =
* Corrected problem with creation of tables on installation
* Corrected problem of lost settings on upgrade

= 1.1.5 =
* Restored ability to put HTML codes in item names

= 1.1.4 =
* Now allows descriptions and item names to contain quotes and other special html characters

= 1.1.3 =
* Added option for tooltip position to be automatically adjusted to be in visible area.

= 1.1.2 =
* Removed debugging statements from admin interface and generated output

= 1.1.1 =
* Corrected bugs with verfication of conflicting items upon addition or deletion of items

= 1.1 =
* Adds new vertical display option
* 24/12 hour display mode is reflected in admin interface
* Various bug fixes

= 1.0.1 =
* Added option to choose between 24 hour and 12 hour time display
* Fixed link to settings panel from Plugins page

= 1.0 = 
* First release

== Frequently Asked Questions ==

= How do I style the items belonging to a category? =

Create an entry in the stylesheet called cat# where # should be replaced with the category ID.

= How do I add images to the tooltip =

Use HTML codes in the item description to load images or do any other type of HTML formatting.

= What parameters are available with the [daily-weekly-schedule] shortcode? =

You can call the shortcode as follows: [daily-weekly-schedule schedule='2' max_items='5' empty_msg='No Items Found' cats="2,3"]

== Screenshots ==

1. A sample schedule created with Weekly Schedule
2. General Plugin Configuration
3. Manage and add items to the schedule
