<?php

/**
 * Implements Special:JavaScriptTest
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SpecialPage
 */

class SpecialJavaScriptTest extends SpecialPage {

	/**
	 * @var $frameworks Array: Mapping of framework ids and their initilizer methods
	 * in this class. If a framework is requested but not in this array,
	 * the 'unknownframework' error is served.
	 */
	static $frameworks = array(
		'qunit' => 'initQUnitTesting',
	);

	public function __construct() {
		parent::__construct( 'JavaScriptTest' );
	}

	public function execute( $par ) {
		global $wgEnableJavaScriptTest;

		$out = $this->getOutput();

		$this->setHeaders();
		$out->disallowUserJs();

		// Abort early if we're disabled
		if ( $wgEnableJavaScriptTest !== true ) {
			$out->addWikiMsg( 'javascripttest-disabled' );
			return;
		}

		$out->addModules( 'mediawiki.special.javaScriptTest' );

		// Determine framework
		$pars = explode( '/', $par );
		$framework = strtolower( $pars[0] );

		// No framework specified
		if ( $par == '' ) {
			$out->setPagetitle( wfMsgHtml( 'javascripttest' ) );
			$summary = $this->wrapSummaryHtml(
				wfMsgHtml( 'javascripttest-pagetext-noframework' ) . $this->getFrameworkListHtml(),
				'noframework'
			);
			$out->addHtml( $summary );

		// Matched! Display proper title and initialize the framework
		} elseif ( isset( self::$frameworks[$framework] ) ) {
			$out->setPagetitle( wfMsgHtml( 'javascripttest-title', wfMsgHtml( "javascripttest-$framework-name" ) ) );
			$out->setSubtitle(
				wfMessage( 'javascripttest-backlink' )->rawParams( Linker::linkKnown( $this->getTitle() ) )->escaped()
			);
			$this->{self::$frameworks[$framework]}();

		// Framework not found, display error
		} else {
			$out->setPagetitle( wfMsgHtml( 'javascripttest' ) );
			$summary = $this->wrapSummaryHtml( '<p class="error">'
				. wfMsgHtml( 'javascripttest-pagetext-unknownframework', $par )
				. '</p>'
				. $this->getFrameworkListHtml(),
				'unknownframework'
			);
			$out->addHtml( $summary );
		}
	}

	/**
	 * Get a list of frameworks (including introduction paragraph and links to the framework run pages)
	 * @return String: HTML
	 */
	private function getFrameworkListHtml() {
		$list = '<ul>';
		foreach( self::$frameworks as $framework => $initFn ) {
			$list .= Html::rawElement(
				'li',
				array(),
				Linker::link( $this->getTitle( $framework ), wfMsgHtml( "javascripttest-$framework-name" ) )
			);
		}
		$list .= '</ul>';
		$msg = wfMessage( 'javascripttest-pagetext-frameworks' )->rawParams( $list )->parseAsBlock();

		return $msg;
	}

	/**
	 * Function to wrap the summary.
	 * It must be given a valid state as a second parameter or an exception will
	 * be thrown.
	 * @param $html String: The raw HTML.
	 * @param $state String: State, one of 'noframework', 'unknownframework' or 'frameworkfound'
	 */
	private function wrapSummaryHtml( $html, $state ) {
		$validStates = array( 'noframework', 'unknownframework', 'frameworkfound' );
		if( !in_array( $state, $validStates ) ) {
			throw new MWException( __METHOD__
				. ' given an invalid state. Must be one of "'
				. join( '", "', $validStates) . '".'
			);
		}
		return "<div id=\"mw-javascripttest-summary\" class=\"mw-javascripttest-$state\">$html</div>";
	}

	/**
	 * Initialize the page for QUnit.
	 */
	private function initQUnitTesting() {
		global $wgJavaScriptTestConfig, $wgLang;

		$out = $this->getOutput();

		$out->addModules( 'mediawiki.tests.qunit.testrunner' );
		$qunitTestModules = $out->getResourceLoader()->getTestModuleNames( 'qunit' );
		$out->addModules( $qunitTestModules );

		$summary = wfMessage( 'javascripttest-qunit-intro' )
			->params( $wgJavaScriptTestConfig['qunit']['documentation'] )
			->parseAsBlock();
		$header = wfMessage( 'javascripttest-qunit-heading' )->escaped();
		$userDir = $wgLang->getDir();

		$baseHtml = <<<HTML
<div class="mw-content-ltr">
<div id="qunit-header"><span dir="$userDir">$header</span></div>
<div id="qunit-banner"></div>
<div id="qunit-testrunner-toolbar"></div>
<div id="qunit-userAgent"></div>
<ol id="qunit-tests"></ol>
<div id="qunit-fixture">test markup, will be hidden</div>
</div>
HTML;
		$out->addHtml( $this->wrapSummaryHtml( $summary, 'frameworkfound' ) . $baseHtml );

		// This special page is disabled by default ($wgEnableJavaScriptTest), and contains
		// no sensitive data. In order to allow TestSwarm to embed it into a test client window,
		// we need to allow iframing of this page.
		$out->allowClickjacking();

		// Used in ./tests/qunit/data/testrunner.js, see also documentation of
		// $wgJavaScriptTestConfig in DefaultSettings.php
		$out->addJsConfigVars( 'QUnitTestSwarmInjectJSPath', $wgJavaScriptTestConfig['qunit']['testswarm-injectjs'] );
	}

	public function isListed(){
		global $wgEnableJavaScriptTest;
		return $wgEnableJavaScriptTest === true;
	}

}
