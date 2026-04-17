<?php
/**
 * WPRobo DocuMerge Lite — Demo Data Seeder
 *
 * Creates realistic demo data for screenshots and testing.
 * Run via: /wp-admin/admin.php?page=wprobo-documerge&seed_demo=1
 *
 * IMPORTANT: This file is for development only.
 * It is excluded from the production ZIP by deploy.sh.
 *
 * @package WPRobo_DocuMerge
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "ERROR: This script must be run within WordPress.\n";
	exit( 1 );
}

global $wpdb;

$templates_table   = $wpdb->prefix . 'wprdm_templates';
$forms_table       = $wpdb->prefix . 'wprdm_forms';
$submissions_table = $wpdb->prefix . 'wprdm_submissions';

// ── Helper ────────────────────────────────────────────────────
function seed_msg( $msg ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $msg );
	} else {
		echo esc_html( $msg ) . '<br>';
	}
}

seed_msg( '🌱 WPRobo DocuMerge Lite — Seeding demo data...' );
seed_msg( '' );

// ══════════════════════════════════════════════════════════════
// 1. TEMPLATES (4)
// ══════════════════════════════════════════════════════════════
seed_msg( '── Creating Templates ──' );

$templates = array(
	array(
		'name'          => 'Client Service Agreement',
		'description'   => 'Standard service agreement for consulting clients. Includes terms, scope of work, and client details.',
		'file_path'     => WPROBO_DOCUMERGE_PATH . 'demo/client-service-agreement.docx',
		'file_name'     => 'client-service-agreement.docx',
		'output_format' => 'pdf',
		'merge_tags'    => wp_json_encode( array( 'client_name', 'email', 'phone', 'service_type', 'start_date', 'notes', 'current_date' ) ),
	),
	array(
		'name'          => 'Student Enrolment Letter',
		'description'   => 'Welcome letter for newly enrolled students with course details and start date.',
		'file_path'     => WPROBO_DOCUMERGE_PATH . 'demo/student-enrolment-letter.docx',
		'file_name'     => 'student-enrolment-letter.docx',
		'output_format' => 'pdf',
		'merge_tags'    => wp_json_encode( array( 'student_name', 'email', 'course_name', 'start_date', 'current_date' ) ),
	),
	array(
		'name'          => 'General NDA',
		'description'   => 'Non-disclosure agreement for business partnerships and contractor engagements.',
		'file_path'     => WPROBO_DOCUMERGE_PATH . 'demo/general-nda.docx',
		'file_name'     => 'general-nda.docx',
		'output_format' => 'both',
		'merge_tags'    => wp_json_encode( array( 'party_name', 'company', 'email', 'effective_date', 'current_date' ) ),
	),
	array(
		'name'          => 'Invoice Template',
		'description'   => 'Professional invoice for freelancers and agencies with itemised billing.',
		'file_path'     => WPROBO_DOCUMERGE_PATH . 'demo/invoice-template.docx',
		'file_name'     => 'invoice-template.docx',
		'output_format' => 'pdf',
		'merge_tags'    => wp_json_encode( array( 'client_name', 'email', 'project_name', 'amount', 'due_date', 'current_date' ) ),
	),
);

$template_ids = array();
foreach ( $templates as $tpl ) {
	$now = current_time( 'mysql' );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$templates_table} WHERE name = %s", $tpl['name'] ) );
	if ( $exists ) {
		$template_ids[] = (int) $exists;
		seed_msg( "  ⏭ Exists: {$tpl['name']} (#{$exists})" );
		continue;
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->insert(
		$templates_table,
		array(
			'name'          => $tpl['name'],
			'description'   => $tpl['description'],
			'file_path'     => $tpl['file_path'],
			'file_name'     => $tpl['file_name'],
			'output_format' => $tpl['output_format'],
			'merge_tags'    => $tpl['merge_tags'],
			'created_at'    => $now,
			'updated_at'    => $now,
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
	$template_ids[] = (int) $wpdb->insert_id;
	seed_msg( "  ✓ Created: {$tpl['name']} (#{$wpdb->insert_id})" );
}

// ══════════════════════════════════════════════════════════════
// 2. FORMS (4)
// ══════════════════════════════════════════════════════════════
seed_msg( '' );
seed_msg( '── Creating Forms ──' );

$forms = array(
	// Form 1: Client Intake.
	array(
		'title'       => 'Client Intake Form',
		'template_id' => isset( $template_ids[0] ) ? $template_ids[0] : 1,
		'fields'      => wp_json_encode( array(
			array( 'id' => 'f1', 'type' => 'text', 'label' => 'Full Name', 'name' => 'client_name', 'placeholder' => 'Enter your full name', 'required' => true, 'width' => 'full', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f2', 'type' => 'email', 'label' => 'Email Address', 'name' => 'email', 'placeholder' => 'you@example.com', 'required' => true, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f3', 'type' => 'phone', 'label' => 'Phone Number', 'name' => 'phone', 'placeholder' => '+1 (555) 000-0000', 'required' => false, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f4', 'type' => 'dropdown', 'label' => 'Service Type', 'name' => 'service_type', 'placeholder' => 'Select...', 'required' => true, 'width' => 'full', 'options' => array( array( 'label' => 'Consultation', 'value' => 'consultation' ), array( 'label' => 'Document Review', 'value' => 'review' ), array( 'label' => 'Full Drafting', 'value' => 'drafting' ) ), 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f5', 'type' => 'date', 'label' => 'Start Date', 'name' => 'start_date', 'placeholder' => '', 'required' => true, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f6', 'type' => 'textarea', 'label' => 'Notes', 'name' => 'notes', 'placeholder' => 'Additional details...', 'required' => false, 'width' => 'full', 'conditions' => array(), 'step' => 1 ),
		) ),
		'settings'    => wp_json_encode( array( 'submit_label' => 'Submit & Get Document', 'success_message' => 'Thank you! Your document is ready.', 'output_format' => 'pdf', 'delivery_methods' => array( 'download' ) ) ),
	),
	// Form 2: Student Enrolment.
	array(
		'title'       => 'Student Enrolment',
		'template_id' => isset( $template_ids[1] ) ? $template_ids[1] : 2,
		'fields'      => wp_json_encode( array(
			array( 'id' => 'f1', 'type' => 'text', 'label' => 'Student Name', 'name' => 'student_name', 'placeholder' => 'Full name', 'required' => true, 'width' => 'full', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f2', 'type' => 'email', 'label' => 'Email', 'name' => 'email', 'placeholder' => 'you@uni.edu', 'required' => true, 'width' => 'full', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f3', 'type' => 'dropdown', 'label' => 'Course', 'name' => 'course_name', 'placeholder' => 'Select course...', 'required' => true, 'width' => 'full', 'options' => array( array( 'label' => 'Business Management', 'value' => 'business' ), array( 'label' => 'Computer Science', 'value' => 'cs' ), array( 'label' => 'Data Analytics', 'value' => 'data' ), array( 'label' => 'Digital Marketing', 'value' => 'marketing' ) ), 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f4', 'type' => 'date', 'label' => 'Start Date', 'name' => 'start_date', 'placeholder' => '', 'required' => true, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
		) ),
		'settings'    => wp_json_encode( array( 'submit_label' => 'Enrol Now', 'success_message' => 'Welcome! Your enrolment letter is ready.', 'output_format' => 'pdf', 'delivery_methods' => array( 'download' ) ) ),
	),
	// Form 3: NDA Request.
	array(
		'title'       => 'NDA Request Form',
		'template_id' => isset( $template_ids[2] ) ? $template_ids[2] : 3,
		'fields'      => wp_json_encode( array(
			array( 'id' => 'f1', 'type' => 'text', 'label' => 'Your Name', 'name' => 'party_name', 'placeholder' => 'Full name', 'required' => true, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f2', 'type' => 'text', 'label' => 'Company', 'name' => 'company', 'placeholder' => 'Company name', 'required' => true, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f3', 'type' => 'email', 'label' => 'Email', 'name' => 'email', 'placeholder' => 'you@company.com', 'required' => true, 'width' => 'full', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f4', 'type' => 'date', 'label' => 'Effective Date', 'name' => 'effective_date', 'placeholder' => '', 'required' => true, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
		) ),
		'settings'    => wp_json_encode( array( 'submit_label' => 'Generate NDA', 'success_message' => 'Your NDA is ready for download.', 'output_format' => 'both', 'delivery_methods' => array( 'download' ) ) ),
	),
	// Form 4: Invoice Generator.
	array(
		'title'       => 'Invoice Generator',
		'template_id' => isset( $template_ids[3] ) ? $template_ids[3] : 4,
		'fields'      => wp_json_encode( array(
			array( 'id' => 'f1', 'type' => 'text', 'label' => 'Client Name', 'name' => 'client_name', 'placeholder' => 'Client or company name', 'required' => true, 'width' => 'full', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f2', 'type' => 'email', 'label' => 'Client Email', 'name' => 'email', 'placeholder' => 'client@company.com', 'required' => true, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f3', 'type' => 'text', 'label' => 'Project Name', 'name' => 'project_name', 'placeholder' => 'Website redesign...', 'required' => true, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f4', 'type' => 'number', 'label' => 'Amount', 'name' => 'amount', 'placeholder' => '0.00', 'required' => true, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
			array( 'id' => 'f5', 'type' => 'date', 'label' => 'Due Date', 'name' => 'due_date', 'placeholder' => '', 'required' => true, 'width' => 'half', 'conditions' => array(), 'step' => 1 ),
		) ),
		'settings'    => wp_json_encode( array( 'submit_label' => 'Create Invoice', 'success_message' => 'Invoice generated successfully.', 'output_format' => 'pdf', 'delivery_methods' => array( 'download' ) ) ),
	),
);

$form_ids = array();
foreach ( $forms as $frm ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$forms_table} WHERE title = %s", $frm['title'] ) );
	if ( $exists ) {
		$form_ids[] = (int) $exists;
		seed_msg( "  ⏭ Exists: {$frm['title']} (#{$exists})" );
		continue;
	}
	$now = current_time( 'mysql' );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->insert(
		$forms_table,
		array(
			'title'            => $frm['title'],
			'template_id'      => $frm['template_id'],
			'mode'             => 'standalone',
			'integration'      => '',
			'fields'           => $frm['fields'],
			'settings'         => $frm['settings'],
			'output_format'    => isset( $frm['settings'] ) ? 'pdf' : 'pdf',
			'delivery_methods' => 'download',
			'submit_label'     => 'Submit',
			'success_message'  => 'Document generated.',
			'redirect_url'     => '',
			'created_at'       => $now,
			'updated_at'       => $now,
		),
		array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
	$form_ids[] = (int) $wpdb->insert_id;
	seed_msg( "  ✓ Created: {$frm['title']} (#{$wpdb->insert_id})" );
}

// ══════════════════════════════════════════════════════════════
// 3. SUBMISSIONS (30 — realistic distribution)
// ══════════════════════════════════════════════════════════════
seed_msg( '' );
seed_msg( '── Creating Submissions ──' );

$docs_dir = \WPRobo\DocuMerge\Core\WPRobo_DocuMerge_Installer::wprobo_documerge_get_docs_dir();

$people = array(
	array( 'name' => 'Alice Johnson', 'email' => 'alice.johnson@example.com', 'phone' => '+1 (555) 123-4567' ),
	array( 'name' => 'Bob Martinez', 'email' => 'bob.martinez@example.com', 'phone' => '+44 7700 900123' ),
	array( 'name' => 'Carol Chen', 'email' => 'carol.chen@example.com', 'phone' => '+1 (555) 234-5678' ),
	array( 'name' => 'David Okafor', 'email' => 'david.okafor@example.com', 'phone' => '+44 7700 900456' ),
	array( 'name' => 'Emma Wilson', 'email' => 'emma.wilson@example.com', 'phone' => '+1 (555) 345-6789' ),
	array( 'name' => 'Frank Dubois', 'email' => 'frank.dubois@example.com', 'phone' => '+33 6 12 34 56 78' ),
	array( 'name' => 'Grace Kim', 'email' => 'grace.kim@example.com', 'phone' => '+82 10 1234 5678' ),
	array( 'name' => 'Henry Patel', 'email' => 'henry.patel@example.com', 'phone' => '+91 98765 43210' ),
	array( 'name' => 'Irene Kowalski', 'email' => 'irene.kowalski@example.com', 'phone' => '+48 501 234 567' ),
	array( 'name' => 'Jack Thompson', 'email' => 'jack.thompson@example.com', 'phone' => '+1 (555) 456-7890' ),
	array( 'name' => 'Karen Nakamura', 'email' => 'karen.nakamura@example.com', 'phone' => '+81 90 1234 5678' ),
	array( 'name' => 'Leo Rossi', 'email' => 'leo.rossi@example.com', 'phone' => '+39 333 123 4567' ),
	array( 'name' => 'Maria Santos', 'email' => 'maria.santos@example.com', 'phone' => '+55 11 91234-5678' ),
	array( 'name' => 'Nikolai Petrov', 'email' => 'nikolai.petrov@example.com', 'phone' => '+7 916 123 4567' ),
	array( 'name' => 'Olivia Brown', 'email' => 'olivia.brown@example.com', 'phone' => '+61 400 123 456' ),
	array( 'name' => 'Priya Sharma', 'email' => 'priya.sharma@example.com', 'phone' => '+91 99887 76655' ),
	array( 'name' => 'Qian Wei', 'email' => 'qian.wei@example.com', 'phone' => '+86 138 0013 8000' ),
	array( 'name' => 'Robert Taylor', 'email' => 'robert.taylor@example.com', 'phone' => '+1 (555) 567-8901' ),
	array( 'name' => 'Sofia Alvarez', 'email' => 'sofia.alvarez@example.com', 'phone' => '+34 612 345 678' ),
	array( 'name' => 'Thomas Mueller', 'email' => 'thomas.mueller@example.com', 'phone' => '+49 170 1234567' ),
	array( 'name' => 'Uma Krishnan', 'email' => 'uma.krishnan@example.com', 'phone' => '+91 98765 12345' ),
	array( 'name' => 'Viktor Novak', 'email' => 'viktor.novak@example.com', 'phone' => '+420 777 123 456' ),
	array( 'name' => 'Wendy Liu', 'email' => 'wendy.liu@example.com', 'phone' => '+886 912 345 678' ),
	array( 'name' => 'Xavier Dupont', 'email' => 'xavier.dupont@example.com', 'phone' => '+33 6 98 76 54 32' ),
	array( 'name' => 'Yuki Tanaka', 'email' => 'yuki.tanaka@example.com', 'phone' => '+81 80 9876 5432' ),
	array( 'name' => 'Zara Ahmed', 'email' => 'zara.ahmed@example.com', 'phone' => '+44 7911 234567' ),
	array( 'name' => 'Andre Costa', 'email' => 'andre.costa@example.com', 'phone' => '+55 21 98765-4321' ),
	array( 'name' => 'Bianca Moretti', 'email' => 'bianca.moretti@example.com', 'phone' => '+39 347 987 6543' ),
	array( 'name' => 'Chen Jiahao', 'email' => 'chen.jiahao@example.com', 'phone' => '+86 139 1234 5678' ),
	array( 'name' => 'Diana Popescu', 'email' => 'diana.popescu@example.com', 'phone' => '+40 721 234 567' ),
);

// Realistic status distribution: 22 completed, 3 pending, 3 error, 2 processing.
$statuses = array(
	'completed', 'completed', 'pending', 'completed', 'completed',
	'completed', 'error', 'completed', 'completed', 'completed',
	'completed', 'pending', 'completed', 'completed', 'error',
	'completed', 'completed', 'completed', 'processing', 'completed',
	'completed', 'pending', 'completed', 'error', 'completed',
	'completed', 'completed', 'processing', 'completed', 'completed',
);

// Submissions per day pattern (last 7 days focus, older days fewer).
// Day 0 = today, day 1 = yesterday, etc.
// Pattern: [today:5, yesterday:6, 2 days:4, 3 days:3, 4 days:3, 5 days:4, 6 days:5]
$day_assignment = array(
	0, 0, 0, 0, 0,       // 5 today.
	1, 1, 1, 1, 1, 1,    // 6 yesterday.
	2, 2, 2, 2,           // 4 two days ago.
	3, 3, 3,              // 3 three days ago.
	4, 4, 4,              // 3 four days ago.
	5, 5, 5, 5,           // 4 five days ago.
	6, 6, 6, 6, 6,        // 5 six days ago.
);

$services  = array( 'Consultation', 'Document Review', 'Full Drafting' );
$courses   = array( 'Business Management', 'Computer Science', 'Data Analytics', 'Digital Marketing' );
$companies = array( 'Acme Corp', 'TechStart Ltd', 'Global Partners', 'Nova Solutions', 'BluePeak Inc' );
$projects  = array( 'Website Redesign', 'Brand Identity', 'SEO Audit', 'App Development', 'Data Migration' );
$amounts   = array( '1500.00', '2800.00', '950.00', '4200.00', '3500.00', '750.00', '1200.00' );

$created_count = 0;
$skipped_count = 0;

for ( $i = 0; $i < count( $people ); $i++ ) {
	$person   = $people[ $i ];
	$status   = $statuses[ $i ];
	$form_idx = $i % count( $form_ids ); // Distribute across all 4 forms.
	$form_id  = $form_ids[ $form_idx ];
	$tpl_id   = $template_ids[ $form_idx ];
	$days_ago = $day_assignment[ $i ];

	// Random time within the day (8am–6pm range).
	$hour   = wp_rand( 8, 17 );
	$minute = wp_rand( 0, 59 );
	$second = wp_rand( 0, 59 );
	$created = gmdate( 'Y-m-d', strtotime( "-{$days_ago} days" ) ) . sprintf( ' %02d:%02d:%02d', $hour, $minute, $second );

	// Check duplicate.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$exists = $wpdb->get_var(
		$wpdb->prepare( "SELECT id FROM {$submissions_table} WHERE submitter_email = %s AND form_id = %d", $person['email'], $form_id )
	);
	if ( $exists ) {
		++$skipped_count;
		continue;
	}

	// Build form_data based on which form.
	switch ( $form_idx ) {
		case 0: // Client Intake.
			$fields_data = array(
				'client_name'  => $person['name'],
				'email'        => $person['email'],
				'phone'        => $person['phone'],
				'service_type' => $services[ $i % count( $services ) ],
				'start_date'   => gmdate( 'Y-m-d', strtotime( "+{$i} days" ) ),
				'notes'        => 'Looking forward to working together on this project.',
			);
			break;
		case 1: // Student Enrolment.
			$fields_data = array(
				'student_name' => $person['name'],
				'email'        => $person['email'],
				'course_name'  => $courses[ $i % count( $courses ) ],
				'start_date'   => gmdate( 'Y-m-d', strtotime( '+2 months' ) ),
			);
			break;
		case 2: // NDA.
			$fields_data = array(
				'party_name'     => $person['name'],
				'company'        => $companies[ $i % count( $companies ) ],
				'email'          => $person['email'],
				'effective_date' => gmdate( 'Y-m-d' ),
			);
			break;
		case 3: // Invoice.
			$fields_data = array(
				'client_name'  => $person['name'],
				'email'        => $person['email'],
				'project_name' => $projects[ $i % count( $projects ) ],
				'amount'       => $amounts[ $i % count( $amounts ) ],
				'due_date'     => gmdate( 'Y-m-d', strtotime( '+30 days' ) ),
			);
			break;
		default:
			$fields_data = array( 'name' => $person['name'], 'email' => $person['email'] );
	}

	$form_data = wp_json_encode( array(
		'fields' => $fields_data,
		'meta'   => array(
			'ip_address' => '192.168.' . wp_rand( 1, 254 ) . '.' . wp_rand( 1, 254 ),
			'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
			'page_url'   => home_url( '/form-' . ( $form_idx + 1 ) . '/' ),
			'referrer'   => home_url( '/' ),
		),
	) );

	// Fake doc paths for completed submissions so download buttons show.
	$doc_pdf  = '';
	$doc_docx = '';
	if ( 'completed' === $status ) {
		$sub_dir  = gmdate( 'Y/m', strtotime( "-{$days_ago} days" ) );
		$doc_pdf  = $docs_dir . $sub_dir . '/demo-document-' . ( $i + 1 ) . '.pdf';
		$doc_docx = ( 2 === $form_idx ) ? $docs_dir . $sub_dir . '/demo-document-' . ( $i + 1 ) . '.docx' : ''; // NDA form outputs both.
	}

	$error_log       = ( 'error' === $status ) ? 'Template file not found. Demo data — no actual DOCX uploaded.' : '';
	$delivery_status = ( 'completed' === $status ) ? 'delivered' : 'pending';
	$ip_address      = '192.168.' . wp_rand( 1, 254 ) . '.' . wp_rand( 1, 254 );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->insert(
		$submissions_table,
		array(
			'form_id'         => $form_id,
			'template_id'     => $tpl_id,
			'submitter_email' => $person['email'],
			'form_data'       => $form_data,
			'doc_path_docx'   => $doc_docx,
			'doc_path_pdf'    => $doc_pdf,
			'status'          => $status,
			'error_log'       => $error_log,
			'retry_count'     => 0,
			'delivery_status' => $delivery_status,
			'ip_address'      => $ip_address,
			'created_at'      => $created,
			'updated_at'      => $created,
		),
		array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
	);
	++$created_count;
}

seed_msg( "  ✓ Created {$created_count} submissions" );
if ( $skipped_count > 0 ) {
	seed_msg( "  ⏭ Skipped {$skipped_count} (already exist)" );
}

// ── Bust caches ───────────────────────────────────────────────
delete_transient( 'wprobo_documerge_templates_list' );
delete_transient( 'wprobo_documerge_templates_count' );
delete_transient( 'wprobo_documerge_forms_count' );

// ── Summary ───────────────────────────────────────────────────
seed_msg( '' );
seed_msg( '════════════════════════════════════════' );
seed_msg( '  ✓ Demo data seeded!' );
seed_msg( '' );
seed_msg( '  Templates:   ' . count( $template_ids ) );
seed_msg( '  Forms:       ' . count( $form_ids ) );
seed_msg( '  Submissions: ' . $created_count );
seed_msg( '' );
seed_msg( '  Chart data: 5-6 per day across 7 days' );
seed_msg( '  Statuses:   22 completed, 3 pending, 3 error, 2 processing' );
seed_msg( '  Documents:  download buttons on completed rows' );
seed_msg( '════════════════════════════════════════' );

if ( ! defined( 'WP_CLI' ) && is_admin() ) {
	echo '<br><a href="' . esc_url( admin_url( 'admin.php?page=wprobo-documerge' ) ) . '" class="button button-primary">Go to Dashboard</a>';
}
