<?php
/**
 * Twenty Twenty-Two functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Two
 * @since Twenty Twenty-Two 1.0
 */


if ( ! function_exists( 'twentytwentytwo_support' ) ) :

	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * @since Twenty Twenty-Two 1.0
	 *
	 * @return void
	 */
	function twentytwentytwo_support() {

		// Add support for block styles.
		add_theme_support( 'wp-block-styles' );

		// Enqueue editor styles.
		add_editor_style( 'style.css' );

	}

endif;

add_action( 'after_setup_theme', 'twentytwentytwo_support' );

if ( ! function_exists( 'twentytwentytwo_styles' ) ) :

	/**
	 * Enqueue styles.
	 *
	 * @since Twenty Twenty-Two 1.0
	 *
	 * @return void
	 */
	function twentytwentytwo_styles() {
		// Register theme stylesheet.
		$theme_version = wp_get_theme()->get( 'Version' );

		$version_string = is_string( $theme_version ) ? $theme_version : false;
		wp_register_style(
			'twentytwentytwo-style',
			get_template_directory_uri() . '/style.css',
			array(),
			$version_string
		);

		// Enqueue theme stylesheet.
		wp_enqueue_style( 'twentytwentytwo-style' );

	}

endif;

add_action( 'wp_enqueue_scripts', 'twentytwentytwo_styles' );

// Add block patterns
require get_template_directory() . '/inc/block-patterns.php';

function formulaire_contact() {
   ob_start();
   // Copiez et collez le code de votre formulaire ici
   
if (isset($_POST['submit'])) {
   $to = "votre_email@votresite.com";
   $subject = "Nouveau message de contact";
   $name = $_POST['name'];
   $email = $_POST['email'];
   $message = $_POST['message'];
   $headers = "From: " . $name . " <" . $email . ">\r\n";
   $headers .= "Reply-To: " . $email . "\r\n";
   $headers .= "Content-Type: text/html\r\n";
   $email_body = "Nouveau message de contact reçu :<br><br>";
   $email_body .= "<strong>Nom :</strong> " . $name . "<br>";
   $email_body .= "<strong>Email :</strong> " . $email . "<br>";
   $email_body .= "<strong>Message :</strong><br>" . nl2br($message);
   mail($to, $subject, $email_body, $headers);
   echo "Votre message a été envoyé avec succès.";
}



   $output = ob_get_clean();
   return $output;
}
add_shortcode('contact', 'formulaire_contact');
