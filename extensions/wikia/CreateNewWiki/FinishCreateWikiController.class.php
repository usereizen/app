<?php

use \Wikia\Logger\WikiaLogger;

class FinishCreateWikiController extends WikiaController {

	const COOKIE_NAME = 'createnewwiki';

	// form field values
	var $params;

	/**
	 * Loads params from cookie.
	 */
	protected function LoadState() {
		wfProfileIn(__METHOD__);
		if(!empty($_COOKIE[self::COOKIE_NAME])) {
			$this->params = json_decode($_COOKIE[self::COOKIE_NAME], true);
		} else {
			$this->params = array();
		}
		wfProfileOut(__METHOD__);
	}

	protected function clearState() {
		wfProfileIn(__METHOD__);
		setcookie(self::COOKIE_NAME, '', time() - 3600, $this->app->wg->cookiePath, $this->app->wg->cookieDomain);
		wfProfileOut(__METHOD__);
	}

	public function WikiWelcomeModal() {
		wfProfileIn(__METHOD__);

		$buttonParams = [
			'type' => 'button',
			'vars' => [
				'type' => 'button',
				'classes' => [ 'wikia-button',  'big', 'createpage' ],
				'value' => wfMessage( 'oasis-navigation-v2-create-page' )->escaped(),
				'imageClass' => 'new',
				'data' => [
					'key' => 'event',
					'value' => 'createpage'
				]
			]
		];

		$this->title = wfMessage( 'cnw-welcome-headline', $this->app->wg->Sitename )->text();
		$this->instruction1 = wfMessage( 'cnw-welcome-instruction1' )->text();
		$this->button = \Wikia\UI\Factory::getInstance()->init( 'button' )->render( $buttonParams );
		$this->help = wfMessage( 'cnw-welcome-help' )->text();

		$this->response->setTemplateEngine( WikiaResponse::TEMPLATE_ENGINE_MUSTACHE );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Updates wiki specific properties set from wiki creation wizard.
	 * Context of this method is on the wiki that the values are changing on.
	 * Main wiki creation happens on www, and it will redirect to the newly created wiki.
	 * The values are read from the session and only accessible by the admin.
	 */
	public function FinishCreate() {
		global $wgUser, $wgOut;

		if ( !$wgUser->isAllowed( 'finishcreate' ) ) {
			return false;
		}

		$this->skipRendering();
		$this->LoadState();

		// set theme
		if(!empty($this->params['color-body'])) {
			$themeSettings = new ThemeSettings();
			$themeSettings->saveSettings($this->params);
		}

		// set description on main page
		if(!empty($this->params['wikiDescription'])) {
			global $wgSitename;

			$mainTitle = Title::newFromText( $wgSitename );
			$mainId = $mainTitle->getArticleID();
			$mainArticle = Article::newFromID( $mainId );

			if ( !empty( $mainArticle ) ) {
				$newMainPageText = $this->getClassicMainPage( $mainArticle );

				$mainArticle->doEdit( $newMainPageText, '' );
			}
		}

		$wgOut->enableClientCache(false);

		$this->clearState();

		/**
		 * Mainpage under $wgSitename may not be ready yet
		 * CreateNewWikiTask::postCreationSetup is run asynchronously on task machine
		 *
		 * MediaWiki will handle redirect to the main page and keep the URL parameter
		 *
		 * @see SUS-1167
		 */
		$wgOut->redirect( '/?wiki-welcome=1' );
	}

	/**
	 * setup main page article content for classic main page
	 * @param $mainArticle Article
	 * @return string - main page article wiki text
	 */
	private function getClassicMainPage( $mainArticle ) {
		global $wgParser, $wgSitename;

		$mainPageText = $mainArticle->getRawText();
		$matches = array();
		$description = $this->params['wikiDescription'];

		if ( preg_match( '/={2,3}[^=]+={2,3}/', $mainPageText, $matches ) ) {
			$newSectionTitle = str_replace( 'Wiki', $wgSitename, $matches[0] );
			$description = "{$newSectionTitle}\n{$description}";
		}

		$newMainPageText = $wgParser->replaceSection( $mainPageText, 1, $description );

		return $newMainPageText;
	}
}
