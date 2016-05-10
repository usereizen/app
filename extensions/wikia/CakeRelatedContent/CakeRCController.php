<?php


class CakeRCController extends WikiaController {

	const DEFAULT_TEMPLATE_ENGINE = WikiaResponse::TEMPLATE_ENGINE_MUSTACHE;

	public function container() {
		$this->baseUrl = "http://content-entity.service.sjc-dev.consul:31930";
		$this->articleName = $_GET['articleTitle'];
	}

	public static function onGetRailModuleList(&$modules) {
		$modules[1350] = [
			'CakeRC',
			'container',
			null,
		];
		return true;
	}
}
