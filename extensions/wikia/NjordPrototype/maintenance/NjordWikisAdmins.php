<?php

require_once( dirname( __FILE__ ) . '/../../../../maintenance/Maintenance.php' );
require_once( dirname(__FILE__) . '/NjordHelper.php');


class NjordWikisAdmins extends Maintenance {
	public function __construct() {
		$this->helper = new NjordHelper();
		parent::__construct();
	}

	public function execute() {
		$wikiIds = $this->helper->getWikiIDsWithNjordExt();

		$adminIds = $this -> getUserIds($wikiIds);
		$admins = $this->getUserData($adminIds);
		$this->helper->exportToCSV($admins);
		$this->output("Done\n");

	}

	private function getUserIds($cityIds) {
		$admins = array();

		foreach($cityIds as $cityId) {
			$db = $this->helper->getDatabaseByCityId($cityId);
			$sql = "SELECT ug_user FROM user_groups WHERE ug_group = 'sysop'";
			$res = $db->query($sql);

			while ($row = $db->fetchObject($res)) {
				$admins[] = $row->ug_user;
			}
		}

		return array_unique($admins);
	}

	private function getUserData($userIds) {
		$db = wfGetDB(DB_SLAVE, array(), 'wikicities');
		$sql = "SELECT user_name, user_email FROM user WHERE user_id in (".implode(',', $userIds).")";
		$res = $db->query($sql);

		$userData = array();
		while ($row = $db->fetchObject($res)) {
			$userData[] = [
				'username' => $row->user_name,
				'email' => $row->user_email
				];
		}

		return $userData;
	}
}

$maintClass = 'NjordWikisAdmins';
require_once( DO_MAINTENANCE );