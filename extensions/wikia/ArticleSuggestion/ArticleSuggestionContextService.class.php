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
				array("130814", 43615, "http://i.imgur.com/J2SesCg.jpg"),
				array("130814", 2125,  "http://i.imgur.com/Zv2BlHC.png"),
				array("130814", 8218,  "http://i.imgur.com/fv7E78K.jpg"),
			);

			$uid = User::idFromName( $wg->User->getName() );

			foreach ($suggestions as $suggestion) {
				$wikiId = $suggestion[0];
				$pageId = $suggestion[1];
				$image  = $suggestion[2];

				$globalTitle = GlobalTitle::newFromId($pageId, $wikiId);
				$title = $globalTitle->getText();
				$url = $globalTitle->getFullURL();
				$data[] = array($image, $url, $title);
			}

			return [
				'uid' => $uid,
				'data' => $data,
			];
		});
	}
}
