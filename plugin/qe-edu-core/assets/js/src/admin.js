/**
 * Theme Settings screen: live preview of colours and fonts, contrast
 * warnings for the custom colour, media pickers and redirect rows.
 * Vanilla JS; the colour maths mirrors QeEdu\Core\Colors\Palette.
 *
 * @param {Window} root Browser window.
 */
( function ( root ) {
	const doc = root.document;

	/* Colour maths --------------------------------------------------- */

	function normalize( color ) {
		const match = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(
			String( color || '' ).trim()
		);
		if ( ! match ) {
			return '';
		}
		let hex = match[ 1 ];
		if ( hex.length === 3 ) {
			hex = hex
				.split( '' )
				.map( ( c ) => c + c )
				.join( '' );
		}
		return '#' + hex.toUpperCase();
	}

	function rgb( color ) {
		const hex = normalize( color ).slice( 1 );
		return [ 0, 2, 4 ].map( ( i ) =>
			parseInt( hex.slice( i, i + 2 ), 16 )
		);
	}

	function luminance( color ) {
		const c = rgb( color ).map( ( v ) => {
			v = v / 255;
			return v <= 0.03928
				? v / 12.92
				: Math.pow( ( v + 0.055 ) / 1.055, 2.4 );
		} );
		return 0.2126 * c[ 0 ] + 0.7152 * c[ 1 ] + 0.0722 * c[ 2 ];
	}

	function contrast( a, b ) {
		const la = luminance( a );
		const lb = luminance( b );
		return ( Math.max( la, lb ) + 0.05 ) / ( Math.min( la, lb ) + 0.05 );
	}

	function mix( color, target, amount ) {
		const a = rgb( color );
		const b = rgb( target );
		return (
			'#' +
			a
				.map( ( v, i ) =>
					Math.round( v * ( 1 - amount ) + b[ i ] * amount )
						.toString( 16 )
						.padStart( 2, '0' )
				)
				.join( '' )
				.toUpperCase()
		);
	}

	function rgba( color, alpha ) {
		const c = rgb( color );
		return `rgb(${ c[ 0 ] } ${ c[ 1 ] } ${ c[ 2 ] } / ${ Math.round(
			alpha * 100
		) }%)`;
	}

	function textOn( background, preferred, min ) {
		if ( contrast( background, preferred ) >= min ) {
			return preferred;
		}
		return contrast( background, '#FFFFFF' ) >=
			contrast( background, '#111111' )
			? '#FFFFFF'
			: '#111111';
	}

	function ensureContrast( color, background, min ) {
		const target =
			contrast( background, '#000000' ) >=
			contrast( background, '#FFFFFF' )
				? '#000000'
				: '#FFFFFF';
		let candidate = normalize( color );
		let step = 0;
		while ( contrast( candidate, background ) < min && step < 50 ) {
			step++;
			candidate = mix( color, target, step / 50 );
		}
		return candidate;
	}

	/**
	 * Tokens for the custom colour, as PHP Palette::resolve() computes them.
	 *
	 * @param {string} primary Custom colour.
	 * @param {Object} data    Localised data.
	 * @param          color
	 * @param          min
	 * @return {Object} Tokens.
	 */
	function readableCustom( color, min ) {
		color = normalize( color );
		if (
			Math.max(
				contrast( color, '#FFFFFF' ),
				contrast( color, '#111111' )
			) >= min
		) {
			return color;
		}
		return ensureContrast( color, '#FFFFFF', min );
	}

	function customPalette( primary, data ) {
		primary = readableCustom( primary, data.minContrast );
		const dark = mix( primary, '#000000', 0.23 );
		return {
			primary,
			dark,
			soft: rgba( primary, 0.08 ),
			on_primary: textOn( primary, '#FFFFFF', data.minContrast ),
			on_dark: textOn( dark, '#FFFFFF', data.minContrast ),
			link: ensureContrast(
				primary,
				data.lightSurface,
				data.minContrast
			),
			link_dark_mode: ensureContrast(
				primary,
				data.darkSurface,
				data.minContrast
			),
		};
	}

	/* Form state → CSS tokens ----------------------------------------- */

	function fieldValue( form, path ) {
		const el = form.querySelector( `[data-qe-field="${ path }"]` );
		if ( ! el ) {
			return undefined;
		}
		const checked = el.querySelector( 'input[type="radio"]:checked' );
		if ( checked ) {
			return checked.value;
		}
		const input = el.querySelector( 'input, select' );
		if ( input.type === 'checkbox' ) {
			return input.checked;
		}
		return input.value;
	}

	/**
	 * Read the form and compute the preview state. Fields that are not on
	 * the current tab are left undefined (the preview keeps the saved look).
	 *
	 * @param {HTMLFormElement} form Form.
	 * @param {Object}          data Localised data.
	 * @return {Object} { vars, classes, fontUrl }.
	 */
	function previewState( form, data ) {
		const vars = {};
		const classes = {};
		let fontUrl = null;

		const accent = fieldValue( form, 'colors.accent' );
		if ( accent !== undefined ) {
			const palette =
				accent === 'custom'
					? customPalette( fieldValue( form, 'colors.custom' ), data )
					: data.palettes[ accent ];
			Object.assign( vars, {
				'--qe-primary': palette.primary,
				'--qe-primary-dark': palette.dark,
				'--qe-primary-soft': palette.soft,
				'--qe-on-primary': palette.on_primary,
				'--qe-on-primary-dark': palette.on_dark,
				'--qe-link': palette.link,
				'--qe-link-dark-mode': palette.link_dark_mode,
			} );
			const tint = fieldValue( form, 'colors.background' );
			vars[ '--qe-surface-alt' ] = {
				neutral: '#F6F5FB',
				accent: mix( palette.primary, '#FFFFFF', 0.94 ),
				white: '#FFFFFF',
			}[ tint ];
			vars[ '--qe-hero-gradient' ] = fieldValue(
				form,
				'colors.hero_gradient'
			)
				? `linear-gradient(135deg, ${ palette.primary } 0%, #140342 100%)`
				: 'none';
			classes.mode = 'qe-mode-' + fieldValue( form, 'colors.mode' );
			classes.button =
				'qe-btn-' + fieldValue( form, 'colors.button_style' );
			classes.underline = ! fieldValue( form, 'colors.link_underline' );
		}

		const pair = fieldValue( form, 'typography.pair' );
		if ( pair !== undefined ) {
			const font = data.fonts[ pair ];
			vars[ '--qe-font-heading' ] = font.heading;
			vars[ '--qe-font-body' ] = font.body;
			vars[ '--qe-base-size' ] =
				Number( fieldValue( form, 'typography.base_size' ) ) / 16 +
				'rem';
			vars[ '--qe-h-scale' ] = String(
				data.scales[ fieldValue( form, 'typography.heading_scale' ) ]
			);
			const selfHost = fieldValue( form, 'typography.self_host' );
			fontUrl = selfHost && pair === data.defaultPair ? null : font.url;
		}

		return { vars, classes, fontUrl };
	}

	/**
	 * Apply a preview state to the preview iframe's document.
	 *
	 * @param {Document} target Preview document.
	 * @param {Object}   state  From previewState().
	 */
	function applyPreview( target, state ) {
		if ( ! target || ! target.documentElement ) {
			return;
		}
		Object.keys( state.vars ).forEach( ( name ) => {
			target.documentElement.style.setProperty(
				name,
				state.vars[ name ]
			);
		} );
		const body = target.body;
		if ( state.classes.mode ) {
			body.classList.remove(
				'qe-mode-light',
				'qe-mode-dark',
				'qe-mode-auto'
			);
			body.classList.add( state.classes.mode );
			body.classList.remove(
				'qe-btn-rounded',
				'qe-btn-pill',
				'qe-btn-square'
			);
			body.classList.add( state.classes.button );
			body.classList.toggle(
				'qe-no-link-underline',
				state.classes.underline
			);
		}
		if ( state.fontUrl ) {
			let link = target.getElementById( 'qe-edu-preview-fonts' );
			if ( ! link ) {
				link = target.createElement( 'link' );
				link.id = 'qe-edu-preview-fonts';
				link.rel = 'stylesheet';
				target.head.appendChild( link );
			}
			link.href = state.fontUrl;
		}
	}

	/* Custom colour contrast hint ------------------------------------- */

	function contrastMessage( color, data ) {
		const ratio = contrast( color, '#FFFFFF' );
		const best = Math.max( ratio, contrast( color, '#111111' ) );
		const template =
			best >= data.minContrast
				? data.i18n.okContrast
				: data.i18n.lowContrast;
		return {
			ok: best >= data.minContrast,
			text: template.replace( '%s', best.toFixed( 1 ) ),
		};
	}

	/* Wiring --------------------------------------------------------- */

	function initPreview( form, data ) {
		const iframe = doc.querySelector( '[data-qe-preview]' );
		const custom = form.querySelector( '[data-qe-custom-color]' );
		const hint = form.querySelector( '[data-qe-contrast]' );

		function update() {
			if ( custom && hint ) {
				const message = contrastMessage( custom.value, data );
				hint.textContent = message.text;
				hint.classList.toggle( 'is-low', ! message.ok );
			}
			if ( iframe ) {
				applyPreview(
					iframe.contentDocument,
					previewState( form, data )
				);
			}
		}

		form.addEventListener( 'input', update );
		form.addEventListener( 'change', update );
		if ( iframe ) {
			iframe.addEventListener( 'load', update );
		}
		update();
		return update;
	}

	function initMedia( container, wp, data ) {
		const input = container.querySelector( '[data-qe-media-id]' );
		const preview = container.querySelector( '[data-qe-media-preview]' );
		const remove = container.querySelector( '[data-qe-media-remove]' );
		const mime = container.getAttribute( 'data-mime' );
		let frame = null;

		container
			.querySelector( '[data-qe-media-choose]' )
			.addEventListener( 'click', () => {
				if ( ! frame ) {
					frame = wp.media( {
						title: data.i18n.choose,
						button: { text: data.i18n.use },
						library: { type: mime },
						multiple: false,
					} );
					frame.on( 'select', () => {
						const file = frame
							.state()
							.get( 'selection' )
							.first()
							.toJSON();
						input.value = file.id;
						preview.textContent = '';
						if ( mime === 'image' ) {
							const img = doc.createElement( 'img' );
							img.src = file.url;
							img.alt = '';
							preview.appendChild( img );
						} else {
							const link = doc.createElement( 'a' );
							link.href = file.url;
							link.textContent = file.filename;
							preview.appendChild( link );
						}
						remove.hidden = false;
					} );
				}
				frame.open();
			} );

		remove.addEventListener( 'click', () => {
			input.value = '0';
			preview.textContent = '';
			remove.hidden = true;
		} );
	}

	function initRedirects( table ) {
		const button = table.parentElement.querySelector( '[data-qe-add-row]' );
		if ( ! button ) {
			return;
		}
		button.addEventListener( 'click', () => {
			const rows = table.querySelectorAll( 'tbody tr' );
			const clone = rows[ rows.length - 1 ].cloneNode( true );
			const index = rows.length;
			clone.querySelectorAll( 'input' ).forEach( ( input ) => {
				input.value = '';
				input.name = input.name.replace( /\[\d+\]/, `[${ index }]` );
			} );
			table.querySelector( 'tbody' ).appendChild( clone );
			clone.querySelector( 'input' ).focus();
		} );
	}

	function init() {
		const data = root.qeEduAdmin;
		if ( ! data ) {
			return null;
		}
		const form = doc.querySelector( '[data-qe-form]' );
		const update = form ? initPreview( form, data ) : null;
		if ( root.wp && root.wp.media ) {
			doc.querySelectorAll( '[data-qe-media]' ).forEach( ( el ) =>
				initMedia( el, root.wp, data )
			);
		}
		doc.querySelectorAll( '[data-qe-redirects]' ).forEach( ( el ) =>
			initRedirects( el )
		);
		return { update };
	}

	root.qeEduAdminApi = {
		normalize,
		rgb,
		luminance,
		contrast,
		mix,
		rgba,
		textOn,
		ensureContrast,
		readableCustom,
		customPalette,
		previewState,
		applyPreview,
		contrastMessage,
		initPreview,
		initMedia,
		initRedirects,
		init,
	};

	if ( doc.readyState === 'loading' ) {
		doc.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )( window );
