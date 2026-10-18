/**
 * Tests for plugin/qe-edu-core/assets/js/src/widgets.js.
 */

function setMatchMedia( matches ) {
	window.matchMedia = jest.fn( ( query ) => ( {
		matches: typeof matches === 'function' ? matches( query ) : matches,
		media: query,
		addEventListener: jest.fn(),
	} ) );
}

function flush() {
	return new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
}

describe( 'widgets.js', () => {
	let api;

	beforeAll( () => {
		setMatchMedia( false );
		require( '../../plugin/qe-edu-core/assets/js/src/widgets.js' );
		api = window.qeEduWidgetsApi;
	} );

	beforeEach( () => {
		document.body.innerHTML = '';
		document.body.className = '';
		delete window.elementorFrontend;
		delete window.Swiper;
		delete window.qeEduWidgets;
		setMatchMedia( false );
		window.requestAnimationFrame = ( cb ) => {
			cb( window.performance.now() + 100000 );
			return 1;
		};
		window.cancelAnimationFrame = jest.fn();
	} );

	test( 'environment helpers', () => {
		expect( api.reducedMotion() ).toBe( false );
		setMatchMedia( true );
		expect( api.reducedMotion() ).toBe( true );
		const mm = window.matchMedia;
		delete window.matchMedia;
		expect( api.reducedMotion() ).toBe( false );
		window.matchMedia = mm;

		expect( api.editMode() ).toBe( false );
		window.elementorFrontend = {};
		expect( api.editMode() ).toBe( false );
		window.elementorFrontend = { isEditMode: () => true };
		expect( api.editMode() ).toBe( true );
	} );

	test( 'once initialises an element a single time', () => {
		const el = document.createElement( 'div' );
		const fn = jest.fn( () => 'done' );
		expect( api.once( el, fn ) ).toBe( 'done' );
		expect( api.once( el, fn ) ).toBeNull();
		expect( api.once( null, fn ) ).toBeNull();
		expect( fn ).toHaveBeenCalledTimes( 1 );
	} );

	/* Hero ----------------------------------------------------------- */

	function heroMarkup( words, extra = '' ) {
		document.body.innerHTML = `<section data-qe-hero data-speed="10" data-pause="20" data-parallax="20">
			${
				words
					? `<span class="qe-hero__rotator" data-words='${ JSON.stringify(
							words
					  ) }'><span class="qe-hero__word">${
							words[ 0 ]
					  }</span></span>`
					: ''
			}
			${ extra }
		</section>`;
		return document.querySelector( '[data-qe-hero]' );
	}

	test( 'hero types, deletes and moves to the next phrase', () => {
		jest.useFakeTimers();
		const section = heroMarkup( [ 'ab', 'cd' ] );
		const result = api.hero( section );
		const word = section.querySelector( '.qe-hero__word' );

		jest.advanceTimersByTime( 20 ); // pause, then delete one letter.
		expect( word.textContent ).toBe( 'a' );
		jest.advanceTimersByTime( 5 ); // deleting runs at half the typing time.
		expect( word.textContent ).toBe( '' );
		jest.advanceTimersByTime( 5 ); // start typing the next phrase.
		expect( word.textContent ).toBe( 'c' );
		jest.advanceTimersByTime( 10 );
		expect( word.textContent ).toBe( 'cd' );
		jest.advanceTimersByTime( 20 );
		expect( word.textContent ).toBe( 'c' );
		expect( result.timers.length ).toBeGreaterThan( 3 );

		// Removed from the page: the loop stops.
		document.body.innerHTML = '';
		const count = result.timers.length;
		jest.advanceTimersByTime( 1000 );
		expect( result.timers.length ).toBe( count );
		jest.useRealTimers();
	} );

	test( 'hero defaults and still modes', () => {
		jest.useFakeTimers();
		let section = heroMarkup( [ 'only' ] );
		section.dataset.speed = '';
		section.dataset.pause = '';
		expect( api.hero( section ).timers ).toHaveLength( 0 );

		setMatchMedia( true );
		section = heroMarkup( [ 'a', 'b' ] );
		expect( api.hero( section ).timers ).toHaveLength( 0 );

		section = heroMarkup( null );
		expect( api.hero( section ).timers ).toHaveLength( 0 );

		setMatchMedia( false );
		section = heroMarkup( [ 'a', 'b' ] );
		section.dataset.speed = '';
		section.dataset.pause = '';
		expect( api.hero( section ).timers ).toHaveLength( 1 );
		setMatchMedia( true );

		section = heroMarkup( [ 'a', 'b' ] );
		section.querySelector( '.qe-hero__rotator' ).dataset.words = '';
		expect( api.hero( section ).timers ).toHaveLength( 0 );
		jest.useRealTimers();
	} );

	test( 'hero parallax moves floating images on desktop pointers', () => {
		setMatchMedia( ( q ) => q === '(pointer: fine)' );
		const section = heroMarkup(
			[ 'a' ],
			'<div class="qe-hero__float" data-depth="0.5"></div><div class="qe-hero__float"></div>'
		);
		section.getBoundingClientRect = () => ( {
			left: 0,
			top: 0,
			width: 100,
			height: 100,
		} );
		const result = api.hero( section );
		expect( typeof result.onMove ).toBe( 'function' );
		result.onMove( { clientX: 100, clientY: 0 } );
		const floats = section.querySelectorAll( '.qe-hero__float' );
		expect( floats[ 0 ].style.transform ).toBe(
			'translate3d(10.0px, -10.0px, 0)'
		);
		expect( floats[ 1 ].style.transform ).toBe(
			'translate3d(0.0px, 0.0px, 0)'
		);
		window.requestAnimationFrame = jest.fn( () => 2 );
		result.onMove( { clientX: 0, clientY: 0 } );
		result.onMove( { clientX: 0, clientY: 0 } );
		expect( window.cancelAnimationFrame ).toHaveBeenCalledWith( 2 );

		// No parallax strength, no floats or a coarse pointer: nothing to do.
		const plain = heroMarkup( [ 'a' ] );
		plain.dataset.parallax = '';
		expect( api.hero( plain ).onMove ).toBeNull();
		setMatchMedia( false );
		const coarse = heroMarkup(
			[ 'a' ],
			'<div class="qe-hero__float" data-depth="1"></div>'
		);
		expect( api.hero( coarse ).onMove ).toBeNull();
		const mm = window.matchMedia;
		delete window.matchMedia;
		expect( api.hero( heroMarkup( [ 'a' ] ) ).onMove ).toBeNull();
		window.matchMedia = mm;
	} );

	/* Sliders -------------------------------------------------------- */

	function sliderMarkup( settings ) {
		document.body.innerHTML = `<div class="qe-slider" data-qe-slider='${ JSON.stringify(
			settings
		) }'><div class="swiper"></div><button class="qe-slider__arrow--prev"></button><button class="qe-slider__arrow--next"></button><div class="qe-slider__dots"></div></div>`;
		return document.querySelector( '.qe-slider' );
	}

	const SETTINGS = {
		perView: 3,
		perViewTablet: 2,
		perViewMobile: 1.15,
		autoplay: 5000,
		arrows: true,
		dots: true,
		static: false,
	};

	test( 'slider config', () => {
		const wrapper = sliderMarkup( SETTINGS );
		const config = api.sliderConfig( wrapper, SETTINGS );
		expect( config.slidesPerView ).toBe( 1.15 );
		expect( config.breakpoints ).toEqual( {
			768: { slidesPerView: 2 },
			1025: { slidesPerView: 3 },
		} );
		expect( config.navigation.prevEl ).toBe(
			wrapper.querySelector( '.qe-slider__arrow--prev' )
		);
		expect( config.pagination.clickable ).toBe( true );
		expect( config.autoplay.delay ).toBe( 5000 );
		expect( config.observer ).toBe( true );
		expect( config.spaceBetween ).toBe( 24 );

		wrapper.style.setProperty( '--qe-gap', '40px' );
		expect( api.sliderConfig( wrapper, SETTINGS ).spaceBetween ).toBe( 40 );
		wrapper.style.removeProperty( '--qe-gap' );

		const off = api.sliderConfig( wrapper, {
			...SETTINGS,
			arrows: false,
			dots: false,
			autoplay: 0,
		} );
		expect( off.navigation ).toBe( false );
		expect( off.pagination ).toBe( false );
		expect( off.autoplay ).toBe( false );
	} );

	test( 'static slider in the editor', async () => {
		const wrapper = sliderMarkup( { ...SETTINGS, static: true } );
		expect( await api.slider( wrapper ) ).toBeNull();
		expect( wrapper.classList.contains( 'is-static' ) ).toBe( true );
		expect( api.slider( wrapper ) ).toBeNull();

		const empty = document.createElement( 'div' );
		empty.dataset.qeSlider = '';
		expect( await api.slider( empty ) ).toBeNull();
	} );

	test( "slider uses Elementor's Swiper and adds a pause button", async () => {
		const autoplay = { stop: jest.fn(), start: jest.fn() };
		window.elementorFrontend = {
			utils: {
				swiper: jest.fn( function () {
					return Promise.resolve( { autoplay } );
				} ),
			},
		};
		window.qeEduWidgets = { pause: 'Pause it', play: 'Play it' };
		const wrapper = sliderMarkup( SETTINGS );
		const instance = await api.slider( wrapper );
		expect( instance.autoplay ).toBe( autoplay );
		expect( wrapper.classList.contains( 'is-ready' ) ).toBe( true );

		const button = wrapper.querySelector( '.qe-pause' );
		expect( button.textContent ).toBe( 'Pause it' );
		button.click();
		expect( autoplay.stop ).toHaveBeenCalled();
		expect( button.getAttribute( 'aria-pressed' ) ).toBe( 'true' );
		expect( button.textContent ).toBe( 'Play it' );
		button.click();
		expect( autoplay.start ).toHaveBeenCalled();
		expect( button.textContent ).toBe( 'Pause it' );
	} );

	test( 'slider falls back to window.Swiper or nothing', async () => {
		window.Swiper = jest.fn( function () {
			this.autoplay = null;
		} );
		let wrapper = sliderMarkup( { ...SETTINGS, autoplay: 0 } );
		expect( await api.slider( wrapper ) ).toBeInstanceOf( window.Swiper );
		expect( wrapper.querySelector( '.qe-pause' ) ).toBeNull();

		delete window.Swiper;
		window.elementorFrontend = { utils: {} };
		wrapper = sliderMarkup( SETTINGS );
		expect( await api.slider( wrapper ) ).toBeNull();
		expect( wrapper.classList.contains( 'is-ready' ) ).toBe( false );

		window.elementorFrontend = {};
		wrapper = sliderMarkup( SETTINGS );
		expect( await api.slider( wrapper ) ).toBeNull();
	} );

	test( 'autoplay is off for reduced motion and in the editor', () => {
		setMatchMedia( true );
		const wrapper = sliderMarkup( SETTINGS );
		expect( api.sliderConfig( wrapper, SETTINGS ).autoplay ).toBe( false );
		setMatchMedia( false );
		window.elementorFrontend = { isEditMode: () => true };
		expect( api.sliderConfig( wrapper, SETTINGS ).autoplay ).toBe( false );
	} );

	test( 'marquee pause button and still modes', () => {
		document.body.innerHTML =
			'<div class="qe-logos"><div class="qe-marquee"></div></div>';
		const marquee = document.querySelector( '.qe-marquee' );
		const button = api.marquee( marquee );
		expect( button.textContent ).toBe( 'Pause' );
		button.click();
		expect( marquee.classList.contains( 'is-paused' ) ).toBe( true );
		button.click();
		expect( marquee.classList.contains( 'is-paused' ) ).toBe( false );

		setMatchMedia( true );
		document.body.innerHTML =
			'<div class="qe-logos"><div class="qe-marquee"></div></div>';
		const still = document.querySelector( '.qe-marquee' );
		expect( api.marquee( still ) ).toBeNull();
		expect( still.classList.contains( 'is-paused' ) ).toBe( true );
	} );

	/* Counter -------------------------------------------------------- */

	function counterMarkup( duration ) {
		document.body.innerHTML = `<div data-qe-counter data-duration="${ duration }"><span class="qe-stat__value" data-to="45000">45,000</span><span class="qe-stat__value" data-to="">0</span></div>`;
		return document.querySelector( '[data-qe-counter]' );
	}

	test( 'number format', () => {
		document.documentElement.lang = 'en-US';
		expect( api.format( 45000.4 ) ).toBe( '45,000' );
		document.documentElement.lang = '';
		expect( api.format( 12 ) ).toBe( '12' );
	} );

	test( 'counter shows final values without animation', () => {
		let wrapper = counterMarkup( 0 );
		expect( api.counter( wrapper ) ).toBeNull();
		expect( wrapper.querySelector( '.qe-stat__value' ).textContent ).toBe(
			'45,000'
		);

		wrapper = counterMarkup( 2 );
		wrapper.dataset.duration = '';
		expect( api.counter( wrapper ) ).toBeNull();

		const io = window.IntersectionObserver;
		delete window.IntersectionObserver;
		wrapper = counterMarkup( 2 );
		expect( api.counter( wrapper ) ).toBeNull();
		window.IntersectionObserver = io;

		window.elementorFrontend = { isEditMode: () => true };
		window.IntersectionObserver = jest.fn();
		wrapper = counterMarkup( 2 );
		expect( api.counter( wrapper ) ).toBeNull();
		expect( window.IntersectionObserver ).not.toHaveBeenCalled();
		window.IntersectionObserver = io;
	} );

	test( 'counter counts up when scrolled into view', () => {
		let callback;
		const observer = { observe: jest.fn(), unobserve: jest.fn() };
		window.IntersectionObserver = jest.fn( ( cb ) => {
			callback = cb;
			return observer;
		} );
		const frames = [];
		window.requestAnimationFrame = ( cb ) => frames.push( cb );

		const wrapper = counterMarkup( 2 );
		expect( api.counter( wrapper ) ).toBe( observer );
		expect( observer.observe ).toHaveBeenCalledTimes( 2 );

		const value = wrapper.querySelector( '.qe-stat__value' );
		callback( [
			{ isIntersecting: false, target: value },
			{ isIntersecting: true, target: value },
		] );
		expect( observer.unobserve ).toHaveBeenCalledWith( value );
		expect( value.textContent ).toBe( '0' );

		const start = window.performance.now();
		frames.shift()( start + 1000 );
		expect( value.textContent ).not.toBe( '45,000' );
		frames.shift()( start + 5000 );
		expect( value.textContent ).toBe( '45,000' );
		expect( frames ).toHaveLength( 0 );
	} );

	/* Forms ---------------------------------------------------------- */

	function formMarkup( multi = true ) {
		document.body.innerHTML = `<form action="/ajax" data-qe-form="register" data-required="Required" data-invalid="Invalid" data-sending="Sending" ${
			multi ? '' : 'id="f1"'
		}>
			<input type="hidden" name="action" value="x">
			<div class="qe-form__hp"><input name="website"></div>
			${
				multi
					? '<ol class="qe-form__progress"><li class="is-active">1</li><li>2</li></ol>'
					: ''
			}
			<fieldset class="qe-form__step" data-step="1">
				<div class="qe-form__field"><input name="qe_name" required><p class="qe-form__error" hidden></p></div>
				<div class="qe-form__field"><input type="email" name="qe_email"><p class="qe-form__error" hidden></p></div>
			</fieldset>
			${
				multi
					? `<fieldset class="qe-form__step" data-step="2" hidden>
				<div class="qe-form__field"><select name="qe_country" required><option value="">-</option><option>Ireland</option></select><p class="qe-form__error" hidden></p></div>
			</fieldset>
			<div class="qe-form__consent" data-step-only="2" hidden><input type="checkbox" name="qe_consent" required><p class="qe-form__error" hidden></p></div>`
					: ''
			}
			${
				multi
					? '<button type="button" data-qe-back hidden>Back</button><button type="button" data-qe-next>Next</button>'
					: ''
			}
			<button type="submit" data-qe-submit ${ multi ? 'hidden' : '' }>Send</button>
			<div class="qe-form__message"></div>
		</form>`;
		return document.querySelector( 'form' );
	}

	test( 'validation and errors', () => {
		const form = formMarkup( false );
		api.initForm( form );
		const name = form.querySelector( '[name="qe_name"]' );
		const email = form.querySelector( '[name="qe_email"]' );
		const error = name.parentElement.querySelector( '.qe-form__error' );
		expect( error.id ).toBe( 'f1-error-0' );

		expect( api.validate( form, [ name, email ] ) ).toBe( false );
		expect( name.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
		expect( name.getAttribute( 'aria-describedby' ) ).toBe( 'f1-error-0' );
		expect( error.textContent ).toBe( 'Required' );
		expect( error.hidden ).toBe( false );
		expect( document.activeElement ).toBe( name );

		email.value = 'nope';
		name.value = 'Ada';
		expect( api.validate( form, [ name, email ] ) ).toBe( false );
		expect( name.getAttribute( 'aria-invalid' ) ).toBeNull();
		expect(
			email.parentElement.querySelector( '.qe-form__error' ).textContent
		).toBe( 'Invalid' );

		// Fixing the value re-validates on input.
		email.value = 'a@b.test';
		email.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );
		expect( email.getAttribute( 'aria-invalid' ) ).toBeNull();
		name.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );

		// An error without its own container or id.
		const loose = document.createElement( 'input' );
		loose.required = true;
		form.appendChild( loose );
		api.setError( loose, 'Oops' );
		expect( loose.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
		const bare = document.createElement( 'form' );
		const orphan = document.createElement( 'input' );
		bare.appendChild( orphan );
		api.setError( orphan, 'x' );
		api.setError( orphan, '' );
		expect( orphan.getAttribute( 'aria-invalid' ) ).toBeNull();
	} );

	test( 'visible inputs skip hidden, honeypot and hidden steps', () => {
		const form = formMarkup();
		expect(
			api.visibleInputs( form ).map( ( input ) => input.name )
		).toEqual( [ 'qe_name', 'qe_email' ] );
	} );

	test( 'two-step form: next, back, progress and consent', () => {
		const form = formMarkup();
		api.initForm( form );
		const next = form.querySelector( '[data-qe-next]' );
		const back = form.querySelector( '[data-qe-back]' );

		next.click();
		expect( form.dataset.step ).toBe( '1' );

		form.querySelector( '[name="qe_name"]' ).value = 'Ada';
		next.click();
		expect( form.dataset.step ).toBe( '2' );
		expect( back.hidden ).toBe( false );
		expect( next.hidden ).toBe( true );
		expect( form.querySelector( '[data-qe-submit]' ).hidden ).toBe( false );
		expect( form.querySelector( '.qe-form__consent' ).hidden ).toBe(
			false
		);
		const steps = form.querySelectorAll( '.qe-form__progress li' );
		expect( steps[ 1 ].getAttribute( 'aria-current' ) ).toBe( 'step' );
		expect( steps[ 0 ].getAttribute( 'aria-current' ) ).toBeNull();
		expect( document.activeElement.name ).toBe( 'qe_country' );

		const consent = form.querySelector( '[name="qe_consent"]' );
		expect( api.validate( form, [ consent ] ) ).toBe( false );
		consent.checked = true;
		expect( api.validate( form, [ consent ] ) ).toBe( true );

		back.click();
		expect( form.dataset.step ).toBe( '1' );
		expect( form.querySelector( '.qe-form__consent' ).hidden ).toBe( true );
	} );

	test( 'next step with no focusable field', () => {
		const form = formMarkup();
		form.querySelector( '[data-step="2"]' ).innerHTML = '';
		form.querySelector( '.qe-form__consent' ).remove();
		api.initForm( form );
		form.querySelector( '[name="qe_name"]' ).value = 'Ada';
		form.querySelector( '[data-qe-next]' ).click();
		expect( form.dataset.step ).toBe( '2' );
	} );

	function respond( json, ok = true ) {
		window.fetch = jest.fn( () =>
			ok
				? Promise.resolve( { json: () => Promise.resolve( json ) } )
				: Promise.reject( new Error( 'offline' ) )
		);
		window.FormData = jest.fn();
	}

	test( 'submit success resets the form and returns to step 1', async () => {
		const form = formMarkup();
		api.initForm( form );
		form.querySelector( '[name="qe_name"]' ).value = 'Ada';
		form.querySelector( '[data-qe-next]' ).click();
		respond( { success: true, data: { message: 'Thanks!' } } );

		const json = await api.submit( form );
		expect( json.success ).toBe( true );
		expect( window.fetch ).toHaveBeenCalledWith( '/ajax', {
			method: 'POST',
			body: expect.any( Object ),
			credentials: 'same-origin',
		} );
		const message = form.querySelector( '.qe-form__message' );
		expect( message.textContent ).toBe( 'Thanks!' );
		expect( message.classList.contains( 'is-success' ) ).toBe( true );
		expect( form.dataset.step ).toBe( '1' );
		expect( form.querySelector( '[name="qe_name"]' ).value ).toBe( '' );
		const button = form.querySelector( '[data-qe-submit]' );
		expect( button.disabled ).toBe( false );
		expect( button.textContent ).toBe( 'Send' );
	} );

	test( 'submit shows field errors and network failures', async () => {
		const form = formMarkup( false );
		delete form.dataset.sending;
		respond( {
			success: false,
			data: {
				message: 'Check the form',
				errors: { email: 'Bad email', missing: 'x' },
			},
		} );
		const pending = api.submit( form );
		expect( form.querySelector( '[data-qe-submit]' ).textContent ).toBe(
			'Send'
		);
		await pending;
		expect(
			form
				.querySelector( '[name="qe_email"]' )
				.getAttribute( 'aria-invalid' )
		).toBe( 'true' );
		expect(
			form
				.querySelector( '.qe-form__message' )
				.classList.contains( 'is-error' )
		).toBe( true );

		window.qeEduWidgets = { error: 'Network down' };
		respond( null, false );
		await api.submit( form );
		expect( form.querySelector( '.qe-form__message' ).textContent ).toBe(
			'Network down'
		);

		respond( {} );
		await api.submit( form );
		expect( form.querySelector( '.qe-form__message' ).textContent ).toBe(
			''
		);

		// Success on a single-step form (no steps to reset).
		respond( { success: true, data: { message: 'OK' } } );
		await api.submit( form );
		expect( form.querySelector( '.qe-form__message' ).textContent ).toBe(
			'OK'
		);
	} );

	test( 'submit event: blocked when invalid or editing, sent when valid', async () => {
		const form = formMarkup( false );
		api.initForm( form );
		respond( { success: true, data: { message: 'Sent' } } );

		form.dispatchEvent(
			new window.Event( 'submit', { bubbles: true, cancelable: true } )
		);
		expect( window.fetch ).not.toHaveBeenCalled();

		form.querySelector( '[name="qe_name"]' ).value = 'Ada';
		window.elementorFrontend = { isEditMode: () => true };
		form.dispatchEvent(
			new window.Event( 'submit', { bubbles: true, cancelable: true } )
		);
		expect( window.fetch ).not.toHaveBeenCalled();

		delete window.elementorFrontend;
		form.dispatchEvent(
			new window.Event( 'submit', { bubbles: true, cancelable: true } )
		);
		expect( window.fetch ).toHaveBeenCalledTimes( 1 );
		await flush();
		expect( form.querySelector( '.qe-form__message' ).textContent ).toBe(
			'Sent'
		);
	} );

	/* Mega menu ------------------------------------------------------ */

	function menuMarkup() {
		document.body.innerHTML = `<nav data-qe-mega data-breakpoint="1024">
			<button class="qe-mega__toggle" aria-expanded="false"></button>
			<ul class="qe-mega__menu">
				<li class="qe-mega__item has-children"><a href="#a">A</a><button class="qe-mega__sub-toggle" aria-expanded="false"></button><div class="qe-mega__panel"><ul><li><a href="#b">B</a></li></ul></div></li>
			</ul>
		</nav>`;
		return document.querySelector( '[data-qe-mega]' );
	}

	test( 'mega menu toggles, submenus and Escape', () => {
		setMatchMedia( true );
		const nav = menuMarkup();
		const menu = api.megaMenu( nav );
		const toggle = nav.querySelector( '.qe-mega__toggle' );
		const sub = nav.querySelector( '.qe-mega__sub-toggle' );

		expect( nav.classList.contains( 'is-mobile' ) ).toBe( true );
		toggle.click();
		expect( nav.classList.contains( 'is-open' ) ).toBe( true );
		expect( toggle.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		expect( document.body.classList.contains( 'qe-menu-locked' ) ).toBe(
			true
		);

		sub.click();
		expect( sub.parentElement.classList.contains( 'is-open' ) ).toBe(
			true
		);
		expect( sub.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		sub.click();
		expect( sub.parentElement.classList.contains( 'is-open' ) ).toBe(
			false
		);
		sub.click();

		nav.querySelector( 'a' ).click();
		nav.dispatchEvent(
			new window.KeyboardEvent( 'keydown', { key: 'Tab', bubbles: true } )
		);
		expect( nav.classList.contains( 'is-open' ) ).toBe( true );

		nav.dispatchEvent(
			new window.KeyboardEvent( 'keydown', {
				key: 'Escape',
				bubbles: true,
			} )
		);
		expect( nav.classList.contains( 'is-open' ) ).toBe( false );
		expect( sub.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( document.activeElement ).toBe( toggle );

		// Escape when the menu is closed only closes submenus.
		nav.dispatchEvent(
			new window.KeyboardEvent( 'keydown', {
				key: 'Escape',
				bubbles: true,
			} )
		);
		menu.setOpen( true );
		menu.setOpen( false );
		menu.closeSubmenus();
	} );

	test( 'mobile menu opens below its button and keeps focus inside', () => {
		setMatchMedia( true );
		const nav = menuMarkup();
		const menu = api.megaMenu( nav );
		const toggle = nav.querySelector( '.qe-mega__toggle' );
		const [ first, sub, last ] = [
			nav.querySelector( 'a[href="#a"]' ),
			nav.querySelector( '.qe-mega__sub-toggle' ),
			nav.querySelector( 'a[href="#b"]' ),
		];
		toggle.getBoundingClientRect = () => ( { bottom: 120.4 } );
		const visible = new Set( [ first, sub, last ] );
		const width = Object.getOwnPropertyDescriptor(
			window.HTMLElement.prototype,
			'offsetWidth'
		);
		const height = Object.getOwnPropertyDescriptor(
			window.HTMLElement.prototype,
			'offsetHeight'
		);
		Object.defineProperty( window.HTMLElement.prototype, 'offsetWidth', {
			configurable: true,
			get() {
				return visible.has( this ) && this !== last ? 10 : 0;
			},
		} );
		Object.defineProperty( window.HTMLElement.prototype, 'offsetHeight', {
			configurable: true,
			get() {
				return visible.has( this ) && this === last ? 10 : 0;
			},
		} );
		const tab = ( shiftKey = false, key = 'Tab' ) => {
			const event = new window.KeyboardEvent( 'keydown', {
				key,
				shiftKey,
				bubbles: true,
				cancelable: true,
			} );
			document.activeElement.dispatchEvent( event );
			return event.defaultPrevented;
		};

		toggle.click();
		expect( nav.style.getPropertyValue( '--qe-mega-top' ) ).toBe( '120px' );
		expect( document.activeElement ).toBe( first );

		last.focus();
		expect( tab() ).toBe( true );
		expect( document.activeElement ).toBe( toggle );
		expect( tab( true ) ).toBe( true );
		expect( document.activeElement ).toBe( last );
		sub.focus();
		expect( tab() ).toBe( false );
		expect( tab( false, 'a' ) ).toBe( false );

		nav.classList.remove( 'is-mobile' );
		last.focus();
		expect( tab() ).toBe( false );
		nav.classList.add( 'is-mobile' );
		menu.setOpen( false );
		last.focus();
		expect( tab() ).toBe( false );

		// Nothing visible to focus: focus stays where it is.
		visible.clear();
		toggle.focus();
		menu.setOpen( true );
		expect( document.activeElement ).toBe( toggle );

		Object.defineProperty(
			window.HTMLElement.prototype,
			'offsetWidth',
			width
		);
		Object.defineProperty(
			window.HTMLElement.prototype,
			'offsetHeight',
			height
		);
	} );

	test( 'mega menu dropdowns stay on screen', () => {
		setMatchMedia( false );
		const nav = menuMarkup();
		nav.querySelector( '.qe-mega__menu' ).insertAdjacentHTML(
			'beforeend',
			'<li class="qe-mega__item"><a href="#c">C</a></li><li class="qe-mega__item is-mega"><a href="#d">D</a><div class="qe-mega__panel"></div></li>'
		);
		api.megaMenu( nav );
		const li = nav.querySelector( '.qe-mega__item' );
		const panel = li.querySelector( '.qe-mega__panel' );
		Object.defineProperty( document.documentElement, 'clientWidth', {
			configurable: true,
			value: 1000,
		} );

		panel.getBoundingClientRect = () => ( { right: 1100 } );
		li.querySelector( 'a' ).dispatchEvent(
			new window.MouseEvent( 'mouseover', { bubbles: true } )
		);
		expect( li.classList.contains( 'is-flip' ) ).toBe( true );

		panel.getBoundingClientRect = () => ( { right: 900 } );
		li.querySelector( 'a' ).dispatchEvent(
			new window.FocusEvent( 'focusin', { bubbles: true } )
		);
		expect( li.classList.contains( 'is-flip' ) ).toBe( false );

		// No panel, a full-width mega panel, outside an item, or mobile: untouched.
		const plain = nav.querySelectorAll( '.qe-mega__item' )[ 1 ];
		plain
			.querySelector( 'a' )
			.dispatchEvent(
				new window.MouseEvent( 'mouseover', { bubbles: true } )
			);
		expect( plain.classList.contains( 'is-flip' ) ).toBe( false );
		const mega = nav.querySelector( '.is-mega' );
		mega.querySelector( '.qe-mega__panel' ).getBoundingClientRect =
			() => ( {
				right: 5000,
			} );
		mega.querySelector( 'a' ).dispatchEvent(
			new window.MouseEvent( 'mouseover', { bubbles: true } )
		);
		expect( mega.classList.contains( 'is-flip' ) ).toBe( false );
		nav.querySelector( '.qe-mega__toggle' ).dispatchEvent(
			new window.MouseEvent( 'mouseover', { bubbles: true } )
		);

		panel.getBoundingClientRect = () => ( { right: 1100 } );
		nav.classList.add( 'is-mobile' );
		li.querySelector( 'a' ).dispatchEvent(
			new window.MouseEvent( 'mouseover', { bubbles: true } )
		);
		expect( li.classList.contains( 'is-flip' ) ).toBe( false );
		delete document.documentElement.clientWidth;
	} );

	test( 'mega menu without matchMedia or change listeners', () => {
		window.matchMedia = jest.fn( () => ( { matches: false } ) );
		const nav = menuMarkup();
		nav.dataset.breakpoint = '';
		api.megaMenu( nav );
		expect( nav.classList.contains( 'is-mobile' ) ).toBe( false );
		expect( window.matchMedia ).toHaveBeenCalledWith(
			'(max-width: 1024px)'
		);

		delete window.matchMedia;
		const other = menuMarkup();
		api.megaMenu( other );
		expect( other.classList.contains( 'is-mobile' ) ).toBe( false );
	} );

	/* Wiring --------------------------------------------------------- */

	test( 'handlers initialise every widget type in a scope', () => {
		document.body.innerHTML = `
			<section data-qe-hero></section>
			<div data-qe-slider='{"static":true}'></div>
			<div class="qe-logos"><div class="qe-marquee"></div></div>
			<div data-qe-counter data-duration="0"></div>
			<form data-qe-form><button data-qe-submit></button><div class="qe-form__message"></div></form>
			<nav data-qe-mega><button class="qe-mega__toggle"></button></nav>`;
		api.initAll( document );
		[
			'[data-qe-hero]',
			'[data-qe-slider]',
			'.qe-marquee',
			'[data-qe-counter]',
			'[data-qe-form]',
			'[data-qe-mega]',
		].forEach( ( selector ) => {
			expect( document.querySelector( selector ).dataset.qeReady ).toBe(
				'1'
			);
		} );
		expect( Object.keys( api.HANDLERS ) ).toHaveLength( 11 );
	} );

	test( 'Elementor hooks run the handler for each widget', () => {
		const actions = {};
		const frontend = {
			hooks: { addAction: ( name, cb ) => ( actions[ name ] = cb ) },
		};
		api.registerHooks( frontend );
		expect( Object.keys( actions ) ).toContain(
			'frontend/element_ready/qe-stats.default'
		);

		document.body.innerHTML =
			'<div class="w"><div data-qe-counter data-duration="0"></div></div>';
		const scope = document.querySelector( '.w' );
		actions[ 'frontend/element_ready/qe-stats.default' ]( [ scope ] );
		expect( scope.firstChild.dataset.qeReady ).toBe( '1' );

		document.body.innerHTML =
			'<div class="w"><div data-qe-counter data-duration="0"></div></div>';
		const raw = document.querySelector( '.w' );
		actions[ 'frontend/element_ready/qe-stats.default' ]( raw );
		expect( raw.firstChild.dataset.qeReady ).toBe( '1' );
	} );

	test( 'boot with Elementor already loaded, after init, or without it', () => {
		const addAction = jest.fn();
		window.elementorFrontend = { hooks: { addAction } };
		document.body.innerHTML =
			'<div data-qe-counter data-duration="0"></div>';
		expect( api.boot() ).toBe( 'hooks' );
		expect( addAction ).toHaveBeenCalled();
		expect(
			document.querySelector( '[data-qe-counter]' ).dataset.qeReady
		).toBeUndefined();

		// Elementor present but not initialised yet: wait for its event.
		window.elementorFrontend = {};
		expect( api.boot() ).toBe( 'event' );
		expect(
			document.querySelector( '[data-qe-counter]' ).dataset.qeReady
		).toBeUndefined();
		window.elementorFrontend = { hooks: { addAction: jest.fn() } };
		window.dispatchEvent( new window.Event( 'elementor/frontend/init' ) );
		expect( window.elementorFrontend.hooks.addAction ).toHaveBeenCalled();

		// No Elementor at all: initialise directly.
		delete window.elementorFrontend;
		expect( api.boot() ).toBe( 'event' );
		expect(
			document.querySelector( '[data-qe-counter]' ).dataset.qeReady
		).toBe( '1' );

		// Still loading: wait for DOMContentLoaded.
		document.body.innerHTML =
			'<div data-qe-counter data-duration="0"></div>';
		const spy = jest
			.spyOn( document, 'readyState', 'get' )
			.mockReturnValue( 'loading' );
		const listen = jest.spyOn( document, 'addEventListener' );
		api.boot();
		expect(
			document.querySelector( '[data-qe-counter]' ).dataset.qeReady
		).toBeUndefined();
		listen.mock.calls
			.filter( ( call ) => call[ 0 ] === 'DOMContentLoaded' )
			.forEach( ( call ) => call[ 1 ]() );
		expect(
			document.querySelector( '[data-qe-counter]' ).dataset.qeReady
		).toBe( '1' );
		spy.mockRestore();
		listen.mockRestore();
	} );
} );
