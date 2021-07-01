<?php declare(strict_types=1);

$template = Smr\Template::getInstance();
$session = Smr\Session::getInstance();
$player = $session->getPlayer();

$template->assign('PageTopic', 'Place Bounty');

Menu::headquarters();

$container = Page::create('bounty_place_processing.php');
$container->addVar('LocationID');
$template->assign('SubmitHREF', $container->href());

$bountyPlayers = [];
$db = Smr\Database::getInstance();
$dbResult = $db->read('SELECT player_id, player_name FROM player JOIN account USING(account_id) WHERE game_id = ' . $db->escapeNumber($player->getGameID()) . ' AND account_id != ' . $db->escapeNumber($player->getAccountID()) . ' ORDER BY player_name');
foreach ($dbResult->records() as $dbRecord) {
	$bountyPlayers[$dbRecord->getInt('player_id')] = htmlentities($dbRecord->getString('player_name'));
}
$template->assign('BountyPlayers', $bountyPlayers);
