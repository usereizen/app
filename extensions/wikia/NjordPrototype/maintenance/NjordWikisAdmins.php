<?php

class NjordWikisAdmins extends NjordUsage {
	public function execute() {
//		$wikiIds = $this->getWikiIDsWithNjordExt();
		$db = $this->getDatabaseByCityId(147);
		$sql = "SELECT user_id FROM user_groups WHERE ug_group = 'sysop'";
		$res = $db->query($sql);

		$admins = array();
		while ($row = $db->fetchObject($res)) {
			$admins[] = $row->user_name;
		}

		$this->output(implode('\n', $admins));
	}
}

$maintClass = 'NjordUsage';
require_once( DO_MAINTENANCE );