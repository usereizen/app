<?php

use Wikia\Util\GlobalStateWrapper;

class ArticleSuggestionContextService {
	public function getContext( Title $title, $skinName ) {

		$wrapper = new GlobalStateWrapper( [
			'wgTitle' => $title,
		] );

		$wg = F::app()->wg;

		return $wrapper->wrap( function () use ( $title, $wg, $skinName ) {
			$data = array();

			// Mock article suggestions
			$suggestions = array(
				array("130814", 2225),
				array("130814", 2119),
				array("130814", 3841),
				array("130814", 4278),
			);

			$uid = User::idFromName( $wg->User->getName() );

			foreach ($suggestions as $suggestion) {
				$wikiId = $suggestion[0];
				$pageId = $suggestion[1];
				$globalTitle = GlobalTitle::newFromId($pageId, $wikiId);
				$title = $globalTitle->getText();
				$url = $globalTitle->getFullURL();
				$themeSettings = unserialize(WikiFactory::getVarByName('wgOasisThemeSettings', $wikiId)->cv_value);
				$logoUrl = $themeSettings['wordmark-image-url'];
				$data[] = array($logoUrl, $url, $title);
			}

			return [
				'uid' => $uid,
				'data' => $data,
			];
		});
	}
}