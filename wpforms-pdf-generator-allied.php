<?php
/**
 * Plugin Name: WPForms PDF Generator - Allied Modular
 * Plugin URI: https://alliedmodular.com/
 * Description: Generates a PDF from WPForms submissions and adds View/Download buttons in the admin entry view.
 * Version: 2.0.0
 * Author: Girivasan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'WPFORMS_PDF_ALLIED_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPFORMS_PDF_ALLIED_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPFORMS_PDF_ALLIED_UPLOAD_DIR_NAME', 'wpforms-allied-pdfs' );

register_activation_hook( __FILE__, 'wpforms_pdf_allied_activate' );
function wpforms_pdf_allied_activate() {
    $upload_dir = wp_upload_dir();
    $pdf_storage_path = $upload_dir['basedir'] . '/' . WPFORMS_PDF_ALLIED_UPLOAD_DIR_NAME;
    $mpdf_temp_dir = $upload_dir['basedir'] . '/mpdf_temp';
    if ( ! is_dir( $pdf_storage_path ) ) wp_mkdir_p( $pdf_storage_path );
    if (!is_dir($mpdf_temp_dir)) wp_mkdir_p($mpdf_temp_dir);
}

// Check if mPDF is available
if ( file_exists( WPFORMS_PDF_ALLIED_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once WPFORMS_PDF_ALLIED_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>WPForms PDF Generator - Allied Modular:</strong> The mPDF library is missing. Please run <code>composer install</code> in the plugin directory.';
        echo '</p></div>';
    });
    // if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) error_log("WPForms PDF Main: mPDF vendor/autoload.php NOT FOUND. Plugin disabled.");
    return; 
}

// Include the class files
require_once WPFORMS_PDF_ALLIED_PLUGIN_DIR . 'includes/class-wpforms-pdf-allied-generator.php';
require_once WPFORMS_PDF_ALLIED_PLUGIN_DIR . 'includes/class-wpforms-pdf-allied-admin.php';

// Initialize the classes
if ( class_exists( 'WPForms_PDF_Allied_Generator' ) ) {
    WPForms_PDF_Allied_Generator::instance();
} else {
    // if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) error_log("WPForms PDF Main: CRITICAL - WPForms_PDF_Allied_Generator class not found.");
}

if ( class_exists( 'WPForms_PDF_Allied_Admin' ) ) {
    WPForms_PDF_Allied_Admin::instance();
} else {
    // if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) error_log("WPForms PDF Main: CRITICAL - WPForms_PDF_Allied_Admin class not found.");
}