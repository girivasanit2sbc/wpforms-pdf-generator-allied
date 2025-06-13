<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WPForms_PDF_Allied_Admin {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action( 'wpforms_entry_details_sidebar_actions', [ $this, 'display_pdf_action_buttons_in_sidebar' ], 10, 2 );
    }

    public function display_pdf_action_buttons_in_sidebar( $entry, $form_data ) {
        $pdf_url = '';
        $entry_id_for_meta_lookup = 0;

        if ( is_object($entry) && isset($entry->entry_id) ) {
            $entry_id_for_meta_lookup = $entry->entry_id;
        } elseif ( is_array($entry) && isset($entry['entry_id']) ) {
            $entry_id_for_meta_lookup = $entry['entry_id'];
        } elseif ( is_array($entry) && isset($entry['id']) ) { 
             $entry_id_for_meta_lookup = $entry['id'];
        }
        
        if ( $entry_id_for_meta_lookup ) {
            if ( function_exists( 'wpforms_get_entry_meta' ) ) {
                $meta_value = wpforms_get_entry_meta( $entry_id_for_meta_lookup , 'allied_generated_pdf_path', true );
                if ( !empty($meta_value) && is_string($meta_value) && filter_var($meta_value, FILTER_VALIDATE_URL) ) { 
                    $pdf_url = $meta_value;
                }
            } 
            elseif ( function_exists( 'wpforms' ) && isset( wpforms()->entry_meta ) && method_exists( wpforms()->entry_meta, 'get_meta' ) ) {
                $entry_metas = wpforms()->entry_meta->get_meta( [
                    'entry_id' => $entry_id_for_meta_lookup,
                    'type'     => 'allied_generated_pdf_path', 
                    'number'   => 1, 
                ] );
                if ( !empty( $entry_metas ) && is_array( $entry_metas ) && isset( $entry_metas[0]->data ) ) {
                    $retrieved_url = $entry_metas[0]->data; 
                    if (is_string($retrieved_url) && filter_var($retrieved_url, FILTER_VALIDATE_URL)) {
                        $pdf_url = $retrieved_url;
                    }
                }
            }
        }

        if ( ! empty( $pdf_url ) ) {
            $filename = basename( $pdf_url );
            echo '<p><a href="' . esc_url( $pdf_url ) . '" class="" target="_blank" style="text-align:center;"><span class="dashicons dashicons-pdf"></span>' . esc_html__( 'View PDF ', 'wpforms-pdf-allied' ) . '</a></p>';
            echo '<p><a href="' . esc_url( $pdf_url ) . '" class="" target="_blank" download="' . esc_attr($filename) . '" style="text-align:center;"><span class="dashicons dashicons-download"></span>' . esc_html__( 'Download PDF ', 'wpforms-pdf-allied' ) . '</a> </p>';
            echo '<p style="font-size:0.9em; text-align:center; color:#777;">File: ' . esc_html($filename) . '</p>';
        } else {
            echo '<p style="margin-top:10px; color: #888; text-align:center;">PDF not available for this entry.</p>';
        }
    }
}