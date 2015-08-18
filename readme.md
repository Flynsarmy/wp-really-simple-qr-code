# Really Simple QR Code

Adds a `[rqcrcode]` shortcode for generating QR codes

## Installation

1. Upload `really-simple-qr-code` folder to the `/wp-content/plugins/` directory
2. `composer install`
3. Activate the plugin
The shortcode will now be available

## Description

* Use the shortcode [rsqrcode] within your string to generate the current URL

* Default parameters (all optional) include:
  - string = <current URL>
  - alt = "Scan the QR code"
  - size = 120
* You can pass any other paramters you like, they'll appear as attributes for the <img> tag.
* Example usage: [rsqrcode string="your string here" size="80" title="my image title text" id="my_image_id" class="my_image_class"]