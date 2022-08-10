<?php declare(strict_types=1);

namespace Smr\Pages\Player\Planet;

use AbstractSmrPlayer;
use Globals;
use Smr\Page\PlayerPageProcessor;
use SmrPlayer;

class KickProcessor extends PlayerPageProcessor {

	public function __construct(
		private readonly int $kickAccountID
	) {}

	public function build(AbstractSmrPlayer $player): never {
		if (!$player->isLandedOnPlanet()) {
			create_error('You are not on a planet!');
		}
		$planet = $player->getSectorPlanet();

		$planetPlayer = SmrPlayer::getPlayer($this->kickAccountID, $player->getGameID());
		$owner = $planet->getOwner();
		if ($owner->getAllianceID() != $player->getAllianceID()) {
			create_error('You can not kick someone off a planet your alliance does not own!');
		}
		$message = 'You have been kicked from ' . $planet->getDisplayName() . ' in ' . Globals::getSectorBBLink($player->getSectorID());
		$player->sendMessage($planetPlayer->getAccountID(), MSG_PLAYER, $message, false);

		$planetPlayer->setLandedOnPlanet(false);
		$planetPlayer->update();

		(new Main())->go();
	}

}
