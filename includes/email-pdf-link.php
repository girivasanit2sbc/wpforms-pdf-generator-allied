<?php
/**
 * WPForms PDF Generator - Email PDF Link Functionality
 *
 * This file handles adding a link to the generated PDF in WPForms email notifications.
 * It uses the wpforms_email_message filter.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Callback function to modify WPForms email message content.
 * Replaces ##PDF_LINK_PLACEHOLDER## with the actual PDF link.
 */
if ( ! function_exists( 'wpfga_add_pdf_link_to_email_via_message_filter_external' ) ) {
    function wpfga_add_pdf_link_to_email_via_message_filter_external( $message, $wpforms_email_obj ) {
        // This function will only be called if the wpforms_email_message filter fires AND calls this callback.
        $can_log_email = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;

        if ($can_log_email) error_log('WPFGA Email CB: wpfga_add_pdf_link_to_email_via_message_filter_external FIRED.');
        if ($can_log_email) error_log('WPFGA Email CB: Entry ID from $wpforms_email_obj: ' . (isset($wpforms_email_obj->entry_id) ? $wpforms_email_obj->entry_id : 'Not set'));
        if ($can_log_email) error_log('WPFGA Email CB: Original message (first 300 chars): ' . substr(esc_html($message), 0, 300));


        if (strpos($message, '##PDF_LINK_PLACEHOLDER##') === false) {
            if ($can_log_email) error_log('WPFGA Email CB: Placeholder ##PDF_LINK_PLACEHOLDER## not found in message. Returning original message.');
            return $message;
        }
        if ($can_log_email) error_log('WPFGA Email CB: Placeholder ##PDF_LINK_PLACEHOLDER## FOUND in message.');


        if ( empty( $wpforms_email_obj->entry_id ) ) {
            if ($can_log_email) error_log('WPFGA Email CB: No entry ID in $wpforms_email_obj. Replacing placeholder with generic message.');
            $message = str_replace( '##PDF_LINK_PLACEHOLDER##', '<p>PDF will be generated for your entry. Entry ID not available to create specific link at this stage.</p>', $message );
            return $message;
        }

        $entry_id = absint( $wpforms_email_obj->entry_id );
        $pdf_url = '';

        // Logic to retrieve the PDF URL from entry meta
        if ( function_exists( 'wpforms_get_entry_meta' ) ) {
            if ($can_log_email) error_log('WPFGA Email CB: Using wpforms_get_entry_meta for entry ID: ' . $entry_id);
            $meta_value = wpforms_get_entry_meta( $entry_id, 'allied_generated_pdf_path', true );
            if ($can_log_email) error_log('WPFGA Email CB: Meta value from wpforms_get_entry_meta: ' . print_r($meta_value, true));
            if ( !empty($meta_value) && is_string($meta_value) && filter_var($meta_value, FILTER_VALIDATE_URL) ) { 
                $pdf_url = $meta_value;
            }
        } elseif ( function_exists( 'wpforms' ) && isset( wpforms()->entry_meta ) && method_exists( wpforms()->entry_meta, 'get_meta' ) ) {
            if ($can_log_email) error_log('WPFGA Email CB: Using wpforms()->entry_meta->get_meta() for entry ID: ' . $entry_id);
            $entry_metas = wpforms()->entry_meta->get_meta( [
                'entry_id' => $entry_id,
                'type'     => 'allied_generated_pdf_path', 
                'number'   => 1, 
            ] );
            if ($can_log_email) error_log('WPFGA Email CB: Meta value from wpforms()->entry_meta->get_meta(): ' . print_r($entry_metas, true));
            if ( !empty( $entry_metas ) && is_array( $entry_metas ) && isset( $entry_metas[0]->data ) ) {
                $retrieved_url = $entry_metas[0]->data; 
                if (is_string($retrieved_url) && filter_var($retrieved_url, FILTER_VALIDATE_URL)) {
                    $pdf_url = $retrieved_url;
                }
            }
        } else {
            if ($can_log_email) error_log('WPFGA Email CB: No known function to retrieve entry meta.');
        }

        if ($can_log_email) error_log('WPFGA Email CB: PDF URL retrieved for entry ' . $entry_id . ': ' . $pdf_url);

        if ( ! empty( $pdf_url ) ) {
            $filename = basename( $pdf_url );
            $pdf_link_html = '<p>You can view or download your generated PDF quote here:<br>';
            $pdf_link_html .= '<a href="' . esc_url( $pdf_url ) . '" target="_blank" style="color: #0073aa; text-decoration: underline;">View/Download PDF: ' . esc_html($filename) . '</a></p>';
            $message = str_replace( '##PDF_LINK_PLACEHOLDER##', $pdf_link_html, $message );
            if ($can_log_email) error_log('WPFGA Email CB: PDF link HTML successfully replaced placeholder for entry ' . $entry_id);
        } else {
            $message = str_replace( '##PDF_LINK_PLACEHOLDER##', '<p>Your PDF quote is being processed. If you do not receive a link shortly or cannot find it, please contact us referencing Entry ID: ' . esc_html($entry_id) . '</p>', $message );
            if ($can_log_email) error_log('WPFGA Email CB: PDF URL not found for entry ' . $entry_id . '. Placeholder replaced with notice message.');
        }
        return $message;
    }
}

/**
 * Sets up the filter for modifying email messages.
 * This function is hooked to 'plugins_loaded'.
 */
if ( ! function_exists( 'wpfga_setup_email_message_filter_external_init' ) ) {
    function wpfga_setup_email_message_filter_external_init() {
        static $filter_added_in_this_request = false; // Static variable for this request

        if ( $filter_added_in_this_request ) {
            // This log helps confirm the static flag is working if plugins_loaded fires this multiple times
            // if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) error_log('WPFGA Email PL Setup: Filter already set up in this request (by wpfga_setup_email_message_filter_external_init). Skipping duplicate add_filter call.');
            return;
        }

        add_filter( 'wpforms_email_message', 'wpfga_add_pdf_link_to_email_via_message_filter_external', 20, 2 );
        $filter_added_in_this_request = true; 

        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('WPFGA Email PL Setup: Filter wpforms_email_message ADDED for wpfga_add_pdf_link_to_email_via_message_filter_external (First time in this request by this function).');
        }
    }
}
// Hook the setup function to plugins_loaded.
add_action( 'plugins_loaded', 'wpfga_setup_email_message_filter_external_init', 15 );

// Final log in this file to confirm it was parsed and the action to plugins_loaded was set up.
// if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
//    error_log('WPFGA Email (email-pdf-link.php): Reached end of file. Action for plugins_loaded (wpfga_setup_email_message_filter_external_init) has been registered.');
//}