<?php

class Bucky {

	const DEFAULT_SAMPLING = 1; // percentage
	const BASE_URL = '//slot1.images.wikia.nocookie.net/__rum';

	static protected $environment;

	static public function getEnvironment() {
		if ( self::$environment === null ) {
			if ( ($stagingEnv = Wikia::getStagingServerName()) ) {
				$environment = $stagingEnv;
			} else {
				$app = F::app();
				$wgDevelEnvironment = $app->wg->DevelEnvironment;
				if ( $wgDevelEnvironment ) {
					$environment = "devbox.{$wgDevelEnvironment}";
				} else {
					$environment = 'production';
				}
			}
			self::$environment = $environment;
		}
		return self::$environment;
	}

	static public function onSkinAfterBottomScripts( Skin $skin, &$bottomScripts ) {
		$environment = self::getEnvironment();
		if ( $environment ) {
			$wgBuckySampling = F::app()->wg->BuckySampling;
			$url = self::BASE_URL; // "/v1/send" is automatically appended
			$sample = (isset($wgBuckySampling) ? $wgBuckySampling : self::DEFAULT_SAMPLING) / 100;
			$config = json_encode(array(
				'host' => $url,
				'sample' => $sample,
			));
			$script = "<script>$(function(){Bucky.setOptions({$config});$(window).load(function(){Bucky.sendPagePerformance('{$environment}');});});</script>";
			$bottomScripts .= $script;
		}

		return true;
	}

	static public function onOasisSkinAssetGroups( &$assetGroups ) {
		if ( self::getEnvironment() ) {
			$assetGroups[] = 'bucky_js';
		}

		return true;
	}

}
