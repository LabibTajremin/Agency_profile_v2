/**
 * QE Education widgets: hero typing + parallax, sliders (Elementor's Swiper),
 * count-up stats, registration / newsletter forms and the mega menu.
 *
 * Every handler is idempotent and runs again whenever Elementor re-renders a
 * widget in the editor (frontend/element_ready). Motion is disabled in the
 * editor and for visitors who prefer reduced motion.
 *
 * @param {Window} root Browser window.
 */
( function ( root ) {
	const doc = root.document;
	const READY = 'qeReady';

	function l10n() {
		return Object.assign(
			{
				pause: 'Pause',
				play: 'Play',
				error: 'Error',
			},
			root.qeEduWidgets || {}
		);
	}

	function reducedMotion() {
		return (
			!! root.matchMedia &&
			root.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	function editMode() {
		return !! (
			root.elementorFrontend &&
			root.elementorFrontend.isEditMode &&
			root.elementorFrontend.isEditMode()
		);
	}

	/**
	 * Run `fn` once per element.
	 *
	 * @param {Element}  el Element.
	 * @param {Function} fn Initialiser.
	 * @return {*} Result of fn, or null when already initialised.
	 */
	function once( el, fn ) {
		if ( ! el || el.dataset[ READY ] ) {
			return null;
		}
		el.dataset[ READY ] = '1';
		return fn( el );
	}

	/* Hero ----------------------------------------------------------- */

	function typer( el, words, speed, pause, timers ) {
		const target = el.querySelector( '.qe-hero__word' );
		let word = 0;
		let chars = words[ 0 ].length;
		let deleting = true;

		function tick() {
			if ( ! doc.contains( el ) ) {
				return;
			}
			const current = words[ word ];
			if ( deleting ) {
				chars--;
				target.textContent = current.slice( 0, chars );
				if ( chars <= 0 ) {
					deleting = false;
					word = ( word + 1 ) % words.length;
				}
				timers.push( setTimeout( tick, speed / 2 ) );
				return;
			}
			chars++;
			target.textContent = words[ word ].slice( 0, chars );
			if ( chars >= words[ word ].length ) {
				deleting = true;
				timers.push( setTimeout( tick, pause ) );
				return;
			}
			timers.push( setTimeout( tick, speed ) );
		}

		timers.push( setTimeout( tick, pause ) );
	}

	function parallax( section, strength ) {
		const floats = section.querySelectorAll( '.qe-hero__float' );
		let frame = null;

		function onMove( event ) {
			const box = section.getBoundingClientRect();
			const x = ( event.clientX - box.left ) / box.width - 0.5;
			const y = ( event.clientY - box.top ) / box.height - 0.5;
			if ( frame ) {
				root.cancelAnimationFrame( frame );
			}
			frame = root.requestAnimationFrame( () => {
				floats.forEach( ( float ) => {
					const depth = parseFloat( float.dataset.depth ) || 0;
					float.style.transform = `translate3d(${ (
						x *
						strength *
						depth *
						2
					).toFixed( 1 ) }px, ${ ( y * strength * depth * 2 ).toFixed(
						1
					) }px, 0)`;
				} );
			} );
		}

		section.addEventListener( 'mousemove', onMove );
		return onMove;
	}

	function hero( section ) {
		return once( section, () => {
			const rotator = section.querySelector( '.qe-hero__rotator' );
			const words = rotator
				? JSON.parse( rotator.dataset.words || '[]' )
				: [];
			const still = reducedMotion() || editMode();
			const timers = [];
			const result = { timers, onMove: null };

			if ( rotator && words.length > 1 && ! still ) {
				typer(
					rotator,
					words,
					parseInt( section.dataset.speed, 10 ) || 70,
					parseInt( section.dataset.pause, 10 ) || 1800,
					timers
				);
			}

			const strength = parseInt( section.dataset.parallax, 10 ) || 0;
			const finePointer =
				!! root.matchMedia &&
				root.matchMedia( '(pointer: fine)' ).matches;
			if (
				strength &&
				finePointer &&
				! still &&
				section.querySelector( '.qe-hero__float' )
			) {
				result.onMove = parallax( section, strength );
			}

			return result;
		} );
	}

	/* Sliders -------------------------------------------------------- */

	function sliderConfig( wrapper, settings ) {
		return {
			slidesPerView: settings.perViewMobile,
			spaceBetween: 24,
			watchOverflow: true,
			observer: true,
			observeParents: true,
			grabCursor: true,
			a11y: { enabled: true },
			keyboard: { enabled: true, onlyInViewport: true },
			breakpoints: {
				768: { slidesPerView: settings.perViewTablet },
				1025: { slidesPerView: settings.perView },
			},
			navigation: settings.arrows
				? {
						prevEl: wrapper.querySelector(
							'.qe-slider__arrow--prev'
						),
						nextEl: wrapper.querySelector(
							'.qe-slider__arrow--next'
						),
				  }
				: false,
			pagination: settings.dots
				? {
						el: wrapper.querySelector( '.qe-slider__dots' ),
						clickable: true,
				  }
				: false,
			autoplay:
				settings.autoplay && ! reducedMotion() && ! editMode()
					? {
							delay: settings.autoplay,
							pauseOnMouseEnter: true,
							disableOnInteraction: false,
					  }
					: false,
		};
	}

	/**
	 * A pause / play button for anything that moves on its own (WCAG 2.2.2).
	 *
	 * @param {Element}  wrapper Where to add the button.
	 * @param {Function} onPause Called with true (pause) or false (play).
	 * @return {Element} The button.
	 */
	function pauseButton( wrapper, onPause ) {
		const strings = l10n();
		const button = doc.createElement( 'button' );
		button.type = 'button';
		button.className = 'qe-pause';
		button.setAttribute( 'aria-pressed', 'false' );
		button.textContent = strings.pause;
		button.addEventListener( 'click', () => {
			const paused = button.getAttribute( 'aria-pressed' ) !== 'true';
			button.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
			button.textContent = paused ? strings.play : strings.pause;
			onPause( paused );
		} );
		wrapper.appendChild( button );
		return button;
	}

	function slider( wrapper ) {
		return once( wrapper, () => {
			const settings = JSON.parse( wrapper.dataset.qeSlider || '{}' );

			if ( settings.static ) {
				wrapper.classList.add( 'is-static' );
				return Promise.resolve( null );
			}

			const container = wrapper.querySelector( '.swiper' );
			const config = sliderConfig( wrapper, settings );
			const SwiperHandler =
				root.elementorFrontend &&
				root.elementorFrontend.utils &&
				root.elementorFrontend.utils.swiper;
			const created = SwiperHandler
				? new SwiperHandler( container, config )
				: Promise.resolve(
						root.Swiper
							? new root.Swiper( container, config )
							: null
				  );

			return Promise.resolve( created ).then( ( instance ) => {
				if ( instance && config.autoplay ) {
					pauseButton( wrapper, ( paused ) =>
						paused
							? instance.autoplay.stop()
							: instance.autoplay.start()
					);
				}
				wrapper.classList.toggle( 'is-ready', !! instance );
				return instance;
			} );
		} );
	}

	function marquee( el ) {
		return once( el, () => {
			if ( reducedMotion() || editMode() ) {
				el.classList.add( 'is-paused' );
				return null;
			}
			return pauseButton( el.parentElement, ( paused ) =>
				el.classList.toggle( 'is-paused', paused )
			);
		} );
	}

	/* Counter -------------------------------------------------------- */

	function format( value ) {
		return Math.round( value ).toLocaleString(
			doc.documentElement.lang || undefined
		);
	}

	function countUp( el, duration ) {
		const to = parseFloat( el.dataset.to ) || 0;
		if ( ! duration ) {
			el.textContent = format( to );
			return;
		}
		const start = root.performance.now();
		function step( now ) {
			const progress = Math.min( 1, ( now - start ) / duration );
			const eased = 1 - Math.pow( 1 - progress, 3 );
			el.textContent = format( to * eased );
			if ( progress < 1 ) {
				root.requestAnimationFrame( step );
			}
		}
		el.textContent = format( 0 );
		root.requestAnimationFrame( step );
	}

	function counter( wrapper ) {
		return once( wrapper, () => {
			const values = wrapper.querySelectorAll( '.qe-stat__value' );
			const duration =
				reducedMotion() || editMode()
					? 0
					: ( parseFloat( wrapper.dataset.duration ) || 0 ) * 1000;

			if ( ! duration || ! root.IntersectionObserver ) {
				values.forEach( ( el ) => countUp( el, 0 ) );
				return null;
			}

			const observer = new root.IntersectionObserver(
				( entries ) => {
					entries.forEach( ( entry ) => {
						if ( entry.isIntersecting ) {
							observer.unobserve( entry.target );
							countUp( entry.target, duration );
						}
					} );
				},
				{ threshold: 0.4 }
			);
			values.forEach( ( el ) => observer.observe( el ) );
			return observer;
		} );
	}

	/* Forms ---------------------------------------------------------- */

	function fieldError( input ) {
		const field =
			input.closest( '.qe-form__field' ) ||
			input.closest( '.qe-form__consent' ) ||
			input.closest( 'form' );
		return field.querySelector( '.qe-form__error' );
	}

	function setError( input, message ) {
		const error = fieldError( input );
		if ( message ) {
			input.setAttribute( 'aria-invalid', 'true' );
			if ( error ) {
				error.textContent = message;
				error.hidden = false;
				if ( error.id ) {
					input.setAttribute( 'aria-describedby', error.id );
				}
			}
		} else {
			input.removeAttribute( 'aria-invalid' );
			if ( error ) {
				error.textContent = '';
				error.hidden = true;
			}
		}
	}

	/**
	 * Validate inputs; show messages; focus the first invalid one.
	 *
	 * @param {HTMLFormElement} form   Form.
	 * @param {Element[]}       inputs Inputs to check.
	 * @return {boolean} Whether all are valid.
	 */
	function validate( form, inputs ) {
		let first = null;
		inputs.forEach( ( input ) => {
			const empty =
				input.type === 'checkbox'
					? ! input.checked
					: ! input.value.trim();
			let message = '';
			if ( input.required && empty ) {
				message = form.dataset.required;
			} else if ( ! empty && ! input.checkValidity() ) {
				message = form.dataset.invalid;
			}
			setError( input, message );
			if ( message && ! first ) {
				first = input;
			}
		} );
		if ( first ) {
			first.focus();
		}
		return ! first;
	}

	function visibleInputs( form ) {
		return Array.prototype.filter.call(
			form.querySelectorAll( 'input, select, textarea' ),
			( input ) =>
				input.type !== 'hidden' &&
				input.name !== 'website' &&
				! input.closest( '[hidden]' )
		);
	}

	function showStep( form, step ) {
		form.querySelectorAll( '.qe-form__step' ).forEach( ( fieldset ) => {
			fieldset.hidden = fieldset.dataset.step !== String( step );
		} );
		form.querySelectorAll( '[data-step-only]' ).forEach( ( el ) => {
			el.hidden = el.dataset.stepOnly !== String( step );
		} );
		const last = step === form.querySelectorAll( '.qe-form__step' ).length;
		const back = form.querySelector( '[data-qe-back]' );
		const next = form.querySelector( '[data-qe-next]' );
		back.hidden = step === 1;
		next.hidden = last;
		form.querySelector( '[data-qe-submit]' ).hidden = ! last;
		form.querySelectorAll( '.qe-form__progress li' ).forEach( ( li, i ) => {
			const active = i === step - 1;
			li.classList.toggle( 'is-active', active );
			if ( active ) {
				li.setAttribute( 'aria-current', 'step' );
			} else {
				li.removeAttribute( 'aria-current' );
			}
		} );
		form.dataset.step = String( step );
	}

	function showMessage( form, text, ok ) {
		const box = form.querySelector( '.qe-form__message' );
		box.textContent = text;
		box.classList.toggle( 'is-success', ok );
		box.classList.toggle( 'is-error', ! ok );
	}

	/**
	 * Submit a form with fetch; resolves with the parsed JSON response.
	 *
	 * @param {HTMLFormElement} form Form.
	 * @return {Promise<Object>} Response.
	 */
	function submit( form ) {
		const button = form.querySelector( '[data-qe-submit]' );
		const label = button.textContent;
		button.disabled = true;
		button.textContent = form.dataset.sending || label;

		// getAttribute: `form.action` would return the hidden input named "action".
		const url = form.getAttribute( 'action' );

		return root
			.fetch( url, {
				method: 'POST',
				body: new root.FormData( form ),
				credentials: 'same-origin',
			} )
			.then( ( response ) => response.json() )
			.catch( () => ( {
				success: false,
				data: { message: l10n().error },
			} ) )
			.then( ( json ) => {
				button.disabled = false;
				button.textContent = label;
				const data = json.data || {};
				showMessage( form, data.message || '', !! json.success );
				if ( json.success ) {
					form.reset();
					if (
						form.querySelector( '.qe-form__step' ) &&
						form.dataset.step
					) {
						showStep( form, 1 );
					}
				} else if ( data.errors ) {
					Object.keys( data.errors ).forEach( ( key ) => {
						const input = form.querySelector(
							`[name="qe_${ key }"]`
						);
						if ( input ) {
							setError( input, data.errors[ key ] );
						}
					} );
				}
				return json;
			} );
	}

	function initForm( el ) {
		return once( el, () => {
			el.querySelectorAll( '.qe-form__error' ).forEach( ( error, i ) => {
				error.id = error.id || `${ el.id || 'qe-form' }-error-${ i }`;
			} );

			const next = el.querySelector( '[data-qe-next]' );
			if ( next ) {
				el.dataset.step = '1';
				next.addEventListener( 'click', () => {
					if ( validate( el, visibleInputs( el ) ) ) {
						showStep( el, parseInt( el.dataset.step, 10 ) + 1 );
						const first = visibleInputs( el )[ 0 ];
						if ( first ) {
							first.focus();
						}
					}
				} );
				el.querySelector( '[data-qe-back]' ).addEventListener(
					'click',
					() => showStep( el, parseInt( el.dataset.step, 10 ) - 1 )
				);
			}

			el.addEventListener( 'input', ( event ) => {
				if ( event.target.getAttribute( 'aria-invalid' ) ) {
					validate( el, [ event.target ] );
				}
			} );

			el.addEventListener( 'submit', ( event ) => {
				event.preventDefault();
				if ( editMode() || ! validate( el, visibleInputs( el ) ) ) {
					return;
				}
				submit( el );
			} );

			return el;
		} );
	}

	/* Mega menu ------------------------------------------------------ */

	function megaMenu( nav ) {
		return once( nav, () => {
			const toggle = nav.querySelector( '.qe-mega__toggle' );
			const breakpoint = parseInt( nav.dataset.breakpoint, 10 ) || 1024;
			const query = root.matchMedia
				? root.matchMedia( `(max-width: ${ breakpoint }px)` )
				: null;

			function setMobile() {
				nav.classList.toggle(
					'is-mobile',
					!! ( query && query.matches )
				);
			}

			// Visible links and buttons of the open menu, toggle first.
			function focusables() {
				return Array.from(
					nav.querySelectorAll(
						'.qe-mega__menu a[href], .qe-mega__menu button'
					)
				).filter( ( el ) => el.offsetWidth > 0 || el.offsetHeight > 0 );
			}

			function setOpen( open ) {
				if ( open ) {
					// Open just below the button, whatever the header height.
					nav.style.setProperty(
						'--qe-mega-top',
						`${ Math.round(
							toggle.getBoundingClientRect().bottom
						) }px`
					);
				}
				nav.classList.toggle( 'is-open', open );
				toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				doc.body.classList.toggle( 'qe-menu-locked', open );
				const first = open && focusables()[ 0 ];
				if ( first ) {
					first.focus();
				}
			}

			// Keep Tab inside the open full-screen menu (and its button).
			function trapFocus( event ) {
				if (
					event.key !== 'Tab' ||
					! nav.classList.contains( 'is-open' ) ||
					! nav.classList.contains( 'is-mobile' )
				) {
					return;
				}
				const items = [ toggle, ...focusables() ];
				const last = items[ items.length - 1 ];
				if ( event.shiftKey && doc.activeElement === toggle ) {
					event.preventDefault();
					last.focus();
				} else if ( ! event.shiftKey && doc.activeElement === last ) {
					event.preventDefault();
					toggle.focus();
				}
			}

			function closeSubmenus() {
				nav.querySelectorAll( '.qe-mega__item.is-open' ).forEach(
					( li ) => {
						li.classList.remove( 'is-open' );
						li.querySelector( '.qe-mega__sub-toggle' ).setAttribute(
							'aria-expanded',
							'false'
						);
					}
				);
			}

			// Keep dropdowns on screen: open towards the left when one would
			// cross the right edge (the last items of a long menu).
			function fitPanel( event ) {
				const li = event.target.closest( '.qe-mega__item' );
				if ( ! li || nav.classList.contains( 'is-mobile' ) ) {
					return;
				}
				const panel = li.querySelector( '.qe-mega__panel' );
				if ( ! panel || li.classList.contains( 'is-mega' ) ) {
					return;
				}
				li.classList.remove( 'is-flip' );
				const width = doc.documentElement.clientWidth;
				if ( panel.getBoundingClientRect().right > width - 8 ) {
					li.classList.add( 'is-flip' );
				}
			}

			nav.addEventListener( 'mouseover', fitPanel );
			nav.addEventListener( 'focusin', fitPanel );

			toggle.addEventListener( 'click', () =>
				setOpen( ! nav.classList.contains( 'is-open' ) )
			);

			nav.addEventListener( 'click', ( event ) => {
				const button = event.target.closest( '.qe-mega__sub-toggle' );
				if ( ! button ) {
					return;
				}
				const li = button.parentElement;
				const open = ! li.classList.contains( 'is-open' );
				li.classList.toggle( 'is-open', open );
				button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );

			nav.addEventListener( 'keydown', trapFocus );

			nav.addEventListener( 'keydown', ( event ) => {
				if ( event.key !== 'Escape' ) {
					return;
				}
				closeSubmenus();
				if ( nav.classList.contains( 'is-open' ) ) {
					setOpen( false );
					toggle.focus();
				}
			} );

			if ( query ) {
				if ( query.addEventListener ) {
					query.addEventListener( 'change', setMobile );
				}
				setMobile();
			}

			return { setOpen, closeSubmenus, fitPanel };
		} );
	}

	/* Wiring --------------------------------------------------------- */

	const HANDLERS = {
		'qe-animated-hero': ( scope ) =>
			scope.querySelectorAll( '[data-qe-hero]' ).forEach( hero ),
		'qe-service-slider': ( scope ) =>
			scope.querySelectorAll( '[data-qe-slider]' ).forEach( slider ),
		'qe-destinations': ( scope ) =>
			scope.querySelectorAll( '[data-qe-slider]' ).forEach( slider ),
		'qe-news-slider': ( scope ) =>
			scope.querySelectorAll( '[data-qe-slider]' ).forEach( slider ),
		'qe-testimonials': ( scope ) =>
			scope.querySelectorAll( '[data-qe-slider]' ).forEach( slider ),
		'qe-logo-carousel': ( scope ) => {
			scope.querySelectorAll( '[data-qe-slider]' ).forEach( slider );
			scope.querySelectorAll( '.qe-marquee' ).forEach( marquee );
		},
		'qe-stats': ( scope ) =>
			scope.querySelectorAll( '[data-qe-counter]' ).forEach( counter ),
		'qe-registration-form': ( scope ) =>
			scope.querySelectorAll( '[data-qe-form]' ).forEach( initForm ),
		'qe-benefits-form': ( scope ) =>
			scope.querySelectorAll( '[data-qe-form]' ).forEach( initForm ),
		'qe-newsletter': ( scope ) =>
			scope.querySelectorAll( '[data-qe-form]' ).forEach( initForm ),
		'qe-mega-menu': ( scope ) =>
			scope.querySelectorAll( '[data-qe-mega]' ).forEach( megaMenu ),
	};

	function registerHooks( frontend ) {
		Object.keys( HANDLERS ).forEach( ( name ) => {
			frontend.hooks.addAction(
				`frontend/element_ready/${ name }.default`,
				( $scope ) => HANDLERS[ name ]( $scope[ 0 ] || $scope )
			);
		} );
	}

	function initAll( scope ) {
		Object.keys( HANDLERS ).forEach( ( name ) =>
			HANDLERS[ name ]( scope )
		);
	}

	/**
	 * Hook into Elementor: it calls every widget handler when the page is
	 * ready and again whenever the editor re-renders a widget. Only when
	 * Elementor is not on the page at all are widgets initialised directly on
	 * DOM ready (once() keeps that from ever running twice per element).
	 *
	 * @return {string} How the widgets were attached.
	 */
	function boot() {
		if ( root.elementorFrontend && root.elementorFrontend.hooks ) {
			registerHooks( root.elementorFrontend );
			return 'hooks';
		}

		root.addEventListener( 'elementor/frontend/init', () =>
			registerHooks( root.elementorFrontend )
		);

		const fallback = () => {
			if ( ! root.elementorFrontend ) {
				initAll( doc );
			}
		};
		if ( doc.readyState === 'loading' ) {
			doc.addEventListener( 'DOMContentLoaded', fallback );
		} else {
			fallback();
		}
		return 'event';
	}

	root.qeEduWidgetsApi = {
		reducedMotion,
		editMode,
		once,
		hero,
		typer,
		parallax,
		sliderConfig,
		pauseButton,
		slider,
		marquee,
		format,
		countUp,
		counter,
		validate,
		visibleInputs,
		showStep,
		setError,
		submit,
		initForm,
		showMessage,
		megaMenu,
		HANDLERS,
		registerHooks,
		initAll,
		boot,
	};

	boot();
} )( window );
