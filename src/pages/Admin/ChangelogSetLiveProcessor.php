<?php declare(strict_types=1);

namespace Smr\Pages\Admin;

use Smr\Database;
use Smr\Epoch;
use Smr\Page\AccountPageProcessor;
use SmrAccount;

class ChangelogSetLiveProcessor extends AccountPageProcessor {

	public function __construct(
		private readonly int $versionID
	) {}

	public function build(SmrAccount $account): never {
		$db = Database::getInstance();
		$db->write('UPDATE version
					SET went_live = ' . $db->escapeNumber(Epoch::time()) . '
					WHERE version_id = ' . $db->escapeNumber($this->versionID));

		// Initialize the next version (since the version set live is not always the
		// last one, we INSERT IGNORE to skip this step in this case).
		$dbResult = $db->read('SELECT * FROM version WHERE version_id = ' . $db->escapeNumber($this->versionID));
		$dbRecord = $dbResult->record();
		$versionID = $dbRecord->getInt('version_id') + 1;
		$major = $dbRecord->getInt('major_version');
		$minor = $dbRecord->getInt('minor_version');
		$patch = $dbRecord->getInt('patch_level') + 1;
		$db->write('INSERT IGNORE INTO version (version_id, major_version, minor_version, patch_level, went_live) VALUES
					(' . $db->escapeNumber($versionID) . ',' . $db->escapeNumber($major) . ',' . $db->escapeNumber($minor) . ',' . $db->escapeNumber($patch) . ',0);');

		(new Changelog())->go();
	}

}