<?php declare(strict_types=1);

namespace Smr\Pages\Admin\UniGen;

use AbstractSmrPlayer;
use Smr\Page\PlayerPageProcessor;
use Smr\SectorsFile;

class SectorsFileDownloadProcessor extends PlayerPageProcessor {

	public function __construct(
		private readonly int $gameID
	) {}

	public function build(AbstractSmrPlayer $player): never {
		SectorsFile::create($this->gameID, player: null, adminCreate: true);
	}

}