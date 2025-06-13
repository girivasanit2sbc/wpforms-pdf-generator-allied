=== WPForms PDF Generator - Allied Modular ===
Contributors: Your Name
Tags: wpforms, pdf, generator, allied modular, quote
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generates a PDF from WPForms submissions for Allied Modular and adds a download link to entries. Dynamically displays all submitted fields.

== Description ==

This plugin hooks into WPForms submissions. When a form is submitted, it:
1. Generates a PDF document containing all the submitted field labels and their values.
2. Includes the Allied Modular logo and submission date in the PDF header.
3. Includes page numbers in the PDF footer.
4. Displays static "Customer Provided" images at the end of the PDF.
5. Saves the PDF to the `wp-content/uploads/wpforms-allied-pdfs/` directory.
6. Names the PDF file using the form title and the value from a field labeled "Quote Number" (or defaults to entry ID).
7. Adds a download link for the generated PDF on the WPForms entry detail page in the WordPress admin.

It uses the mPDF library for PDF generation.

== Installation ==

1.  **Using Composer (Recommended):**
    *   Place the `wpforms-pdf-generator-allied` folder into your `wp-content/plugins/` directory.
    *   Navigate to the `wpforms-pdf-generator-allied` directory in your terminal.
    *   Run `composer install`. This will download mPDF and its dependencies into a `vendor` folder.
    *   Activate the plugin through the 'Plugins' menu in WordPress.

2.  **Manual Installation (If you already have the `vendor` directory):**
    *   Upload the entire `wpforms-pdf-generator-allied` folder (including the `vendor` directory with mPDF) to the `/wp-content/plugins/` directory.
    *   Activate the plugin through the 'Plugins' menu in WordPress.

3.  Ensure the `wp-content/uploads/` directory and its subdirectories `wpforms-allied-pdfs` and `mpdf_temp` are writable by your web server. The plugin attempts to create these on activation.

== Configuration ==

*   To use a specific quote number in the PDF filename, ensure your WPForm has a field with the exact label "Quote Number".
*   The Allied Modular logo URL is hardcoded. If it changes, it needs to be updated in `includes/class-wpforms-pdf-allied-generator.php`.
*   The "Customer Provided" images at the end of the PDF are also hardcoded.

== Changelog ==

= 1.1.0 =
* Modified PDF content generation to dynamically display all submitted fields and their values.
* Updated PDF styling for a generic field display.
* Added page numbers to PDF footer.
* Improved Quote Number detection for filename.
* Ensured mPDF temp directory is created on activation.

= 1.0.1 =
* Added check for mPDF temp directory writability.
* Minor fix for relative path storage for older WPForms.

= 1.0.0 =
* Initial release. PDF generation based on predefined fields from sample.