<?php

$dir = __DIR__ . "/../../../../";
require_once( $dir . 'maintenance/Maintenance.php' );

class UnprotectScripts extends Maintenance {

	const JS_FILE_EXTENSION = '.js';

	private $wikiaUser;

	/**
	 * Set script options
	 */
	public function __construct() {
		parent::__construct();

		$this->addOption( 'reason', 'Reason for the move.');
	}

	public function execute() {
		global $wgCityId, $wgEnableContentReviewExt;

		$reason = $this->getOption( 'reason', '' );

		if ( !empty( $wgEnableContentReviewExt ) ) {
			$helper = new \Wikia\ContentReview\Helper();
			$jsPages = $helper->getJsPages();

			foreach ( $jsPages as $jsPage ) {
				$title = Title::newFromText( $jsPage['page_title'], NS_MEDIAWIKI );
				$page = WikiPage::factory( $title );
				$cascade = 0;
				$page->doUpdateRestrictions( [], [], $cascade, $reason, $this->getWikiaUser() );
			}
		} else {
			$this->output( "Wiki (Id: {$wgCityId}) has disabled custom scripts.\n" );
		}

	}

	private function getWikiaUser() {
		if ( empty( $this->wikiaUser ) ) {
			$this->wikiaUser = User::newFromName( 'Wikia' );
		}

		return $this->wikiaUser;
	}
}

$maintClass = 'UnprotectScripts';
require_once( RUN_MAINTENANCE_IF_MAIN );
