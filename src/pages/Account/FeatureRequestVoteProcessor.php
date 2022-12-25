<?php declare(strict_types=1);

namespace Smr\Pages\Account;

use Smr\Database;
use Smr\Epoch;
use Smr\Page\AccountPageProcessor;
use Smr\Request;
use SmrAccount;

class FeatureRequestVoteProcessor extends AccountPageProcessor {

	public function __construct(
		private readonly FeatureRequest|FeatureRequestComments $previousPage
	) {}

	public function build(SmrAccount $account): never {
		$db = Database::getInstance();

		$action = Request::get('action');
		if ($action == 'Vote') {
			if ($account->getAccountID() == ACCOUNT_ID_NHL) {
				create_error('This account is not allowed to cast a vote!');
			}
			if (Request::has('vote')) {
				$query = 'REPLACE INTO account_votes_for_feature VALUES ';
				foreach (Request::getArray('vote') as $requestID => $vote) {
					$query .= '(' . $db->escapeNumber($account->getAccountID()) . ', ' . $db->escapeNumber($requestID) . ',' . $db->escapeString($vote) . '),';
				}
				$db->write(substr($query, 0, -1));
			}
			if (Request::has('favourite')) {
				$db->replace('account_votes_for_feature', [
					'account_id' => $db->escapeNumber($account->getAccountID()),
					'feature_request_id' => $db->escapeNumber(Request::getInt('favourite')),
					'vote_type' => $db->escapeString('FAVOURITE'),
				]);
			}

		} elseif ($action == 'Set Status') {
			if (!$account->hasPermission(PERMISSION_MODERATE_FEATURE_REQUEST)) {
				create_error('You do not have permission to do that');
			}
			if (!Request::has('status')) {
				create_error('You have to select a status to set');
			}
			$status = Request::get('status');
			if (!Request::has('set_status_ids')) {
				create_error('You have to select a feature');
			}
			$setStatusIDs = Request::getIntArray('set_status_ids');

			$db->write('UPDATE feature_request fr SET status = ' . $db->escapeString($status) . '
					, fav = (
						SELECT COUNT(feature_request_id)
						FROM account_votes_for_feature
						WHERE feature_request_id=fr.feature_request_id
							AND vote_type=' . $db->escapeString('FAVOURITE') . '
					)
					, yes = (
						SELECT COUNT(feature_request_id)
						FROM account_votes_for_feature
						WHERE feature_request_id=fr.feature_request_id
							AND vote_type IN (' . $db->escapeString('YES') . ',' . $db->escapeString('FAVOURITE') . ')
					)
					, no = (
						SELECT COUNT(feature_request_id)
						FROM account_votes_for_feature
						WHERE feature_request_id=fr.feature_request_id
							AND vote_type=' . $db->escapeString('NO') . '
					)
					WHERE feature_request_id IN (' . $db->escapeArray($setStatusIDs) . ')');
			foreach ($setStatusIDs as $featureID) {
				$db->insert('feature_request_comments', [
					'feature_request_id' => $db->escapeNumber($featureID),
					'poster_id' => $db->escapeNumber($account->getAccountID()),
					'posting_time' => $db->escapeNumber(Epoch::time()),
					'anonymous' => $db->escapeBoolean(false),
					'text' => $db->escapeString($status),
				]);
			}
		}

		$this->previousPage->go();
	}

}