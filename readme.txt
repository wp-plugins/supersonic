=== Plugin Name ===
Contributors: kursorA
Donate link: 
Tags: cloudflare, speed, cache, optimize, security, bruteforce, CDN
Requires at least: 3.6
Tested up to: 4.1
Stable tag: 1.0.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Wordpress SuperSonic with CloudFlare

== Description ==

Important information: this plugins works only with CloudFlare!

With this plugin you can speed up Wordpress to supersonic speed. 

By default CloudFlare do not caches HTML content. It can be done by adding one page rule in CloudFlare. But when site content is changed (by adding, editing or deleting post, page or comment) CloudFlare do not refreshes cached content. This functionality is taken by this plugin. 
When content is changed plugin purges only files previously served to CloudFlare. It saves resources and time. You can choose which files are purged on defined events.

Wordpress SuperSonic with CloudFlare integrates Wordpress with CloudFlare for more speed and security. With this plugin Wordpress pages will load as fast as 100 miliseconds!

Major features:
* automatically purge changed pages from CloudFlare cache (posts, pages, custom post types and associates pages: categories, tags, date archives)
* country information of commenter in comments
* bruteforce protection by bannig IP address in CloudFlare
* ban, with list, clear commenter IP address in CloudFlare from comments list
* disable Wordpress login by blocking selected countries
* disable possibility to post Wordpress comments by blocking selected countries
* block Wordpress XML-RPC for selected countries
* displays CloudFlare statistics for domain
* event logging

 
== Installation ==

1. Upload zip archive content to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Administration area and choose SuperSonic from menu.
4. Configure CloudFlare credintials.

== Frequently Asked Questions ==

= CloudFlare is required? =

Yes. Without CloudFlare SuperSonic functions will not works.


== Screenshots ==

1. CloudFlare configuration
2. Options
3. Tools
4. Cache purge configuration
5. Security
6. Comment list

== Changelog ==

= 1.0.10 =
* Fixed bug in configuration form

= 1.0.9 =
* Initial version

== Upgrade Notice ==

