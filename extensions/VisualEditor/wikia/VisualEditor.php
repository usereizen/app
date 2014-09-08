<?php

/* Setup */

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'VisualEditor for Wikia'
);

$dir = dirname( __FILE__ ) . '/';

/* Classes */

$wgAutoloadClasses['VisualEditorWikiaHooks'] = $dir . 'VisualEditor.hooks.php';
$wgAutoloadClasses['ApiMediaSearch'] = $dir . 'ApiMediaSearch.php';
$wgAutoloadClasses['ApiAddMedia'] = $dir . 'ApiAddMedia.php';
$wgAutoloadClasses['ApiAddMediaTemporary'] = $dir . 'ApiAddMediaTemporary.php';
$wgAutoloadClasses['ApiAddMediaPermanent'] = $dir . 'ApiAddMediaPermanent.php';
$wgAutoloadClasses['ApiVideoPreview'] = $dir . 'ApiVideoPreview.php';
$wgAutoloadClasses['ApiTemplateParameters'] = $dir . 'ApiTemplateParameters.php';

/* API Modules */

$wgAPIModules['apimediasearch'] = 'ApiMediaSearch';
$wgAPIModules['addmediatemporary'] = 'ApiAddMediaTemporary';
$wgAPIModules['addmediapermanent'] = 'ApiAddMediaPermanent';
$wgAPIModules['videopreview'] = 'ApiVideoPreview';
$wgAPIModules['templateparameters'] = 'ApiTemplateParameters';

/* Resource Loader Modules */

$wgVisualEditorWikiaResourceTemplate = array(
	'localBasePath' => $dir . 'modules',
	'remoteExtPath' => 'VisualEditor/wikia/modules',
);

$wgResourceModules += array(
	'ext.visualEditor.wikiaViewPageTarget.init' => $wgVisualEditorWikiaResourceTemplate + array(
		'scripts' => 've/init/ve.init.mw.WikiaViewPageTarget.init.js',
		'dependencies' => array(
			'jquery.client',
			'jquery.byteLength',
			'mediawiki.Title',
			'mediawiki.Uri',
			'mediawiki.util',
			'user.options'
		),
		'messages' => array(
			'wikia-visualeditor-loading'
		),
		'position' => 'top'
	),
	'ext.visualEditor.wikiaViewPageTarget' => $wgVisualEditorWikiaResourceTemplate + array(
		'scripts' => array(
			've/init/ve.init.mw.WikiaViewPageTarget.js'
		),
		'styles' => array(
			've/init/styles/ve.init.mw.WikiaViewPageTarget.css'
		),
		'dependencies' => array(
			'ext.visualEditor.viewPageTarget'
		)
	),
	'ext.visualEditor.wikiaCore' => $wgVisualEditorWikiaResourceTemplate + array(
		'scripts' => array(
			've/ve.track.js',

			// dm
			've/dm/ve.dm.WikiaMediaCaptionNode.js',
			've/dm/ve.dm.WikiaVideoCaptionNode.js',
			've/dm/ve.dm.WikiaImageCaptionNode.js',
			've/dm/ve.dm.WikiaBlockMediaNode.js',
			've/dm/ve.dm.WikiaBlockImageNode.js',
			've/dm/ve.dm.WikiaBlockVideoNode.js',
			've/dm/ve.dm.WikiaInlineVideoNode.js',
			've/dm/ve.dm.WikiaCart.js',
			've/dm/ve.dm.WikiaCartItem.js',
			've/dm/ve.dm.WikiaMapNode.js',

			// ce
			've/ce/ve.ce.WikiaMediaCaptionNode.js',
			've/ce/ve.ce.WikiaVideoCaptionNode.js',
			've/ce/ve.ce.WikiaImageCaptionNode.js',
			've/ce/ve.ce.WikiaBlockMediaNode.js',
			've/ce/ve.ce.WikiaBlockImageNode.js',
			've/ce/ve.ce.WikiaVideoNode.js',
			've/ce/ve.ce.WikiaBlockVideoNode.js',
			've/ce/ve.ce.WikiaInlineVideoNode.js',
			've/ce/ve.ce.WikiaMapNode.js',

			// ui
			've/ui/ve.ui.WikiaCommandRegistry.js',
			've/ui/ve.ui.WikiaTrigger.js',
			've/ui/ve.ui.WikiaTriggerRegistry.js',
			've/ui/dialogs/ve.ui.WikiaCommandHelpDialog.js',
			've/ui/dialogs/ve.ui.WikiaMediaEditDialog.js',
			've/ui/dialogs/ve.ui.WikiaMediaInsertDialog.js',
			've/ui/dialogs/ve.ui.WikiaReferenceDialog.js',
			've/ui/dialogs/ve.ui.WikiaSaveDialog.js',
			've/ui/dialogs/ve.ui.WikiaSourceModeDialog.js',
			've/ui/dialogs/ve.ui.WikiaOrientationDialog.js',
			've/ui/dialogs/ve.ui.WikiaMapInsertDialog.js',
			've/ui/dialogs/ve.ui.WikiaTemplateInsertDialog.js',
			've/ui/dialogs/ve.ui.WikiaTransclusionDialog.js',
			've/ui/pages/ve.ui.WikiaParameterPage.js',
			've/ui/tools/ve.ui.WikiaDialogTool.js',
			've/ui/tools/ve.ui.WikiaHelpTool.js',
			've/ui/tools/ve.ui.WikiaMWGalleryInspectorTool.js',
			've/ui/tools/ve.ui.WikiaMWLinkNodeInspectorTool.js',
			've/ui/widgets/ve.ui.WikiaCartWidget.js',
			've/ui/widgets/ve.ui.WikiaCartItemWidget.js',
			've/ui/widgets/ve.ui.WikiaDimensionsWidget.js',
			've/ui/widgets/ve.ui.WikiaMediaPageWidget.js',
			've/ui/widgets/ve.ui.WikiaMediaSelectWidget.js',
			've/ui/widgets/ve.ui.WikiaMediaOptionWidget.js',
			've/ui/widgets/ve.ui.WikiaPhotoOptionWidget.js',
			've/ui/widgets/ve.ui.WikiaTemplateOptionWidget.js',
			've/ui/widgets/ve.ui.WikiaVideoOptionWidget.js',
			've/ui/widgets/ve.ui.WikiaMapOptionWidget.js',
			've/ui/widgets/ve.ui.WikiaMediaResultsWidget.js',
			've/ui/widgets/ve.ui.WikiaMediaQueryWidget.js',
			've/ui/widgets/ve.ui.WikiaUploadWidget.js',
			've/ui/widgets/ve.ui.WikiaMediaPreviewWidget.js',
			've/ui/widgets/ve.ui.WikiaDropTargetWidget.js',
			've/ui/widgets/ve.ui.WikiaFocusWidget.js',
			've/ui/widgets/ve.ui.WikiaCategoryInputWidget.js'
		),
		'messages' => array(
			'oasis-content-picture-added-by',
			'videohandler-video-views',
			'wikia-visualeditor-preference-enable',
			'wikia-visualeditor-dialogbutton-wikiamediainsert-tooltip',
			'wikia-visualeditor-dialogbutton-wikiamapinsert-tooltip',
			'wikia-visualeditor-dialog-wikiamapinsert-create-button',
			'wikia-visualeditor-dialog-wikiamapinsert-headline',
			'wikia-visualeditor-dialog-wikiamapinsert-empty-headline',
			'wikia-visualeditor-dialog-wikiamapinsert-empty-text',

			'wikia-visualeditor-dialog-wikiamediainsert-insert-button',
			'wikia-visualeditor-dialog-wikiamediainsert-item-license-label',
			'wikia-visualeditor-dialog-wikiamediainsert-item-title-label',
			'wikia-visualeditor-dialog-wikiamediainsert-item-remove-button',
			'wikia-visualeditor-dialog-wikiamediainsert-upload-label',
			'wikia-visualeditor-dialog-wikiamediainsert-upload-button',
			'wikia-visualeditor-dialog-wikiamediainsert-upload-error',
			'wikia-visualeditor-dialog-wikiamediainsert-search-input-placeholder',
			'wikia-visualeditor-dialog-wikiamediainsert-preview-alert',
			'wikia-visualeditor-dialog-wikiamediainsert-upload-error-size',
			'wikia-visualeditor-dialog-wikiamediainsert-upload-error-filetype',
			'wikia-visualeditor-dialog-wikiamediainsert-policy-message',
			'wikia-visualeditor-dialog-wikiamediainsert-read-more',
			'wikia-visualeditor-dialog-drop-target-callout',
			'wikia-visualeditor-help-label',
			'wikia-visualeditor-help-link',
			'wikia-visualeditor-beta-warning',
			'wikia-visualeditor-wikitext-warning',
			'wikia-visualeditor-aliennode-tooltip',
			'wikia-visualeditor-dialog-transclusion-title',
			'wikia-visualeditor-dialog-transclusion-filter',
			'wikia-visualeditor-dialogbutton-transclusion-tooltip',
			'wikia-visualeditor-savedialog-label-save',
			'wikia-visualeditor-savedialog-label-restore',
			'wikia-visualeditor-toolbar-savedialog',
			'visualeditor-descriptionpagelink',
			'wikia-visualeditor-dialogbutton-wikiasourcemode-tooltip',
			'wikia-visualeditor-dialog-wikiasourcemode-title',
			'wikia-visualeditor-dialog-wikiasourcemode-apply-button',
			'wikia-visualeditor-dialog-wikiasourcemode-help-link',
			'wikia-visualeditor-dialog-wikiasourcemode-help-text',
			'wikia-visualeditor-notification-media-must-be-logged-in',
			'wikia-visualeditor-notification-media-only-premium-videos-allowed',
			'wikia-visualeditor-notification-media-query-failed',
			'wikia-visualeditor-notification-media-permission-denied',
			'wikia-visualeditor-notification-video-preview-not-available',
			'accesskey-save',
			'wikia-visualeditor-dialog-orientation-headline',
			'wikia-visualeditor-dialog-orientation-text',
			'wikia-visualeditor-dialog-orientation-start-button',
			'wikia-visualeditor-dialog-meta-languages-readonlynote',
			'wikia-visualeditor-dialog-transclusion-no-template-description',
			'wikia-visualeditor-dialog-map-insert-title',
			'wikia-visualeditor-save-error-generic',
			'wikia-visualeditor-dialogbutton-wikiasourcemode',
			'wikia-visualeditor-dialog-done-button',
			'wikia-visualeditor-dialog-transclusion-get-info',
			'wikia-visualeditor-dialog-transclusion-preview-button',
			'wikia-visualeditor-context-transclusion-description',
			'wikia-visualeditor-dialog-wikiatemplateinsert-search',
			'wikia-visualeditor-wikiatemplateoptionwidget-appears',
		),
		'dependencies' => array(
			'ext.visualEditor.core.desktop',
			'ext.visualEditor.mwimage',
			'ext.visualEditor.mwmeta',
		)
	),
);

$wgVisualEditorPluginModules[] = 'ext.visualEditor.wikiaCore';

/* Messages */

$wgExtensionMessagesFiles['VisualEditorWikia'] = $dir . 'VisualEditor.i18n.php';

/* Hooks */

$wgHooks['GetPreferences'][] = 'VisualEditorWikiaHooks::onGetPreferences';
$wgHooks['ResourceLoaderTestModules'][] = 'VisualEditorWikiaHooks::onResourceLoaderTestModules';
$wgHooks['MakeGlobalVariablesScript'][] = 'VisualEditorWikiaHooks::onMakeGlobalVariablesScript';

/* Configuration */

$wgDefaultUserOptions['useeditwarning'] = true;

// Disable VE for blog namespaces
if ( !empty( $wgEnableBlogArticles ) ) {
	$tempArray = array();
	foreach ( $wgVisualEditorNamespaces as $key => &$value ) {
		if ( $value === NS_BLOG_ARTICLE || $value === NS_BLOG_ARTICLE_TALK ) {
			continue;
		}
		$tempArray[] = $value;
	}
	$wgVisualEditorNamespaces = $tempArray;
}

// Add additional valid namespaces for Wikia
$wgVisualEditorNamespaces[] = NS_CATEGORY;
$wgVisualEditorNamespaces[] = NS_PROJECT;
