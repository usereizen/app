/*!
 * VisualEditor user interface WikiaMediaInsertDialog class.
 */

/* global mw */

/**
 * Dialog for inserting MediaWiki media objects.
 *
 * @class
 * @extends ve.ui.Dialog
 *
 * @constructor
 * @param {Object} [config] Config options
 */
ve.ui.WikiaMediaInsertDialog = function VeUiMWMediaInsertDialog( config ) {
	// Parent constructor
	ve.ui.Dialog.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.WikiaMediaInsertDialog, ve.ui.Dialog );

/* Static Properties */

ve.ui.WikiaMediaInsertDialog.static.name = 'wikiaMediaInsert';

ve.ui.WikiaMediaInsertDialog.static.title = OO.ui.deferMsg( 'visualeditor-dialog-media-insert-title' );

ve.ui.WikiaMediaInsertDialog.static.icon = 'media';

ve.ui.WikiaMediaInsertDialog.static.pages = [ 'main', 'search' ];

/**
 * Properly format the media policy message
 * Strip all HTML tags except for anchors. Make anchors open in a new window.
 *
 * @method
 * @param {string} html The HTML to format
 * @returns {jQuery}
 */
ve.ui.WikiaMediaInsertDialog.static.formatPolicy = function ( html ) {
	return $( '<div>' )
		.html( html )
		.find( '*' )
			.each( function () {
				if ( this.tagName.toLowerCase() === 'a' ) {
					$( this ).attr( 'target', '_blank' );
				} else {
					$( this ).contents().unwrap();
				}
			} )
			.end();
};

/* Methods */

/**
 * Initialize the dialog.
 *
 * @method
 */
ve.ui.WikiaMediaInsertDialog.prototype.initialize = function () {
	var uploadEvents = {
		'change': 'onUploadChange',
		'upload': 'onUploadSuccess'
	};

	// Parent method
	ve.ui.Dialog.prototype.initialize.call( this );

	// Properties
	this.cartModel = new ve.dm.WikiaCart();
	this.mediaPreview = new ve.ui.WikiaMediaPreviewWidget();
	this.cart = new ve.ui.WikiaCartWidget( this.cartModel );
	this.dropTarget = new ve.ui.WikiaDropTargetWidget( {
		'$': this.$,
		'$document': this.frame.$document,
		'$overlay': this.$overlay
	} );
	this.insertButton = new OO.ui.ButtonWidget( {
		'$': this.$,
		'label': ve.msg( 'wikia-visualeditor-dialog-wikiamediainsert-insert-button' ),
		'flags': ['primary']
	} );
	this.insertionDetails = {};
	this.license = { 'promise': null, 'html': null };
	this.pages = new OO.ui.BookletLayout( { '$': this.$, 'attachPagesPanel': true } );
	this.query = new ve.ui.WikiaMediaQueryWidget( {
		'$': this.$,
		'placeholder': ve.msg( 'wikia-visualeditor-dialog-wikiamediainsert-search-input-placeholder' )
	} );
	this.queryInput = this.query.getInput();
	this.queryUpload = this.query.getUpload();
	this.search = new ve.ui.WikiaMediaResultsWidget( { '$': this.$ } );
	this.results = this.search.getResults();
	this.timings = {};
	this.upload = new ve.ui.WikiaUploadWidget( { '$': this.$ } );

	this.$cart = this.$( '<div>' );
	this.$content = this.$( '<div>' );
	this.$mainPage = this.$( '<div>' );
	this.$policy = this.$( '<div>' )
		.addClass('ve-ui-wikiaMediaInsertDialog-policy')
		.html(
			this.constructor.static.formatPolicy(
				ve.init.platform.getParsedMessage( 'wikia-visualeditor-dialog-wikiamediainsert-policy-message' )
			)
		);
	this.$policyReadMore = this.$( '<div>' )
		.addClass( 've-ui-wikiaMediaInsertDialog-readMore' );
	this.$policyReadMoreLink = this.$( '<a>' )
		.html( ve.msg( 'wikia-visualeditor-dialog-wikiamediainsert-read-more' ) );
	this.$policyReadMore.append( this.$policyReadMoreLink );
	// Core VE used to pass VeUiSurface to this constructor. Getting it now with DOM traversal.
	this.$globalOverlay = this.$frame.closest('.ve-ui-surface-overlay-global');

	// Events
	this.cartModel.connect( this, {
		'add': 'onCartModelAdd',
		'remove': 'onCartModelRemove'
	} );
	this.cart.on( 'select', ve.bind( this.onCartSelect, this ) );
	this.insertButton.connect( this, { 'click': [ 'close', 'insert' ] } );
	this.pages.on( 'set', ve.bind( this.onPageSet, this ) );
	this.query.connect( this, {
		'requestSearchDone': 'onQueryRequestSearchDone',
		'requestVideoDone': 'onQueryRequestVideoDone'
	} );
	this.queryInput.connect( this, {
		'change': 'onQueryInputChange',
		'enter': 'onQueryInputEnter'
	} );
	this.queryInput.$input.on( 'keydown', ve.bind( this.onQueryInputKeydown, this ) );
	this.search.connect( this, {
		'nearingEnd': 'onSearchNearingEnd',
		'check': 'onSearchCheck',
		'preview': 'onMediaPreview'
	} );
	this.upload.connect( this, uploadEvents );
	this.queryUpload.connect( this, uploadEvents );
	this.$policyReadMoreLink.on( 'click', ve.bind( this.onReadMoreLinkClick, this ) );
	this.dropTarget.on( 'drop', ve.bind( this.onFileDropped, this ) );

	// Initialization
	this.$mainPage.append( this.upload.$element, this.$policy, this.$policyReadMore );

	this.mainPage = new OO.ui.PageLayout( 'main', { '$content': this.$mainPage } );
	this.searchPage = new OO.ui.PageLayout( 'search', { '$content': this.search.$element } );
	this.pages.addPages( [ this.mainPage, this.searchPage ] );

	this.$cart
		.addClass( 've-ui-wikiaCartWidget-wrapper' )
		.append( this.cart.$element );
	this.$content
		.addClass( 've-ui-wikiaMediaInsertDialog-content' )
		.append( this.query.$element, this.pages.$element );

	this.$body.append( this.$content, this.$cart );
	this.frame.$content.addClass( 've-ui-wikiaMediaInsertDialog' );
	this.$foot.append( this.insertButton.$element );
	this.$frame.prepend( this.dropTarget.$element );
	this.$globalOverlay.append( this.mediaPreview.$element );
};

/**
 * Handle clicking the media policy read more link.
 *
 * @method
 * @param {jQuery} e The jQuery event
 */
ve.ui.WikiaMediaInsertDialog.prototype.onReadMoreLinkClick = function ( e ) {
	e.preventDefault();
	this.$policyReadMore.hide();
	this.$policy.animate( { 'max-height': this.$policy.children().first().height() } );
};

/**
 * Handle drag & drop file uploaded
 *
 * @method
 * @param {Object} file instance of file
 */
ve.ui.WikiaMediaInsertDialog.prototype.onFileDropped = function ( file ) {
	this.upload.$file.trigger( 'change', file );
};

/**
 * Handle query input changes.
 *
 * @method
 * @param {string} value The query input value
 */
ve.ui.WikiaMediaInsertDialog.prototype.onQueryInputChange = function ( value ) {
	this.results.clearItems();
	if ( value.trim().length === 0 ) {
		this.setPage( 'main' );
	}
};

/**
 * Handle pressing the enter key inside the query input.
 *
 * @method
 */
ve.ui.WikiaMediaInsertDialog.prototype.onQueryInputEnter = function () {
	this.results.selectItem( this.results.getHighlightedItem() );
};

/**
 * @inheritdoc
 */
ve.ui.WikiaMediaInsertDialog.prototype.onQueryInputKeydown =
	OO.ui.SearchWidget.prototype.onQueryKeydown;

/**
 * Handle the resulting data from a query media request.
 *
 * @method
 * @param {Object} items An object containing items to add to the search results
 */
ve.ui.WikiaMediaInsertDialog.prototype.onQueryRequestSearchDone = function ( items ) {
	items.forEach( function ( item ) {
		if ( item.type === 'video' ) {
			item.provider = 'wikia';
		}
	} );
	this.search.addItems( items );
	this.results.setChecked( this.cartModel.getItems(), true );
	this.pages.setPage( 'search' );
};

/**
 * Handle the resulting data from a query video request.
 *
 * @method
 * @param {Object} data An object containing the data for a video
 */
ve.ui.WikiaMediaInsertDialog.prototype.onQueryRequestVideoDone = function ( data ) {
	this.queryInput.setValue( '' );
	//this.addCartItem( model, true );
	this.addCartItem( new ve.dm.WikiaCartItem(
		data.title,
		data.tempUrl || data.url,
		'video',
		data.tempName,
		data.provider,
		data.videoId
	), true );
};

/**
 * Handle nearing the end of search results.
 *
 * @method
 */
ve.ui.WikiaMediaInsertDialog.prototype.onSearchNearingEnd = function () {
	if ( !this.queryInput.isPending() ) {
		this.query.requestMedia();
	}
};

/**
 * Handle check/uncheck of items in search results.
 *
 * @method
 * @param {Object} item The search result item data.
 */
ve.ui.WikiaMediaInsertDialog.prototype.onSearchCheck = function ( item ) {
	var cartItem;

	cartItem = this.cart.getItemFromData( item.title );

	if ( cartItem ) {
		this.cartModel.removeItems( [ cartItem.getModel() ] );
	} else {
		if ( item.type === 'video' ) {
			this.addCartItem( new ve.dm.WikiaCartItem( item.title, item.url, item.type, undefined, 'wikia' ) );
		} else {
			this.addCartItem( new ve.dm.WikiaCartItem( item.title, item.url, item.type ) );
		}
	}
};

/**
 * Handle showing or hiding the media preview
 *
 * @method
 * @param {Object|null} item The item to preview or `null` if closing the preview.
 */
ve.ui.WikiaMediaInsertDialog.prototype.onMediaPreview = function ( item ) {
	var model = item.getModel();
	if ( model.type === 'photo' ) {
		this.mediaPreview.openForImage( model.title, model.url );
	} else {
		this.mediaPreview.openForVideo( model.title, model.provider, model.videoId );
	}
};

/**
 * Handle clicking on cart items.
 *
 * @method
 * @param {ve.ui.WikiaCartItemWidget|null} item The selected cart item, or `null` if none are
 * selected.
 */
ve.ui.WikiaMediaInsertDialog.prototype.onCartSelect = function ( item ) {
	if ( item !== null ) {
		this.setPage( item.getModel().getId() );
	}
};

/**
 * Handle adding items to the cart model.
 *
 * @method
 * @param {ve.dm.WikiaCartItem[]} items Cart models
 */
ve.ui.WikiaMediaInsertDialog.prototype.onCartModelAdd = function ( items ) {
	var config, i, item, page;

	for ( i = 0; i < items.length; i++ ) {
		item = items[i];
		config = { '$': this.$ };
		if ( item.isTemporary() ) {
			config.editable = true;
			config.$license = this.$( this.license.html );
		}
		page = new ve.ui.WikiaMediaPageWidget( item, config );
		page.connect( this, {
			'remove': 'onMediaPageRemove',
			'preview': 'onMediaPreview'
		} );
		this.pages.addPages( [ page ] );
	}
	this.results.setChecked( items, true );
};

/**
 * Handle removing items from the cart model.
 *
 * @method
 * @param {ve.dm.WikiaCartItem[]} items
 */
ve.ui.WikiaMediaInsertDialog.prototype.onCartModelRemove = function ( items ) {
	this.results.setChecked( items, false );
};

/**
 * Set which page should be visible.
 *
 * @method
 * @param {string} name The name of the page to set as the current page.
 */
ve.ui.WikiaMediaInsertDialog.prototype.setPage = function ( name ) {
	var isStaticPage = ve.indexOf( name, ve.ui.WikiaMediaInsertDialog.static.pages ) > -1,
		isCartItemToggle = this.pages.getPageName() === name && !isStaticPage;

	if ( isStaticPage || isCartItemToggle ) {
		this.cart.selectItem( null );
	}

	this.pages.setPage( isCartItemToggle ? this.getDefaultPage() : name );
};

/**
 * Add an item to the cart, optionally selecting it.
 *
 * @method
 * @param {ve.dm.WikiaCartItem} item The cart item's data model.
 * @param {boolean} [select] Whether to select the cart item.
 */
ve.ui.WikiaMediaInsertDialog.prototype.addCartItem = function ( item, select ) {
	this.cartModel.addItems( [ item ] );
	if ( select ) {
		this.cart.selectItem( this.cart.getItemFromData( item.getId() ) );
	}
};

/**
 * Gets the page to use as default when a cart item is not selected.
 *
 * @method
 */
ve.ui.WikiaMediaInsertDialog.prototype.getDefaultPage = function () {
	return this.queryInput.getValue().trim().length === 0 ? 'main' : 'search';
};

/**
 * Handle clicks on the file page remove item button.
 *
 * @method
 * @param {ve.dm.WikiaCartItem} item The cart item model
 */
ve.ui.WikiaMediaInsertDialog.prototype.onMediaPageRemove = function ( item ) {
	this.cartModel.removeItems( [ item ] );
	this.setPage( this.getDefaultPage() );
};

/**
 * Handle opening the dialog.
 *
 * @method
 */
ve.ui.WikiaMediaInsertDialog.prototype.setup = function () {
	// Parent method
	ve.ui.Dialog.prototype.setup.call( this );
	this.pages.setPage( 'main' );

	// If the policy height (which has a max-height property set) is the same as the first child of the policy
	// then there is no more of the policy to show and the read more link can be hidden.
	if ( this.$policy.height() === this.$policy.children().first().height() ) {
		this.$policyReadMore.hide();
	}
	this.dropTarget.setup();
};

/**
 * Handle when a page is set.
 *
 * @method
 */
ve.ui.WikiaMediaInsertDialog.prototype.onPageSet = function () {
	this.queryInput.$input.focus();
	if ( this.pages.getPageName() === 'main' ) {
		this.query.hideUpload();
	} else {
		this.query.showUpload();
	}
};

/**
 * Handle closing the dialog.
 *
 * @method
 * @param {string} action Which action is being performed on close.
 */
ve.ui.WikiaMediaInsertDialog.prototype.teardown = function ( action ) {
	if ( action === 'insert' ) {
		this.insertMedia( ve.copy( this.cartModel.getItems() ), this.fragment );
	}
	this.cartModel.clearItems();
	this.queryInput.setValue( '' );
	this.dropTarget.teardown();

	// Parent method
	ve.ui.Dialog.prototype.teardown.call( this, action );
};

/**
 * Converts temporary cart item into permanent.
 *
 * @method
 * @param {ve.dm.WikiaCartItem} cartItem Cart item to convert.
 */
ve.ui.WikiaMediaInsertDialog.prototype.convertTemporaryToPermanent = function ( cartItem ) {
	var deferred = $.Deferred(),
		data = {
			'action': 'addmediapermanent',
			'format': 'json',
			'title': cartItem.title
		};
	if ( cartItem.provider ) {
		data.provider = cartItem.provider;
		data.videoId = cartItem.videoId;
	} else {
		data.license = cartItem.license;
		data.tempName = cartItem.temporaryFileName;
	}
	$.ajax( {
		'url': mw.util.wikiScript( 'api' ),
		'data': data,
		'success': function ( data ) {
			deferred.resolve( data.addmediapermanent.title );
		}
	} );

	return deferred.promise();
};

/**
 * @method
 * @param {ve.dm.WikiaCartItem[]} cartItems Items to add
 * @param {ve.dm.SurfaceFragment} fragment
 */
ve.ui.WikiaMediaInsertDialog.prototype.insertMedia = function ( cartItems, fragment ) {
	var i, promises = [];

	this.timings.insertStart = ve.now();

	// TODO: consider encapsulating this so it doesn't get created on every function call
	function temporaryToPermanentCallback( cartItem, name ) {
		cartItem.temporaryFileName = null;
		cartItem.url = null;
		cartItem.title = name;
	}

	for ( i = 0; i < cartItems.length; i++ ) {
		if ( cartItems[i].isTemporary() ) {
			promises.push(
				this.convertTemporaryToPermanent( cartItems[i] ).done(
					ve.bind( temporaryToPermanentCallback, this, cartItems[i] )
				)
			);
		}
	}

	$.when.apply( $, promises ).done( ve.bind( function () {
		this.insertPermanentMedia( cartItems, fragment );
	}, this ) );
};

/**
 * @method
 * @param {Object} cartItems Cart items to insert.
 * @param {ve.dm.SurfaceFragment} fragment
 */
ve.ui.WikiaMediaInsertDialog.prototype.insertPermanentMedia = function ( cartItems, fragment ) {
	var items = {},
		promises = [],
		types = {
			'photo': [],
			'video': []
		},
		cartItem,
		i;

	// Populates attributes, items.video and items.photo
	for ( i = 0; i < cartItems.length; i++ ) {
		cartItem = cartItems[i];
		cartItem.title = 'File:' + cartItem.title;
		items[ cartItem.title ] = {
			'title': cartItem.title,
			'type': cartItem.type
		};
		types[ cartItem.type ].push( cartItem.title );
	}

	function updateImageinfo( results ) {
		var i, result;
		for ( i = 0; i < results.length; i++ ) {
			result = results[i];
			items[result.title].height = result.height;
			items[result.title].width = result.width;
			items[result.title].url = result.url;
		}
	}

	// Imageinfo for photo request
	if ( types.photo.length ) {
		promises.push(
			this.getImageInfo( types.photo, 220 ).done(
				ve.bind( updateImageinfo, this )
			)
		);
	}

	// Imageinfo for videos request
	if ( types.video.length ) {
		promises.push(
			this.getImageInfo( types.video, 330 ).done(
				ve.bind( updateImageinfo, this )
			)
		);
	}

	// When all ajax requests are finished, insert media
	$.when.apply( $, promises ).done(
		ve.bind( this.insertPermanentMediaCallback, this, items, fragment )
	);
};

/**
 * Inserts media items into the document
 *
 * @method
 * @param {Object} items Items to insert
 * @param {ve.dm.SurfaceFragment} fragment
 */
ve.ui.WikiaMediaInsertDialog.prototype.insertPermanentMediaCallback = function ( items, fragment ) {
	var count, item, title, type, captionType,
		typeCount = { 'photo': 0, 'video': 0 },
		linmod = [];

	for ( title in items ) {
		item = items[title];
		type = 'wikiaBlock' + ( item.type === 'photo' ? 'Image' : 'Video' );
		captionType = ( item.type === 'photo' ) ? 'wikiaImageCaption' : 'wikiaVideoCaption';
		typeCount[item.type]++;
		linmod.push(
			{
				'type': type,
				'attributes': {
					'type': 'thumb',
					'align': 'default',
					'href': './' + item.title,
					'src': item.url,
					'width': item.width,
					'height': item.height,
					'resource': './' + item.title,
					'user': item.username
				}
			},
			{ 'type': captionType },
			{ 'type': '/' + captionType },
			{ 'type': '/' + type }
		);
	}

	for ( type in typeCount ) {
		count = typeCount[type];
		if ( type === 'photo' ) {
			type = 'image';
		}
		if ( count ) {
			ve.track( 'wikia', {
				'action': ve.track.actions.ADD,
				'label': 'dialog-media-insert-' + type,
				'value': count
			} );
		}
	}

	if ( count.image && count.video ) {
		ve.track( 'wikia', {
			'action': ve.track.actions.ADD,
			'label': 'dialog-media-insert-multiple'
		} );
	}

	fragment.collapseRangeToEnd().insertContent( linmod );

	ve.track( 'wikia', {
		'action': ve.track.actions.SUCCESS,
		'label': 'dialog-media-insert',
		'value': ve.now() - this.timings.insertStart
	} );
};

/**
 * Gets imageinfo for titles
 *
 * @method
 * @param {Object} [titles] Array of titles
 * @param {integer} width The requested width
 * @returns {jQuery.Promise}
 */
ve.ui.WikiaMediaInsertDialog.prototype.getImageInfo = function ( titles, width ) {
	var deferred = $.Deferred();
	$.ajax( {
		'url': mw.util.wikiScript( 'api' ),
		'data': {
			'action': 'query',
			'format': 'json',
			'prop': 'imageinfo',
			'iiurlwidth': width,
			'iiprop': 'url',
			'indexpageids': 'true',
			'titles': titles.join( '|' )
		},
		'success': ve.bind( this.onGetImageInfoSuccess, this, deferred )
	} );
	return deferred.promise();
};

/**
 * Responds to getImageInfo success
 *
 * @method
 * @param {jQuery.Deferred} deferred
 * @param {Object} data Response from API
 */
ve.ui.WikiaMediaInsertDialog.prototype.onGetImageInfoSuccess = function ( deferred, data ) {
	var results = [], item, i;
	for ( i = 0; i < data.query.pageids.length; i++ ) {
		item = data.query.pages[ data.query.pageids[i] ];
		results.push( {
			'title': item.title,
			'height': item.imageinfo[0].thumbheight,
			'width': item.imageinfo[0].thumbwidth,
			'url': item.imageinfo[0].thumburl
		} );
	}
	deferred.resolve( results );
};

/**
 * Gets media license dropdown HTML template.
 *
 * @method
 * @returns {jQuery.Deferred} The AJAX API request promise
 */
ve.ui.WikiaMediaInsertDialog.prototype.getLicense = function () {
	var deferred;

	if ( !this.license.promise ) {
		deferred = $.Deferred();
		this.license.promise = deferred.promise();
		$.ajax( {
			'url': mw.util.wikiScript( 'api' ),
			'data': {
				'action': 'licenses',
				'format': 'json',
				'id': 'license',
				'name': 'license'
			},
			'success': ve.bind( function ( data ) {
				deferred.resolve( this.license.html = data.licenses.html );
			}, this )
		} );
	}

	return this.license.promise;
};

/**
 * Handle file input changes.
 *
 * @method
 */
ve.ui.WikiaMediaInsertDialog.prototype.onUploadChange = function () {
	this.getLicense();
};

/**
 * Handle successful file uploads.
 *
 * @method
 * @param {Object} data The uploaded file information
 */
ve.ui.WikiaMediaInsertDialog.prototype.onUploadSuccess = function ( data ) {
	if ( !this.license.html ) {
		this.license.promise.done( ve.bind( this.onUploadSuccess, this, data ) );
	} else {
		this.addCartItem( new ve.dm.WikiaCartItem(
			data.title,
			data.tempUrl || data.url,
			'photo',
			data.tempName
		), true );
	}
};

/**
 * Overrides parent method in order to handle escape key differently
 *
 * @method
 * @param {jQuery.Event} e The jQuery event Object.
 */
ve.ui.WikiaMediaInsertDialog.prototype.onFrameDocumentKeyDown = function ( e ) {
	if ( e.which === OO.ui.Keys.ESCAPE && this.mediaPreview.isOpen() ) {
		this.mediaPreview.close();
		return false; // stop propagation
	}
	ve.ui.Dialog.prototype.onFrameDocumentKeyDown.call( this, e );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.WikiaMediaInsertDialog );
