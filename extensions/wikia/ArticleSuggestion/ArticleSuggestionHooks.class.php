<?php

class ArticleSuggestionHooks {
	const ASSET_GROUP_ARTICLE_SUGGESTION = 'article_suggestion_desktop_js';

	/**
	 * Modify assets appended to the bottom of the page
	 *
	 * @param array $jsAssets
	 *
	 * @return bool
	 */
	public static function onOasisSkinAssetGroups( &$jsAssets ) {
		$jsAssets[] = self::ASSET_GROUP_ARTICLE_SUGGESTION;

		return true;
	}

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		JSMessages::enqueuePackage( 'ArticleSuggestion', JSMessages::EXTERNAL );
		Wikia::addAssetsToOutput( 'article_suggestion_scss' );
		return true;
	}

	/**
	 * Register ad-related vars on top
	 *
	 * @param array $vars
	 * @param array $scripts
	 *
	 * @return bool
	 */
	public static function onWikiaSkinTopScripts( &$vars, &$scripts ) {
		global $wgTitle;
		$skin = RequestContext::getMain()->getSkin();
		$skinName = $skin->getSkinName();

		$context = ( new ArticleSuggestionContextService() )->getContext( $wgTitle, $skinName );

		$vars['articlesuggestions'] = [ 'context' => $context ];

		return true;
	}
}
