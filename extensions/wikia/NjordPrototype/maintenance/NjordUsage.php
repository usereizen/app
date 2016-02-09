<?php
/**
 * Maintenance script for generating CSV file with data about NJORD (MOM) usage.
 * Usage: $ SERVER_ID=1 php /usr/wikia/source/wiki/extensions/wikia/NjordPrototype/maintenance/NjordUsage.php
 */
ini_set( 'display_errors', '1' );
ini_set( 'error_reporting', E_ALL );

require_once( dirname( __FILE__ ) . '/../../../../maintenance/Maintenance.php' );
require_once(dirname(__FILE__) . '/NjordHelper.php');

class NjordUsage extends Maintenance {
	public function __construct() {
		$this->helper = new NjordHelper();
		parent::__construct();
	}

	/**
	 * Do the actual work. All child classes will need to implement this
	 */
	public function execute() {
		$city_list_with_njord_ext = $this->helper->getWikiIDsWithNjordExt();
		$date_created = $this->helper->getWikiCreationDates( $city_list_with_njord_ext );

		if ( count( $city_list_with_njord_ext ) > 0 ) {
			$outputCSV = [];
			$outputHTML = [];
			foreach ( $city_list_with_njord_ext as $cityId ) {
				$njordData = $this->helper->getNjordData( $cityId );
				$outputCSV[ ] = $this->helper->formatOutputRowCSV( $cityId, $njordData, $date_created[ $cityId ] );
				$outputHTML[ ] = $this->helper->formatOutputRowHTML( $cityId, $njordData );
			}
			$this->helper->exportToCSV( $outputCSV );
			$this->helper->exportToHTML( $outputHTML );
		} else {
			echo "COULD NOT FIND WIKIS WITH NJORD ENABLED!";
		}
	}


}

$maintClass = 'NjordUsage';
require_once( DO_MAINTENANCE );
