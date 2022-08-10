<?php declare(strict_types=1);

namespace Smr\Pages\Player\GalacticPost;

use AbstractSmrPlayer;
use Smr\Database;
use Smr\Page\PlayerPage;
use Smr\Template;

class ArticleDeleteConfirm extends PlayerPage {

	public string $file = 'galactic_post_delete_confirm.php';

	public function __construct(
		private readonly int $articleID
	) {}

	public function build(AbstractSmrPlayer $player, Template $template): void {
		$db = Database::getInstance();

		$template->assign('PageTopic', 'Delete Article - Confirm');
		$dbResult = $db->read('SELECT title FROM galactic_post_article WHERE article_id = ' . $db->escapeNumber($this->articleID) . ' AND game_id = ' . $db->escapeNumber($player->getGameID()));
		$template->assign('ArticleTitle', $dbResult->record()->getString('title'));

		$container = new ArticleDeleteProcessor($this->articleID);
		$template->assign('SubmitHREF', $container->href());
	}

}
