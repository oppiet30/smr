<?php declare(strict_types=1);

$db = Smr\Database::getInstance();
$session = Smr\Session::getInstance();
$var = $session->getCurrentVar();
$player = $session->getPlayer();

$message = '<div class="center">';

if ($player->getCredits() < 10) {
	create_error('Come back when you get some money!');
}
$player->decreaseCredits(10);

if (isset($var['action']) && $var['action'] != 'drink') {
	$drinkName = 'water';
	$message .= 'You ask the bartender for some water and you quickly down it.<br />You don\'t feel quite so intoxicated anymore.<br />';
	$db->write('DELETE FROM player_has_drinks WHERE ' . $player->getSQL() . ' LIMIT 1');
	$player->increaseHOF(1, array('Bar', 'Drinks', 'Water'), HOF_PUBLIC);
} else {
	$random = rand(1, 20);
	//only get Azool or Spock drink if they are very lucky
	if ($random != 1) {
		$dbResult = $db->read('SELECT drink_id, drink_name FROM bar_drink WHERE drink_id != 1 && drink_id != 11 ORDER BY rand() LIMIT 1');
	} else {
		$dbResult = $db->read('SELECT drink_id, drink_name FROM bar_drink ORDER BY rand() LIMIT 1');
	}
	$dbRecord = $dbResult->record(); // the bar_drink table should not be empty

	$drinkName = $dbRecord->getField('drink_name');
	$drink_id = $dbRecord->getInt('drink_id');

	$dbResult = $db->read('SELECT drink_id FROM player_has_drinks WHERE game_id = ' . $db->escapeNumber($player->getGameID()) . ' ORDER by drink_id DESC LIMIT 1');
	if ($dbResult->hasRecord()) {
		$curr_drink_id = $dbResult->record()->getInt('drink_id') + 1;
	} else {
		$curr_drink_id = 1;
	}

	if ($drink_id != 11 && $drink_id != 1) {
		$message .= ('You have bought a ' . $drinkName . ' for $10');
		$db->write('INSERT INTO player_has_drinks (account_id, game_id, drink_id, time) VALUES (' . $db->escapeNumber($player->getAccountID()) . ', ' . $db->escapeNumber($player->getGameID()) . ', ' . $db->escapeNumber($curr_drink_id) . ', ' . $db->escapeNumber(Smr\Epoch::time()) . ')');
		$player->increaseHOF(1, array('Bar', 'Drinks', 'Alcoholic'), HOF_PUBLIC);
	} else {
		$message .= ('The bartender says, Ive got something special for ya.<br />');
		$message .= ('The bartender turns around for a minute and whips up a ' . $drinkName . '.<br />');

		if ($drink_id == 1) {
			$message .= ('The bartender says that Spock himself gave him the directions to make this drink.<br />');
		}

		$message .= ('You drink the ' . $drinkName . ' and feel like like you have been drinking for hours.<br />');

		if ($drink_id == 11) {
			$message .= ('After drinking the ' . $drinkName . ' you feel like nothing can bring you down and like you are the best trader in the universe.<br />');
		}

		//has the power of 2 drinks
		$db->write('INSERT INTO player_has_drinks (account_id, game_id, drink_id, time) VALUES (' . $db->escapeNumber($player->getAccountID()) . ', ' . $db->escapeNumber($player->getGameID()) . ', ' . $db->escapeNumber($curr_drink_id) . ', ' . $db->escapeNumber(Smr\Epoch::time()) . ')');
		$curr_drink_id++;
		$db->write('INSERT INTO player_has_drinks (account_id, game_id, drink_id, time) VALUES (' . $db->escapeNumber($player->getAccountID()) . ', ' . $db->escapeNumber($player->getGameID()) . ', ' . $db->escapeNumber($curr_drink_id) . ', ' . $db->escapeNumber(Smr\Epoch::time()) . ')');
		$player->increaseHOF(1, array('Bar', 'Drinks', 'Special'), HOF_PUBLIC);
	}

	$dbResult = $db->read('SELECT count(*) FROM player_has_drinks WHERE ' . $player->getSQL());
	$num_drinks = $dbResult->record()->getInt('count(*)');
	//display woozy message
	$message .= '<br />You feel a little W' . str_repeat('oO', $num_drinks) . 'zy<br />';
}

$player->actionTaken('BuyDrink', array(
	'SectorID' => $player->getSectorID(),
	'Drink' => $drinkName
));

//see if the player blacksout or not
if (isset($num_drinks) && $num_drinks > 15) {
	$percent = rand(1, 25);
	$lostCredits = IRound($player->getCredits() * $percent / 100);

	$message .= '<span class="red">You decide you need to go to the restroom.  So you stand up and try to start walking but immediately collapse!<br />About 10 minutes later you wake up and find yourself missing ' . number_format($lostCredits) . ' credits</span><br />';

	$player->decreaseCredits($lostCredits);
	$player->increaseHOF(1, array('Bar', 'Robbed', 'Number Of Times'), HOF_PUBLIC);
	$player->increaseHOF($lostCredits, array('Bar', 'Robbed', 'Money Lost'), HOF_PUBLIC);

	$db->write('DELETE FROM player_has_drinks WHERE ' . $player->getSQL());

}
$player->increaseHOF(1, array('Bar', 'Drinks', 'Total'), HOF_PUBLIC);
$message .= '</div>';

$container = Page::create('skeleton.php', 'bar_main.php');
$container->addVar('LocationID');
$container['message'] = $message;
$container->go();
