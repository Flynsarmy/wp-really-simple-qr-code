<?php
/*

Plugin Name: Really Simple QR Code
Plugin URI: http://www.flynsarmy.com
Version: 2.0.1
Author: Flynsarmy
Author URI: http://www.flynsarmy.com
Description: Adds a shortcode for generating QR codes

Usage:

 * Use the shortcode [rsqrcode] within your string to generate the current URL

 * Default parameters (all optional) include:
	string = <current URL>
    alt = "Scan the QR code"
    size = 120

 * You can pass any other paramters you like, they'll appear as attributes for the <img> tag.

 * Example usage: [rsqrcode string="your string here" size="80" title="my image title text" id="my_image_id" class="my_image_class"]

*/

/* Add the Shortcode */
add_shortcode('rsqrcode', 'rsqrcode');
function rsqrcode( $atts ) {
	$atts = array_merge(array(
		'alt' => 'Scan the QR code',
		'width' => '200',
		'height' => '200',
	), (array)$atts);

	$relpath = rsqrcode_generate($atts);

	// Clean up shortcode attributes so we only display relevant info on
	// the returned IMG element
	unset(
		$atts['size'],
		$atts['string'],
		$atts['cache'],
		$atts['dir'],
		$atts['filetype'],
		$atts['quality']
	);

	// Generate the image HTML
	$html = '<img src="'.site_url($relpath).'" ';
	// Add all our image attributes
	foreach ( $atts as $att=>$value )
		$html .= $att.'="'.htmlspecialchars($value).'" ';
	$html .= '/>';

	return $html;

	// // Sanitize input
	// $size = max(10, intval($atts['size']));
	// $string = urlencode($atts['string']);
	// $atts = array_map('htmlspecialchars', $atts);

	// // Don't add unnecessary attributes
	// foreach ( $atts as $key=>$value )
	// 	if ( empty($value) )
	// 		unset($atts[$key]);

	// // Grab the image URL
	// $image_url = "https://chart.googleapis.com/chart?chs=" . $size . 'x' . $size . '&cht=qr&chl=' . $string;

	// // We dont' want size or string as image attributes
	// unset($atts['size'], $atts['string']);

	// // Generate the image HTML
	// $html = '<img src="'.$image_url.'" ';
	// // Add all our image attributes
	// foreach ( $atts as $att=>$value )
	// 	$html .= $att.'="'.$value.'" ';
	// $html .= '/>';

	// return $html;
}

/**
 * Generate and cache a QR code image
 *
 * @param  array $options array(
 * 		'size' => 6 							// (int) (optional) Size of QR code
 * 		'cache' => true 						// (bool) (optional) Serve a cached copy of the image if one exists
 * 		'dir' => 'wp-content/uploads/rsqrcode'	// (string) (optional) Relative path to cache directory
 * 		'string' => current_url()				// (string) (required) QR code contents
 * 		'filetype' => 'jpeg',					// (string) (optional) Output filetype. jpeg or png
 * 		'quality' => 80,						// (int) (optional) Output image quality
 * )
 *
 * @return string         File path to image relative to WP root
 */
function rsqrcode_generate( $options )
{
	if ( !is_array($options) )
		throw new Exception("You must specify an options array");

	$upload_dir = wp_upload_dir();
	$defaults = array(
		'width' => 256,
        'height' => 256,
        'margin' => 0,
		'cache' => true,
		'dir' => 'wp-content' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'rsqrcode',
		'string' => rsqrcode_current_url(),

	);
	$options = array_merge($defaults, $options);

	// Validation/Sanitization
	$options['cache'] = !!$options['cache'];
    $options['width'] = intval($options['width']);
    $options['height'] = intval($options['height']);
    $options['margin'] = intval($options['margin']);
	$options['dir'] = trim($options['dir'], DIRECTORY_SEPARATOR);

	if ( empty($options['string']) )
		throw new Exception("You must specify a string option");

	$relpath = $options['dir'] . DIRECTORY_SEPARATOR . $options['width'] . 'x' . $options['height'] . '_' . md5($options['string']) . '.png';
	$abspath = ABSPATH . DIRECTORY_SEPARATOR . $relpath;

	if ( !$options['cache'] || !file_exists($abspath) )
	{
		require_once __DIR__.'/vendor/autoload.php';

		// Create output directory if it doesn't already exist
		if ( !is_dir(dirname($abspath)) && !mkdir(dirname($abspath)) )
			throw new Exception("Could not create output directory '".dirname($abspath)."'");

        $renderer = new \BaconQrCode\Renderer\Image\Png();
        $renderer->setHeight($options['height']);
        $renderer->setWidth($options['width']);
        $renderer->setMargin($options['margin']);
        $writer = new \BaconQrCode\Writer($renderer);
        $writer->writeFile($options['string'], $abspath);
	}

	return $relpath;
}

/**
 * Returns the current URL
 *
 * @return string     current URL
 */
function rsqrcode_current_url()
{
    $s = @$_SERVER["HTTPS"] == 'on' ? 's' : '';;
    $sp = strtolower($_SERVER["SERVER_PROTOCOL"]);
    $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
    $port = in_array($_SERVER["SERVER_PORT"], array("80", '443')) ? "" : (":".$_SERVER["SERVER_PORT"]);
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $port . $_SERVER['REQUEST_URI'];
}