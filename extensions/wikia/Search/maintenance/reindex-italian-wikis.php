<?php

require_once( "../../../../maintenance/commandLine.inc" );
include( "$IP/extensions/wikia/Search/WikiaSearch.setup.php" );

$wikis = json_decode( file_get_contents( __DIR__ . '/italian.json' ) );
if ( empty( $wikis[ 'w' . $wgCityId ] ) ) {
	exit;
}

echo "Indexing wiki #" . $wgCityId;

$ids = $wikis[ 'w' . $wgCityId ];

$indexer = new Wikia\Search\Indexer();
$idCount = count( $ids );
$sliceCount = 0;
$batchSize = 2000;
foreach ( array_chunk( $ids, $batchSize ) as $idSlice ) {
	$sliceCount += $batchSize;
	$indexer->reindexBatch( $idSlice );
	echo "Reindexed {$sliceCount}/{$idCount} docs\n";
}

echo "Indexing process complete.\n";
