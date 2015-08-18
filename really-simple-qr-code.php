<?php
/*

Plugin Name: Really Simple QR Code
Plugin URI: http://www.flynsarmy.com
Version: 1
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
		'size' => 6,
		'cache' => true,
		'dir' => 'wp-content' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'rsqrcode',
		'string' => rsqrcode_current_url(),
		'filetype' => 'jpeg',
		'quality' => 80,
	);
	$options = array_merge($defaults, $options);

	// Validation/Sanitization
	$options['cache'] = !!$options['cache'];
	$options['size'] = intval($options['size']);
	$options['quality'] = intval($options['quality']);
	$options['dir'] = trim($options['dir'], DIRECTORY_SEPARATOR);

	if ( empty($options['string']) )
		throw new Exception("You must specify a string option");
	if ( !$options['size'] )
		throw new Exception("Size option must be a valid integer above 0");
	if ( !in_array($options['filetype'], array('jpeg', 'png')) )
		throw new Exception("Only jpeg and png output filetypes are supported");
	if ( $options['quality'] <= 0 || $options['quality'] > 100 )
		throw new Exception("Quality option must be a valid integer from 1 to 100");

	$relpath = $options['dir'] . DIRECTORY_SEPARATOR . $options['size'] . '_' . md5($options['string']) . '.' . $options['filetype'];
	$abspath = ABSPATH . DIRECTORY_SEPARATOR . $relpath;

	if ( !$options['cache'] || !file_exists($abspath) )
	{
		require_once(dirname(__FILE__)."/vendor/porkaria/php-qrcode-generator/qrcode/Image/QRCode.php");

		// Create output directory if it doesn't already exist
		if ( !is_dir(dirname($abspath)) && !mkdir(dirname($abspath)) )
			throw new Exception("Could not create output directory '".dirname($abspath)."'");


		$qr = new Image_QRCode();
		$resource = $qr->makeCode($options['string'], array(
			'module_size' => $options['size'],
			'output_type' => 'return',
		));

		if ( $options['filetype'] == 'jpeg' )
			$created = imagejpeg($resource, $abspath, $options['quality']);
		else
			$created = imagepng($resource, $abspath, $options['quality']);

		if ( !$created )
			throw new Exception("Unable to create " . $options['filetype'] . " of quality " . $options['quality'] . " at filepath '" . $abspath . "'");
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
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
    $sp = strtolower($_SERVER["SERVER_PROTOCOL"]);
    $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
    $port = in_array($_SERVER["SERVER_PORT"], array("80", '443')) ? "" : (":".$_SERVER["SERVER_PORT"]);
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $port . $_SERVER['REQUEST_URI'];
}