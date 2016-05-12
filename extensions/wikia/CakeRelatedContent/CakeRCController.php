<?php


class CakeRCController extends WikiaController {

	const DEFAULT_TEMPLATE_ENGINE = WikiaResponse::TEMPLATE_ENGINE_MUSTACHE;
	const API_PROXY = "/api/v1/CakeRC/RelatedContent";

	public function container() {
		$this->baseUrl = self::API_PROXY;
		$this->articleName = $_GET['articleTitle'];
		$this->limit = 3;
	}

	public static function onGetRailModuleList(&$modules) {
		$modules[1350] = [
			'CakeRC',
			'container',
			null,
		];
		return true;
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return bool
	 */
	public static function onBeforePageDisplay(&$out) {
		$out->addLink([
			'rel'  => 'import',
			'href' => '/bower_components/related-content/related-content.html',
		]);
		$out->addScript('<script type="text/javascript" src="/bower_components/webcomponentsjs/webcomponents-lite.min.js"></script>');

		return true;
	}
}
