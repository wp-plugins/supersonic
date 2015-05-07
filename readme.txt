=== Plugin Name ===
Contributors: kursorA
Donate link: http://www.wp-supersonic.com/donate-supersonic
Tags: cloudflare, speed, cache, optimize, security, bruteforce, CDN, performance, spam, antispam
Requires at least: 3.6
Tested up to: 4.2
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Wordpress SuperSonic with CloudFlare

== Description ==

Important information: this plugins works only with CloudFlare!

With this plugin you can speed up Wordpress to supersonic speed. 

By default CloudFlare do not caches HTML content. It can be done by adding one page rule in CloudFlare. But when site content is changed (by adding, editing or deleting post, page or comment) CloudFlare do not refreshes cached content. This functionality is taken by this plugin. 
When content is changed plugin purges only files previously served to CloudFlare. It saves resources and time. You can choose which files are purged on defined events.

Wordpress SuperSonic with CloudFlare integrates Wordpress with CloudFlare for more speed and security. With this plugin Wordpress pages will load as fast as 100 miliseconds!

= Major features =
* automatically purge changed pages from CloudFlare cache (posts, pages, custom post types and associates pages: categories, tags, date archives)
* country information of commenter in comments
* bruteforce protection by bannig IP address in CloudFlare
* ban, with list, clear commenter IP address in CloudFlare from comments list
* disable Wordpress login by blocking selected countries
* disable possibility to post Wordpress comments by blocking selected countries
* block Wordpress XML-RPC for selected countries
* displays CloudFlare statistics for domain
* event logging

= Example sites with SuperSonic plugin - check how fast they loading =
* [Site 1](http://www.wp-supersonic.com/ "www.wp-supersonic.com")
* [Site 2](http://www.zespoldowna.info/ "www.zespoldowna.info")
 
== Installation ==

1. Upload zip archive content to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Administration area and choose SuperSonic from menu.
4. Configure CloudFlare credintials.

== Frequently Asked Questions ==

= CloudFlare is required? =

Yes. Without CloudFlare SuperSonic functions will not works.

= Which Page Rules should You define in CloudFlare? =

To properly working Supersonic requires at least 3 Page Rules in CloudFlare.

1. URL pattern: /*.php* 
   Custom caching: Bypass cache 
2. URL pattern: /\*wp-admin\*
   Custom caching: Bypass cache
3. URL pattern: /*
   Custom caching: Cache everything
   Edge cache expire TTL: 1 week
   Browser cache expire TTL: 30 minutes
   
= How to configure Supersonic to serve non cached pages with Admin Bar for logged in users? =

Supersonic serves front end pages as for non logged in users (except pages that are defined in options).
But for users with specified roles there is posibility to serve non cached pages. It is done by adding parameter (supersonic=wp-admin) to all frontend URLs.

CloudFlare should not cache pages with this parameter. There must be PageRule with Custom caching: Bypass cache.
If you have Page Rule with URL pattern /wp-admin/* or /wp-admin* siply change URL pattern to /*wp-admin.


== Screenshots ==

1. CloudFlare configuration
2. Options
3. Tools
4. Cache purge configuration
5. Security
6. Log
7. Statistics
8. Comment list

== Changelog ==

= 1.2.0 =
* Added configuration option for start purging CloudFlare cache without waiting for wp-cron (in Options tab)
* Added functionality to serve non cached pages for logged in users with specified role(s), this functionality is bit tricky - see FAQ

= 1.1.3 =
* Short opening tags (<?) changed to PHP opening tags (<?php)

= 1.1.2 =
* Added support for post preview

= 1.1.1 =
* Initiate first purge immediately without cron

= 1.1.0 =
* Wordpress 4.2 compatibility

= 1.0.15 =
* New log message for purge error in wp-cron
* Admin message in SuperSonic screen with pages count to purge from cache

= 1.0.14 =
* Fixed not working bulk delete in Log

= 1.0.13 =
* Fixed bug in "List of URLs to purge"

= 1.0.12 =
* Tabs renamed

= 1.0.11 =
* Added zone to CloudFlare connection test
* Cosmetic changes in statistics

= 1.0.10 =
* Fixed bug in configuration form

= 1.0.9 =
* Initial version

== Upgrade Notice ==

