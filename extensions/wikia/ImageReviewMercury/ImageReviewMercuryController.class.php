<?php

class ImageReviewMercuryController extends WikiaSpecialPageController {

	public function __construct() {
<<<<<<< HEAD
		parent::__construct( 'ImageReviewMercury' );
=======

		$this->imageReview = (new ImageReviewMercury);
		parent::__construct( 'ImageReviewMercury', '', false );
>>>>>>> dev
	}

	public function index() {
		$this->wg->Title = Title::newFromText( 'ImageReviewMercury', NS_SPECIAL );
	}
}
