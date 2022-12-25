<?php declare(strict_types=1);

namespace Smr\Pages\Admin;

use Smr\Database;
use Smr\Epoch;
use Smr\Page\AccountPageProcessor;
use Smr\Request;
use SmrAccount;

class AlbumModerateProcessor extends AccountPageProcessor {

	public function __construct(
		private readonly int $albumAccountID,
		private readonly string $task
	) {}

	public function build(SmrAccount $account): never {
		$db = Database::getInstance();

		// get account_id from session
		$account_id = $this->albumAccountID;

		// check for each task
		if ($this->task == 'reset_image') {
			$email_txt = Request::get('email_txt');
			$db->write('UPDATE album SET disabled = \'TRUE\' WHERE account_id = ' . $db->escapeNumber($account_id));

			$db->lockTable('album_has_comments');
			$dbResult = $db->read('SELECT IFNULL(MAX(comment_id)+1, 0) as next_comment_id FROM album_has_comments WHERE album_id = ' . $db->escapeNumber($account_id));
			$comment_id = $dbResult->record()->getInt('next_comment_id');

			$db->insert('album_has_comments', [
				'album_id' => $db->escapeNumber($account_id),
				'comment_id' => $db->escapeNumber($comment_id),
				'time' => $db->escapeNumber(Epoch::time()),
				'post_id' => 0,
				'msg' => $db->escapeString('<span class="green">*** Picture disabled by an admin</span>'),
			]);
			$db->unlock();

			// get his email address and send the mail
			$receiver = SmrAccount::getAccount($account_id);
			if (!empty($receiver->getEmail())) {
				$mail = setupMailer();
				$mail->Subject = 'SMR Photo Album Notification';
				$mail->setFrom('album@smrealms.de', 'SMR Photo Album');
				$mail->msgHTML(nl2br($email_txt));
				$mail->addAddress($receiver->getEmail(), $receiver->getHofName());
				$mail->send();
			}

		} elseif ($this->task == 'reset_location') {
			$db->write('UPDATE album SET location = \'\' WHERE account_id = ' . $db->escapeNumber($account_id));
		} elseif ($this->task == 'reset_email') {
			$db->write('UPDATE album SET email = \'\' WHERE account_id =' . $db->escapeNumber($account_id));
		} elseif ($this->task == 'reset_website') {
			$db->write('UPDATE album SET website = \'\' WHERE account_id = ' . $db->escapeNumber($account_id));
		} elseif ($this->task == 'reset_birthdate') {
			$db->write('UPDATE album SET day = 0, month = 0, year = 0 WHERE account_id = ' . $db->escapeNumber($account_id));
		} elseif ($this->task == 'reset_other') {
			$db->write('UPDATE album SET other = \'\' WHERE account_id = ' . $db->escapeNumber($account_id));
		} elseif ($this->task == 'delete_comment') {
			// we just ignore if nothing was set
			if (Request::has('comment_ids')) {
				$db->write('DELETE
							FROM album_has_comments
							WHERE album_id = ' . $db->escapeNumber($account_id) . ' AND
								  comment_id IN (' . $db->escapeArray(Request::getIntArray('comment_ids')) . ')');
			}
		} else {
			create_error('No action chosen!');
		}

		$container = new AlbumModerate($this->albumAccountID);
		$container->go();
	}

}