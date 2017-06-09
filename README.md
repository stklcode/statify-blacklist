# Statify Blacklist #
* Contributors:      Stefan Kalscheuer
* Requires at least: 3.9
* Tested up to:      4.8
* Stable tag:        1.3.1
* License:           GPLv3 or later
* License URI:       https://www.gnu.org/licenses/gpl-3.0.html

## Description ##
A blacklist extension for the famous [Statify](https://wordpress.org/plugins/statify/) Wordpress plugin.

This plugin adds customizable blacklist to Statify to allow blocking of referer spam or internal interactions.

### Current Features ##
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
* If experience any issues, use the [support forums](https://wordpress.org/support/plugin/statify-statify).
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
* WordPress 3.9 or above
* Statify plugin installed and activated (tested up to 1.5.1)

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

### 1.4.0 / work in progress ###
* IP blacklist implemented (#7)
* Target page blacklist implemented (#8)
* Internal configuration restructured (upgrade on plugin activation)

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
