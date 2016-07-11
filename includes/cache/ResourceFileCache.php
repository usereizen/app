<?php
/**
 * Contain the ResourceFileCache class
 * @file
 * @ingroup Cache
 */
class ResourceFileCache extends FileCacheBase {
	protected $mCacheWorthy;

	/* @TODO: configurable? */
	const MISS_THRESHOLD = 360; // 6/min * 60 min

	/**
	 * Construct an ResourceFileCache from a context
	 * @param $context ResourceLoaderContext
	 * @return ResourceFileCache
	 */
	public static function newFromContext( ResourceLoaderContext $context ) {
		$cache = new self();

		// Wikia change - begin: Backporting ResourceLoader image module
		if ( $context->getImage() ) {
			$cache->mType = 'image';
		} elseif ( $context->getOnly() === 'styles' ) {
		// Wikia change - end
			$cache->mType = 'css';
		} else {
			$cache->mType = 'js';
		}
		$modules = array_unique( $context->getModules() ); // remove duplicates
		sort( $modules ); // normalize the order (permutation => combination)
		$cache->mKey = sha1( $context->getHash() . implode( '|', $modules ) );
		if ( count( $modules ) == 1 ) {
			$cache->mCacheWorthy = true; // won't take up much space
		}

		return $cache;
	}

	/**
	 * Check if an RL request can be cached.
	 * Caller is responsible for checking if any modules are private.
	 * @param $context ResourceLoaderContext
	 * @return bool
	 */
	public static function useFileCache( ResourceLoaderContext $context ) {
		global $wgUseFileCache, $wgDefaultSkin, $wgLanguageCode;
		if ( !$wgUseFileCache ) {
			return false;
		}
		// Get all query values
		$queryVals = $context->getRequest()->getValues();
		foreach ( $queryVals as $query => $val ) {
			// Wikia change - begin: Backporting ResourceLoader image module
			if ( in_array( $query, array( 'modules', 'image', 'variant', 'version', '*' ) ) ) {
				// Use file cache regardless of the value of this parameter
			// Wikia change - end
				continue; // note: &* added as IE fix
			} elseif ( $query === 'skin' && $val === $wgDefaultSkin ) {
				continue;
			} elseif ( $query === 'lang' && $val === $wgLanguageCode ) {
				continue;
			} elseif ( $query === 'only' && in_array( $val, array( 'styles', 'scripts' ) ) ) {
				continue;
			} elseif ( $query === 'debug' && $val === 'false' ) {
				continue;
			// Wikia change - begin: Backporting ResourceLoader image module
			} elseif ( $query === 'format' && $val === 'rasterized' ) {
				continue;
			}
			// Wikia change - end
			return false;
		}
		return true; // cacheable
	}

	/**
	 * Get the base file cache directory
	 * @return string
	 */
	protected function cacheDirectory() {
		return $this->baseCacheDirectory() . '/resources';
	}

	/**
	 * Item has many recent cache misses
	 * @return bool
	 */
	public function isCacheWorthy() {
		if ( $this->mCacheWorthy === null ) {
			$this->mCacheWorthy = (
				$this->isCached() || // even stale cache indicates it was cache worthy
				$this->getMissesRecent() >= self::MISS_THRESHOLD // many misses
			);
		}
		return $this->mCacheWorthy;
	}
}
