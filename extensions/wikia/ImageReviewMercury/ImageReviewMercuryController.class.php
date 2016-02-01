<?php

class ImageReviewMercuryController extends WikiaSpecialPageController {

	public function __construct() {
		parent::__construct( 'ImageReviewMercury' );
	}

	public function index() {
		$this->wg->Title = Title::newFromText( 'ImageReviewMercury', NS_SPECIAL );
	}
}
