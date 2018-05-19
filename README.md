# Statify Blacklist #
* Contributors:      Stefan Kalscheuer
* Requires at least: 4.7
* Tested up to:      4.9
* Requires PHP:      5.5
* Stable tag:        1.4.3
* License:           GPLv2 or later
* License URI:       http://www.gnu.org/licenses/gpl-2.0.html

## Description ##
A blacklist extension for the famous [Statify](https://wordpress.org/plugins/statify/) Wordpress plugin.

This plugin adds customizable blacklist to Statify to allow blocking of referer spam or internal interactions.

### Features ##

#### Referer Blacklist ####
Add a list of domains (for simplicity only second-level, e.g. _example.com_ which blocks _everything.example.com_).

#### Target Blacklist ####
Add a list of target pages (e.g. _/test/page/_, _/?page_id=123_) that will be excluded from tracking.

#### IP Blacklist ####
Add a list of IP addresses or subnets (e.g. _192.0.2.123_, _198.51.100.0/24_, _2001:db8:a0b:12f0::/64_).

#### CleanUp Database ####
Filters can be applied to data stored in database after modifying filter rules or for one-time clean-up.

#### Compatibility ####
This plugin requires Statify to be installed. The extension has been tested with Statify up to version 1.5.1
The plugin is capable of handling multisite installations.

### Support & Contributions ###
* If you experience any issues, use the [support forums](https://wordpress.org/support/plugin/statify-blacklist).
* Latest sources and development are handled on [GitHub](https://github.com/stklcode/statify-blacklist). You might contribute there or file an issue for code related bugs.
* If you want to translate this plugin you can do this on [WordPress Translate](https://translate.wordpress.org/projects/wp-plugins/statify-blacklist).

### Credits ###
* Author: Stefan Kalscheuer
* Special Thanks to [pluginkollektiv](https://github.com/pluginkollektiv) for maintaining _Statify_

## Installation ##
* If you don’t know how to install a plugin for WordPress, [here’s how](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).
* Make sure _Statify_ plugin is installed and active 
* Goto _Settings_ -> _Statify Blacklist_ to configure the plugin

### Requirements ###
* PHP 5.5 or above
* WordPress 4.7 or above
* Statify plugin installed and activated (1.5.0 or above)

## Frequently Asked Questions ##

### What is blocked by default? ###
Nothing. By default all blacklists are empty and disabled. They can and have to be filled by the blog administrator.

A default blacklist is not provided, as the plugin itself is totally neutral. If you want to filter out referer spam, 
visitors from search engines, just "false" referers from 301 redirects or you own IP address used for testing only depends on you.

### Does the filter effect user experience? ###
No. It only prevent's _Statify_ from tracking, nothing more or less.

### Does live filtering impact performance? ###
Yes, but probalby not noticeable. Checking a single referer string against a (usually small) list should be negligible compared to the total loading procedure.
If this still is an issue for you, consider deactivating the filter and only run the one-time-cleanup or activate the cron job.
 
### Is any personal data collected? ###
No. The privacy policy of _Statify_ is untouched. Data is only processed, not stored or exposed to anyone.

### Are regular expression filters possible? ###
Yes, it is. Just select if you want to filter using regular expressions case sensitive or insensitive.

Note, that regular expression matching is significantly slower than the plain domain filter. Hence it is only recommended for asynchronous cron or manual execution and not for live filtering.

### Why is IP filtering only available as live filter? ###
As you might know, Statify does not store any personal information, including IP addresses in the database.
Because of this, an IP blacklist can only be applied while processing the request and not afterwards.


## Screenshots ##
1. Statify Blacklist settings page

## Changelog ##

### 1.5.0 / unreleased ###
* Minimum required WordPress version is 4.7

### 1.4.4 / 19.05.2018 ###
* Fix live filter chain when regular expressions are active (#12)

### 1.4.3 / 09.01.2018 ###
* Fix issues with multisite installation (#11)

### 1.4.2 / 12.11.2017 ###
* Minor code fixes

### 1.4.1 / 16.07.2017 ###
* Relicensed to GPLv2 or later
* Fix filter hook if referer is disabled (#9)
* Fix problem with faulty IPv6 netmask in IP blacklist
* Minor changes for WP Coding Standard
* Minimum required WordPress version is 4.4 (#10)

### 1.4.0 / 10.06.2017 ###
* IP blacklist implemented (#7)
* Target page blacklist implemented (#8)
* Internal configuration restructured (upgrade on plugin activation)
* Statify hook name changed to `statify__skip_tracking` (as of Statify 1.5.0)

### 1.3.1 / 09.12.2016 ###
* Continue filtering if no filter applies (#6)

### 1.3.0 / 17.10.2016 ###
* Regular expressions filtering implemented

### 1.2.1 / 10.10.2016 ###
* Fix live filter configuration check

### 1.2.0 / 29.08.2016 ###
* Switched from `in_array()` to faster `isset()` for referer checking
* Optional cron execiton implemented

### 1.1.2 / 17.08.2016 ###
* Prepared for localization

### 1.1.1 / 16.08.2016 ###
* Some security fixes

### 1.1.0 / 15.08.2016 ###
* One-time execution on database

### 1.0.0 / 14.08.2016 ###
* First release
