<?php declare(strict_types=1);

namespace Smr\Pages\Admin;

use Smr\Account;
use Smr\Page\AccountPageProcessor;
use Smr\Request;

class ManageDraftLeadersSelectProcessor extends AccountPageProcessor {

	public function build(Account $account): never {
		$selectedGameID = Request::getInt('selected_game_id');
		(new ManageDraftLeaders($selectedGameID))->go();
	}

}
