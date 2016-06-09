<?php

$wgAutoloadClasses['ArticleSuggestionContextService']	=  __DIR__ . '/ArticleSuggestionContextService.class.php';
$wgAutoloadClasses['ArticleSuggestionHooks']			=  __DIR__ . '/ArticleSuggestionHooks.class.php';

$wgHooks['OasisSkinAssetGroups'][]	= 'ArticleSuggestionHooks::onOasisSkinAssetGroups';
$wgHooks['BeforePageDisplay'][]		= 'ArticleSuggestionHooks::onBeforePageDisplay';
$wgHooks['WikiaSkinTopScripts'][]	= 'ArticleSuggestionHooks::onWikiaSkinTopScripts';