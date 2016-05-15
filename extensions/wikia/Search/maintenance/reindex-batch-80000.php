<?php

$dataFile = str_replace('reindex-', '', __FILE__);

require_once( __DIR__ . '/../../../../maintenance/commandLine.inc' );
require( $dataFile );

if ( empty( $w[ $wgCityId ] ) ) {
	echo "$wgCityId: Nothing to do\n";
	exit;
}

echo "$wgCityId: Indexing wiki\n";

include( "$IP/extensions/wikia/Search/WikiaSearch.setup.php" );

$ids = $w[ $wgCityId ];

$indexer = new Wikia\Search\Indexer();
$idCount = count( $ids );
$sliceCount = 0;
$batchSize = 2000;
foreach ( array_chunk( $ids, $batchSize ) as $idSlice ) {
	$sliceCount += $batchSize;
	$indexer->reindexBatch( $idSlice );
	echo "$wgCityId: Reindexed {$sliceCount}/{$idCount} docs\n";
}

echo "$wgCityId: Indexing process complete\n";
