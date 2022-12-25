<?php declare(strict_types=1);

namespace Smr\Pages\Player\GalacticPost;

use AbstractSmrPlayer;
use Menu;
use Smr\Database;
use Smr\Page\PlayerPage;
use Smr\Template;

class PaperEdit extends PlayerPage {

	public string $file = 'galactic_post_paper_edit.php';

	public function __construct(
		private readonly int $paperID
	) {}

	public function build(AbstractSmrPlayer $player, Template $template): void {
		$template->assign('PageTopic', 'Edit Paper');
		Menu::galacticPost();

		$db = Database::getInstance();
		$dbResult = $db->read('SELECT title FROM galactic_post_paper WHERE paper_id = ' . $db->escapeNumber($this->paperID) . ' AND game_id = ' . $db->escapeNumber($player->getGameID()));
		$template->assign('PaperTitle', bbifyMessage($dbResult->record()->getString('title')));

		$dbResult = $db->read('SELECT * FROM galactic_post_paper_content JOIN galactic_post_article USING (game_id, article_id) WHERE paper_id = ' . $db->escapeNumber($this->paperID) . ' AND game_id = ' . $db->escapeNumber($player->getGameID()));

		$articles = [];
		foreach ($dbResult->records() as $dbRecord) {
			$container = new PaperEditProcessor($this->paperID, $dbRecord->getInt('article_id'));
			$articles[] = [
				'title' => bbifyMessage($dbRecord->getString('title')),
				'text' => bbifyMessage($dbRecord->getString('text')),
				'editHREF' => $container->href(),
			];
		}
		$template->assign('Articles', $articles);
	}

}