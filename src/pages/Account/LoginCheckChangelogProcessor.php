<?php declare(strict_types=1);

namespace Smr\Pages\Account;

use Smr\Account;
use Smr\Database;
use Smr\Page\AccountPageProcessor;

class LoginCheckChangelogProcessor extends AccountPageProcessor {

	public function build(Account $account): never {
		$lastLogin = $account->getLastLogin();

		$db = Database::getInstance();
		$dbResult = $db->read('SELECT 1 FROM version WHERE went_live > ' . $db->escapeNumber($lastLogin) . ' LIMIT 1');
		// do we have updates?
		if ($dbResult->hasRecord()) {
			(new ChangelogView($lastLogin))->go();
		}

		(new LoginProcessor())->go();
	}

}
