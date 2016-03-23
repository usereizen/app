<?php

class PortableInfoboxHooks {
	const PARSER_TAG_GALLERY = 'gallery';

	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		global $wgEnablePortableInfoboxEuropaTheme;

		Wikia::addAssetsToOutput( 'portable_infobox_js' );
		if ( F::app()->checkSkin( 'monobook', $skin ) ) {
			Wikia::addAssetsToOutput( 'portable_infobox_monobook_scss' );
		} else {
			Wikia::addAssetsToOutput( 'portable_infobox_scss' );
			if ( !empty( $wgEnablePortableInfoboxEuropaTheme ) ) {
				Wikia::addAssetsToOutput( 'portable_infobox_europa_theme_scss' );
			}
		}

		return true;
	}

	public static function onImageServingCollectImages( &$imageNamesArray, $articleTitle ) {
		if ( $articleTitle ) {
			$infoboxImages = PortableInfoboxDataService::newFromTitle( $articleTitle )->getImages();
			if ( !empty( $infoboxImages ) ) {
				$imageNamesArray = array_merge( $infoboxImages, (array)$imageNamesArray );
			}
		}

		return true;
	}

	/**
	 * Store information about raw content of all galleries in article to handle images in infoboxes
	 *
	 * @param $name Parser tag name
	 * @param $marker substitution marker
	 * @param $content raw tag contents
	 * @param $attributes
	 * @param $parser
	 * @param $frame
	 *
	 * @return bool
	 */
	public static function onParserTagHooksBeforeInvoke( $name, $marker, $content, $attributes, $parser, $frame ) {
		if ( $name === self::PARSER_TAG_GALLERY ) {
			\Wikia\PortableInfobox\Helpers\PortableInfoboxDataBag::getInstance()->setGallery( $marker, $content );
		}

		return true;
	}

	public static function onWgQueryPages( &$queryPages = [ ] ) {
		$queryPages[] = [ 'AllinfoboxesQueryPage', 'AllInfoboxes' ];

		return true;
	}

	public static function onAllInfoboxesQueryRecached() {
		F::app()->wg->Memc->delete( wfMemcKey( ApiQueryAllinfoboxes::MCACHE_KEY ) );

		return true;
	}

	/**
	 * Purge memcache before edit
	 *
	 * @param $article Page|WikiPage
	 * @param $user
	 * @param $text
	 * @param $summary
	 * @param $minor
	 * @param $watchthis
	 * @param $sectionanchor
	 * @param $flags
	 * @param $status
	 *
	 * @return bool
	 */
	public static function onArticleSave( Page &$article, &$user, &$text, &$summary, $minor, $watchthis, $sectionanchor,
		&$flags, &$status ) {
		PortableInfoboxDataService::newFromTitle( $article->getTitle() )->delete();

		return true;
	}

	/**
	 * Purge memcache, this will not rebuild infobox data
	 *
	 * @param Page|WikiPage $article
	 *
	 * @return bool
	 */
	public static function onArticlePurge( Page &$article ) {
		PortableInfoboxDataService::newFromTitle( $article->getTitle() )->purge();

		return true;
	}

	/**
	 * Purge articles memcache when template is edited
	 *
	 * @param $articles Array of Titles
	 *
	 * @return bool
	 */
	public static function onBacklinksPurge( Array $articles ) {
		foreach ( $articles as $title ) {
			PortableInfoboxDataService::newFromTitle( $title )->purge();
		}

		return true;
	}

	/**
	 *
	 * @param array $args
	 */
	public static function onAfterWikiCreated($cityId, $somethingElse) {
//		 This is needed to initialise $wgQueryPages
		global $IP;
		require_once( "$IP/includes/QueryPage.php" );

		global $wgQueryPages;

		$queryCacheLimit = WikiFactory::getVarValueByName("wgQueryCacheLimit", $cityId);

		$dbw = wfGetDB( DB_MASTER );

		$allInfoboxesQP = array_filter($wgQueryPages, function($page) {
			list( $class, $special ) = $page;
			return $special == \AllinfoboxesQueryPage::ALL_INFOBOXES_TYPE;
		});

		if ( empty($allInfoboxesQP) ) {
			return true;
		}

		$limit = isset( $allInfoboxesQP[0][2] ) ? $allInfoboxesQP[0][2] : null;

		$queryPage = SpecialPageFactory::getPage( \AllinfoboxesQueryPage::ALL_INFOBOXES_TYPE );

		# Do the query
		$num = $queryPage->recache( $limit === null ? $queryCacheLimit : $limit );
		if ( $num === false ) {
			wfDebugLog( 'FAILED: database error', true );
			return true;
		}

		# Commit the results
		$res = $dbw->commit( __METHOD__ );

		//TODO: handle commit failure in better way (async task?)
//			# try to reconnect to the master
//			if ( $res === false ) {
//				do {
//					var_dump('commit failed, reconnecting');
//					sleep( 10 );
//				} while ( !$dbw->ping() );
//				var_dump( "Reconnected\n\n" );
//			}
		# Wait for the slave to catch up
		wfWaitForSlaves();

		return true;
	}
}
