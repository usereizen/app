<?php

use Wikia\DependencyInjection\Injector;
use Wikia\Service\Gateway\UrlProvider;

class CakeRCApiController extends WikiaApiController {
	const SERVICE_NAME = "content-entity";

	public function getRelatedContent() {
		// nirvana does not pass through the path
		$path = explode(CakeRCController::API_PROXY.'/', $_SERVER['REQUEST_URI'])[1];
		$host = $this->getUrlProvider()->getUrl(self::SERVICE_NAME);

		/** @var MWHttpRequest $req */
		$req = Http::request(
				'GET', 
				"http://{$host}/{$path}", 
				['noProxy' => true, 'returnInstance' => true]);

		$this->response->setCode($req->getStatus());
		$this->response->setContentType($req->getResponseHeader('Content-Type'));
		$this->response->setBody($req->getContent());
	}

	/**
	 * @return UrlProvider
	 */
	private function getUrlProvider() {
		return Injector::getInjector()->get(UrlProvider::class);
	}
}
