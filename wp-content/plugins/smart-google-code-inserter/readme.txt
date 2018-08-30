=== Smart Google Code Inserter ===
Contributors: oturia
Requires at least: 3.3
Tested up to: 4.9
Stable tag: 3.5
Tags: Google, Analytics, Webmaster Tools, Oturia, Jason Whitaker

== Description ==
This plugin makes it easy for you to add your Google Analytics tracking code as well as your meta tag verification of Webmaster Tools. NEW - Now you are able to add your Google AdWords conversion tracking code and select a list of pages from a dropdown to choose which page you want the code to be placed on (e.g. a thank you page).

== Plugin Official Page ==
http://oturia.com/plugins/smart-google-code-inserter/

== Installation ==
1. Upload the entire `smartgooglecode` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Add your Google Analytics tracking code, Webmaster Tools Verification HTML Snippet and/or your Google AdWords Conversion Tracking code to the Smart Google Code settings.

== Changelog ==
3.5 Updated to ensure admin capabilities for access to the saveGoogleCode operation.

3.4 Updates to the admin interface to work with the new WP 3.8.

3.2 Removed deactivation hook that deletes data upon deactivation.

3.1 Fixed a jQuery bug, moved menu to "Settings", changed size of AdWords textarea on options page.

3.0 Added the ability to include multiple conversion codes for different pages.

2.2 Corrected an instance of a PHP shortcode in smartgooglecode.php.

2.1 Added detailed descriptions to the admin panel.

2.0 Added AdWords Conversion Tracking.

1.0 Tested and launched.


== Upgrade Notice ==

= 3.1 =
Sites with an apostrophe weren't able to add new conversions. Thanks to Reece Denzel for helping us locate this bug.

= 3.0 =
We made a change to how we store the AdWords code, so you'll need to deactivate and reactivate the plugin when upgrading to 3.0.

== Screenshots ==
1. This is a screenshot of the admin panel.