'use strict';

function attachVisualModeListener( editorId, push ) {
	if ( typeof window.tinymce?.on !== 'function' ) {
		return;
	}

	window.tinymce.on( 'AddEditor', ( event ) => {
		if ( event.editor.id !== editorId ) {
			return;
		}

		event.editor.on( 'change keyup input SetContent ExecCommand', push );
	} );
}

function bindEditor( control ) {
	const container = control.container?.[ 0 ];

	if ( ! container ) {
		return;
	}

	const target = container.querySelector( '[data-wpc-builder-editor-target]' );

	if ( ! target ) {
		return;
	}

	const hiddenInputId = target.dataset.wpcBuilderEditorTarget;
	const hidden = document.getElementById( hiddenInputId );
	const textarea = target.querySelector( 'textarea' );

	if ( ! hidden || ! textarea ) {
		return;
	}

	const editorId = textarea.id;

	const push = () => {
		let content = textarea.value;
		const editor = window.tinymce?.get?.( editorId );

		if ( editor && ! editor.isHidden() ) {
			content = editor.getContent();
		}

		if ( hidden.value === content ) {
			return;
		}

		hidden.value = content;
		hidden.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	};

	textarea.addEventListener( 'input', push );
	textarea.addEventListener( 'change', push );

	if ( ! window.wp?.editor ) {
		return;
	}

	attachVisualModeListener( editorId, push );

	try {
		window.wp.editor.initialize( editorId, {
			tinymce: true,
			quicktags: true,
			mediaButtons: true,
		} );
	} catch ( error ) {
		if ( window.console?.warn ) {
			window.console.warn(
				'wpc-builder-editor: wp.editor.initialize() failed, falling back to a plain textarea.',
				error
			);
		}
	}
}

function bindWhenEmbedded( control ) {
	if ( 'wpc-builder-editor' !== control.params.type ) {
		return;
	}

	control.deferred.embedded.done( () => {
		bindEditor( control );
	} );
}

function init() {
	if ( ! window.wp?.customize ) {
		return;
	}

	window.wp.customize.control.each( bindWhenEmbedded );

	window.wp.customize.control.bind( 'add', bindWhenEmbedded );
}

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
