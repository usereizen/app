<?php

class WikiaSpecialVersion {

	public static function onSoftwareInfo( array &$software ) {
		$wikiaSoftware = [
			wfMessage( 'wikia-version-code' )->escaped() => static::getWikiaCodeVersion(),
			wfMessage( 'wikia-version-config' )->escaped() => static::getWikiaConfigVersion()
		];

		$software = $wikiaSoftware + $software;
	}
	/**
	 * Identifies tag we're on based on file
	 * @return string
	 */
	public static function getWikiaCodeVersion() {
		global $IP;
		return self::getVersionFromDir($IP);
	}

	public static function getWikiaConfigVersion() {
		global $IP;
		return self::getVersionFromDir("$IP/../config");
	}

	private static function getVersionFromDir($dir) {
		$filename = $dir . '/wikia.version.txt';
		if ( file_exists( $filename ) ) {
			return file_get_contents( $filename );
		}

		$gitInfo = new GitInfo( $dir );

		return $gitInfo->getCurrentBranch();
	}
}
