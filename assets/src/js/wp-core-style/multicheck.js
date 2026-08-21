'use strict';

function closest( element, selector ) {
	return element?.closest?.( selector ) || null;
}

function init() {
	document.addEventListener( 'change', ( event ) => {
		const checkbox = event.target;

		if ( ! checkbox?.classList?.contains( 'wpc-builder-multicheck__checkbox' ) ) {
			return;
		}

		const list = closest( checkbox, '[data-wpc-builder-multicheck-setting]' );

		if ( ! list || ! window.wp?.customize ) {
			return;
		}

		const settingId = list.dataset.wpcBuilderMulticheckSetting;
		const setting = window.wp.customize( settingId );

		if ( ! setting ) {
			return;
		}

		const checkboxes = list.querySelectorAll( '.wpc-builder-multicheck__checkbox' );
		const values = [];

		for ( const item of checkboxes ) {
			if ( item.checked ) {
				values.push( item.value );
			}
		}

		setting.set( values );
	} );
}

if ( 'undefined' !== typeof document ) {
	init();
}
