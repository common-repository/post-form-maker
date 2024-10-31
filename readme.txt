=== Post Form Maker ===
Contributors: Frederic Vauchelles, Cathy Vauchelles
Donate link: http://fredpointzero.com
Tags: post, form, maker, generator, poll
Requires at least: 2.8.3
Tested up to: 2.8.3
Stable tag: 0.1.2

Post Form Maker let you create polls and forms on your posts.

== Description ==

This plugins makes really easy to create a poll or a vote contest. You can make your form quickly and easily with
text, radio, select and checkboxes items, and the form wil be automaticly displayed at the bottom of the post you have just chosen.

== Installation ==

1.  Download the plugin
1.  Copy the directory under your wp-content/plugins
1.  Enable the plugin in your admin pages
1.	Configure the plugin

That's all !

== Frequently Asked Questions ==

= How can I stylize forms ? =
A default css is provided in the css subdirectory and is loaded. But you can ovveride these styles with your own CSS rules !

= Where can I find translation files ? =
All translation files can be found in the lang subdirectory. It contains pot, po and mo files.

== Features ==

= Form creation =
*	Give a label
*	Give a post name (autocompletion box fore easier selection !)
*	Give a type (text, radio, select or checkbox)
*	Give possible values (for radio, select or chackbox)
*	Give the limitation (One vote per IP adress or one vote per IP adress per day)

= Form displaying =
*	Automatic display of the form at the end of the post
*	Display message on already submitted

= Form processing =
*	Store the results in database
*	Display results of the form

= Options =
*	Send a mail when someone submit datas

== About Submit Limitations ==

The submit limitations are currently base on IP adress. So you can choose to allow one submit per IP or
one submit per IP per day.

Enjoy !

== Screenshots ==

1. Form list menu screen shot
1. Add/Edit form menu screen shot
1. Form displayed in a post

== Changelog ==

= 0.1.2 =
*	Fixed : plugin directory name has no influence

= 0.1.1 =
*	Fixed : strip slashes on displayed elements

= 0.1 =
* Initial widget