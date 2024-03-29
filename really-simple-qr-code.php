<?php
/*

Plugin Name: Really Simple QR Code
Plugin URI: http://www.flynsarmy.com
Version: 3.1.0
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
function rsqrcode(array $atts): string
{
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
        $atts['quality'],
        $atts['inline']
    );

    // Generate the image HTML
    $html = '<img src="'.site_url($relpath).'" ';
    // Add all our image attributes
    foreach ($atts as $att=>$value) {
        $html .= $att.'="'.htmlspecialchars($value).'" ';
    }
    $html .= '/>';

    return $html;
}

/**
 * Generate and cache a QR code image
 *
 * @param array{
 *      ?size: int,
 *      ?cache: bool,
 *      ?dir: string,
 *      'string': string,
 *      ?filetype: string,
 *      ?quality: int
 * } $options array(
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
function rsqrcode_generate(array $options): string
{
    if (!is_array($options)) {
        throw new Exception("You must specify an options array");
    }

    $upload_dir = wp_upload_dir();
    $defaults = array(
        'width' => 256,
        'height' => 256,
        'margin' => 0,
        'cache' => true,
        'dir' => 'wp-content' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'rsqrcode',
        'string' => rsqrcode_current_url(),
        'inline' => false,
    );
    $options = array_merge($defaults, $options);

    // Validation/Sanitization
    $options['cache'] = false;//!!$options['cache'];
    $options['width'] = intval($options['width']);
    $options['height'] = intval($options['height']);
    $options['margin'] = intval($options['margin']);
    $options['dir'] = trim($options['dir'], DIRECTORY_SEPARATOR);
    $options['inline'] = !!$options['inline'];

    if (empty($options['string'])) {
        throw new Exception("You must specify a string option");
    }

    $relpath = $options['dir'] . DIRECTORY_SEPARATOR . $options['width'] . 'x' . $options['height'] . '_' . md5($options['string']) . '.png';
    $abspath = ABSPATH . DIRECTORY_SEPARATOR . $relpath;

    if (!$options['cache'] || $options['inline'] || !file_exists($abspath)) {
        // Create output directory if it doesn't already exist
        if (!$options['inline'] && !is_dir(dirname($abspath)) && !mkdir(dirname($abspath))) {
            throw new Exception("Could not create output directory '".dirname($abspath)."'");
        }

        $writer = rsqrcode_get_writer($options['width'], $options['margin']);

        if ($options['inline']) {
            $relpath = 'data:image/png;charset=binary;base64,' . base64_encode($writer->writeString($options['string']));
        } else {
            $writer->writeFile($options['string'], $abspath);
        }
    }

    return $relpath;
}

/**
 * Undocumented function
 *
 * @param integer $width
 * @param integer $margin
 * @return \BaconQrCode\Writer
 */
function rsqrcode_get_writer(int $width, int $margin): \BaconQrCode\Writer
{
    require_once __DIR__.'/vendor/autoload.php';

    $renderer = new \BaconQrCode\Renderer\ImageRenderer(
        new \BaconQrCode\Renderer\RendererStyle\RendererStyle($width, $margin),
        new BaconQrCode\Renderer\Image\ImagickImageBackEnd
    );

    return new \BaconQrCode\Writer($renderer);
}

/**
 * Returns the current URL
 *
 * @return string     current URL
 */
function rsqrcode_current_url(): string
{
    $s = @$_SERVER["HTTPS"] == 'on' ? 's' : '';
    $sp = strtolower($_SERVER["SERVER_PROTOCOL"]);
    $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
    $port = in_array($_SERVER["SERVER_PORT"], array("80", '443')) ? "" : (":".$_SERVER["SERVER_PORT"]);
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $port . $_SERVER['REQUEST_URI'];
}
