<?php
$wgAutoloadClasses['CakeRCController'] = __DIR__."/CakeRCController.php";
$wgAutoloadClasses['CakeRCApiController'] = __DIR__."/CakeRCApiController.php";
$wgHooks['GetRailModuleList'][] = 'CakeRCController::onGetRailModuleList';
$wgHooks['BeforePageDisplay'][] = 'CakeRCController::onBeforePageDisplay';
