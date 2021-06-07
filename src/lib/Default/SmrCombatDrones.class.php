<?php declare(strict_types=1);

class SmrCombatDrones extends AbstractSmrCombatWeapon {
	const MAX_CDS_RAND = 54;
	protected int $numberOfCDs;

	public function __construct(int $numberOfCDs, bool $portPlanetDrones = false) {
		$this->numberOfCDs = $numberOfCDs;
		$this->name = 'Combat Drones';
		if ($portPlanetDrones === false) {
			$this->shieldDamage = 2;
			$this->armourDamage = 2;
		} else {
			$this->shieldDamage = 1;
			$this->armourDamage = 1;
		}
		$this->accuracy = 3;
		$this->damageRollover = true;
	}

	public function getNumberOfCDs() : int {
		return $this->numberOfCDs;
	}

	public function getModifiedAccuracy() : float {
		$modifiedAccuracy = $this->getBaseAccuracy();
		return $modifiedAccuracy;
	}

	protected function getModifiedAccuracyAgainstForcesUsingRandom(AbstractSmrPlayer $weaponPlayer, SmrForce $forces, int $random) : float {
		$modifiedAccuracy = $this->getModifiedAccuracy();
		$modifiedAccuracy += $random;

		return max(0, min(100, $modifiedAccuracy));
	}

	public function getModifiedAccuracyAgainstForces(AbstractSmrPlayer $weaponPlayer, SmrForce $forces) : float {
		return $this->getModifiedAccuracyAgainstForcesUsingRandom($weaponPlayer, $forces, rand(3, self::MAX_CDS_RAND));
	}

	protected function getModifiedAccuracyAgainstPortUsingRandom(AbstractSmrPlayer $weaponPlayer, SmrPort $port, int $random) : float {
		$modifiedAccuracy = $this->getModifiedAccuracy();
		$modifiedAccuracy += $random;

		return max(0, min(100, $modifiedAccuracy));
	}

	public function getModifiedAccuracyAgainstPort(AbstractSmrPlayer $weaponPlayer, SmrPort $port) : float {
		return $this->getModifiedAccuracyAgainstPortUsingRandom($weaponPlayer, $port, rand(3, self::MAX_CDS_RAND));
	}

	protected function getModifiedAccuracyAgainstPlanetUsingRandom(AbstractSmrPlayer $weaponPlayer, SmrPlanet $planet, int $random) : float {
		$modifiedAccuracy = $this->getModifiedAccuracy();
		$modifiedAccuracy += $random;

		return max(0, min(100, $modifiedAccuracy));
	}

	public function getModifiedAccuracyAgainstPlanet(AbstractSmrPlayer $weaponPlayer, SmrPlanet $planet) : float {
		return $this->getModifiedAccuracyAgainstPlanetUsingRandom($weaponPlayer, $planet, rand(3, self::MAX_CDS_RAND));
	}

	public function getModifiedAccuracyAgainstPlayer(AbstractSmrPlayer $weaponPlayer, AbstractSmrPlayer $targetPlayer) : float {
		return $this->getModifiedAccuracyAgainstPlayerUsingRandom($weaponPlayer, $targetPlayer, rand(3, self::MAX_CDS_RAND));
	}

	protected function getModifiedAccuracyAgainstPlayerUsingRandom(AbstractSmrPlayer $weaponPlayer, AbstractSmrPlayer $targetPlayer, int $random) : float {
		$modifiedAccuracy = $this->getModifiedAccuracy();
		$levelRand = rand(IFloor($weaponPlayer->getLevelID() / 2), $weaponPlayer->getLevelID());
		$modifiedAccuracy += ($random + $levelRand - ($targetPlayer->getLevelID() - $weaponPlayer->getLevelID()) / 3) / 1.5;

		$weaponShip = $weaponPlayer->getShip();
		$targetShip = $targetPlayer->getShip();
		$mrDiff = $targetShip->getMR() - $weaponShip->getMR();
		if ($mrDiff > 0) {
			$modifiedAccuracy -= $this->getBaseAccuracy() * ($mrDiff / MR_FACTOR) / 100;
		}

		return max(0, min(100, $modifiedAccuracy));
	}

	public function getModifiedForceAccuracyAgainstPlayer(SmrForce $forces, AbstractSmrPlayer $targetPlayer) : float {
		return $this->getModifiedForceAccuracyAgainstPlayerUsingRandom($forces, $targetPlayer, rand(3, self::MAX_CDS_RAND));
	}

	protected function getModifiedForceAccuracyAgainstPlayerUsingRandom(SmrForce $forces, AbstractSmrPlayer $targetPlayer, int $random) : float {
		$modifiedAccuracy = $this->getModifiedAccuracy();
		$modifiedAccuracy += $random;

		return max(0, min(100, $modifiedAccuracy));
	}

	protected function getModifiedPortAccuracyAgainstPlayer(SmrPort $port, AbstractSmrPlayer $targetPlayer) : float {
		return 100;
	}

	protected function getModifiedPlanetAccuracyAgainstPlayer(SmrPlanet $planet, AbstractSmrPlayer $targetPlayer) : float {
		return 100;
	}

	public function getModifiedDamageAgainstForces(AbstractSmrPlayer $weaponPlayer, SmrForce $forces) : array {
		if (!$this->canShootForces()) {
			// If we can't shoot forces then just return a damageless array and don't waste resources calculated any damage mods.
			return array('MaxDamage' => 0, 'Shield' => 0, 'Armour' => 0, 'Rollover' => $this->isDamageRollover());
		}
		$damage = $this->getDamage();
		$damage['Launched'] = ICeil($this->getNumberOfCDs() * $this->getModifiedAccuracyAgainstForces($weaponPlayer, $forces) / 100);
		$damage['Kamikaze'] = 0;
		if ($weaponPlayer->isCombatDronesKamikazeOnMines()) { // If kamikaze then damage is same as MINE_ARMOUR
			$damage['Kamikaze'] = min($damage['Launched'], $forces->getMines());
			$damage['Launched'] -= $damage['Kamikaze'];
		}
		$damage['MaxDamage'] = ICeil($damage['Launched'] * $damage['MaxDamage']);
		$damage['Shield'] = ICeil($damage['Launched'] * $damage['Shield']);
		$damage['Armour'] = ICeil($damage['Launched'] * $damage['Armour']);

		$damage['Launched'] += $damage['Kamikaze'];
		$damage['MaxDamage'] += $damage['Kamikaze'] * MINE_ARMOUR;
		$damage['Shield'] += $damage['Kamikaze'] * MINE_ARMOUR;
		$damage['Armour'] += $damage['Kamikaze'] * MINE_ARMOUR;

		return $damage;
	}

	public function getModifiedDamageAgainstPort(AbstractSmrPlayer $weaponPlayer, SmrPort $port) : array {
		if (!$this->canShootPorts()) {
			// If we can't shoot forces then just return a damageless array and don't waste resources calculated any damage mods.
			return array('MaxDamage' => 0, 'Shield' => 0, 'Armour' => 0, 'Rollover' => $this->isDamageRollover());
		}
		$damage = $this->getDamage();
		$damage['Launched'] = ICeil($this->getNumberOfCDs() * $this->getModifiedAccuracyAgainstPort($weaponPlayer, $port) / 100);
		$damage['MaxDamage'] = ICeil($damage['Launched'] * $damage['MaxDamage']);
		$damage['Shield'] = ICeil($damage['Launched'] * $damage['Shield']);
		$damage['Armour'] = ICeil($damage['Launched'] * $damage['Armour']);

		return $damage;
	}

	public function getModifiedDamageAgainstPlanet(AbstractSmrPlayer $weaponPlayer, SmrPlanet $planet) : array {
		if (!$this->canShootPlanets()) {
			// If we can't shoot forces then just return a damageless array and don't waste resources calculated any damage mods.
			return array('MaxDamage' => 0, 'Shield' => 0, 'Armour' => 0, 'Rollover' => $this->isDamageRollover());
		}
		$damage = $this->getDamage();
		$damage['Launched'] = ICeil($this->getNumberOfCDs() * $this->getModifiedAccuracyAgainstPlanet($weaponPlayer, $planet) / 100);
		$planetMod = self::PLANET_DAMAGE_MOD;
		$damage['MaxDamage'] = ICeil($damage['Launched'] * $damage['MaxDamage'] * $planetMod);
		$damage['Shield'] = ICeil($damage['Launched'] * $damage['Shield'] * $planetMod);
		$damage['Armour'] = ICeil($damage['Launched'] * $damage['Armour'] * $planetMod);

		return $damage;
	}

	public function getModifiedDamageAgainstPlayer(AbstractSmrPlayer $weaponPlayer, AbstractSmrPlayer $targetPlayer) : array {
		if (!$this->canShootTraders()) { // If we can't shoot traders then just return a damageless array and don't waste resources calculated any damage mods.
			$return = array('MaxDamage' => 0, 'Shield' => 0, 'Armour' => 0, 'Rollover' => $this->isDamageRollover());
			return $return;
		}
		$damage = $this->getDamage();
		if ($targetPlayer->getShip()->hasDCS()) {
			$damage['MaxDamage'] *= DCS_PLAYER_DAMAGE_DECIMAL_PERCENT;
			$damage['Shield'] *= DCS_PLAYER_DAMAGE_DECIMAL_PERCENT;
			$damage['Armour'] *= DCS_PLAYER_DAMAGE_DECIMAL_PERCENT;
		}
		$damage['Launched'] = ICeil($this->getNumberOfCDs() * $this->getModifiedAccuracyAgainstPlayer($weaponPlayer, $targetPlayer) / 100);
		$damage['MaxDamage'] = ICeil($damage['Launched'] * $damage['MaxDamage']);
		$damage['Shield'] = ICeil($damage['Launched'] * $damage['Shield']);
		$damage['Armour'] = ICeil($damage['Launched'] * $damage['Armour']);
		return $damage;
	}

	public function getModifiedForceDamageAgainstPlayer(SmrForce $forces, AbstractSmrPlayer $targetPlayer) : array {
		if (!$this->canShootTraders()) { // If we can't shoot traders then just return a damageless array and don't waste resources calculated any damage mods.
			$return = array('MaxDamage' => 0, 'Shield' => 0, 'Armour' => 0, 'Rollover' => $this->isDamageRollover());
			return $return;
		}
		$damage = $this->getDamage();

		if ($targetPlayer->getShip()->hasDCS()) {
			$damage['MaxDamage'] *= DCS_FORCE_DAMAGE_DECIMAL_PERCENT;
			$damage['Shield'] *= DCS_FORCE_DAMAGE_DECIMAL_PERCENT;
			$damage['Armour'] *= DCS_FORCE_DAMAGE_DECIMAL_PERCENT;
		}

		$damage['Launched'] = ICeil($this->getNumberOfCDs() * $this->getModifiedForceAccuracyAgainstPlayer($forces, $targetPlayer) / 100);
		$damage['MaxDamage'] = ICeil($damage['Launched'] * $damage['MaxDamage']);
		$damage['Shield'] = ICeil($damage['Launched'] * $damage['Shield']);
		$damage['Armour'] = ICeil($damage['Launched'] * $damage['Armour']);
		return $damage;
	}

	public function getModifiedPortDamageAgainstPlayer(SmrPort $port, AbstractSmrPlayer $targetPlayer) : array {
		if (!$this->canShootTraders()) { // If we can't shoot traders then just return a damageless array and don't waste resources calculated any damage mods.
			$return = array('MaxDamage' => 0, 'Shield' => 0, 'Armour' => 0, 'Rollover' => $this->isDamageRollover());
			return $return;
		}
		$damage = $this->getDamage();

		if ($targetPlayer->getShip()->hasDCS()) {
			$damage['MaxDamage'] *= DCS_PORT_DAMAGE_DECIMAL_PERCENT;
			$damage['Shield'] *= DCS_PORT_DAMAGE_DECIMAL_PERCENT;
			$damage['Armour'] *= DCS_PORT_DAMAGE_DECIMAL_PERCENT;
		}
		$damage['Launched'] = ICeil($this->getNumberOfCDs() * $this->getModifiedPortAccuracyAgainstPlayer($port, $targetPlayer) / 100);
		$damage['MaxDamage'] = ICeil($damage['Launched'] * $damage['MaxDamage']);
		$damage['Shield'] = ICeil($damage['Launched'] * $damage['Shield']);
		$damage['Armour'] = ICeil($damage['Launched'] * $damage['Armour']);
		return $damage;
	}

	public function getModifiedPlanetDamageAgainstPlayer(SmrPlanet $planet, AbstractSmrPlayer $targetPlayer) : array {
		if (!$this->canShootTraders()) { // If we can't shoot traders then just return a damageless array and don't waste resources calculated any damage mods.
			$return = array('MaxDamage' => 0, 'Shield' => 0, 'Armour' => 0, 'Rollover' => $this->isDamageRollover());
			return $return;
		}
		$damage = $this->getDamage();

		if ($targetPlayer->getShip()->hasDCS()) {
			$damage['MaxDamage'] *= DCS_PLANET_DAMAGE_DECIMAL_PERCENT;
			$damage['Shield'] *= DCS_PLANET_DAMAGE_DECIMAL_PERCENT;
			$damage['Armour'] *= DCS_PLANET_DAMAGE_DECIMAL_PERCENT;
		}
		$damage['Launched'] = ICeil($this->getNumberOfCDs() * $this->getModifiedPlanetAccuracyAgainstPlayer($planet, $targetPlayer) / 100);
		$damage['MaxDamage'] = ICeil($damage['Launched'] * $damage['MaxDamage']);
		$damage['Shield'] = ICeil($damage['Launched'] * $damage['Shield']);
		$damage['Armour'] = ICeil($damage['Launched'] * $damage['Armour']);
		return $damage;
	}

	public function shootForces(AbstractSmrPlayer $weaponPlayer, SmrForce $forces) : array {
		$return = array('Weapon' => $this, 'TargetForces' => $forces, 'Hit' => true);
		$return = $this->doPlayerDamageToForce($return, $weaponPlayer, $forces);
		if ($return['WeaponDamage']['Kamikaze'] > 0) {
			$weaponPlayer->getShip()->decreaseCDs($return['WeaponDamage']['Kamikaze']);
		}
		return $return;
	}

	public function shootPort(AbstractSmrPlayer $weaponPlayer, SmrPort $port) : array {
		$return = array('Weapon' => $this, 'TargetPort' => $port, 'Hit' => true);
		return $this->doPlayerDamageToPort($return, $weaponPlayer, $port);
	}

	public function shootPlanet(AbstractSmrPlayer $weaponPlayer, SmrPlanet $planet, bool $delayed) : array {
		$return = array('Weapon' => $this, 'TargetPlanet' => $planet, 'Hit' => true);
		return $this->doPlayerDamageToPlanet($return, $weaponPlayer, $planet, $delayed);
	}

	public function shootPlayer(AbstractSmrPlayer $weaponPlayer, AbstractSmrPlayer $targetPlayer) : array {
		$return = array('Weapon' => $this, 'TargetPlayer' => $targetPlayer, 'Hit' => true);
		return $this->doPlayerDamageToPlayer($return, $weaponPlayer, $targetPlayer);
	}

	public function shootPlayerAsForce(SmrForce $forces, AbstractSmrPlayer $targetPlayer) : array {
		$return = array('Weapon' => $this, 'TargetPlayer' => $targetPlayer, 'Hit' => true);
		return $this->doForceDamageToPlayer($return, $forces, $targetPlayer);
	}

	public function shootPlayerAsPort(SmrPort $forces, AbstractSmrPlayer $targetPlayer) : array {
		$return = array('Weapon' => $this, 'TargetPlayer' => $targetPlayer, 'Hit' => true);
		return $this->doPortDamageToPlayer($return, $forces, $targetPlayer);
	}

	public function shootPlayerAsPlanet(SmrPlanet $forces, AbstractSmrPlayer $targetPlayer) : array {
		$return = array('Weapon' => $this, 'TargetPlayer' => $targetPlayer, 'Hit' => true);
		return $this->doPlanetDamageToPlayer($return, $forces, $targetPlayer);
	}
}
