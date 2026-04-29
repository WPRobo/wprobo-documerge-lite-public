=== WPRobo DocuMerge Lite ===
Contributors: alishanvr, wprobo
Tags: document automation, pdf generator, form to pdf, document merge, word template
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free document automation for WordPress. Upload DOCX templates, collect data via forms, and deliver personalised PDF or DOCX documents automatically.

== Description ==

**WPRobo DocuMerge Lite** is a free WordPress plugin that automates document generation from form submissions — no SaaS subscription, no monthly fees.

Upload a Word/DOCX template with merge tags like `{client_name}` or `{email}`, connect it to a form, and on every submission the plugin fills those tags with submitted data and delivers a completed document as DOCX or PDF.

= Why DocuMerge Lite? =

SaaS platforms like Formstack Documents and Gavel charge $1,000–$2,000/year for document automation. DocuMerge Lite gives you the core functionality completely free, self-hosted on your own WordPress site.

= Free Features =

* **DOCX Template Upload** — upload existing Word templates with merge tags
* **Automatic Tag Detection** — plugin scans your template and finds all `{merge_tags}`
* **Built-in Form Builder** — drag-and-drop form builder with 6 field types (Text, Textarea, Email, Phone, Number, Date)
* **Merge Tag Mapping** — visually map template tags to form fields
* **PDF Output** — generate PDF documents from submissions via mPDF
* **DOCX Output** — generate completed DOCX documents via PHPWord
* **Browser Download** — submitters can download their document immediately
* **Submissions Archive** — view all submissions with document download links
* **Shortcode Embed** — embed forms anywhere with `[wprobo_documerge_form id="X"]`
* **Gutenberg Block** — embed forms via the block editor
* **Setup Wizard** — guided first-run configuration
* **Fully Translatable** — complete .pot file included
* **Multisite Compatible** — works on WordPress multisite installations
* **No External Dependencies** — everything runs on your server

= Pro Features (Upgrade Available) =

Need more power? [Upgrade to WPRobo DocuMerge Pro](https://wprobo.com/plugins/wprobo-documerge/) to unlock:

* **Signature Field** — canvas-based signature capture embedded in documents
* **Payment Field** — charge via Stripe before delivering documents
* **Dropdown, Radio & Checkbox Fields** — advanced choice fields for forms
* **Conditional Logic** — show/hide form fields based on user input
* **Conditional Document Sections** — `{if:field == value}...{/if}` blocks in templates
* **Multi-Step Forms** — break long forms into guided steps with progress bar
* **Email Delivery** — automatically email documents to submitters
* **Media Library Storage** — save generated documents to WordPress media
* **Google reCAPTCHA v2/v3** — protect forms from spam submissions
* **hCaptcha Support** — privacy-focused CAPTCHA alternative
* **WPForms Integration** — trigger document generation from WPForms submissions
* **Contact Form 7 Integration** — trigger from CF7 submissions
* **Gravity Forms Integration** — trigger from Gravity Forms submissions
* **Fluent Forms Integration** — trigger from Fluent Forms submissions
* **Priority Support** — direct support from the WPRobo team

[View Pro Features and Pricing](https://wprobo.com/plugins/wprobo-documerge/)

= Source Code =

The full, unminified source code for this plugin (including SCSS and uncompressed JavaScript) is publicly available at:

https://github.com/WPRobo/wprobo-documerge-lite-public

Third-party libraries bundled with the plugin are sourced from their official upstream repositories:

* Chart.js — https://github.com/chartjs/Chart.js
* intl-tel-input — https://github.com/jackocnr/intl-tel-input
* Flatpickr — https://github.com/flatpickr/flatpickr
* Select2 — https://github.com/select2/select2
* Signature Pad — https://github.com/szimek/signature_pad
* PHPWord — https://github.com/PHPOffice/PHPWord (via Composer)
* mPDF — https://github.com/mpdf/mpdf (via Composer)

= Build Instructions =

To build the plugin from source:

1. `git clone https://github.com/WPRobo/wprobo-documerge-lite-public.git`
2. `cd wprobo-documerge-lite-public`
3. `composer install --no-dev` (installs PHPWord, mPDF and other PHP dependencies)
4. `npm install` (installs the build toolchain — sass, terser)
5. `npm run build` (compiles SCSS → CSS and minifies JS, writing to `assets/css/` and `assets/js/`)

Alternatively, run `bash deploy.sh` from the repository root to execute all of the above and produce a distribution ZIP under `production/`.

= Use Cases =

* Law firms — client intake forms that generate signed contracts
* Education — student applications that produce enrolment documents
* HR — employee onboarding forms that generate offer letters
* Real estate — buyer inquiries that produce tenancy agreements
* Consulting — proposal forms that generate branded proposals
* Any business — automate repetitive document creation from form data

= How It Works =

1. **Upload** a Word/DOCX template with merge tags like `{client_name}`
2. **Create** a form using the built-in drag-and-drop form builder
3. **Map** each template tag to a form field
4. **Embed** the form on any page using a shortcode or Gutenberg block
5. **Done** — every submission generates a personalised document automatically

== Installation ==

1. Upload the `wprobo-documerge-lite` folder to `/wp-content/plugins/` or install directly from the WordPress plugin directory
2. Activate the plugin through the Plugins menu in WordPress
3. Follow the setup wizard that appears on first activation
4. Upload your first DOCX template via DocuMerge → Templates
5. Create a form via DocuMerge → Forms
6. Embed the form using the shortcode `[wprobo_documerge_form id="1"]` or the Gutenberg block

== Frequently Asked Questions ==

= Is this plugin really free? =

Yes. WPRobo DocuMerge Lite is 100% free with no usage limits on documents generated, forms created, or submissions received. The Pro version adds advanced features like signature fields, payments, conditional logic, and third-party form integrations.

= What is the difference between Lite and Pro? =

The Lite version includes the core document automation workflow: upload templates, build forms with basic field types (text, textarea, email, phone, number, date), generate PDF/DOCX documents, and deliver via browser download. The Pro version adds signature capture, Stripe payments, choice fields (dropdown/radio/checkbox), conditional logic, multi-step forms, email delivery, CAPTCHA protection, and integrations with WPForms, CF7, Gravity Forms, and Fluent Forms.

= What document formats are supported? =

Templates must be uploaded as DOCX (Microsoft Word) files. Output can be DOCX, PDF, or both.

= How do I add merge tags to my template? =

In your Word document, type tags using curly braces: `{field_name}`. Upload the document to DocuMerge and the plugin will automatically detect all tags. Then map each tag to a form field using the visual mapper.

= Does this plugin require any other plugins? =

No. WPRobo DocuMerge Lite works completely standalone with its own built-in form builder. No other plugins are required.

= Can I upgrade to Pro later without losing data? =

Yes. Installing the Pro version preserves all your existing templates, forms, and submissions. The upgrade is seamless.

= Is the plugin GDPR compliant? =

Generated documents are stored securely on your own server and are not publicly accessible via direct URL. No data is sent to third-party services. All data stays on your WordPress installation.

= Can I use multiple templates? =

Yes. Create as many templates as you need. Each form is assigned one template.

= Does it work with my theme? =

Yes. The frontend form output uses its own namespaced CSS classes that do not conflict with themes. The form will adapt to your site layout.

== Screenshots ==

1. Dashboard — overview with stat cards, submission trends chart, and status breakdown doughnut
2. Template Manager — manage DOCX templates with merge tags, output format, and linked forms
3. Upload Template — slide-in panel with drag-and-drop DOCX upload and output format selection
4. Forms List — all forms with template, field count, submission count, shortcode, and mode columns
5. Form Builder — drag-and-drop fields with free field types and locked Pro fields (PRO badges)
6. Form Settings — template selection, output format, delivery methods with Pro upgrade prompts
7. Submissions Archive — filterable list with status badges, form names, and PDF download links
8. Submission Detail — full submitted data, admin notes, tracking info, and document download
9. Settings General — document output format, delivery method, and download link expiry
10. Settings General (cont.) — form mode configuration with Pro integration option and admin notifications
11. Settings Advanced — storage cleanup, debug logging, developer options, and setup wizard re-run
12. Import / Export — side-by-side export checkboxes and drag-and-drop JSON import
13. Danger Zone — selective data deletion for submissions, forms, templates, and documents
14. Danger Zone (cont.) — settings reset and full factory reset with confirmation

== Changelog ==

= 1.0.1 =
* Fix: merge tags in `{tag}` format were not detected when uploading a DOCX template (scanner returned an empty list).
* Fix: tag replacement during document generation now correctly substitutes `{tag}` placeholders.

= 1.0.0 =
* Initial release
* Setup wizard with guided configuration
* DOCX template manager with automatic merge tag scanning
* Drag-and-drop form builder with 6 field types (Text, Textarea, Email, Phone, Number, Date)
* Visual merge tag mapper
* PDF output via mPDF
* DOCX output via PHPWord
* Browser download delivery
* Submissions archive with document management
* Shortcode embed support
* Gutenberg block for form embedding
* Full translation support with .pot file

== Upgrade Notice ==

= 1.0.1 =
Important fix: template merge-tag detection was broken in 1.0.0. Update to 1.0.1 to restore template uploads.

= 1.0.0 =
Initial release of WPRobo DocuMerge Lite. Upload DOCX templates, build forms, generate documents automatically.
