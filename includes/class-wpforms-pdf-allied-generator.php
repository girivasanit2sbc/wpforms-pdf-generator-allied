<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WPForms_PDF_Allied_Generator {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action( 'wpforms_process_complete', [ $this, 'generate_pdf_on_submission' ], 10, 4 );
    }

    public function generate_pdf_on_submission( $fields, $entry_data_array, $form_data, $entry_id ) {
        // if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) error_log("WPForms PDF Gen: generate_pdf_on_submission called for Entry ID: " . $entry_id);
        
        $quote_number_val = '';
        // ... (quote number extraction logic - keep as is) ...
        if (is_array($fields)) {
            foreach ( $fields as $field_id_loop => $field_submitted_data_loop ) {
                if (isset($form_data['fields'][$field_id_loop]['label'])) {
                    $field_label_loop = $form_data['fields'][$field_id_loop]['label'];
                    if ( strtolower( $field_label_loop ) === 'quote number' ) {
                        $current_field_value = is_array($field_submitted_data_loop) && isset($field_submitted_data_loop['value']) ? $field_submitted_data_loop['value'] : (is_string($field_submitted_data_loop) ? $field_submitted_data_loop : '');
                        if (!empty($current_field_value)) {
                             $quote_number_val = sanitize_file_name( $current_field_value );
                             break;
                        }
                    }
                }
            }
        }
        if ( empty( $quote_number_val ) ) $quote_number_val = 'entry-' . $entry_id;


        $filename_base = sanitize_title( $form_data['settings']['form_title'] ) . '-' . $quote_number_val . '.pdf';
        
        $upload_dir = wp_upload_dir(); // Get WordPress upload directory info
        $pdf_storage_path = $upload_dir['basedir'] . '/' . WPFORMS_PDF_ALLIED_UPLOAD_DIR_NAME;
        $pdf_filepath = $pdf_storage_path . '/' . $filename_base;
        $pdf_fileurl  = $upload_dir['baseurl'] . '/' . WPFORMS_PDF_ALLIED_UPLOAD_DIR_NAME . '/' . $filename_base;

        if ( ! is_dir( $pdf_storage_path ) ) wp_mkdir_p( $pdf_storage_path );
        $mpdf_temp_dir = $upload_dir['basedir'] . '/mpdf_temp';
        if (!is_dir($mpdf_temp_dir)) wp_mkdir_p($mpdf_temp_dir);

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8', 'format' => 'A4',
                'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 28, 'margin_bottom' => 20,
                'tempDir' => $mpdf_temp_dir,
            ]);
            
            $html_content = $this->get_pdf_html_content( $fields, $form_data, $entry_id, $upload_dir ); // Pass $upload_dir

            // --- HEADER LOGO using SERVER PATH ---
            $logo_relative_path_from_wp_content = 'uploads/2024/08/long-logo.png'; // As per your URL
            $logo_server_path = WP_CONTENT_DIR . '/' . $logo_relative_path_from_wp_content;
            $logo_src_for_header = 'https://alliedmodular.com/wp-content/uploads/2024/08/long-logo.png'; // Fallback URL

            if (file_exists($logo_server_path)) {
                // if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) error_log("WPForms PDF Gen: Using server path for header logo: " . $logo_server_path);
                $logo_src_for_header = $logo_server_path;
            } else {
                // if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) error_log("WPForms PDF Gen: Server path for header logo NOT FOUND: " . $logo_server_path . ". Falling back to URL.");
            }
            // --- END HEADER LOGO ---

            $header_html = '<div style="width: 100%; overflow: auto; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px;"><div style="float: left; width: 70%;"><img src="' . esc_attr($logo_src_for_header) . '" style="height: 45px;"></div><div style="float: right; width: 30%; text-align: right; font-size: 9pt; color: #555; padding-top:10px;">' . date_i18n( get_option( 'date_format' ) ) . '</div></div>';
            $mpdf->SetHTMLHeader($header_html);
            $footer_html = '<div style="text-align: right; font-size: 8pt; color: #777; width:100%;">{PAGENO}/{nbpg}</div>';
            $mpdf->SetHTMLFooter($footer_html);

            $mpdf->WriteHTML( $html_content );
            $mpdf->Output( $pdf_filepath, \Mpdf\Output\Destination::FILE );

            // ... (Meta saving logic - keep as is, it was working) ...
            $meta_saved_correctly = false;
            if ( function_exists( 'wpforms_update_entry_meta' ) ) {
                wpforms_update_entry_meta( $entry_id, 'allied_generated_pdf_path', $pdf_fileurl, $form_data['id'] );
                $meta_saved_correctly = true;
            }
            elseif ( function_exists( 'wpforms' ) && isset( wpforms()->entry_meta ) && method_exists( wpforms()->entry_meta, 'add' ) ) {
                $user_id_to_store = isset($entry_data_array['user_id']) ? $entry_data_array['user_id'] : (is_user_logged_in() ? get_current_user_id() : 0);
                $args = ['entry_id' => $entry_id, 'form_id' => absint( $form_data['id'] ), 'user_id' => $user_id_to_store, 'type' => 'allied_generated_pdf_path', 'data' => $pdf_fileurl];
                $meta_id = wpforms()->entry_meta->add( $args );
                if ($meta_id) $meta_saved_correctly = true;
            } 
            // if (!$meta_saved_correctly && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) error_log("WPForms PDF Gen: CRITICAL - Failed to save PDF meta for Entry ID: " . $entry_id);


        } catch ( \Mpdf\MpdfException $e ) { /* ... error logging ... */ } 
          catch ( Exception $e ) { /* ... error logging ... */ }
    }

    private function get_pdf_html_content( $submitted_field_values, $form_data, $entry_id, $wp_upload_dir_info ) {
        // $wp_upload_dir_info is passed from the calling function
        $html = '<style> /* ... CSS styles ... */ </style>'; // Keep your existing CSS
        $html = '<style>body { font-family: sans-serif; font-size: 10pt; color: #333; } .form-title-pdf { font-size: 14pt; color: #003366; margin-bottom: 15px; text-align: center; } .entry-info-pdf { font-size:9pt; text-align:center; margin-bottom:20px; color: #444; } .entry-details-table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; } .entry-details-table th, .entry-details-table td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; font-size: 9pt; word-wrap: break-word; } .entry-details-table th { background-color: #f2f2f2; font-weight: bold; width: 30%; } .entry-details-table td { width: 70%; } .field-value-image { max-width: 100%; height: auto; max-height: 150px; border: 1px solid #ccc; padding: 3px; margin-top: 5px; display: block; } hr.pdf-separator { border: 0; border-top: 1px solid #ccc; margin: 20px 0; }</style>';
        // Removed .customer-provided-section styles as you removed those images
        $html .= '<body>';
        $html .= '<h1 class="form-title-pdf">' . esc_html($form_data['settings']['form_title']) . ' Submission</h1>';
        // ... (quote number display logic - keep as is) ...
        $quote_number_display = 'N/A';
        if (is_array($submitted_field_values)) {
            foreach ($submitted_field_values as $field_id_for_quote => $field_data_for_quote) {
                if (isset($form_data['fields'][$field_id_for_quote]['label'])) {
                    $field_label_for_quote = $form_data['fields'][$field_id_for_quote]['label'];
                    if (strtolower($field_label_for_quote) === 'quote number') {
                        $current_quote_val = is_array($field_data_for_quote) && isset($field_data_for_quote['value']) ? $field_data_for_quote['value'] : (is_string($field_data_for_quote) ? $field_data_for_quote : '');
                        if(!empty($current_quote_val)){ $quote_number_display = esc_html($current_quote_val); break; }
                    }
                }
            }
        }
        $html .= '<p class="entry-info-pdf">Entry ID: ' . esc_html($entry_id) . '  |  Quote Number: ' . $quote_number_display . '</p>';
        $html .= '<table class="entry-details-table"><thead><tr><th>Field Label</th><th>Value Submitted</th></tr></thead><tbody>';

        if (is_array($submitted_field_values)) {
            foreach ( $submitted_field_values as $field_id => $s_field_data ) {
                $field_settings = isset( $form_data['fields'][ $field_id ] ) ? $form_data['fields'][ $field_id ] : null;
                if ( ! $field_settings ) continue; 
                $skip_types = ['divider', 'html', 'pagebreak', 'captcha', 'entry_preview', 'password', 'hidden'];
                if ( in_array( $field_settings['type'], $skip_types ) ) continue;
                $field_label = ! empty( $field_settings['label'] ) ? esc_html( $field_settings['label'] ) : '<em>Field ID: ' . esc_html($field_id) . '</em>';
                
                $field_value_text = ''; 
                $submitted_image_url_from_form = null; // URL from $s_field_data['image']
                
                if (is_array($s_field_data)) {
                    if (isset($s_field_data['value'])) $field_value_text = $s_field_data['value'];
                    // This is where we get the image URL from WPForms "Use image choices"
                    if (isset($s_field_data['image']) && is_string($s_field_data['image']) && filter_var($s_field_data['image'], FILTER_VALIDATE_URL)) {
                        $submitted_image_url_from_form = $s_field_data['image'];
                    }
                } elseif (is_string($s_field_data)) {
                    $field_value_text = $s_field_data;
                }

                if ( $field_value_text === '' && is_null($submitted_image_url_from_form) && !is_numeric($field_value_text) ) continue;

                $processed_value_html = '';
                $image_src_for_pdf = null; // This will hold either a server path or a URL

                // --- DYNAMIC IMAGE PATH/URL LOGIC ---
                if (!is_null($submitted_image_url_from_form) && preg_match('/\.(jpeg|jpg|gif|png|svg)(\?.*)?$/i', $submitted_image_url_from_form)) {
                    // Attempt to convert URL to server path
                    $image_path_relative_to_uploads = str_replace($wp_upload_dir_info['baseurl'], '', $submitted_image_url_from_form);
                    $image_server_path = $wp_upload_dir_info['basedir'] . $image_path_relative_to_uploads;
                    $image_server_path_clean = strtok($image_server_path, '?'); // Remove query strings for file_exists

                    if (file_exists($image_server_path_clean)) {
                        // if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) error_log("WPForms PDF Gen: Using server path for dynamic image '".$field_label."': " . $image_server_path_clean);
                        $image_src_for_pdf = $image_server_path_clean;
                    } else {
                        // if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) error_log("WPForms PDF Gen: Server path for dynamic image '".$field_label."' NOT FOUND (" . $image_server_path_clean . "). Falling back to URL: " . $submitted_image_url_from_form);
                        $image_src_for_pdf = $submitted_image_url_from_form; // Fallback, might be blocked by Cloudflare
                    }
                    
                    if ($image_src_for_pdf) {
                        $processed_value_html = '<img src="' . esc_attr($image_src_for_pdf) . '" alt="' . esc_attr($field_label) . '" class="field-value-image">';
                        if ($field_value_text !== '' && $field_value_text !== $submitted_image_url_from_form) {
                             $processed_value_html .= '<br><small>Selected: ' . esc_html($field_value_text) . '</small>';
                        }
                    }
                } 
                // Fallback for general text processing or if image logic didn't produce HTML
                if (empty($processed_value_html) && is_string($field_value_text) && $field_value_text !== '') {
                    $value_lines = explode("\n", trim($field_value_text)); 
                    $line_html_parts = [];
                    foreach($value_lines as $line) {
                        $trimmed_line = trim($line);
                        if ($trimmed_line === '') continue;
                        // Check if this text itself is an image URL (e.g., from a text field where user pasted a URL)
                        if (filter_var($trimmed_line, FILTER_VALIDATE_URL) && preg_match('/\.(jpeg|jpg|gif|png|svg)(\?.*)?$/i', $trimmed_line)) {
                            // For these, we probably still want to try server path conversion if they are on the same domain
                            $generic_image_server_path = str_replace($wp_upload_dir_info['baseurl'], $wp_upload_dir_info['basedir'], $trimmed_line);
                            $generic_image_server_path_clean = strtok($generic_image_server_path, '?');
                            if (strpos($trimmed_line, $wp_upload_dir_info['baseurl']) === 0 && file_exists($generic_image_server_path_clean)) {
                                $line_html_parts[] = '<img src="' . esc_attr($generic_image_server_path_clean) . '" alt="' . esc_attr($field_label) . '" class="field-value-image">';
                            } else {
                                $line_html_parts[] = '<img src="' . esc_url($trimmed_line) . '" alt="' . esc_attr($field_label) . '" class="field-value-image">'; // Fallback to URL
                            }
                        } elseif (filter_var($trimmed_line, FILTER_VALIDATE_URL) && preg_match('/\.(pdf|doc|docx|xls|xlsx|txt|zip)(\?.*)?$/i', $trimmed_line)) {
                            $line_html_parts[] = '<a href="' . esc_url($trimmed_line) . '" target="_blank">' . esc_html(basename($trimmed_line)) . '</a>';
                        } else {
                            $line_html_parts[] = nl2br(esc_html($trimmed_line));
                        }
                    }
                    $processed_value_html = implode('<br>', $line_html_parts);
                }
                // --- END DYNAMIC IMAGE PATH/URL LOGIC ---

                if (trim(strip_tags($processed_value_html, '<img><a>')) === '') continue;
                $html .= '<tr><td>' . $field_label . '</td><td>' . $processed_value_html . '</td></tr>';
            }
        } else {
            $html .= '<tr><td colspan="2" style="text-align:center;">No field data was submitted or available for display.</td></tr>';
        }
        $html .= '</tbody></table>';
        // Removed the static "Customer Provided" images section as per your request
        // $html .= '<hr class="pdf-separator"><div class="customer-provided-section">...</div>';
        $html .= '</body>';
        return $html;
    }
}