<?php declare(strict_types=1);

namespace Smr\Pages\Admin;

use Smr\Database;
use Smr\Page\AccountPageProcessor;
use Smr\Request;
use SmrAccount;

class AdminPermissionManageProcessor extends AccountPageProcessor {

	public function __construct(
		private readonly int $adminAccountID
	) {}

	public function build(SmrAccount $account): never {
		if (Request::get('action') == 'Change') {
			// Check to see if admin previously was displaying Admin tag
			$hadAdminTag = SmrAccount::getAccount($this->adminAccountID)->hasPermission(PERMISSION_DISPLAY_ADMIN_TAG);

			// delete everything first
			$db = Database::getInstance();
			$db->write('DELETE
						FROM account_has_permission
						WHERE account_id = ' . $db->escapeNumber($this->adminAccountID));

			// Grant permissions
			$permissions = Request::getIntArray('permission_ids', []);
			foreach ($permissions as $permission_id) {
				$db->replace('account_has_permission', [
					'account_id' => $db->escapeNumber($this->adminAccountID),
					'permission_id' => $db->escapeNumber($permission_id),
				]);
			}

			// Process adding/removing the Admin tag
			if (in_array(PERMISSION_DISPLAY_ADMIN_TAG, $permissions)) {
				// This might overwrite an existing unrelated tag.
				$tag = '<span class="blue">Admin</span>';
				$db->replace('cpl_tag', [
					'account_id' => $db->escapeNumber($this->adminAccountID),
					'tag' => $db->escapeString($tag),
					'custom' => 0,
				]);
			} elseif ($hadAdminTag) {
				// Only delete the tag if they previously had an admin tag;
				// otherwise we might accidentally delete an unrelated tag.
				$db->write('DELETE FROM cpl_tag WHERE custom=0 AND account_id=' . $db->escapeNumber($this->adminAccountID));
			}
		}

		(new AdminPermissionManage())->go();
	}

}