<?php declare(strict_types=1);

namespace Smr\Pages\Player;

use AbstractSmrPlayer;
use Globals;
use Smr\Database;
use Smr\Page\PlayerPage;
use Smr\Template;

class ExaminePlanet extends PlayerPage {

	public string $file = 'planet_examine.php';

	public function build(AbstractSmrPlayer $player, Template $template): void {
		$template->assign('PageTopic', 'Examine Planet');

		$planet = $player->getSectorPlanet();
		$template->assign('ThisPlanet', $planet);

		$planetLand =
			!$planet->hasOwner()
			|| $planet->getOwner()->sameAlliance($player)
			|| in_array($player->getAccountID(), Globals::getHiddenPlayers());

		if (!$planetLand) {
			// Only check treaties if we can't otherwise land.
			$ownerAllianceID = 0;
			if ($planet->hasOwner()) {
				$ownerAllianceID = $planet->getOwner()->getAllianceID();
			}
			$db = Database::getInstance();
			$dbResult = $db->read('
				SELECT 1
				FROM alliance_treaties
				WHERE (alliance_id_1 = ' . $db->escapeNumber($ownerAllianceID) . ' OR alliance_id_1 = ' . $db->escapeNumber($player->getAllianceID()) . ')
				AND (alliance_id_2 = ' . $db->escapeNumber($ownerAllianceID) . ' OR alliance_id_2 = ' . $db->escapeNumber($player->getAllianceID()) . ')
				AND game_id = ' . $db->escapeNumber($player->getGameID()) . '
				AND planet_land = 1
				AND official = ' . $db->escapeBoolean(true));
			$planetLand = $dbResult->hasRecord();
		}
		$template->assign('PlanetLand', $planetLand);

		if ($planetLand) {
			$eligibleAttackers = []; // no option to attack if we can land
		} else {
			$eligibleAttackers = $player->getSector()->getFightingTradersAgainstPlanet($player, $planet, allEligible: true);
		}
		$template->assign('VisiblePlayers', $eligibleAttackers);
		$template->assign('SectorPlayersLabel', 'Attackers');
	}

}