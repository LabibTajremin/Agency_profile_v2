/**
 * QE Edu theme behaviour for the fallback header/footer:
 * mobile menu (focus trap, Esc to close), submenu accordion, back-to-top.
 * Vanilla JS, no dependencies.
 *
 * @param {Window} root Browser window.
 */
( function ( root ) {
	const FOCUSABLE =
		'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

	function ready( doc, callback ) {
		if ( doc.readyState === 'loading' ) {
			doc.addEventListener( 'DOMContentLoaded', callback );
		} else {
			callback();
		}
	}

	function labels( win ) {
		const l10n = ( win && win.qeEduTheme ) || {};
		return {
			open: l10n.menuOpen || '',
			close: l10n.menuClose || '',
		};
	}

	function initMenu( doc, win ) {
		const header = doc.querySelector( '[data-qe-header]' );
		const toggle = doc.querySelector( '[data-qe-menu-toggle]' );
		const panel = doc.querySelector( '[data-qe-menu-panel]' );
		if ( ! header || ! toggle || ! panel ) {
			return null;
		}
		const label = toggle.querySelector( '[data-qe-menu-label]' );
		const text = labels( win );

		function setOpen( open ) {
			header.classList.toggle( 'qe-nav-open', open );
			doc.body.classList.toggle( 'qe-menu-locked', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( label && text.open && text.close ) {
				label.textContent = open ? text.close : text.open;
			}
			if ( open ) {
				const first = panel.querySelector( FOCUSABLE );
				if ( first ) {
					first.focus();
				}
			} else {
				toggle.focus();
			}
		}

		function isOpen() {
			return header.classList.contains( 'qe-nav-open' );
		}

		toggle.addEventListener( 'click', function () {
			setOpen( ! isOpen() );
		} );

		doc.addEventListener( 'keydown', function ( event ) {
			if ( ! isOpen() ) {
				return;
			}
			if ( event.key === 'Escape' ) {
				setOpen( false );
				return;
			}
			if ( event.key !== 'Tab' ) {
				return;
			}
			// Focus trap: cycle between the toggle and the panel's focusables.
			const items = [ toggle ].concat(
				Array.prototype.slice.call(
					panel.querySelectorAll( FOCUSABLE )
				)
			);
			const first = items[ 0 ];
			const last = items[ items.length - 1 ];
			if ( event.shiftKey && doc.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && doc.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		} );

		panel.addEventListener( 'click', function ( event ) {
			const button = event.target.closest( '.qe-submenu-toggle' );
			if ( ! button ) {
				return;
			}
			const item = button.parentElement;
			const open = ! item.classList.contains( 'is-open' );
			item.classList.toggle( 'is-open', open );
			button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );

		return { setOpen, isOpen };
	}

	function initBackToTop( doc, win ) {
		const button = doc.querySelector( '.qe-back-to-top' );
		if ( ! button ) {
			return null;
		}
		button.hidden = false;

		function update() {
			button.classList.toggle( 'is-visible', win.scrollY > 600 );
		}

		win.addEventListener( 'scroll', update, { passive: true } );
		update();

		return { update };
	}

	function init( doc, win ) {
		return {
			menu: initMenu( doc, win ),
			backToTop: initBackToTop( doc, win ),
		};
	}

	const api = { ready, init, initMenu, initBackToTop, labels };
	root.qeEduThemeApi = api;

	ready( root.document, function () {
		api.init( root.document, root );
	} );
} )( window );
