<?php
$wgAutoloadClasses['CakeRCController'] = __DIR__."/CakeRCController.php";
$wgHooks['GetRailModuleList'][] = 'CakeRCController::onGetRailModuleList';