# Statify Blacklist #
* Contributors:      Stefan Kalscheuer
* Requires at least: 3.9
* Tested up to:      4.6
* Stable tag:        1.2.1
* License:           GPLv3 or later
* License URI:       https://www.gnu.org/licenses/gpl-3.0.html

## Description ##
A blacklist extension for the famous [Statify](http://statify.de) Wordpress plugin.

This plugin adds customizable blacklist to Statify to allow blocking of referer spam or internal interactions.

### Current Features ##
#### Referer Blacklist ####
Add a list of domains (for simplicity only second-level, e.g. _example.com_ which blocks _everything.example.com_).

#### CleanUp Database ####
Filters can be applied to data stored in database after modifying filter rules or for one-time clean-up.

#### Compatibility ####
This plugin requires Statify to be installed. The extension has been tested with Statify 1.4.3
The plugin is capable of handling multisite installations.

### Credits ###
* Author: Stefan Kalscheuer
* Special Thanks to [pluginkollektiv](http://pluginkollektiv.org/) for maintaining _Statify_

## Installation ##
* If you don’t know how to install a plugin for WordPress, [here’s how](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).
* Make sure _Statify_ plugin is installed and active 
* Goto _Settings_ -> _Statify Blacklist_ to configure the plugin

### Requirements ###
* PHP 5.2.4 or above
* WordPress 3.9 or above
* Statify plugin installed and activated (tested up to 1.4.3)

## Frequently Asked Questions ##

### What is blocked by default? ###
Nothing. By default all blacklists are empty and disabled. They can and have to be filled by the blog administrator.

A default blacklist is not provided, as the plugin itself is totally neutral. If you want to filter out referer spam, 
visitors from search engines or just "false" referers from 301 redirects only depends on you.

### Does the filter effect user experience? ###
No. It only prevent's _Statify_ from tracking, nothing more or less.

### Does live filtering impact performance? ###
Yes, but probalby not noticeable. Checking a single referer string against a (usually small) list should be neglectible compared to the total loading procedure.
If this still is an issue for you, consider deactivating the filter and only run the one-time-cleanup or activate the cron job.
 
### Is any personal data collected? ###
No. The privacy policy of _Statify_ is untouched. Data is only processed, not stored or exposed to anyone.

### Are regular expression filters possible? ###
Not for now. At the moment it's only a simple domain filter, as regular expression matching is significantly slower.

If you like to have this feature, please leave a feature request in GitHub or the WordPress support forum.


## Screenshots ##
1. Statify Blacklist settings page

## Changelog ##

### 1.3.0 / [under development] ###
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