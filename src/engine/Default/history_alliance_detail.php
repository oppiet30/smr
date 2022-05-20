<?php declare(strict_types=1);

$template = Smr\Template::getInstance();
$session = Smr\Session::getInstance();
$var = $session->getCurrentVar();
$account = $session->getAccount();

Menu::historyGames($var['selected_index']);

//offer a back button
$container = Page::copy($var);
$container['body'] = 'history_games.php';
$template->assign('BackHREF', $container->href());

$game_id = $var['view_game_id'];
$id = $var['alliance_id'];

$db = Smr\Database::getInstance();
$db->switchDatabases($var['HistoryDatabase']);
$dbResult = $db->read('SELECT alliance_name, leader_id FROM alliance WHERE alliance_id = ' . $db->escapeNumber($id) . ' AND game_id = ' . $db->escapeNumber($game_id));
$dbRecord = $dbResult->record();
$leaderID = $dbRecord->getInt('leader_id');
$template->assign('PageTopic', 'Alliance Roster - ' . htmlentities($dbRecord->getString('alliance_name')));

//get alliance members
$oldAccountID = $account->getOldAccountID($var['HistoryDatabase']);
$dbResult = $db->read('SELECT * FROM player WHERE alliance_id = ' . $db->escapeNumber($id) . ' AND game_id = ' . $db->escapeNumber($game_id) . ' ORDER BY experience DESC');
$players = [];
foreach ($dbResult->records() as $dbRecord) {
	$memberAccountID = $dbRecord->getInt('account_id');
	$players[] = [
		'leader' => $memberAccountID == $leaderID ? '*' : '',
		'bold' => $memberAccountID == $oldAccountID ? 'class="bold"' : '',
		'player_name' => $dbRecord->getString('player_name'),
		'experience' => number_format($dbRecord->getInt('experience')),
		'alignment' => number_format($dbRecord->getInt('alignment')),
		'race' => Smr\Race::getName($dbRecord->getInt('race')),
		'kills' => number_format($dbRecord->getInt('kills')),
		'deaths' => number_format($dbRecord->getInt('deaths')),
		'bounty' => number_format($dbRecord->getInt('bounty')),
	];
}
$template->assign('Players', $players);

$db->switchDatabaseToLive(); // restore database
