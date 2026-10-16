<?php
/**
 * Theme Settings schema: every tab and field, with plain-English labels, a
 * one-line help text and a default. The single source of truth for the admin
 * panel, sanitising, defaults, export / import and reset.
 *
 * Field keys:
 *  - type:    text|textarea|code|url|email|tel|number|checkbox|select|color|media|template|swatch|redirects
 *  - label:   label shown to the site owner;
 *  - help:    one-line help shown under the field;
 *  - default: default value;
 *  - path:    optional storage path (defaults to "{tab}.{field}");
 *  - options: choices for `select`;
 *  - group:   optional sub-heading the field is listed under;
 *  - min/max: bounds for `number`;
 *  - mime:    media library filter for `media` (image or application/pdf).
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Settings;

use QeEdu\Core\Colors\FontPairs;
use QeEdu\Core\Colors\Palette;

defined( 'ABSPATH' ) || exit;

/**
 * Schema.
 */
final class Schema {

	/**
	 * All tabs, filterable through `qe_edu_settings_schema`.
	 *
	 * @return array<string,array<string,mixed>> tab id => [label, icon, intro, fields].
	 */
	public static function tabs() {
		$tabs = array(
			'brand'       => array(
				'label'  => __( 'Brand', 'qe-edu' ),
				'intro'  => __( 'Your logo, site icon and company profile.', 'qe-edu' ),
				'fields' => self::brand(),
			),
			'colors'      => array(
				'label'   => __( 'Colours', 'qe-edu' ),
				'intro'   => __( 'Pick an accent colour — the whole site and Elementor update together.', 'qe-edu' ),
				'preview' => true,
				'fields'  => self::colors(),
			),
			'typography'  => array(
				'label'   => __( 'Typography', 'qe-edu' ),
				'intro'   => __( 'Fonts and text sizes used across the site.', 'qe-edu' ),
				'preview' => true,
				'fields'  => self::typography(),
			),
			'header'      => array(
				'label'  => __( 'Header', 'qe-edu' ),
				'intro'  => __( 'The bar at the top of every page.', 'qe-edu' ),
				'fields' => self::header(),
			),
			'footer'      => array(
				'label'  => __( 'Footer', 'qe-edu' ),
				'intro'  => __( 'The area at the bottom of every page.', 'qe-edu' ),
				'fields' => self::footer(),
			),
			'contact'     => array(
				'label'  => __( 'Contact & Social', 'qe-edu' ),
				'intro'  => __( 'Used by widgets, the footer, the WhatsApp button and search engines.', 'qe-edu' ),
				'fields' => self::contact(),
			),
			'seo'         => array(
				'label'  => __( 'SEO', 'qe-edu' ),
				'intro'  => __( 'How your site appears on Google and when shared on social media.', 'qe-edu' ),
				'fields' => self::seo(),
			),
			'performance' => array(
				'label'  => __( 'Performance', 'qe-edu' ),
				'intro'  => __( 'Speed options. The defaults are right for almost every site.', 'qe-edu' ),
				'fields' => self::performance(),
			),
			'texts'       => array(
				'label'  => __( 'Texts', 'qe-edu' ),
				'intro'  => __( 'Every small text the theme shows outside your Elementor pages. Leave a box empty to use the default shown in grey.', 'qe-edu' ),
				'fields' => self::texts(),
			),
			'tools'       => array(
				'label'  => __( 'Tools', 'qe-edu' ),
				'intro'  => __( 'Import the demo, back up or restore your settings, or reset a section.', 'qe-edu' ),
				'fields' => array(),
			),
		);

		/**
		 * Filters the Theme Settings schema.
		 *
		 * @param array $tabs Tabs.
		 */
		return apply_filters( 'qe_edu_settings_schema', $tabs );
	}

	/**
	 * Flat list of every field, keyed by storage path, with `tab` and `key` added.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function fields() {
		$fields = array();

		foreach ( self::tabs() as $tab => $definition ) {
			foreach ( $definition['fields'] as $key => $field ) {
				$path            = isset( $field['path'] ) ? $field['path'] : $tab . '.' . $key;
				$field['tab']    = $tab;
				$field['key']    = $key;
				$field['path']   = $path;
				$fields[ $path ] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Default value of every field, as a nested array.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		$defaults = array();

		foreach ( self::fields() as $path => $field ) {
			Settings::set_path( $defaults, $path, $field['default'] );
		}

		return $defaults;
	}

	/**
	 * Brand tab.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function brand() {
		return array(
			'org_name'    => array(
				'type'    => 'text',
				'label'   => __( 'Organisation name', 'qe-edu' ),
				'help'    => __( 'Your official name, used by search engines. Leave empty to use the site title.', 'qe-edu' ),
				'default' => '',
			),
			'logo'        => array(
				'type'    => 'media',
				'mime'    => 'image',
				'label'   => __( 'Logo', 'qe-edu' ),
				'help'    => __( 'Shown in the header. A transparent PNG or SVG around 240×80 looks best.', 'qe-edu' ),
				'default' => 0,
			),
			'logo_dark'   => array(
				'type'    => 'media',
				'mime'    => 'image',
				'label'   => __( 'Logo for dark backgrounds', 'qe-edu' ),
				'help'    => __( 'A light version of your logo, used in dark mode and on dark footers.', 'qe-edu' ),
				'default' => 0,
			),
			'favicon'     => array(
				'type'    => 'media',
				'mime'    => 'image',
				'label'   => __( 'Site icon (favicon)', 'qe-edu' ),
				'help'    => __( 'The small square icon in browser tabs. Use a square image of at least 512×512.', 'qe-edu' ),
				'default' => 0,
			),
			'profile_pdf' => array(
				'type'    => 'media',
				'mime'    => 'application/pdf',
				'label'   => __( 'Company profile PDF', 'qe-edu' ),
				'help'    => __( 'The file visitors get when they click "Download PDF".', 'qe-edu' ),
				'default' => 0,
			),
		);
	}

	/**
	 * Colours tab.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function colors() {
		return array(
			'accent'         => array(
				'type'    => 'swatch',
				'label'   => __( 'Accent colour', 'qe-edu' ),
				'help'    => __( 'Click a swatch. Buttons, links and highlights change everywhere, including in Elementor.', 'qe-edu' ),
				'default' => Palette::DEFAULT_ACCENT,
			),
			'custom'         => array(
				'type'    => 'color',
				'label'   => __( 'Custom accent colour', 'qe-edu' ),
				'help'    => __( 'Used when the "Custom" swatch is selected. You will see a warning if text on it would be hard to read.', 'qe-edu' ),
				'default' => '#A41E34',
			),
			'mode'           => array(
				'type'    => 'select',
				'label'   => __( 'Light or dark mode', 'qe-edu' ),
				'help'    => __( '"Automatic" follows each visitor\'s device setting.', 'qe-edu' ),
				'default' => 'light',
				'options' => array(
					'light' => __( 'Light', 'qe-edu' ),
					'dark'  => __( 'Dark', 'qe-edu' ),
					'auto'  => __( 'Automatic', 'qe-edu' ),
				),
			),
			'background'     => array(
				'type'    => 'select',
				'label'   => __( 'Background tint', 'qe-edu' ),
				'help'    => __( 'A very light wash of your accent colour behind soft sections.', 'qe-edu' ),
				'default' => 'neutral',
				'options' => array(
					'neutral' => __( 'Neutral (light grey-violet)', 'qe-edu' ),
					'accent'  => __( 'Tinted with the accent colour', 'qe-edu' ),
					'white'   => __( 'Plain white', 'qe-edu' ),
				),
			),
			'button_style'   => array(
				'type'    => 'select',
				'label'   => __( 'Button shape', 'qe-edu' ),
				'help'    => __( 'Applies to every button on the site.', 'qe-edu' ),
				'default' => 'rounded',
				'options' => array(
					'rounded' => __( 'Rounded corners', 'qe-edu' ),
					'pill'    => __( 'Pill (fully round)', 'qe-edu' ),
					'square'  => __( 'Square corners', 'qe-edu' ),
				),
			),
			'link_underline' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Underline links in text', 'qe-edu' ),
				'help'    => __( 'Underlined links are easier to spot for everyone.', 'qe-edu' ),
				'default' => true,
			),
			'hero_gradient'  => array(
				'type'    => 'checkbox',
				'label'   => __( 'Use an accent-to-navy gradient behind heroes', 'qe-edu' ),
				'help'    => __( 'Applies to hero sections that have no background image.', 'qe-edu' ),
				'default' => true,
			),
		);
	}

	/**
	 * Typography tab.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function typography() {
		$pairs = array();
		foreach ( FontPairs::all() as $slug => $pair ) {
			$pairs[ $slug ] = $pair['label'];
		}

		return array(
			'pair'          => array(
				'type'    => 'select',
				'label'   => __( 'Font pair', 'qe-edu' ),
				'help'    => __( 'One font for headings and one for body text, chosen to look good together.', 'qe-edu' ),
				'default' => FontPairs::DEFAULT_PAIR,
				'options' => $pairs,
			),
			'base_size'     => array(
				'type'    => 'number',
				'label'   => __( 'Body text size (px)', 'qe-edu' ),
				'help'    => __( 'Between 14 and 20. 16 is the web standard.', 'qe-edu' ),
				'default' => 16,
				'min'     => 14,
				'max'     => 20,
			),
			'heading_scale' => array(
				'type'    => 'select',
				'label'   => __( 'Heading size', 'qe-edu' ),
				'help'    => __( 'How much bigger headings are than body text.', 'qe-edu' ),
				'default' => 'normal',
				'options' => array(
					'compact' => __( 'Compact', 'qe-edu' ),
					'normal'  => __( 'Normal', 'qe-edu' ),
					'large'   => __( 'Large', 'qe-edu' ),
				),
			),
			'self_host'     => array(
				'type'    => 'checkbox',
				'label'   => __( 'Serve fonts from this website', 'qe-edu' ),
				'help'    => __( 'Faster and privacy-friendly (no Google request). Works with the default font pair.', 'qe-edu' ),
				'default' => true,
			),
		);
	}

	/**
	 * Header tab.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function header() {
		return array(
			'template'    => array(
				'type'    => 'template',
				'path'    => 'templates.header',
				'label'   => __( 'Header design', 'qe-edu' ),
				'help'    => __( 'Pick the Elementor template used as the header, or keep the built-in header.', 'qe-edu' ),
				'default' => 0,
			),
			'sticky'      => array(
				'type'    => 'checkbox',
				'label'   => __( 'Keep the header visible while scrolling', 'qe-edu' ),
				'help'    => __( 'The header stays at the top of the screen as visitors scroll.', 'qe-edu' ),
				'default' => true,
			),
			'transparent' => array(
				'type'    => 'checkbox',
				'label'   => __( 'See-through header over the hero', 'qe-edu' ),
				'help'    => __( 'The header floats over the first section of the home page.', 'qe-edu' ),
				'default' => false,
			),
			'topbar'      => array(
				'type'    => 'checkbox',
				'label'   => __( 'Show the top bar', 'qe-edu' ),
				'help'    => __( 'Small links above the header (News, Events, Careers…). Edit them in Appearance → Menus → "Top bar links".', 'qe-edu' ),
				'default' => true,
			),
			'cta_text'    => array(
				'type'    => 'text',
				'label'   => __( 'Header button text', 'qe-edu' ),
				'help'    => __( 'For example "Apply Now". The button only shows when it has a link.', 'qe-edu' ),
				'default' => '',
			),
			'cta_link'    => array(
				'type'    => 'url',
				'label'   => __( 'Header button link', 'qe-edu' ),
				'help'    => __( 'The page the header button opens.', 'qe-edu' ),
				'default' => '',
			),
			'mobile_menu' => array(
				'type'    => 'select',
				'label'   => __( 'Mobile menu style', 'qe-edu' ),
				'help'    => __( 'How the menu opens on phones.', 'qe-edu' ),
				'default' => 'fullscreen',
				'options' => array(
					'fullscreen' => __( 'Full screen', 'qe-edu' ),
					'slide'      => __( 'Slide in from the side', 'qe-edu' ),
				),
			),
		);
	}

	/**
	 * Footer tab.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function footer() {
		return array(
			'template'        => array(
				'type'    => 'template',
				'path'    => 'templates.footer',
				'label'   => __( 'Footer design', 'qe-edu' ),
				'help'    => __( 'Pick the Elementor template used as the footer, or keep the built-in footer.', 'qe-edu' ),
				'default' => 0,
			),
			'newsletter'      => array(
				'type'    => 'checkbox',
				'label'   => __( 'Show the newsletter sign-up', 'qe-edu' ),
				'help'    => __( 'Shows the newsletter box in the footer.', 'qe-edu' ),
				'default' => true,
			),
			'mailchimp_key'   => array(
				'type'    => 'text',
				'label'   => __( 'Mailchimp API key (optional)', 'qe-edu' ),
				'help'    => __( 'Only if newsletter sign-ups should go to Mailchimp. Mailchimp → Account → Extras → API keys.', 'qe-edu' ),
				'default' => '',
			),
			'mailchimp_list'  => array(
				'type'    => 'text',
				'label'   => __( 'Mailchimp audience ID', 'qe-edu' ),
				'help'    => __( 'Mailchimp → Audience → Settings → "Audience ID".', 'qe-edu' ),
				'default' => '',
			),
			'copyright'       => array(
				'type'    => 'text',
				'label'   => __( 'Copyright text', 'qe-edu' ),
				'help'    => __( 'Use {year} for the current year and {site} for your site name.', 'qe-edu' ),
				'default' => '',
			),
			'back_to_top'     => array(
				'type'    => 'checkbox',
				'label'   => __( 'Show the "back to top" button', 'qe-edu' ),
				'help'    => __( 'A small round button that appears after scrolling down.', 'qe-edu' ),
				'default' => true,
			),
			'mobile_bar'      => array(
				'type'    => 'checkbox',
				'label'   => __( 'Sticky "Apply / WhatsApp" bar on phones', 'qe-edu' ),
				'help'    => __( 'A bar fixed to the bottom of the screen on phones.', 'qe-edu' ),
				'default' => true,
			),
			'mobile_bar_text' => array(
				'type'    => 'text',
				'label'   => __( 'Phone bar: button text', 'qe-edu' ),
				'help'    => __( 'For example "Apply now".', 'qe-edu' ),
				'default' => '',
			),
			'mobile_bar_chat' => array(
				'type'    => 'text',
				'label'   => __( 'Phone bar: WhatsApp button text', 'qe-edu' ),
				'help'    => __( 'For example "WhatsApp us". Shows when a WhatsApp number is set.', 'qe-edu' ),
				'default' => '',
			),
			'mobile_bar_link' => array(
				'type'    => 'url',
				'label'   => __( 'Phone bar: button link', 'qe-edu' ),
				'help'    => __( 'The page the phone bar button opens. The WhatsApp button uses Contact & Social → WhatsApp.', 'qe-edu' ),
				'default' => '',
			),
		);
	}

	/**
	 * Contact & Social tab.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function contact() {
		$fields = array(
			'phone'    => array(
				'type'    => 'tel',
				'label'   => __( 'Main phone', 'qe-edu' ),
				'help'    => __( 'Include the country code, e.g. +971 4 000 0000.', 'qe-edu' ),
				'default' => '',
				'group'   => __( 'Contact details', 'qe-edu' ),
			),
			'phone_2'  => array(
				'type'    => 'tel',
				'label'   => __( 'Second phone', 'qe-edu' ),
				'help'    => __( 'Optional.', 'qe-edu' ),
				'default' => '',
				'group'   => __( 'Contact details', 'qe-edu' ),
			),
			'email'    => array(
				'type'    => 'email',
				'label'   => __( 'Email', 'qe-edu' ),
				'help'    => __( 'Public email address. Form notifications also go here unless a widget says otherwise.', 'qe-edu' ),
				'default' => '',
				'group'   => __( 'Contact details', 'qe-edu' ),
			),
			'whatsapp' => array(
				'type'    => 'tel',
				'label'   => __( 'WhatsApp number', 'qe-edu' ),
				'help'    => __( 'With country code. Turns on the WhatsApp buttons.', 'qe-edu' ),
				'default' => '',
				'group'   => __( 'Contact details', 'qe-edu' ),
			),
			'address'  => array(
				'type'    => 'textarea',
				'label'   => __( 'Address', 'qe-edu' ),
				'help'    => __( 'Your office address, one line per row.', 'qe-edu' ),
				'default' => '',
				'group'   => __( 'Contact details', 'qe-edu' ),
			),
			'map'      => array(
				'type'    => 'url',
				'label'   => __( 'Map link', 'qe-edu' ),
				'help'    => __( 'In Google Maps: Share → Embed a map → copy the address inside src="…".', 'qe-edu' ),
				'default' => '',
				'group'   => __( 'Contact details', 'qe-edu' ),
			),
		);

		foreach ( self::social_networks() as $key => $label ) {
			$fields[ $key ] = array(
				'type'    => 'url',
				'path'    => 'social.' . $key,
				/* translators: %s: social network name. */
				'label'   => sprintf( __( '%s page', 'qe-edu' ), $label ),
				/* translators: %s: social network name. */
				'help'    => sprintf( __( 'Full link to your %s profile. Leave empty to hide the icon.', 'qe-edu' ), $label ),
				'default' => '',
				'group'   => __( 'Social media', 'qe-edu' ),
			);
		}

		return $fields;
	}

	/**
	 * The 10 supported social networks.
	 *
	 * @return array<string,string>
	 */
	public static function social_networks() {
		return array(
			'facebook'  => 'Facebook',
			'instagram' => 'Instagram',
			'linkedin'  => 'LinkedIn',
			'x'         => 'X (Twitter)',
			'youtube'   => 'YouTube',
			'tiktok'    => 'TikTok',
			'pinterest' => 'Pinterest',
			'snapchat'  => 'Snapchat',
			'telegram'  => 'Telegram',
			'threads'   => 'Threads',
		);
	}

	/**
	 * SEO tab.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function seo() {
		$general  = __( 'Titles & descriptions', 'qe-edu' );
		$social   = __( 'Social sharing', 'qe-edu' );
		$schema   = __( 'Organisation details for Google', 'qe-edu' );
		$indexing = __( 'Indexing', 'qe-edu' );
		$verify   = __( 'Site verification', 'qe-edu' );
		$tracking = __( 'Analytics', 'qe-edu' );

		return array(
			'title_template'   => array(
				'type'    => 'text',
				'label'   => __( 'Page title format', 'qe-edu' ),
				'help'    => __( 'Shown in Google and browser tabs. {title} = page title, {sep} = separator, {site} = site name.', 'qe-edu' ),
				'default' => '{title} {sep} {site}',
				'group'   => $general,
			),
			'separator'        => array(
				'type'    => 'select',
				'label'   => __( 'Separator', 'qe-edu' ),
				'help'    => __( 'The symbol between the page title and the site name.', 'qe-edu' ),
				'default' => '|',
				'options' => array(
					'|' => '|',
					'–' => '–',
					'—' => '—',
					'·' => '·',
					'•' => '•',
					'/' => '/',
				),
				'group'   => $general,
			),
			'home_title'       => array(
				'type'    => 'text',
				'label'   => __( 'Home page title', 'qe-edu' ),
				'help'    => __( 'The title Google shows for your home page. Leave empty for "Site name | Tagline".', 'qe-edu' ),
				'default' => '',
				'group'   => $general,
			),
			'home_description' => array(
				'type'    => 'textarea',
				'label'   => __( 'Home page description', 'qe-edu' ),
				'help'    => __( 'One or two sentences (up to 160 characters) shown under your title in Google.', 'qe-edu' ),
				'default' => '',
				'group'   => $general,
			),
			'default_image'    => array(
				'type'    => 'media',
				'mime'    => 'image',
				'label'   => __( 'Default share image', 'qe-edu' ),
				'help'    => __( 'Shown when a page is shared on Facebook, LinkedIn, WhatsApp or X. 1200×630 is ideal.', 'qe-edu' ),
				'default' => 0,
				'group'   => $social,
			),
			'facebook_app_id'  => array(
				'type'    => 'text',
				'label'   => __( 'Facebook App ID', 'qe-edu' ),
				'help'    => __( 'Optional. Only needed for Facebook Insights.', 'qe-edu' ),
				'default' => '',
				'group'   => $social,
			),
			'twitter_handle'   => array(
				'type'    => 'text',
				'label'   => __( 'X (Twitter) username', 'qe-edu' ),
				'help'    => __( 'Including the @, e.g. @youreducation.', 'qe-edu' ),
				'default' => '',
				'group'   => $social,
			),
			'org_type'         => array(
				'type'    => 'select',
				'label'   => __( 'Organisation type', 'qe-edu' ),
				'help'    => __( 'Helps Google understand what you are. Your name, logo, address, phone and social links come from the other tabs.', 'qe-edu' ),
				'default' => 'EducationalOrganization',
				'options' => array(
					'EducationalOrganization' => __( 'Educational organisation', 'qe-edu' ),
					'CollegeOrUniversity'     => __( 'College or university', 'qe-edu' ),
					'School'                  => __( 'School', 'qe-edu' ),
					'Organization'            => __( 'Company / other organisation', 'qe-edu' ),
				),
				'group'   => $schema,
			),
			'sitemap'          => array(
				'type'    => 'checkbox',
				'label'   => __( 'XML sitemap', 'qe-edu' ),
				'help'    => __( 'A list of your pages for search engines, at /wp-sitemap.xml.', 'qe-edu' ),
				'default' => true,
				'group'   => $indexing,
			),
			'breadcrumbs'      => array(
				'type'    => 'checkbox',
				'label'   => __( 'Breadcrumbs', 'qe-edu' ),
				'help'    => __( 'The "Home › Section › Page" trail, also shown in Google results.', 'qe-edu' ),
				'default' => true,
				'group'   => $indexing,
			),
			'robots_txt'       => array(
				'type'    => 'code',
				'label'   => __( 'Extra robots.txt rules', 'qe-edu' ),
				'help'    => __( 'Advanced. Added to the end of /robots.txt. Leave empty if unsure.', 'qe-edu' ),
				'default' => '',
				'group'   => $indexing,
			),
			'redirects'        => array(
				'type'    => 'redirects',
				'label'   => __( 'Redirects', 'qe-edu' ),
				'help'    => __( 'Send visitors from an old address to a new one (permanent 301 redirect).', 'qe-edu' ),
				'default' => array(),
				'group'   => $indexing,
			),
			'log_404'          => array(
				'type'    => 'checkbox',
				'label'   => __( 'Keep a list of broken links (404 log)', 'qe-edu' ),
				'help'    => __( 'Records the last 500 addresses visitors could not find, so you can redirect them.', 'qe-edu' ),
				'default' => true,
				'group'   => $indexing,
			),
			'verify_google'    => array(
				'type'    => 'text',
				'label'   => __( 'Google verification code', 'qe-edu' ),
				'help'    => __( 'From Google Search Console → HTML tag. Paste only the content="…" value.', 'qe-edu' ),
				'default' => '',
				'group'   => $verify,
			),
			'verify_bing'      => array(
				'type'    => 'text',
				'label'   => __( 'Bing verification code', 'qe-edu' ),
				'help'    => __( 'From Bing Webmaster Tools.', 'qe-edu' ),
				'default' => '',
				'group'   => $verify,
			),
			'verify_yandex'    => array(
				'type'    => 'text',
				'label'   => __( 'Yandex verification code', 'qe-edu' ),
				'help'    => __( 'From Yandex Webmaster.', 'qe-edu' ),
				'default' => '',
				'group'   => $verify,
			),
			'verify_pinterest' => array(
				'type'    => 'text',
				'label'   => __( 'Pinterest verification code', 'qe-edu' ),
				'help'    => __( 'From Pinterest → Claim website.', 'qe-edu' ),
				'default' => '',
				'group'   => $verify,
			),
			'ga4_id'           => array(
				'type'    => 'text',
				'label'   => __( 'Google Analytics 4 ID', 'qe-edu' ),
				'help'    => __( 'Looks like G-XXXXXXXXXX.', 'qe-edu' ),
				'default' => '',
				'group'   => $tracking,
			),
			'gtm_id'           => array(
				'type'    => 'text',
				'label'   => __( 'Google Tag Manager ID', 'qe-edu' ),
				'help'    => __( 'Looks like GTM-XXXXXXX. Use this or Analytics, not both.', 'qe-edu' ),
				'default' => '',
				'group'   => $tracking,
			),
			'consent'          => array(
				'type'    => 'checkbox',
				'label'   => __( 'Ask for cookie consent before loading analytics', 'qe-edu' ),
				'help'    => __( 'Shows a small consent bar; analytics load only after visitors accept.', 'qe-edu' ),
				'default' => false,
				'group'   => $tracking,
			),
			'consent_text'     => array(
				'type'    => 'textarea',
				'label'   => __( 'Consent bar text', 'qe-edu' ),
				'help'    => __( 'Leave empty for the default text.', 'qe-edu' ),
				'default' => '',
				'group'   => $tracking,
			),
			'consent_accept'   => array(
				'type'    => 'text',
				'label'   => __( 'Consent bar: accept button', 'qe-edu' ),
				'help'    => __( 'Leave empty for "Accept".', 'qe-edu' ),
				'default' => '',
				'group'   => $tracking,
			),
			'consent_decline'  => array(
				'type'    => 'text',
				'label'   => __( 'Consent bar: decline button', 'qe-edu' ),
				'help'    => __( 'Leave empty for "Decline".', 'qe-edu' ),
				'default' => '',
				'group'   => $tracking,
			),
		);
	}

	/**
	 * Performance tab.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function performance() {
		return array(
			'lazy_load'      => array(
				'type'    => 'checkbox',
				'label'   => __( 'Load images only when they scroll into view', 'qe-edu' ),
				'help'    => __( 'Makes pages open faster.', 'qe-edu' ),
				'default' => true,
			),
			'preload_hero'   => array(
				'type'    => 'checkbox',
				'label'   => __( 'Load the hero image first', 'qe-edu' ),
				'help'    => __( 'The big image at the top of a page appears sooner.', 'qe-edu' ),
				'default' => true,
			),
			'disable_emojis' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Remove the WordPress emoji script', 'qe-edu' ),
				'help'    => __( 'Emojis still show — your visitors\' devices draw them.', 'qe-edu' ),
				'default' => true,
			),
			'disable_embeds' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Remove the WordPress embed script', 'qe-edu' ),
				'help'    => __( 'Only turn off if you never paste links to other WordPress posts.', 'qe-edu' ),
				'default' => false,
			),
			'defer_js'       => array(
				'type'    => 'checkbox',
				'label'   => __( 'Load theme scripts after the page', 'qe-edu' ),
				'help'    => __( 'The page shows before scripts run.', 'qe-edu' ),
				'default' => true,
			),
		);
	}

	/**
	 * Texts tab: one field per theme string. The theme exposes its strings
	 * through `qe_edu_default_strings()`; the default shows as a placeholder.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function texts() {
		$fields = array(
			'not_found_template' => array(
				'type'    => 'template',
				'path'    => 'templates.not_found',
				'label'   => __( '404 page design', 'qe-edu' ),
				'help'    => __( 'An Elementor template shown when a page is not found. Keep the built-in one if unsure.', 'qe-edu' ),
				'default' => 0,
				'group'   => __( 'Pages', 'qe-edu' ),
			),
			'single_template'    => array(
				'type'    => 'template',
				'path'    => 'templates.single',
				'label'   => __( 'Blog post banner', 'qe-edu' ),
				'help'    => __( 'An Elementor template shown at the top of every blog post (for example a Page Title Banner). It replaces the plain post title.', 'qe-edu' ),
				'default' => 0,
				'group'   => __( 'Pages', 'qe-edu' ),
			),
			'archive_template'   => array(
				'type'    => 'template',
				'path'    => 'templates.archive',
				'label'   => __( 'Blog & archive banner', 'qe-edu' ),
				'help'    => __( 'An Elementor template shown at the top of the news page and category lists. It replaces the plain heading.', 'qe-edu' ),
				'default' => 0,
				'group'   => __( 'Pages', 'qe-edu' ),
			),
		);

		if ( ! function_exists( 'qe_edu_default_strings' ) ) {
			return $fields;
		}

		foreach ( qe_edu_default_strings() as $key => $default ) {
			$fields[ $key ] = array(
				'type'        => 'text',
				'path'        => 'strings.' . $key,
				'label'       => $default,
				'help'        => '',
				'placeholder' => $default,
				'default'     => '',
				'group'       => __( 'Theme texts', 'qe-edu' ),
			);
		}

		return $fields;
	}
}
