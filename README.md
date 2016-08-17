# Statify Blacklist #
* Contributors:      Stefan Kalscheuer
* Requires at least: 3.9
* Tested up to:      4.6
* Stable tag:        1.1.2
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
* PHP 5.2.4
* WordPress 3.9
* Statify plugin installed and activated (tested up to 1.4.3)

## Screenshots ##
1. Statify Blacklist settings page

## Changelog ##
### 1.1.2 / 17.08.2016 ###
* Prepared for localization

### 1.1.1 / 16.08.2016 ###
* Some security fixes

### 1.1.0 / 15.08.2016 ###
* One-time execution on database

### 1.0.0 / 14.08.2016 ###
* First release