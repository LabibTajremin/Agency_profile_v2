<?php
/**
 * SEO output in <head>: the document title, meta description, canonical,
 * robots, Open Graph / X cards, verification codes and one JSON-LD graph.
 * Switches itself off when Yoast SEO or Rank Math is active.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Seo;

use QeEdu\Core\Elementor\Widgets\EventList;
use QeEdu\Core\Elementor\Widgets\SiteLogo;
use QeEdu\Core\Settings\Schema;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Head output.
 */
final class Head {

	/**
	 * Hook in (only when no other SEO plugin is active).
	 *
	 * @return void
	 */
	public function register() {
		if ( ! Conflict::output_enabled() ) {
			return;
		}

		add_filter( 'document_title_parts', array( $this, 'title_parts' ), 20 );
		add_filter( 'wp_robots', array( $this, 'robots' ) );
		add_action( 'wp_head', array( $this, 'head' ), 1 );
		remove_action( 'wp_head', 'rel_canonical' );
	}

	/**
	 * Replace {title}, {site}, {sep} and {tagline}.
	 *
	 * @param string $template Template.
	 * @param string $title    Page title.
	 * @return string
	 */
	public static function fill( $template, $title ) {
		$text = str_replace(
			array( '{title}', '{site}', '{sep}', '{tagline}' ),
			array( $title, get_bloginfo( 'name' ), (string) Settings::get( 'seo.separator' ), get_bloginfo( 'description' ) ),
			(string) $template
		);

		return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
	}

	/**
	 * The current post id when the request is singular.
	 *
	 * @return int
	 */
	private static function post_id() {
		return is_singular() ? (int) get_queried_object_id() : 0;
	}

	/**
	 * Replace the core title parts with our single, complete title.
	 *
	 * @param array<string,string> $parts Core parts (title, page, tagline, site).
	 * @return array<string,string>
	 */
	public function title_parts( $parts ) {
		$title = $this->title();

		return '' !== $title ? array( 'title' => $title ) : $parts;
	}

	/**
	 * Document title for the current request.
	 *
	 * @param string $title Title from earlier filters.
	 * @return string
	 */
	public function title( $title = '' ) {
		$template = (string) Settings::get( 'seo.title_template' );
		$post_id  = self::post_id();

		if ( $post_id ) {
			$custom = Fields::get( $post_id, 'title' );
			if ( '' !== $custom ) {
				return self::fill( $custom, get_the_title( $post_id ) );
			}
		}

		if ( is_front_page() ) {
			$home = (string) Settings::get( 'seo.home_title' );
			if ( '' !== $home ) {
				return self::fill( $home, get_bloginfo( 'name' ) );
			}
			return self::fill( '' !== get_bloginfo( 'description' ) ? '{site} {sep} {tagline}' : '{site}', '' );
		}

		if ( $post_id ) {
			return self::fill( $template, get_the_title( $post_id ) );
		}

		if ( is_home() ) {
			return self::fill( $template, single_post_title( '', false ) );
		}

		if ( is_search() ) {
			/* translators: %s: search terms. */
			return self::fill( $template, sprintf( __( 'Search results for “%s”', 'qe-edu' ), get_search_query( false ) ) );
		}

		if ( is_404() ) {
			return self::fill( $template, __( 'Page not found', 'qe-edu' ) );
		}

		if ( is_archive() ) {
			return self::fill( $template, wp_strip_all_tags( get_the_archive_title() ) );
		}

		return $title;
	}

	/**
	 * Trim text to about 155 characters at a word boundary.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function excerpt( $text ) {
		$text = trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) $text ) ) ) );

		if ( strlen( $text ) <= 160 ) {
			return $text;
		}

		$cut = substr( $text, 0, 155 );

		return rtrim( substr( $cut, 0, (int) strrpos( $cut, ' ' ) ), ' ,.;:' ) . '…';
	}

	/**
	 * Meta description for the current request.
	 *
	 * @return string
	 */
	public static function description() {
		$post_id = self::post_id();

		if ( $post_id ) {
			$custom = Fields::get( $post_id, 'description' );
			if ( '' !== $custom ) {
				return $custom;
			}
		}

		if ( is_front_page() ) {
			$home = (string) Settings::get( 'seo.home_description' );
			return '' !== $home ? $home : (string) get_bloginfo( 'description' );
		}

		if ( $post_id ) {
			$post = get_post( $post_id );
			return self::excerpt( has_excerpt( $post ) ? $post->post_excerpt : $post->post_content );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			return self::excerpt( term_description() );
		}

		return '';
	}

	/**
	 * Canonical URL for the current request.
	 *
	 * @return string
	 */
	public static function canonical() {
		$post_id = self::post_id();

		if ( $post_id ) {
			$custom = Fields::get( $post_id, 'canonical' );
			return '' !== $custom ? $custom : (string) wp_get_canonical_url( $post_id );
		}

		if ( is_front_page() ) {
			return home_url( '/' );
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$link = get_term_link( get_queried_object() );
			return is_string( $link ) ? $link : '';
		}

		if ( is_post_type_archive() ) {
			return (string) get_post_type_archive_link( (string) get_query_var( 'post_type' ) );
		}

		return '';
	}

	/**
	 * Robots directives (core wp_robots API).
	 *
	 * @param array<string,bool|string> $robots Directives.
	 * @return array<string,bool|string>
	 */
	public function robots( $robots ) {
		$post_id = self::post_id();

		if ( is_search() || is_404() || ( $post_id && '1' === Fields::get( $post_id, 'noindex' ) ) ) {
			$robots['noindex'] = true;
		}

		if ( $post_id && '1' === Fields::get( $post_id, 'nofollow' ) ) {
			$robots['nofollow'] = true;
		}

		if ( empty( $robots['noindex'] ) ) {
			$robots['max-image-preview'] = 'large';
		}

		return $robots;
	}

	/**
	 * Share image: page image, featured image or the default share image.
	 *
	 * @return array{0:string,1:int,2:int}|null [url, width, height].
	 */
	public static function image() {
		$post_id = self::post_id();
		$ids     = array();

		if ( $post_id ) {
			$ids[] = (int) Fields::get( $post_id, 'og_image' );
			$ids[] = (int) get_post_thumbnail_id( $post_id );
		}
		$ids[] = absint( Settings::get( 'seo.default_image' ) );

		foreach ( array_filter( $ids ) as $id ) {
			$src = wp_get_attachment_image_src( $id, 'large' );
			if ( $src ) {
				return array( (string) $src[0], (int) $src[1], (int) $src[2] );
			}
		}

		return null;
	}

	/**
	 * Print a meta tag.
	 *
	 * @param string $attr    `name` or `property`.
	 * @param string $name    Name.
	 * @param string $content Content.
	 * @return void
	 */
	private static function meta( $attr, $name, $content ) {
		if ( '' !== (string) $content ) {
			printf( '<meta %1$s="%2$s" content="%3$s">' . "\n", esc_attr( $attr ), esc_attr( $name ), esc_attr( $content ) );
		}
	}

	/**
	 * Print everything in <head>.
	 *
	 * @return void
	 */
	public function head() {
		$post_id     = self::post_id();
		$title       = wp_get_document_title();
		$description = self::description();
		$canonical   = self::canonical();
		$image       = self::image();
		$og_title    = $post_id && '' !== Fields::get( $post_id, 'og_title' ) ? Fields::get( $post_id, 'og_title' ) : $title;
		$og_desc     = $post_id && '' !== Fields::get( $post_id, 'og_description' ) ? Fields::get( $post_id, 'og_description' ) : $description;

		echo "\n<!-- QE Edu SEO -->\n";
		self::meta( 'name', 'description', $description );
		if ( '' !== $canonical ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
		}

		self::meta( 'property', 'og:locale', get_locale() );
		self::meta( 'property', 'og:type', $post_id && 'post' === get_post_type( $post_id ) ? 'article' : 'website' );
		self::meta( 'property', 'og:title', $og_title );
		self::meta( 'property', 'og:description', $og_desc );
		self::meta( 'property', 'og:url', $canonical );
		self::meta( 'property', 'og:site_name', get_bloginfo( 'name' ) );
		self::meta( 'property', 'fb:app_id', (string) Settings::get( 'seo.facebook_app_id' ) );
		if ( $image ) {
			self::meta( 'property', 'og:image', $image[0] );
			self::meta( 'property', 'og:image:width', (string) $image[1] );
			self::meta( 'property', 'og:image:height', (string) $image[2] );
		}

		self::meta( 'name', 'twitter:card', $image ? 'summary_large_image' : 'summary' );
		self::meta( 'name', 'twitter:site', (string) Settings::get( 'seo.twitter_handle' ) );
		self::meta( 'name', 'twitter:title', $og_title );
		self::meta( 'name', 'twitter:description', $og_desc );
		if ( $image ) {
			self::meta( 'name', 'twitter:image', $image[0] );
		}

		self::meta( 'name', 'google-site-verification', (string) Settings::get( 'seo.verify_google' ) );
		self::meta( 'name', 'msvalidate.01', (string) Settings::get( 'seo.verify_bing' ) );
		self::meta( 'name', 'yandex-verification', (string) Settings::get( 'seo.verify_yandex' ) );
		self::meta( 'name', 'p:domain_verify', (string) Settings::get( 'seo.verify_pinterest' ) );

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( self::graph(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with HEX_TAG.
		);
		echo "<!-- / QE Edu SEO -->\n";
	}

	/**
	 * Organization node.
	 *
	 * @return array<string,mixed>
	 */
	public static function organization() {
		$name = (string) Settings::get( 'brand.org_name' );
		$node = array(
			'@type' => (string) Settings::get( 'seo.org_type' ),
			'@id'   => home_url( '/#organization' ),
			'name'  => '' !== $name ? $name : get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);

		$logo = SiteLogo::logo_id( 'default' );
		$url  = $logo ? (string) wp_get_attachment_url( $logo ) : '';
		if ( '' !== $url ) {
			$node['logo'] = $url;
		}

		foreach ( array(
			'telephone' => 'contact.phone',
			'email'     => 'contact.email',
		) as $prop => $path ) {
			$value = (string) Settings::get( $path );
			if ( '' !== $value ) {
				$node[ $prop ] = $value;
			}
		}

		$address = trim( (string) Settings::get( 'contact.address' ) );
		if ( '' !== $address ) {
			$node['address'] = array(
				'@type'         => 'PostalAddress',
				'streetAddress' => (string) preg_replace( '/\s*\n\s*/', ', ', $address ),
			);
		}

		$same_as = array();
		foreach ( array_keys( Schema::social_networks() ) as $network ) {
			$profile = (string) Settings::get( 'social.' . $network );
			if ( '' !== $profile ) {
				$same_as[] = $profile;
			}
		}
		if ( $same_as ) {
			$node['sameAs'] = $same_as;
		}

		return $node;
	}

	/**
	 * The JSON-LD graph for the current request.
	 *
	 * @return array<string,mixed>
	 */
	public static function graph() {
		$graph = array(
			self::organization(),
			array(
				'@type'           => 'WebSite',
				'@id'             => home_url( '/#website' ),
				'url'             => home_url( '/' ),
				'name'            => get_bloginfo( 'name' ),
				'publisher'       => array( '@id' => home_url( '/#organization' ) ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => home_url( '/?s={search_term_string}' ),
					'query-input' => 'required name=search_term_string',
				),
			),
		);

		$post_id   = self::post_id();
		$canonical = self::canonical();
		$crumbs    = Settings::get( 'seo.breadcrumbs' ) && ! is_front_page();

		if ( $post_id || is_front_page() || is_archive() || is_home() ) {
			$type        = $post_id ? Fields::get( $post_id, 'schema' ) : '';
			$page        = array(
				'@type'    => in_array( $type, array( 'AboutPage', 'ContactPage' ), true ) ? $type : ( is_archive() || is_home() ? 'CollectionPage' : 'WebPage' ),
				'@id'      => ( '' !== $canonical ? $canonical : home_url( '/' ) ) . '#webpage',
				'url'      => '' !== $canonical ? $canonical : home_url( '/' ),
				'name'     => wp_get_document_title(),
				'isPartOf' => array( '@id' => home_url( '/#website' ) ),
			);
			$description = self::description();
			if ( '' !== $description ) {
				$page['description'] = $description;
			}
			if ( $crumbs ) {
				$page['breadcrumb'] = array( '@id' => $page['@id'] . '-breadcrumb' );
			}
			$graph[] = $page;

			if ( $crumbs ) {
				$trail        = Breadcrumbs::schema( Breadcrumbs::trail( __( 'Home', 'qe-edu' ) ) );
				$trail['@id'] = $page['@id'] . '-breadcrumb';
				$graph[]      = array_diff_key( $trail, array( '@context' => true ) );
			}

			if ( $post_id && 'qe_event' === get_post_type( $post_id ) && '' !== (string) get_post_meta( $post_id, '_qe_date', true ) ) {
				$graph[] = array_diff_key( EventList::schema( get_post( $post_id ) ), array( '@context' => true ) );
			} elseif ( $post_id && 'Course' === $type ) {
				$graph[] = array(
					'@type'       => 'Course',
					'name'        => get_the_title( $post_id ),
					'description' => '' !== $description ? $description : get_the_title( $post_id ),
					'provider'    => array( '@id' => home_url( '/#organization' ) ),
				);
			}
		}

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
	}
}
