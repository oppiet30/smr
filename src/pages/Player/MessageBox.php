<?php declare(strict_types=1);

namespace Smr\Pages\Player;

use AbstractSmrPlayer;
use Menu;
use Smr\Database;
use Smr\Messages;
use Smr\Page\PlayerPage;
use Smr\Page\ReusableTrait;
use Smr\Template;

class MessageBox extends PlayerPage {

	use ReusableTrait;

	public string $file = 'message_box.php';

	public function build(AbstractSmrPlayer $player, Template $template): void {
		$db = Database::getInstance();

		Menu::messages();

		$template->assign('PageTopic', 'View Messages');

		$messageBoxes = [];
		foreach (Messages::getMessageTypeNames() as $message_type_id => $message_type_name) {
			$messageBox = [];
			$messageBox['Name'] = $message_type_name;

			// do we have unread msges in that folder?
			if ($message_type_id == MSG_SENT) {
				$messageBox['HasUnread'] = false;
			} else {
				$dbResult = $db->read('SELECT 1 FROM message
						WHERE account_id = ' . $db->escapeNumber($player->getAccountID()) . '
							AND game_id = ' . $db->escapeNumber($player->getGameID()) . '
							AND message_type_id = ' . $db->escapeNumber($message_type_id) . '
							AND msg_read = ' . $db->escapeBoolean(false) . '
							AND receiver_delete = ' . $db->escapeBoolean(false) . ' LIMIT 1');
				$messageBox['HasUnread'] = $dbResult->hasRecord();
			}

			// get number of msges
			if ($message_type_id == MSG_SENT) {
				$dbResult = $db->read('SELECT count(message_id) as message_count FROM message
						WHERE sender_id = ' . $db->escapeNumber($player->getAccountID()) . '
							AND game_id = ' . $db->escapeNumber($player->getGameID()) . '
							AND message_type_id = ' . $db->escapeNumber(MSG_PLAYER) . '
							AND sender_delete = ' . $db->escapeBoolean(false));
			} else {
				$dbResult = $db->read('SELECT count(message_id) as message_count FROM message
						WHERE account_id = ' . $db->escapeNumber($player->getAccountID()) . '
							AND game_id = ' . $db->escapeNumber($player->getGameID()) . '
							AND message_type_id = ' . $db->escapeNumber($message_type_id) . '
							AND receiver_delete = ' . $db->escapeBoolean(false));
			}
			$messageBox['MessageCount'] = $dbResult->record()->getInt('message_count');

			$container = new MessageView($message_type_id);
			$messageBox['ViewHref'] = $container->href();

			$container = new MessageBoxDeleteProcessor($message_type_id);
			$messageBox['DeleteHref'] = $container->href();
			$messageBoxes[] = $messageBox;
		}

		$template->assign('MessageBoxes', $messageBoxes);
	}

}