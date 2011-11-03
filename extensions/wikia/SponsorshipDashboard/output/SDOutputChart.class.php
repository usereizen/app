<?php

/**
 * SponsorshipDashboard
 * @author Jakub "Szeryf" Kurcek
 *
 * Returns chart based on report object
 */

class SponsorshipDashboardOutputChart extends SponsorshipDashboardOutputFormatter {

	public $hiddenSeries;
	public $currentCityHub;
	public $popularCityHubs;
	public $fromYear = 2000;
	public $groupId = 0;
	public $showActionsButton = true;
	public $chartNumericId = 1; // So that multiple charts can be on the same page, each chart needs a unique numeric id

	protected $actualDate;
	
	public function  __construct() {
		parent::__construct();
		static $nextChartId = 1; // this var will count up as Charts are initialized

		$this->chartNumericId = $nextId++;
	}

	// const TEMPLATE_TEASER_CHART = 'teaser';
	const TEMPLATE_CHART_INFOONLY = 'chart_info';
	const TEMPLATE_CHART = 'chart';
	const TEMPLATE_CHART_EMPTY = 'chart_empty';

	public function getHTML() {

		wfProfileIn( __METHOD__ );
		$wgTitle = $this->App->getGlobal('wgTitle');
		$wgOut = $this->App->getGlobal('wgOut');
		$wgJsMimeType = $this->App->getGlobal('wgJsMimeType');
		$wgStyleVersion = $this->App->getGlobal('wgStyleVersion');
		$wgHTTPProxy = $this->App->getGlobal('wgHTTPProxy');

		$this->report->loadSources();
		$aData = $this->getChartData();

		$wgOut->setHTMLTitle( wfMsg( 'sponsorship-dashboard-report-page-title', $this->report->name ) );
		$wgOut->addStyle( AssetsManager::getInstance()->getSassCommonURL( 'extensions/wikia/SponsorshipDashboard/css/SponsorshipDashboard.scss' ) );

		// TODO: REFACTOR: Use Nirvana instead of EasyTemplate.
		$oTmpl = F::build( 'EasyTemplate', array( ( dirname( __FILE__ )."/templates/" ) ) );
		if ( count( $this->report->reportSources ) == 0  ) {

			$description = wfMsgExt(
				'sponsorship-dashboard-empty-description',
				'parseinline',
				$this->report->description
			);

			$oTmpl->set_vars(
				array(
					'title'			=> $this->report->name,
					'description'		=> $description,
				)
			);
			wfProfileOut( __METHOD__ );

			return $oTmpl->execute( '../../templates/output/'.self::TEMPLATE_CHART_INFOONLY );


		} elseif ( ( !isset ( $aData['ticks'] ) || !isset ( $aData['serie'] ) || !isset ( $aData['fullTicks'] ) ||
			empty ( $aData['ticks'] ) || empty ( $aData['serie'] ) || empty ( $aData['fullTicks'] ) ) )
		{
			return $oTmpl->execute( '../../templates/output/'.self::TEMPLATE_CHART_EMPTY );
		} else {

			$datasets = isset($aData['serie']) ? $aData['serie'] : array();
			$ticks = isset($aData['ticks']) ? $aData['ticks'] : array();;
			$fullTicks = isset($aData['fullTicks']) ? $aData['fullTicks'] : array();

			$description = wfMsgExt(
				'sponsorship-dashboard-empty-description',
				'parseinline',
				$this->report->description
			);

			$oTmpl->set_vars(
				array(
					'title'			=> $this->report->name,
					'description'		=> $description,
					'date'			=> $this->actualDate,
					'datasets'		=> $datasets,
					'ticks'			=> $ticks,
					'fullTicks'		=> $fullTicks,
					'hiddenSeries'		=> Wikia::json_encode($this->hiddenSeries),
					'number'		=> $this->chartNumericId,
					'path'			=> !empty( $this->groupId )
									? $wgTitle->getFullURL().'/'.$this->groupId.'/'.$this->report->id.'/csv'
									: $wgTitle->getFullURL().'/admin/CSVReport/'.$this->report->id,
					'monthly'		=> $this->report->frequency == SponsorshipDashboardDateProvider::SD_FREQUENCY_MONTH,
					'fromYear'		=> $this->fromYear,
					'showActionsButton' => $this->showActionsButton
				)
			);

			$wgOut->addScript( "<!--[if IE]><script type=\"{$wgJsMimeType}\" src=\"/skins/common/jquery/excanvas.min.js?{$wgStyleVersion}\"></script><![endif]-->\n" );
			$wgOut->addScript( "<script type=\"{$wgJsMimeType}\" src=\"/skins/common/jquery/jquery.flot.js?{$wgStyleVersion}\"></script>\n" );
			$wgOut->addScript( "<script type=\"{$wgJsMimeType}\" src=\"/skins/common/jquery/jquery.flot.trendline.js?{$wgStyleVersion}\"></script>\n" );
			$wgOut->addScript( "<script type=\"{$wgJsMimeType}\" src=\"/skins/common/jquery/jquery.flot.selection.js?{$wgStyleVersion}\"></script>\n" );
		}

		wfProfileOut( __METHOD__ );

		return $oTmpl->execute( '../../templates/output/'.self::TEMPLATE_CHART );
	}

	static function newFromReport( $oReport, $iGroupId = 0 ) {

		$obj = new self;
		$obj->set( $oReport );
		$obj->groupId = (int)$iGroupId;
		return $obj;
	}

	// ==========================================================================================

	protected function simplePrepareToDisplay( $data, $labels, $aSecondYAxis = array() ) {
		if ( empty( $data ) || empty( $labels ) ) {
			return false;
		}
		ksort( $data );
		ksort( $labels );
		$labels = array_reverse( $labels );
		$dataLen = count($data);
		$results = array();
		$i = 0;

		foreach ($data as $collumns) {
			foreach ($collumns as $key => $val) {
				if (!in_array($key, array('date', 'chacheDate'))) {
					$results[$key][] = array($i, (int)$val);
				}
			}
			if ($i % ceil($dataLen / $this->report->getNumberOfXGuideLines()) == 0) {
				$result['date'][] = array($i, $collumns['date']);
			}
			$result['fullWikiaDate'][$collumns['date']] = array($collumns['date'], $i);
			$i++;
		};

		if (empty($results)) {
			return false;
		}

		$aSerie = array();
		foreach ($results as $key => $val) {
			$aSerie[$key] = array(
				'data' => $val,
				'label' => $labels[$key],
				'yaxis' => in_array($key, $aSecondYAxis) ? 2 : 1
			);
		}

		return array(
			'serie' => $aSerie,
			SponsorshipDashboardReport::SD_RETURNPARAM_TICKS => $result['date'],
			SponsorshipDashboardReport::SD_RETURNPARAM_FULL_TICKS => $result['fullWikiaDate']
		);
	}

	public function getChartData() {
		$aData = array();
		$aLabel = array();

		foreach ( $this->report->reportSources as $reportSource ) {

			$reportSource->getData();
			$this->actualDate = $reportSource->actualDate;

			if ( !empty( $reportSource->dataAll ) && !empty( $reportSource->dataTitles ) ) {
				if ( is_array( $reportSource->dataAll ) ) {
					foreach ( $reportSource->dataAll as $key => $val ) {
						if ( isset( $aData[$key] ) ) {
							$aData[$key] = array_merge( $aData[$key], $val );
						} else {
							$aData[$key] = $val;
						}
					}
				}
				$aLabel += $reportSource->dataTitles;
			}
		}
		return $this->simplePrepareToDisplay( $aData, $aLabel );
	}
}