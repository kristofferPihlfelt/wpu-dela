/* WPU Dela: native share sheet on touch devices and "Kopiera länk". No dependencies. */
( function () {
	var touch = !! navigator.share && matchMedia( '(pointer: coarse)' ).matches;
	document.querySelectorAll( '.wpu-dela' ).forEach( function ( nav ) {
		var d = nav.dataset;
		var status = nav.querySelector( '.wpu-dela__status' );
		var native = nav.querySelector( '.wpu-dela__inbyggd' );
		var copy = nav.querySelector( '.wpu-dela__kopiera' );
		function say( text ) {
			// Clear first so the same message is announced again on a second click.
			status.textContent = '';
			setTimeout( function () { status.textContent = text; }, 50 );
		}
		if ( native && touch ) {
			native.hidden = false;
			nav.classList.add( 'wpu-dela--inbyggd' );
			native.firstChild.addEventListener( 'click', function () {
				navigator.share( { title: d.titel, url: d.url } ).catch( function () {} );
			} );
		}
		if ( copy && navigator.clipboard ) {
			copy.hidden = false;
			copy.firstChild.addEventListener( 'click', function () {
				navigator.clipboard.writeText( d.url ).then(
					function () { say( d.kopierad ); },
					function () { say( d.fel ); }
				);
			} );
		}
	} );
} )();
