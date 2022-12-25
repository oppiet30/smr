<?php declare(strict_types=1);

namespace Smr\Pages\Player;

use AbstractSmrPlayer;
use Smr\Database;
use Smr\Epoch;
use Smr\Page\PlayerPage;
use Smr\Page\ReusableTrait;
use Smr\Template;
use SmrForce;

class ForcesList extends PlayerPage {

	use ReusableTrait;

	public string $file = 'forces_list.php';

	public function build(AbstractSmrPlayer $player, Template $template): void {
		$template->assign('PageTopic', 'View Forces');

		$db = Database::getInstance();
		$dbResult = $db->read('SELECT *
					FROM sector_has_forces
					WHERE owner_id = ' . $db->escapeNumber($player->getAccountID()) . '
					AND game_id = ' . $db->escapeNumber($player->getGameID()) . '
					AND expire_time >= ' . $db->escapeNumber(Epoch::time()) . '
					ORDER BY sector_id ASC');

		$forces = [];
		foreach ($dbResult->records() as $dbRecord) {
			$forces[] = SmrForce::getForce($player->getGameID(), $dbRecord->getInt('sector_id'), $dbRecord->getInt('owner_id'), false, $dbRecord);
		}
		$template->assign('Forces', $forces);
	}

}