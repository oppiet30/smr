<?php declare(strict_types=1);

namespace Smr;

/**
 * Collection of functions to help with Lotto processing.
 */
class Lotto {

	public const TICKET_COST = 1000000; // cost of 1 ticket
	public const WIN_FRAC = 0.9; // fraction of ticket sales returned to winner

	public static function checkForLottoWinner(int $gameID): void {

		// No more lotto winners after the game has ended
		if (Game::getGame($gameID)->hasEnded()) {
			return;
		}

		// we check for a lotto winner...
		$db = Database::getInstance();
		$db->lockTable('player_has_ticket');
		$lottoInfo = self::getLottoInfo($gameID);

		if ($lottoInfo['TimeRemaining'] > 0) {
			// Drawing is not closed yet
			$db->unlock();
			return;
		}

		//we need to pick a winner
		$dbResult = $db->read('SELECT * FROM player_has_ticket WHERE game_id = ' . $db->escapeNumber($gameID) . ' AND time > 0 ORDER BY rand() LIMIT 1');
		$winner_id = $dbResult->record()->getInt('account_id');

		// Any unclaimed prizes get merged into this prize
		$dbResult = $db->read('SELECT IFNULL(SUM(prize), 0) AS total_prize FROM player_has_ticket WHERE time = 0 AND game_id = ' . $db->escapeNumber($gameID));
		$lottoInfo['Prize'] += $dbResult->record()->getInt('total_prize');

		// Delete all tickets and re-insert the winning ticket
		$db->write('DELETE FROM player_has_ticket WHERE game_id = ' . $db->escapeNumber($gameID));
		$db->insert('player_has_ticket', [
			'game_id' => $db->escapeNumber($gameID),
			'account_id' => $db->escapeNumber($winner_id),
			'time' => 0,
			'prize' => $db->escapeNumber($lottoInfo['Prize']),
		]);
		$db->unlock();

		// create news msg
		$winner = Player::getPlayer($winner_id, $gameID);
		$winner->increaseHOF($lottoInfo['Prize'], ['Bar', 'Lotto', 'Money', 'Winnings'], HOF_PUBLIC);
		$winner->increaseHOF(1, ['Bar', 'Lotto', 'Results', 'Wins'], HOF_PUBLIC);
		$news_message = $winner->getBBLink() . ' has won the lotto! The jackpot was ' . number_format($lottoInfo['Prize']) . '. ' . $winner->getBBLink() . ' can report to any bar to claim their prize before the next drawing!';
		// insert the news entry
		$db->write('DELETE FROM news WHERE type = \'lotto\' AND game_id = ' . $db->escapeNumber($gameID));
		$db->insert('news', [
			'game_id' => $db->escapeNumber($gameID),
			'time' => $db->escapeNumber(Epoch::time()),
			'news_message' => $db->escapeString($news_message),
			'type' => $db->escapeString('lotto'),
			'dead_id' => $db->escapeNumber($winner->getAccountID()),
			'dead_alliance' => $db->escapeNumber($winner->getAllianceID()),
		]);
	}

	/**
	 * @return array<string, int>
	 */
	public static function getLottoInfo(int $gameID): array {
		$amount = self::TICKET_COST; // pot starts with 1 ticket value
		$firstBuy = Epoch::time();

		$db = Database::getInstance();
		$dbResult = $db->read('SELECT count(*) as num, min(time) as time FROM player_has_ticket
				WHERE game_id = ' . $db->escapeNumber($gameID) . ' AND time > 0');
		$dbRecord = $dbResult->record();
		if ($dbRecord->getInt('num') > 0) {
			$amount += $dbRecord->getInt('num') * IFloor(self::TICKET_COST * self::WIN_FRAC);
			$firstBuy = $dbRecord->getInt('time');
		}
		//find the time remaining in this jackpot. (which is 2 days from the first purchased ticket)
		return ['Prize' => $amount, 'TimeRemaining' => $firstBuy + TIME_LOTTO - Epoch::time()];
	}

}
