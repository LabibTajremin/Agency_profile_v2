<?php
/**
 * Generate the demo content in plugin/qe-edu-core/demo (Elementor JSON for
 * every page and template plus manifest.json with posts, menus and settings).
 * The importer (QeEdu\Core\DemoImport) reads only the generated files.
 *
 * Placeholders resolved on import:
 *   {{image:key}} attachment id     {{image-url:key}} attachment URL
 *   {{page:key}}  page id           {{page-url:key}}  page URL
 *   {{menu:key}}  menu id           {{home-url}}      site URL
 *
 * Usage: php bin/make-demo.php
 *
 * @package QeEdu
 */

// phpcs:disable -- developer build script, not shipped.

$out = dirname( __DIR__ ) . '/plugin/qe-edu-core/demo';

/* Element helpers ------------------------------------------------------ */

$ids = array();

/**
 * Deterministic 7-character element id.
 */
function demo_id( $seed ) {
	global $ids;
	$n          = isset( $ids[ $seed ] ) ? $ids[ $seed ] + 1 : 0;
	$ids[ $seed ] = $n;
	return substr( md5( $seed . '#' . $n ), 0, 7 );
}

$current = 'page';

function el( $type, $settings = array(), $children = array() ) {
	global $current;
	if ( 'container' === $type ) {
		return array(
			'id'       => demo_id( $current ),
			'elType'   => 'container',
			'settings' => (object) $settings,
			'elements' => $children,
			'isInner'  => false,
		);
	}
	return array(
		'id'         => demo_id( $current ),
		'elType'     => 'widget',
		'widgetType' => $type,
		'settings'   => (object) $settings,
		'elements'   => array(),
	);
}

function px( $top, $right = null, $bottom = null, $left = null ) {
	return array(
		'unit'     => 'px',
		'top'      => (string) $top,
		'right'    => (string) ( null === $right ? $top : $right ),
		'bottom'   => (string) ( null === $bottom ? $top : $bottom ),
		'left'     => (string) ( null === $left ? ( null === $right ? $top : $right ) : $left ),
		'isLinked' => false,
	);
}

function gap( $size ) {
	return array(
		'unit'   => 'px',
		'size'   => $size,
		'column' => (string) $size,
		'row'    => (string) $size,
	);
}

/** A full-bleed band with boxed content (one column). */
function section( $children, $extra = array() ) {
	return el(
		'container',
		array_merge(
			array(
				'content_width'  => 'boxed',
				'flex_direction' => 'column',
				'flex_gap'       => gap( 24 ),
				'padding'        => px( 80, 0 ),
				'padding_tablet' => px( 64, 0 ),
				'padding_mobile' => px( 48, 0 ),
			),
			$extra
		),
		$children
	);
}

/** Side-by-side columns that stack on mobile. */
function row( $columns, $extra = array() ) {
	return el(
		'container',
		array_merge(
			array(
				'content_width'         => 'full',
				'flex_direction'        => 'row',
				'flex_direction_mobile' => 'column',
				'flex_gap'              => gap( 48 ),
				'flex_align_items'      => 'center',
				'padding'               => px( 0 ),
			),
			$extra
		),
		$columns
	);
}

function column( $children, $width = 50, $extra = array() ) {
	return el(
		'container',
		array_merge(
			array(
				'content_width'  => 'full',
				'flex_direction' => 'column',
				'flex_gap'       => gap( 16 ),
				'width'          => array( 'unit' => '%', 'size' => $width ),
				'width_mobile'   => array( 'unit' => '%', 'size' => 100 ),
				'padding'        => px( 0 ),
			),
			$extra
		),
		$children
	);
}

/**
 * A band holding one widget. Widgets leave section spacing to Elementor, so
 * the band is boxed with vertical padding; `$full` makes it edge to edge with
 * no padding (hero, page banner, map).
 */
function band( $widget, $full = false, $extra = array() ) {
	$settings = $full
		? array(
			'content_width'  => 'full',
			'flex_direction' => 'column',
			'padding'        => px( 0 ),
		)
		: array(
			'content_width'  => 'boxed',
			'flex_direction' => 'column',
			'padding'        => px( 72, 0 ),
			'padding_tablet' => px( 56, 0 ),
			'padding_mobile' => px( 40, 0 ),
		);
	return el( 'container', array_merge( $settings, $extra ), array( $widget ) );
}

function heading( $title, $size = 'h2', $extra = array() ) {
	return el( 'heading', array_merge( array( 'title' => $title, 'header_size' => $size ), $extra ) );
}

function text( $html ) {
	return el( 'text-editor', array( 'editor' => $html ) );
}

function button( $label, $url, $extra = array() ) {
	return el(
		'button',
		array_merge(
			array(
				'text' => $label,
				'link' => array( 'url' => $url, 'is_external' => '', 'nofollow' => '' ),
				'size' => 'md',
			),
			$extra
		)
	);
}

function image( $key, $alt_size = 'large' ) {
	return el(
		'image',
		array(
			'image'      => media( $key ),
			'image_size' => $alt_size,
		)
	);
}

function media( $key ) {
	return array(
		'id'  => '{{image:' . $key . '}}',
		'url' => '{{image-url:' . $key . '}}',
	);
}

function checklist( $items, $view = 'traditional' ) {
	$list = array();
	foreach ( $items as $i => $item ) {
		$text = is_array( $item ) ? $item[0] : $item;
		$row  = array(
			'_id'           => demo_id( 'list' ),
			'text'          => $text,
			'selected_icon' => array( 'value' => 'fas fa-check-circle', 'library' => 'fa-solid' ),
		);
		if ( is_array( $item ) ) {
			$row['link'] = array( 'url' => $item[1], 'is_external' => '', 'nofollow' => '' );
		}
		$list[] = $row;
	}
	return el( 'icon-list', array( 'icon_list' => $list, 'view' => $view ) );
}

function links( $items, $view = 'traditional', $extra = array() ) {
	$list = array();
	foreach ( $items as $item ) {
		$list[] = array(
			'_id'           => demo_id( 'list' ),
			'text'          => $item[0],
			'link'          => array( 'url' => $item[1], 'is_external' => '', 'nofollow' => '' ),
			'selected_icon' => array( 'value' => '', 'library' => '' ),
		);
	}
	return el( 'icon-list', array_merge( array( 'icon_list' => $list, 'view' => $view ), $extra ) );
}

/** Text from Theme Settings via the "Theme Settings text" dynamic tag. */
function setting_text( $key, $control = 'editor' ) {
	$tag = sprintf( '[elementor-tag id="%s" name="qe-setting-text" settings="%s"]', demo_id( 'tag' ), rawurlencode( json_encode( array( 'key' => $key ) ) ) );
	return array( '__dynamic__' => array( $control => $tag ) );
}

function banner( $subtitle = '' ) {
	return band(
		el(
			'qe-page-title',
			array(
				'subtitle'         => $subtitle,
				'background_image' => media( 'banner' ),
			)
		),
		true
	);
}

function page_url( $key ) {
	return '{{page-url:' . $key . '}}';
}

/** Intro paragraph(s) with optional image beside them. */
function intro( $title, $paragraphs, $image = null, $buttons = array() ) {
	$copy = array( heading( $title ), text( '<p>' . implode( '</p><p>', $paragraphs ) . '</p>' ) );
	foreach ( $buttons as $b ) {
		$copy[] = button( $b[0], $b[1] );
	}
	if ( ! $image ) {
		return section( $copy, array( 'boxed_width' => array( 'unit' => 'px', 'size' => 860 ) ) );
	}
	return section( array( row( array( column( $copy, 55 ), column( array( image( $image ) ), 45 ) ) ) ) );
}

function cta( $title, $text, $label, $url ) {
	return section(
		array(
			heading( $title, 'h2', array( 'align' => 'center' ) ),
			el( 'text-editor', array( 'editor' => '<p>' . $text . '</p>', 'align' => 'center' ) ),
			button( $label, $url, array( 'align' => 'center' ) ),
		),
		array( '_css_classes' => 'qe-section-alt', 'flex_align_items' => 'center' )
	);
}

function faq( $items, $heading = 'Frequently asked questions' ) {
	$rows = array();
	foreach ( $items as $qa ) {
		$rows[] = array( '_id' => demo_id( 'faq' ), 'question' => $qa[0], 'answer' => '<p>' . $qa[1] . '</p>' );
	}
	return band( el( 'qe-faq', array( 'heading' => $heading, 'items' => $rows ) ) );
}

/* Pages ---------------------------------------------------------------- */

$pages = array();

$current = 'home';
$pages['home'] = array(
	'title' => 'Home',
	'slug'  => 'home',
	'seo'   => array( 'description' => 'Study abroad with Your Education Group: university applications, pathways, English courses and one-to-one advice for international students.' ),
	'data'  => array(
		band(
			el(
				'qe-animated-hero',
				array(
					'button_1_link' => array( 'url' => page_url( 'study-abroad' ) ),
					'button_2_link' => array( 'url' => page_url( 'international-pathways' ) ),
					'floats'        => array(
						array( '_id' => demo_id( 'float' ), 'image' => media( 'hero-1' ), 'x' => array( 'unit' => '%', 'size' => 6 ), 'y' => array( 'unit' => '%', 'size' => 14 ), 'size' => array( 'unit' => 'px', 'size' => 150 ), 'depth' => array( 'size' => 0.4 ) ),
						array( '_id' => demo_id( 'float' ), 'image' => media( 'hero-2' ), 'x' => array( 'unit' => '%', 'size' => 82 ), 'y' => array( 'unit' => '%', 'size' => 18 ), 'size' => array( 'unit' => 'px', 'size' => 170 ), 'depth' => array( 'size' => 0.7 ) ),
						array( '_id' => demo_id( 'float' ), 'image' => media( 'hero-3' ), 'x' => array( 'unit' => '%', 'size' => 76 ), 'y' => array( 'unit' => '%', 'size' => 68 ), 'size' => array( 'unit' => 'px', 'size' => 120 ), 'depth' => array( 'size' => 1 ) ),
					),
				)
			),
			true
		),
		band( el( 'qe-benefits-form', array() ) ),
		band( el( 'qe-service-slider', array() ) ),
		band( el( 'qe-destinations', array( 'count' => 16 ) ) ),
		band( el( 'qe-stats', array() ) ),
		band( el( 'qe-news-slider', array() ) ),
		band( el( 'qe-logo-carousel', array() ) ),
		band( el( 'qe-testimonials', array() ) ),
		band( el( 'qe-cta-download', array( 'image' => media( 'about' ) ) ) ),
	),
);

$current = 'about';
$pages['about'] = array(
	'title' => 'About Us',
	'slug'  => 'about',
	'seo'   => array( 'schema' => 'AboutPage', 'description' => 'Fifteen years of helping students study abroad: who we are, what we believe in and the global network behind Your Education Group.' ),
	'data'  => array(
		banner( 'Fifteen years of opening doors to world-class education.' ),
		intro(
			'Who we are',
			array(
				'Your Education Group helps students from more than 60 countries find the right university, programme and city — and supports them all the way to graduation.',
				'Our advisors, admissions specialists and teachers work together so every student gets honest advice and a clear plan.',
			),
			'about',
			array( array( 'What we do', page_url( 'what-we-do' ) ) )
		),
		band( el( 'qe-stats', array() ) ),
		section(
			array(
				heading( 'Vision & values', 'h2', array( 'align' => 'center' ) ),
				row(
					array(
						column( array( heading( 'Students first', 'h3' ), text( '<p>Every recommendation starts with what is right for the student, not for us.</p>' ) ), 33 ),
						column( array( heading( 'Honest advice', 'h3' ), text( '<p>Clear costs, realistic chances and no surprises along the way.</p>' ) ), 33 ),
						column( array( heading( 'Global, personal', 'h3' ), text( '<p>A worldwide network with an advisor who knows you by name.</p>' ) ), 33 ),
					),
					array( 'flex_align_items' => 'stretch' )
				),
			),
			array( '_css_classes' => 'qe-section-alt' )
		),
		band( el( 'qe-team', array() ) ),
		band( el( 'qe-logo-carousel', array( 'heading' => 'Accreditation & institution partners' ) ) ),
		band( el( 'qe-cta-download', array( 'image' => media( 'about' ) ) ) ),
	),
);

$current = 'what-we-do';
$pages['what-we-do'] = array(
	'title' => 'What We Do',
	'slug'  => 'what-we-do',
	'seo'   => array( 'description' => 'Student recruitment, international pathways, university preparation and managed campuses — the four ways we help students and institutions.' ),
	'data'  => array(
		banner( 'Four ways we help students and institutions succeed.' ),
		intro( 'From first question to first lecture', array( 'We guide students through every step: choosing a course, applying, preparing for the move and settling in. For universities we recruit, prepare and support talented international students.' ) ),
		band( el( 'qe-service-slider', array( 'heading' => 'Our services' ) ) ),
		faq(
			array(
				array( 'Do you charge students for applications?', 'Most of our application support is free for students. If a service has a fee, we tell you before you start.' ),
				array( 'Which countries do you work with?', 'We place students in more than 16 study destinations, including the UK, Canada, Australia and Europe.' ),
				array( 'Can you help with visas?', 'Yes. Our advisors explain the visa steps for your destination and check your documents before you apply.' ),
			)
		),
		cta( 'Not sure where to start?', 'Tell us what you would like to study and we will suggest the best options for you.', 'Talk to an advisor', page_url( 'contact' ) ),
	),
);

$current = 'study-abroad';
$pages['study-abroad'] = array(
	'title' => 'Study Abroad',
	'slug'  => 'study-abroad',
	'seo'   => array( 'description' => 'Choose from 16 study destinations and thousands of programmes. Free application support from our study abroad advisors.' ),
	'data'  => array(
		banner( 'Make the world your classroom.' ),
		band( el( 'qe-destinations', array( 'count' => 16, 'heading' => 'Choose your destination' ) ) ),
		band( el( 'qe-benefits-form', array() ) ),
		faq(
			array(
				array( 'When should I start my application?', 'Ideally 9 to 12 months before your course starts. Some popular programmes fill up early.' ),
				array( 'What documents do I need?', 'Usually your transcripts, an English test result, a personal statement and a copy of your passport.' ),
				array( 'Can I get a scholarship?', 'Many universities offer scholarships for international students. We will show you the ones you can apply for.' ),
			)
		),
	),
);

$current = 'international-pathways';
$pages['international-pathways'] = array(
	'title' => 'International Pathways',
	'slug'  => 'international-pathways',
	'seo'   => array( 'schema' => 'Course', 'description' => 'Foundation, international year one and pre-master’s pathways that lead to degrees at partner universities.' ),
	'data'  => array(
		banner( 'A guaranteed route to your degree.' ),
		intro(
			'What is a pathway?',
			array( 'A pathway programme combines academic study, English and study skills. Pass it at the required level and you progress to the next year of a degree at a partner university.' ),
			'service-pathways'
		),
		section(
			array(
				heading( 'Pathway programmes' ),
				checklist( array( 'Foundation year — before the first year of a bachelor’s degree', 'International Year One — enter the second year of a degree', 'Pre-master’s — prepare for a master’s degree', 'English for academic purposes — before or alongside your studies' ) ),
			),
			array( '_css_classes' => 'qe-section-alt' )
		),
		band( el( 'qe-logo-carousel', array( 'heading' => 'Pathway partner universities', 'partner_type' => 'university' ) ) ),
		cta( 'Find your pathway', 'Our advisors will match your grades and English level with the right programme.', 'Get free advice', page_url( 'contact' ) ),
	),
);

$current = 'language-academy';
$pages['language-academy'] = array(
	'title' => 'Language Academy',
	'slug'  => 'language-academy',
	'seo'   => array( 'schema' => 'Course', 'description' => 'General English, academic English and test preparation courses for students planning to study abroad.' ),
	'data'  => array(
		banner( 'Build the English you need to succeed.' ),
		intro(
			'Learn with experienced teachers',
			array( 'Small classes, flexible timetables and teachers who know exactly what universities expect. Study in class or online.' ),
			'service-preparation'
		),
		section(
			array(
				heading( 'Our courses' ),
				checklist( array( 'General English — all levels, beginner to advanced', 'Academic English — essays, presentations and research skills', 'Test preparation — IELTS, PTE and our ELP Test', 'One-to-one lessons — at a time that suits you' ) ),
			),
			array( '_css_classes' => 'qe-section-alt' )
		),
		band( el( 'qe-testimonials', array() ) ),
		cta( 'Check your level for free', 'Take a short placement test and we will recommend the right course.', 'Book a placement test', page_url( 'contact' ) ),
	),
);

$current = 'elp-test';
$pages['elp-test'] = array(
	'title' => 'ELP Test',
	'slug'  => 'elp-test',
	'seo'   => array( 'description' => 'The English Language Proficiency (ELP) Test: an online English test accepted by our partner universities. Results in 48 hours.' ),
	'data'  => array(
		banner( 'An online English test with results in 48 hours.' ),
		intro( 'About the ELP Test', array( 'The English Language Proficiency Test checks reading, writing, listening and speaking in about two hours. Take it from home and receive your certificate within 48 hours.' ), null, array( array( 'Book your test', page_url( 'contact' ) ) ) ),
		section(
			array(
				heading( 'What the test includes' ),
				checklist( array( 'Reading — 30 minutes', 'Listening — 30 minutes', 'Writing — 40 minutes', 'Speaking — 15 minutes with a live examiner' ) ),
			),
			array( '_css_classes' => 'qe-section-alt' )
		),
		faq(
			array(
				array( 'Which universities accept the ELP Test?', 'All of our partner universities accept it for admission. Ask us about a specific university.' ),
				array( 'What do I need on the day?', 'A computer with a camera and microphone, a quiet room and your passport.' ),
				array( 'Can I retake the test?', 'Yes, you can book a new test after 14 days.' ),
			)
		),
	),
);

$current = 'partner-with-us';
$pages['partner-with-us'] = array(
	'title' => 'Partner With Us',
	'slug'  => 'partner-with-us',
	'seo'   => array( 'description' => 'Universities, colleges and recruitment agents: partner with Your Education Group to reach motivated international students.' ),
	'data'  => array(
		banner( 'Grow your international student community with us.' ),
		intro(
			'Why partner with us',
			array( 'We bring well-prepared, motivated students to institutions around the world, and we give recruitment partners the tools, training and support to succeed.' ),
			'service-recruitment',
			array( array( 'Become a partner', page_url( 'contact' ) ) )
		),
		section(
			array(
				heading( 'What partners get' ),
				checklist( array( 'Qualified applicants who meet your entry requirements', 'Marketing in more than 60 countries', 'A dedicated partner manager', 'Clear reporting on every application' ) ),
			),
			array( '_css_classes' => 'qe-section-alt' )
		),
		band( el( 'qe-logo-carousel', array() ) ),
	),
);

$current = 'partner-institutions';
$pages['partner-institutions'] = array(
	'title' => 'Partner Institutions',
	'slug'  => 'partner-institutions',
	'seo'   => array( 'description' => 'The universities, colleges and schools we work with around the world.' ),
	'data'  => array(
		banner( 'Universities and colleges we are proud to work with.' ),
		intro( 'A trusted global network', array( 'We work with carefully selected universities and colleges so our students receive excellent teaching and support.' ) ),
		band( el( 'qe-logo-carousel', array( 'heading' => 'Our partner institutions', 'partner_type' => 'university', 'mode' => 'grid' ) ) ),
		cta( 'Is your institution interested?', 'We are always happy to talk to universities and colleges that share our values.', 'Partner with us', page_url( 'partner-with-us' ) ),
	),
);

$current = 'recruitment-partners';
$pages['recruitment-partners'] = array(
	'title' => 'Recruitment Partners',
	'slug'  => 'recruitment-partners',
	'seo'   => array( 'description' => 'Join our network of recruitment partners and offer your students hundreds of universities and pathway programmes.' ),
	'data'  => array(
		banner( 'Offer your students more choice.' ),
		intro( 'Join our recruitment network', array( 'Agents and counsellors in our network can offer their students hundreds of universities and pathway programmes, with fast decisions and transparent commission.' ), null, array( array( 'Apply to become a partner', page_url( 'contact' ) ) ) ),
		band( el( 'qe-logo-carousel', array( 'heading' => 'Some of our recruitment partners', 'partner_type' => 'recruitment', 'mode' => 'grid' ) ) ),
	),
);

$current = 'study-mobility-program';
$pages['study-mobility-program'] = array(
	'title' => 'Study Mobility Program',
	'slug'  => 'study-mobility-program',
	'seo'   => array( 'description' => 'Short study abroad experiences: summer schools, exchange semesters and university visits for school and university students.' ),
	'data'  => array(
		banner( 'Short programmes, life-long experiences.' ),
		intro(
			'Study abroad for a few weeks or a semester',
			array( 'Our mobility programmes let students experience university life abroad before committing to a full degree — or add an international semester to their studies.' ),
			'service-campus'
		),
		section(
			array(
				heading( 'Programme types' ),
				checklist( array( 'Summer schools — two to four weeks', 'Exchange semesters — with credit transfer', 'University visit tours — meet admissions teams in person' ) ),
			),
			array( '_css_classes' => 'qe-section-alt' )
		),
		band( el( 'qe-event-list', array( 'heading' => 'Upcoming departures & info sessions' ) ) ),
	),
);

$current = 'careers';
$pages['careers'] = array(
	'title' => 'Careers',
	'slug'  => 'careers',
	'seo'   => array( 'description' => 'Work with Your Education Group: open positions and why our team loves helping students study abroad.' ),
	'data'  => array(
		banner( 'Help students change their lives.' ),
		intro( 'Why work with us', array( 'We are a friendly international team of advisors, teachers and specialists. We invest in training and give people the freedom to do great work.' ), 'about' ),
		section(
			array(
				heading( 'Open positions' ),
				checklist( array( 'Student Advisor — full time', 'Admissions Officer — full time', 'English Teacher — part time', 'Partnerships Manager — full time' ) ),
				text( '<p>Send your CV and a short note about yourself using the contact page.</p>' ),
				button( 'Apply now', page_url( 'contact' ) ),
			),
			array( '_css_classes' => 'qe-section-alt' )
		),
		band( el( 'qe-team', array( 'heading' => 'Meet some of the team' ) ) ),
	),
);

$current = 'events';
$pages['events'] = array(
	'title' => 'Events & Webinars',
	'slug'  => 'events-webinars', // "/events/" is the Event archive.

	'seo'   => array( 'description' => 'Education fairs, webinars and information sessions about studying abroad.' ),
	'data'  => array(
		banner( 'Fairs, webinars and information sessions.' ),
		band( el( 'qe-event-list', array( 'count' => 12 ) ) ),
		band( el( 'qe-newsletter', array( 'heading' => 'Never miss an event' ) ) ),
	),
);

$current = 'news';
$pages['news'] = array(
	'title'   => 'News',
	'slug'    => 'news',
	'seo'     => array( 'description' => 'Study abroad news, scholarship tips and student stories.' ),
	'builder' => false,
	'data'    => array(),
);

$current = 'contact';
$pages['contact'] = array(
	'title' => 'Contact Us',
	'slug'  => 'contact',
	'seo'   => array( 'schema' => 'ContactPage', 'description' => 'Contact Your Education Group: call, email, WhatsApp or send us a message and an advisor will reply within one working day.' ),
	'data'  => array(
		banner( 'An advisor will reply within one working day.' ),
		section(
			array(
				row(
					array(
						column(
							array(
								heading( 'Get in touch' ),
								heading( 'Phone', 'h3' ),
								el( 'text-editor', setting_text( 'phone' ) ),
								heading( 'Email', 'h3' ),
								el( 'text-editor', setting_text( 'email' ) ),
								heading( 'Visit us', 'h3' ),
								el( 'text-editor', setting_text( 'address' ) ),
								el( 'qe-social-links', array() ),
							),
							40
						),
						column(
							array(
								heading( 'Send us a message', 'h3' ),
								el(
									'qe-registration-form',
									array(
										'multi_step'     => '',
										'create_account' => '',
										'submit_label'   => 'Send message',
										'fields'         => array(
											array( '_id' => demo_id( 'field' ), 'label' => 'Your name', 'type' => 'text', 'required' => 'yes', 'width' => '50', 'step' => '1' ),
											array( '_id' => demo_id( 'field' ), 'label' => 'Email', 'type' => 'email', 'required' => 'yes', 'width' => '50', 'step' => '1' ),
											array( '_id' => demo_id( 'field' ), 'label' => 'Phone', 'type' => 'tel', 'required' => '', 'width' => '50', 'step' => '1' ),
											array( '_id' => demo_id( 'field' ), 'label' => 'Country', 'type' => 'country', 'required' => '', 'width' => '50', 'step' => '1' ),
											array( '_id' => demo_id( 'field' ), 'label' => 'Message', 'type' => 'textarea', 'required' => 'yes', 'width' => '100', 'step' => '1' ),
										),
									)
								),
							),
							60
						),
					),
					array( 'flex_align_items' => 'flex-start' )
				),
			)
		),
		band( el( 'google_maps', array( 'address' => 'London, United Kingdom', 'height' => array( 'unit' => 'px', 'size' => 380 ) ) ), true ),
	),
);

$legal = array(
	'privacy-policy' => array(
		'Privacy Policy',
		'How we collect, use and protect your personal information.',
		array(
			'What we collect' => 'When you contact us or apply through our website we collect the details you give us, such as your name, email address, phone number and study plans.',
			'How we use it'   => 'We use your details to answer your questions, help with your applications and, if you agree, send you news about studying abroad.',
			'Who we share it with' => 'We only share your details with universities and partners you apply to, and with service providers who help us run our website.',
			'Your rights'     => 'You can ask to see, correct or delete your personal information at any time by contacting us.',
		),
	),
	'payment-terms'  => array(
		'Payment Terms',
		'How and when payments for our paid services are made.',
		array(
			'Fees'            => 'Any fee for a paid service is confirmed in writing before you start. Prices include all taxes unless stated otherwise.',
			'How to pay'      => 'You can pay by card or bank transfer. We send a receipt for every payment.',
			'When to pay'     => 'Course and test fees are due before the start date shown on your booking confirmation.',
		),
	),
	'refund-policy'  => array(
		'Refund Policy',
		'When you can get your money back and how to ask for it.',
		array(
			'Cancelling a booking' => 'Cancel at least 14 days before the start date and we refund the full amount.',
			'Late cancellations'   => 'Cancellations within 14 days of the start date receive a 50% refund, unless we can fill your place.',
			'How to ask for a refund' => 'Contact us with your booking reference. Refunds are paid within 10 working days.',
		),
	),
);
foreach ( $legal as $slug => $doc ) {
	$current = $slug;
	$html    = '<p><em>This is demo text. Replace it with your own ' . strtolower( $doc[0] ) . '.</em></p>';
	foreach ( $doc[2] as $h => $p ) {
		$html .= '<h2>' . $h . '</h2><p>' . $p . '</p>';
	}
	$pages[ $slug ] = array(
		'title' => $doc[0],
		'slug'  => $slug,
		'seo'   => array( 'description' => $doc[1] ),
		'data'  => array(
			banner(),
			section( array( text( $html ) ), array( 'boxed_width' => array( 'unit' => 'px', 'size' => 860 ) ) ),
		),
	);
}

/* Templates -------------------------------------------------------------- */

$templates = array();
$white     = array( 'title_color' => '#FFFFFF' );

$current             = 'header';
$templates['header'] = array(
	'title'   => 'Header',
	'setting' => 'templates.header',
	'data'    => array(
		band( el( 'qe-top-bar', array( 'source' => 'menu', 'menu' => '{{menu:topbar}}' ) ), true ),
		el(
			'container',
			array(
				'content_width'        => 'full',
				'flex_direction'       => 'row',
				'flex_justify_content' => 'space-between',
				'flex_align_items'     => 'center',
				'flex_gap'             => gap( 24 ),
				'flex_wrap'            => 'nowrap',
				'padding'              => px( 12, 32 ),
				'padding_mobile'       => px( 10, 16 ),
			),
			array(
				el( 'qe-site-logo', array( '_flex_size' => 'none', 'width' => array( 'unit' => 'px', 'size' => 200 ), 'width_mobile' => array( 'unit' => 'px', 'size' => 160 ) ) ),
				el( 'qe-mega-menu', array( 'menu' => '{{menu:primary}}', '_flex_size' => 'grow' ) ),
				button( 'Apply Now', page_url( 'contact' ), array( '_flex_size' => 'none', 'hide_mobile' => 'hidden-mobile' ) ),
			)
		),
	),
);

$current             = 'footer';
$col                 = static function ( $title, $items ) use ( $white ) {
	return column(
		array(
			heading( $title, 'h4', $white ),
			links( $items, 'traditional', array( 'text_color' => '#FFFFFFC7', 'text_color_hover' => '#FFFFFF' ) ),
		),
		20,
		array( 'width_tablet' => array( 'unit' => '%', 'size' => 30 ) )
	);
};
$templates['footer'] = array(
	'title'   => 'Footer',
	'setting' => 'templates.footer',
	'data'    => array(
		band( el( 'qe-newsletter', array() ) ),
		el(
			'container',
			array(
				'content_width'          => 'boxed',
				'flex_direction'         => 'column',
				'flex_gap'               => gap( 32 ),
				'padding'                => px( 64, 0, 24, 0 ),
				'padding_mobile'         => px( 48, 0, 24, 0 ),
				'background_background'  => 'classic',
				'__globals__'            => array( 'background_color' => 'globals/colors?id=secondary' ),
			),
			array(
				row(
					array(
						column(
							array(
								el( 'qe-site-logo', array( 'variant' => 'dark' ) ),
								el( 'text-editor', array( 'editor' => '<p>Helping students study at great universities around the world since 2011.</p>', 'text_color' => '#FFFFFFC7' ) ),
								el( 'qe-social-links', array() ),
							),
							30,
							array( 'width_tablet' => array( 'unit' => '%', 'size' => 100 ) )
						),
						$col( 'Resources', array( array( 'Language Academy', page_url( 'language-academy' ) ), array( 'ELP Test', page_url( 'elp-test' ) ), array( 'Careers', page_url( 'careers' ) ), array( 'Events & Webinars', page_url( 'events' ) ) ) ),
						$col( 'Study', array( array( 'Study Destinations', page_url( 'study-abroad' ) ), array( 'International Pathways', page_url( 'international-pathways' ) ), array( 'Study Mobility Program', page_url( 'study-mobility-program' ) ), array( 'Study News', page_url( 'news' ) ) ) ),
						$col( 'About', array( array( 'About Us', page_url( 'about' ) ), array( 'What We Do', page_url( 'what-we-do' ) ), array( 'Partner With Us', page_url( 'partner-with-us' ) ), array( 'Contact Us', page_url( 'contact' ) ) ) ),
					),
					array( 'flex_align_items' => 'flex-start', 'flex_gap' => gap( 32 ), 'flex_wrap' => 'nowrap', 'flex_wrap_tablet' => 'wrap', 'flex_direction_tablet' => 'row' )
				),
				el( 'divider', array( 'color' => '#FFFFFF1F' ) ),
				row(
					array(
						el( 'text-editor', array_merge( array( 'text_color' => '#FFFFFFC7', '_element_width' => 'auto' ), setting_text( 'copyright' ) ) ),
						links( array( array( 'Privacy Policy', page_url( 'privacy-policy' ) ), array( 'Payment Terms', page_url( 'payment-terms' ) ), array( 'Refund Policy', page_url( 'refund-policy' ) ) ), 'inline', array( 'text_color' => '#FFFFFFC7', 'text_color_hover' => '#FFFFFF', '_element_width' => 'auto' ) ),
					),
					array( 'flex_justify_content' => 'space-between', 'flex_wrap' => 'wrap', 'flex_gap' => gap( 16 ) )
				),
			)
		),
	),
);

$current               = 'not-found';
$templates['not-found'] = array(
	'title'   => '404 Page',
	'setting' => 'templates.not_found',
	'data'    => array(
		section(
			array(
				heading( '404', 'h1', array( 'align' => 'center' ) ),
				heading( 'We could not find that page', 'h2', array( 'align' => 'center' ) ),
				el( 'text-editor', array( 'editor' => '<p>The page may have moved. Try the home page or tell us what you were looking for.</p>', 'align' => 'center' ) ),
				row(
					array(
						button( 'Go to the home page', '{{home-url}}', array( '_element_width' => 'auto' ) ),
						button( 'Contact us', page_url( 'contact' ), array( '_element_width' => 'auto' ) ),
					),
					array( 'flex_justify_content' => 'center', 'flex_gap' => gap( 16 ), 'flex_direction_mobile' => 'row', 'flex_wrap' => 'wrap' )
				),
			),
			array( 'flex_align_items' => 'center', 'min_height' => array( 'unit' => 'vh', 'size' => 60 ), 'flex_justify_content' => 'center' )
		),
	),
);

$current             = 'single';
$templates['single'] = array(
	'title'   => 'Single Post',
	'setting' => 'templates.single',
	'data'    => array( band( el( 'qe-page-title', array( 'background_image' => media( 'banner' ), 'min_height' => 260 ) ), true ) ),
);

$current              = 'archive';
$templates['archive'] = array(
	'title'   => 'Archive',
	'setting' => 'templates.archive',
	'data'    => array( band( el( 'qe-page-title', array( 'background_image' => media( 'banner' ), 'min_height' => 260 ) ), true ) ),
);

/* Content: images, posts, menus, settings -------------------------------- */

$images = array();
foreach ( glob( $out . '/images/*' ) as $file ) {
	$name = basename( $file );
	$key  = preg_replace( '/\.[a-z]+$/', '', $name );
	$images[ $key ] = array(
		'file' => $name,
		'alt'  => ucfirst( str_replace( '-', ' ', $key ) ),
	);
}
foreach ( $destinations_for_alt = array(
	'united-kingdom' => 'United Kingdom', 'united-states' => 'United States', 'canada' => 'Canada', 'australia' => 'Australia',
	'ireland' => 'Ireland', 'germany' => 'Germany', 'france' => 'France', 'netherlands' => 'Netherlands',
	'new-zealand' => 'New Zealand', 'malaysia' => 'Malaysia', 'uae' => 'United Arab Emirates', 'turkey' => 'Türkiye',
	'cyprus' => 'Cyprus', 'malta' => 'Malta', 'spain' => 'Spain', 'italy' => 'Italy',
) as $key => $country ) {
	$images[ 'destination-' . $key ]['alt'] = 'Study in ' . $country;
}
$partner_alts = array( 'Northbridge University', 'Harbour Institute of Technology', 'St Aldric College', 'Westmere University', 'Lakeside Business School', 'Kingsgate University', 'Avalon Global Academy', 'Meridian College London', 'Pacific Rim University', 'Crestwood Institute', 'Bright Path Advisors', 'Global Study Link' );
foreach ( $partner_alts as $i => $name ) {
	$images[ 'partner-' . ( $i + 1 ) ]['alt'] = $name . ' logo';
}
foreach ( array( 'team-1' => 'Amira Rahman', 'team-2' => 'Samuel Kim', 'team-3' => 'Maria Lopez', 'team-4' => 'James Turner', 'student-1' => 'Nadia A.', 'student-2' => 'Daniel O.', 'student-3' => 'Lina C.' ) as $key => $name ) {
	$images[ $key ]['alt'] = $name;
}
foreach ( array( 'hero-1', 'hero-2', 'hero-3', 'banner', 'about' ) as $key ) {
	$images[ $key ]['alt'] = '';
}
$images['logo']['alt']            = 'Your Education Group';
$images['logo-light']['alt']      = 'Your Education Group';
$images['company-profile']['alt'] = 'Company profile';

$posts = array();

$destinations = array(
	'united-kingdom' => 'United Kingdom', 'united-states' => 'United States', 'canada' => 'Canada', 'australia' => 'Australia',
	'ireland' => 'Ireland', 'germany' => 'Germany', 'france' => 'France', 'netherlands' => 'Netherlands',
	'new-zealand' => 'New Zealand', 'malaysia' => 'Malaysia', 'uae' => 'United Arab Emirates', 'turkey' => 'Türkiye',
	'cyprus' => 'Cyprus', 'malta' => 'Malta', 'spain' => 'Spain', 'italy' => 'Italy',
);
$order = 0;
foreach ( $destinations as $key => $country ) {
	$posts[] = array(
		'key'     => 'destination-' . $key,
		'type'    => 'qe_destination',
		'title'   => $country,
		'excerpt' => 'Universities, cities and student life in ' . $country . '.',
		'content' => '<p>Find out about universities, costs, visas and student life in ' . $country . '. Our advisors will help you choose the right programme.</p>',
		'image'   => 'destination-' . $key,
		'order'   => $order++,
		'meta'    => array( 'country' => $country, 'link' => page_url( 'study-abroad' ) ),
	);
}

$services = array(
	'recruitment' => array( 'Direct Entry Student Recruitment', 'We match qualified students with the right university and guide them through admission.' ),
	'pathways'    => array( 'International Pathways', 'Foundation and pre-master’s programmes that lead straight into degree study.' ),
	'preparation' => array( 'In-country University Preparation', 'English and academic skills courses taught in the student’s home country.' ),
	'campus'      => array( 'Managed Campus', 'We run international study centres in partnership with universities.' ),
);
$links = array( 'recruitment' => 'study-abroad', 'pathways' => 'international-pathways', 'preparation' => 'language-academy', 'campus' => 'what-we-do' );
$order = 0;
foreach ( $services as $key => $s ) {
	$posts[] = array(
		'key'     => 'service-' . $key,
		'type'    => 'qe_service',
		'title'   => $s[0],
		'excerpt' => $s[1],
		'image'   => 'service-' . $key,
		'order'   => $order++,
		'meta'    => array( 'link' => page_url( $links[ $key ] ) ),
	);
}

$partner_names = array( 'Northbridge University', 'Harbour Institute of Technology', 'St Aldric College', 'Westmere University', 'Lakeside Business School', 'Kingsgate University', 'Avalon Global Academy', 'Meridian College London', 'Pacific Rim University', 'Crestwood Institute', 'Bright Path Advisors', 'Global Study Link' );
foreach ( $partner_names as $i => $name ) {
	$posts[] = array(
		'key'   => 'partner-' . ( $i + 1 ),
		'type'  => 'qe_partner',
		'title' => $name . ' (demo)',
		'image' => 'partner-' . ( $i + 1 ),
		'order' => $i,
		'meta'  => array( 'type' => $i >= 10 ? 'recruitment' : 'university', 'url' => '' ),
	);
}

$events = array(
	array( 'Study in Europe Education Fair', '+20 days', '11:00', 'Grand Hall, Dubai', '', 'Meet admissions teams from more than 30 European universities in one afternoon.' ),
	array( 'Webinar: Scholarships for International Students', '+34 days', '18:00', 'Online', 'https://example.com/webinar', 'Learn how to find and apply for scholarships, with tips from our advisors.' ),
	array( 'Pathway Programmes Information Session', '+55 days', '16:30', 'Our office, London', '', 'Everything you need to know about foundation and pre-master’s pathways.' ),
);
foreach ( $events as $i => $e ) {
	$posts[] = array(
		'key'     => 'event-' . ( $i + 1 ),
		'type'    => 'qe_event',
		'title'   => $e[0],
		'excerpt' => $e[5],
		'content' => '<p>' . $e[5] . '</p><p>Places are free but limited — register early.</p>',
		'image'   => 'event-' . ( $i + 1 ),
		'meta'    => array( 'date' => '{{date:' . $e[1] . '}}', 'time' => $e[2], 'venue' => $e[3], 'online_link' => $e[4] ),
	);
}

$team = array(
	array( 'Amira Rahman', 'Head of Admissions', 'Amira has helped more than 2,000 students find their university.' ),
	array( 'Samuel Kim', 'Senior Student Advisor', 'Samuel specialises in UK and Irish universities.' ),
	array( 'Maria Lopez', 'Academic Director', 'Maria leads our pathway and English programmes.' ),
	array( 'James Turner', 'Partnerships Manager', 'James looks after our university and agent partners.' ),
);
foreach ( $team as $i => $t ) {
	$posts[] = array(
		'key'     => 'team-' . ( $i + 1 ),
		'type'    => 'qe_team',
		'title'   => $t[0],
		'excerpt' => $t[2],
		'image'   => 'team-' . ( $i + 1 ),
		'order'   => $i,
		'meta'    => array( 'role' => $t[1], 'linkedin' => '' ),
	);
}

$quotes = array(
	array( 'Nadia A.', 'MSc student, Northbridge University', 'My advisor answered every question, even at midnight before my visa interview. I could not have done it alone.' ),
	array( 'Daniel O.', 'Foundation year student', 'The pathway programme gave me the confidence and English I needed for my degree.' ),
	array( 'Lina C.', 'BA student, Westmere University', 'From choosing a course to finding accommodation, everything was clear and simple.' ),
);
foreach ( $quotes as $i => $q ) {
	$posts[] = array(
		'key'     => 'testimonial-' . ( $i + 1 ),
		'type'    => 'qe_testimonial',
		'title'   => $q[0],
		'excerpt' => $q[2],
		'image'   => 'student-' . ( $i + 1 ),
		'order'   => $i,
		'meta'    => array( 'role' => $q[1], 'rating' => '5' ),
	);
}

$news = array(
	array( '10 scholarships international students often miss', 'Scholarships', 'Many scholarships go unclaimed every year. Here are ten worth checking before you apply.' ),
	array( 'How to write a personal statement that stands out', 'Applications', 'Admissions teams read thousands of statements. These simple steps will make yours memorable.' ),
	array( 'Foundation year or direct entry: which is right for you?', 'Pathways', 'We compare the two routes so you can choose with confidence.' ),
	array( 'What to pack for your first year abroad', 'Student life', 'A practical checklist from students who have done it.' ),
	array( 'Understanding English test requirements', 'English', 'IELTS, PTE or ELP Test? What universities ask for and how to prepare.' ),
	array( 'Five cities students love in 2026', 'Destinations', 'Affordable, friendly and full of opportunities — our students’ favourite study cities.' ),
);
foreach ( $news as $i => $n ) {
	$posts[] = array(
		'key'      => 'news-' . ( $i + 1 ),
		'type'     => 'post',
		'title'    => $n[0],
		'excerpt'  => $n[2],
		'content'  => '<p>' . $n[2] . '</p><h2>Start early</h2><p>The earlier you begin, the more options you have. Make a short list, note the deadlines and ask an advisor to check your plan.</p><h2>Ask for help</h2><p>Our advisors are happy to help — the service is free for students.</p>',
		'image'    => 'news-' . ( $i + 1 ),
		'category' => $n[1],
		'date'     => '{{date:-' . ( $i * 9 + 2 ) . ' days}}',
	);
}

$menus = array(
	'primary' => array(
		'name'     => 'Main menu',
		'location' => 'primary',
		'items'    => array(
			array( 'Home', 'home' ),
			array(
				'About Us',
				'about',
				array(
					array( 'About Us', 'about' ),
					array( 'What We Do', 'what-we-do' ),
					array( 'Careers', 'careers' ),
					array( 'Events', 'events' ),
					array( 'News', 'news' ),
				),
			),
			array( 'ELP Test', 'elp-test' ),
			array( 'Partner With Us', 'partner-with-us' ),
			array( 'Study Abroad', 'study-abroad' ),
			array( 'International Pathways', 'international-pathways' ),
			array( 'Language Academy', 'language-academy' ),
			array(
				'Partners',
				'partner-institutions',
				array(
					array( 'Partner Institutions', 'partner-institutions' ),
					array( 'Recruitment Partners', 'recruitment-partners' ),
				),
			),
		),
	),
	'topbar'  => array(
		'name'     => 'Top bar',
		'location' => 'topbar',
		'items'    => array( array( 'News', 'news' ), array( 'Events', 'events' ), array( 'Careers', 'careers' ), array( 'Study Mobility Program', 'study-mobility-program' ), array( 'Contact Us', 'contact' ) ),
	),
	'footer-resources' => array(
		'name'     => 'Footer: Resources',
		'location' => 'footer-resources',
		'items'    => array( array( 'Language Academy', 'language-academy' ), array( 'ELP Test', 'elp-test' ), array( 'Careers', 'careers' ), array( 'Events & Webinars', 'events' ) ),
	),
	'footer-study'     => array(
		'name'     => 'Footer: Study',
		'location' => 'footer-study',
		'items'    => array( array( 'Study Destinations', 'study-abroad' ), array( 'International Pathways', 'international-pathways' ), array( 'Study Mobility Program', 'study-mobility-program' ), array( 'Study News', 'news' ) ),
	),
	'footer-about'     => array(
		'name'     => 'Footer: About',
		'location' => 'footer-about',
		'items'    => array( array( 'About Us', 'about' ), array( 'What We Do', 'what-we-do' ), array( 'Partner With Us', 'partner-with-us' ), array( 'Contact Us', 'contact' ) ),
	),
	'legal'            => array(
		'name'     => 'Footer: Legal',
		'location' => 'legal',
		'items'    => array( array( 'Privacy Policy', 'privacy-policy' ), array( 'Payment Terms', 'payment-terms' ), array( 'Refund Policy', 'refund-policy' ) ),
	),
);

$settings = array(
	'brand.org_name'     => 'Your Education Group',
	'brand.logo'         => '{{image:logo}}',
	'brand.logo_dark'    => '{{image:logo-light}}',
	'brand.profile_pdf'  => '{{image:company-profile}}',
	'contact.phone'      => '+44 20 0000 0000',
	'contact.email'      => 'hello@example.com',
	'contact.whatsapp'   => '+44 7000 000000',
	'contact.address'    => "1 Example Street\nLondon, United Kingdom",
	'social.facebook'    => 'https://www.facebook.com/',
	'social.instagram'   => 'https://www.instagram.com/',
	'social.linkedin'    => 'https://www.linkedin.com/',
	'social.youtube'     => 'https://www.youtube.com/',
	'header.cta_text'    => 'Apply Now',
	'header.cta_link'    => page_url( 'contact' ),
	'seo.default_image'  => '{{image:banner}}',
	'seo.home_title'     => '{site} {sep} Study Abroad & International Pathways',
);

/* Write ---------------------------------------------------------------- */

$write = static function ( $path, $data ) {
	if ( ! is_dir( dirname( $path ) ) ) {
		mkdir( dirname( $path ), 0755, true );
	}
	file_put_contents( $path, json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" );
};

$manifest = array(
	'version'    => 1,
	'images'     => $images,
	'posts'      => $posts,
	'pages'      => array(),
	'templates'  => array(),
	'menus'      => $menus,
	'settings'   => $settings,
	'front_page' => 'home',
	'posts_page' => 'news',
);

foreach ( $pages as $key => $page ) {
	$entry = array(
		'key'   => $key,
		'title' => $page['title'],
		'slug'  => $page['slug'],
		'seo'   => $page['seo'],
	);
	if ( ! isset( $page['builder'] ) || $page['builder'] ) {
		$entry['file'] = 'pages/' . $key . '.json';
		$write( $out . '/' . $entry['file'], $page['data'] );
	}
	$manifest['pages'][] = $entry;
}

foreach ( $templates as $key => $template ) {
	$file                    = 'templates/' . $key . '.json';
	$manifest['templates'][] = array(
		'key'     => $key,
		'title'   => $template['title'],
		'setting' => $template['setting'],
		'file'    => $file,
	);
	$write( $out . '/' . $file, $template['data'] );
}

$write( $out . '/manifest.json', $manifest );

echo count( $manifest['pages'] ) . ' pages, ' . count( $manifest['templates'] ) . ' templates, ' . count( $posts ) . ' posts, ' . count( $images ) . " images\n";
