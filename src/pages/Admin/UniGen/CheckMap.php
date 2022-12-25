<?php declare(strict_types=1);

namespace Smr\Pages\Admin\UniGen;

use Globals;
use Plotter;
use Smr\Page\AccountPage;
use Smr\Page\ReusableTrait;
use Smr\Race;
use Smr\Routes\RouteGenerator;
use Smr\Template;
use SmrAccount;
use SmrGame;
use SmrLocation;

class CheckMap extends AccountPage {

	use ReusableTrait;

	public string $file = 'admin/unigen/check_map.php';

	public function __construct(
		private readonly int $gameID,
		private readonly int $galaxyID
	) {}

	public function build(SmrAccount $account, Template $template): void {
		$game = SmrGame::getGame($this->gameID);
		$template->assign('PageTopic', 'Check Map : ' . $game->getDisplayName());

		$container = new EditGalaxy($this->gameID, $this->galaxyID);
		$template->assign('BackHREF', $container->href());

		$galaxies = $game->getGalaxies();

		// Check if any locations are missing
		$existingLocs = [];
		foreach ($galaxies as $galaxy) {
			foreach ($galaxy->getLocations() as $sectorLocs) {
				foreach (array_keys($sectorLocs) as $locID) {
					$existingLocs[$locID] = true;
				}
			}
		}
		$missingLocs = array_diff(
			array_keys(SmrLocation::getAllLocations($this->gameID)),
			array_keys($existingLocs)
		);
		$missingLocNames = [];
		foreach ($missingLocs as $locID) {
			$missingLocNames[] = SmrLocation::getLocation($this->gameID, $locID)->getName();
		}
		$template->assign('MissingLocNames', $missingLocNames);

		// Calculate the best trade routes for each galaxy
		$tradeGoods = [GOODS_NOTHING => true];
		foreach (array_keys(Globals::getGoods()) as $goodID) {
			$tradeGoods[$goodID] = true;
		}
		$tradeRaces = [];
		foreach (Race::getAllIDs() as $raceID) {
			$tradeRaces[$raceID] = true;
		}

		$maxNumberOfPorts = 2;
		$routesForPort = -1;
		$numberOfRoutes = 1;
		$maxDistance = 999;

		$allGalaxyRoutes = [];
		foreach ($galaxies as $galaxy) {
			$galaxy->getSectors(); // Efficiently construct the sector cache
			$ports = $galaxy->getPorts();
			$distances = Plotter::calculatePortToPortDistances($ports, $tradeRaces, $maxDistance, $galaxy->getStartSector(), $galaxy->getEndSector());
			$allGalaxyRoutes[$galaxy->getDisplayName()] = RouteGenerator::generateMultiPortRoutes($maxNumberOfPorts, $ports, $tradeGoods, $tradeRaces, $distances, $routesForPort, $numberOfRoutes);
		}
		$template->assign('AllGalaxyRoutes', $allGalaxyRoutes);

		$routeTypes = [
			RouteGenerator::EXP_ROUTE => 'Experience',
			RouteGenerator::MONEY_ROUTE => 'Profit',
		];
		$template->assign('RouteTypes', $routeTypes);

		// Largest port sell multipliers per galaxy
		$maxSellMultipliers = [];
		foreach ($galaxies as $galaxy) {
			$max = [];
			foreach ($galaxy->getPorts() as $port) {
				foreach ($port->getSellGoodIDs() as $goodID) {
					$distance = $port->getGoodDistance($goodID);
					// For distance ties, prefer higher good IDs
					if (empty($max) || $distance >= $max['Distance']) {
						$max = [
							'Port' => $port,
							'GoodID' => $goodID,
							'Distance' => $distance,
						];
					}
				}
			}
			if (!empty($max)) {
				$output = $max['Distance'] . 'x ' . Globals::getGoodName($max['GoodID']) . ' at Port #' . $max['Port']->getSectorID() . ' (' . $max['Port']->getRaceName() . ')';
				$maxSellMultipliers[$galaxy->getDisplayName()] = $output;
			}
		}
		$template->assign('MaxSellMultipliers', $maxSellMultipliers);
	}

}