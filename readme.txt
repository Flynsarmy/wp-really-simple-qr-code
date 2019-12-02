=== Really Simple QR Code ===
Contributors: flynsarmy
Tags: qr, shortcode
Requires at least: 3.2.1
Tested up to: 5.3
Stable tag: 3.0.0

== Description ==

* Use the shortcode [rsqrcode] within your string to generate the current URL

* Default parameters (all optional) include:
  - string = <current URL>
  - alt = "Scan the QR code"
  - width = 256
  - height = 256
* You can pass any other paramters you like, they'll appear as attributes for the <img> tag.
* Example usage: [rsqrcode string="your string here" size="80" title="my image title text" id="my_image_id" class="my_image_class"]

== Installation ==

1. Upload `really-simple-qr-code` folder to the `/wp-content/plugins/` directory
2. `composer install`
3. Activate the plugin
The shortcode will now be available

== Changelog ==

= 3.0.0 =

December 2, 2019

* Updated bacon-qr-code dependency

= 2.0.1 =

December 2, 2019

* PHP 7.4 warning fix

= 2.0 =

July 25, 2016

* Updated vendor package to a more modern one
* Replaced 'size' option with 'width/height'

= 1.0 =

August 18, 2015

* First version released