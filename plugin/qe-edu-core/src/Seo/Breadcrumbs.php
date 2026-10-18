<?php
/**
 * Breadcrumb trail for the current request, used by the Breadcrumbs widget,
 * the Page Title Banner and the BreadcrumbList schema.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumbs.
 */
final class Breadcrumbs {

	/**
	 * The trail: list of [label, url]; the last item (current page) has no url.
	 *
	 * @param string $home_label Label of the first crumb.
	 * @return array<int,array<string,string>>
	 */
	public static function trail( $home_label ) {
		$trail = array(
			array(
				'label' => $home_label,
				'url'   => home_url( '/' ),
			),
		);

		if ( is_front_page() ) {
			$trail[0]['url'] = '';
			return $trail;
		}

		if ( is_singular() ) {
			$post = get_queried_object();
			$type = get_post_type_object( $post->post_type );

			if ( 'post' === $post->post_type ) {
				$blog = (int) get_option( 'page_for_posts' );
				if ( $blog ) {
					$trail[] = self::crumb( get_the_title( $blog ), get_permalink( $blog ) );
				}
				$categories = get_the_category( $post->ID );
				if ( $categories ) {
					$trail[] = self::crumb( $categories[0]->name, get_category_link( $categories[0] ) );
				}
			} elseif ( 'page' !== $post->post_type && $type && $type->has_archive ) {
				$trail[] = self::crumb( $type->labels->name, get_post_type_archive_link( $post->post_type ) );
			}

			foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
				$trail[] = self::crumb( get_the_title( $ancestor ), get_permalink( $ancestor ) );
			}

			$trail[] = self::crumb( get_the_title( $post ), '' );

			return $trail;
		}

		if ( is_home() ) {
			$trail[] = self::crumb( single_post_title( '', false ), '' );
		} elseif ( is_search() ) {
			$trail[] = self::crumb( get_search_query(), '' );
		} elseif ( is_404() ) {
			$trail[] = self::crumb( __( 'Page not found', 'qe-edu-core' ), '' );
		} else {
			$trail[] = self::crumb( wp_strip_all_tags( get_the_archive_title() ), '' );
		}

		return $trail;
	}

	/**
	 * One crumb.
	 *
	 * @param string       $label Label.
	 * @param string|false $url   URL.
	 * @return array<string,string>
	 */
	private static function crumb( $label, $url ) {
		return array(
			'label' => (string) $label,
			'url'   => (string) $url,
		);
	}

	/**
	 * BreadcrumbList JSON-LD for a trail.
	 *
	 * @param array<int,array<string,string>> $trail Trail.
	 * @return array<string,mixed>
	 */
	public static function schema( $trail ) {
		$items = array();
		foreach ( array_values( $trail ) as $i => $crumb ) {
			$item = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $crumb['label'],
			);
			if ( '' !== $crumb['url'] ) {
				$item['item'] = $crumb['url'];
			}
			$items[] = $item;
		}

		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}
}
