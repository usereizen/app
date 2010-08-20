<?php

$dir = dirname(__FILE__) . '/';

// register extension classes
$wgAutoloadClasses['MyHome'] = $dir.'MyHome.class.php';
$wgAutoloadClasses['MyHomeAjax'] = $dir.'MyHomeAjax.class.php';
$wgAutoloadClasses['SpecialActivityFeed'] = $dir.'SpecialActivityFeed.class.php';

/// data providers
$wgAutoloadClasses['iAPIProxy'] = $dir . 'data/iAPIProxy.php';
$wgAutoloadClasses['DataFeedProvider'] = $dir . 'data/DataFeedProvider.php';
$wgAutoloadClasses['ActivityFeedAPIProxy'] = $dir . 'data/ActivityFeedAPIProxy.php';
$wgAutoloadClasses['WatchlistFeedAPIProxy'] = $dir . 'data/WatchlistFeedAPIProxy.php';

$wgAutoloadClasses['HotSpotsProvider'] = $dir.'data/HotSpotsProvider.php';
$wgAutoloadClasses['UserContributionsProvider'] = $dir.'data/UserContributionsProvider.php';

// renderers
$wgAutoloadClasses['FeedRenderer'] = $dir.'renderers/FeedRenderer.php';
$wgAutoloadClasses['ActivityFeedRenderer'] = $dir.'renderers/ActivityFeedRenderer.php';
$wgAutoloadClasses['ActivityFeedForAnonsRenderer'] = $dir.'renderers/ActivityFeedForAnonsRenderer.php';
$wgAutoloadClasses['WatchlistFeedRenderer'] = $dir.'renderers/WatchlistRenderer.php';

$wgAutoloadClasses['HotSpotsRenderer'] = $dir.'renderers/HotSpotsRenderer.php';
$wgAutoloadClasses['UserContributionsRenderer'] = $dir.'renderers/UserContributionsRenderer.php';

// hooks
$wgHooks['RecentChange_beforeSave'][] = 'MyHome::storeInRecentChanges';
$wgHooks['EditFilter'][] = 'MyHome::getSectionName';
$wgHooks['LinksUpdateComplete'][] = 'MyHome::getInserts';

global $wgWikiaForceAIAFdebug;
$wgWikiaForceAIAFdebug = false;
if((!empty($wgEnableAchievementsInActivityFeed)) && (!empty($wgEnableAchievementsExt))){
	$wgHooks['AchievementEarned'][] = 'SpecialActivityFeed::attachAchievementToRc';
	$wgHooks['RecentChange_beforeSave'][] = 'SpecialActivityFeed::savingAnRc';
	$wgHooks['RecentChange_save'][] = 'SpecialActivityFeed::savedAnRc';
	//$wgWikiaForceAIAFdebug = true;
}

// i18n
$wgExtensionMessagesFiles['MyHome'] = $dir . 'MyHome.i18n.php';

if (!empty($wgEnableActivityFeedApiFeed)) {
	$wgAutoloadClasses["ApiQueryActivityFeed"] = $dir . "ApiQueryActivityFeed.php";
	$wgAPIListModules["activityfeed"] = "ApiQueryActivityFeed";
	$wgAutoloadClasses["ApiFeedActivityFeed"] = $dir . "ApiFeedActivityFeed.php";
	$wgAPIModules["feedactivityfeed"] = "ApiFeedActivityFeed";
}

// Ajax dispatcher
$wgAjaxExportList[] = 'MyHomeAjax';
function MyHomeAjax() {
	global $wgUser, $wgRequest;
	$method = $wgRequest->getVal('method', false);

	if (method_exists('MyHomeAjax', $method)) {
		wfProfileIn(__METHOD__);
		wfLoadExtensionMessages('MyHome');

		$data = MyHomeAjax::$method();
		$json = Wikia::json_encode($data);

		$response = new AjaxResponse($json);
		$response->setContentType('application/json; charset=utf-8');
		wfProfileOut(__METHOD__);
		return $response;
	}
}
