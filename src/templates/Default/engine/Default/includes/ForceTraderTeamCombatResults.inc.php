<?php declare(strict_types=1);

/**
 * @var Smr\Template $this
 * @var array<string, mixed> $TraderTeamCombatResults
 */

if (is_array($TraderTeamCombatResults['Traders'])) {
	foreach ($TraderTeamCombatResults['Traders'] as $TraderResults) {
		$ShootingPlayer = $TraderResults['Player'];
		$TotalDamage = $TraderResults['TotalDamage'];
		if ($TraderResults['DeadBeforeShot']) {
			echo $ShootingPlayer->getDisplayName() ?> died before they were able to attack!<br /><?php
		} else {
			if (isset($TraderResults['Weapons']) && is_array($TraderResults['Weapons'])) {
				foreach ($TraderResults['Weapons'] as $WeaponResults) {
					$ShootingWeapon = $WeaponResults['Weapon'];
					$ShotHit = $WeaponResults['Hit'];
					if ($ShotHit) {
						$ActualDamage = $WeaponResults['ActualDamage'];
						$WeaponDamage = $WeaponResults['WeaponDamage'];
					}

					echo $ShootingPlayer->getDisplayName() ?> fires their <?php echo $ShootingWeapon->getName() ?> at<?php if ($ShotHit && $ActualDamage['TargetAlreadyDead']) { ?> the debris that was once<?php } ?> the forces<?php
					if (!$ShotHit || !$ActualDamage['TargetAlreadyDead']) {
						if (!$ShotHit) {
							?> and misses<?php
						} elseif ($ActualDamage['TotalDamage'] == 0) {
							if ($WeaponDamage['Shield'] > 0) {
								?> which proves ineffective against the <?php if ($ActualDamage['HasMines']) { ?>mines<?php } elseif ($ActualDamage['HasCDs']) { ?>combat drones<?php } else { ?>scout drones<?php }
							} elseif ($WeaponDamage['Armour'] > 0) {
								?> which is deflected by the <?php if ($ActualDamage['HasMines']) { ?>mines<?php } elseif ($ActualDamage['HasCDs']) { ?>combat drones<?php } else { ?>scout drones<?php } ?> shields<?php
							} else {
								?> but it cannot do any damage<?php
							}
						} else {
							?> destroying <?php
							$DamageTypes = 0;
							if ($ActualDamage['NumMines'] > 0) { $DamageTypes = $DamageTypes + 1; }
							if ($ActualDamage['NumCDs'] > 0) { $DamageTypes = $DamageTypes + 1; }
							if ($ActualDamage['NumSDs'] > 0) { $DamageTypes = $DamageTypes + 1; }

							if ($ActualDamage['NumMines'] > 0) {
								?><span class="red"><?php echo number_format($ActualDamage['NumMines']) ?></span> mines<?php
								$this->doDamageTypeReductionDisplay($DamageTypes);
							}
							if ($ActualDamage['NumCDs'] > 0) {
								?><span class="red"><?php echo number_format($ActualDamage['NumCDs']) ?></span> combat drones<?php
								$this->doDamageTypeReductionDisplay($DamageTypes);
							}
							if ($ActualDamage['NumSDs'] > 0) {
								?><span class="red"><?php echo number_format($ActualDamage['NumSDs']) ?></span> scout drones<?php
							}
						}
					} ?>.
					<br />
					<?php if ($ShotHit && $ActualDamage['KillingShot']) {
						?>Forces are <span class="red">DESTROYED!</span><br /><?php
					}
				}
			}
			if (isset($TraderResults['Drones'])) {
				$Drones = $TraderResults['Drones'];
				$ActualDamage = $Drones['ActualDamage'];
				$WeaponDamage = $Drones['WeaponDamage'];

				echo $ShootingPlayer->getDisplayName();
				if ($WeaponDamage['Launched'] == 0) {
					?> fails to launch their combat drones<?php
				} else {
					?> launches <span class="cds"><?php echo $WeaponDamage['Launched'] ?></span> combat drones at<?php if ($ActualDamage['TargetAlreadyDead']) { ?> the debris that was once <?php } ?> the forces<?php
					if (!$ActualDamage['TargetAlreadyDead']) {
						if ($ActualDamage['TotalDamage'] == 0) {
							if ($WeaponDamage['Shield'] > 0) {
								?> which prove ineffective against the <?php if ($ActualDamage['HasMines']) { ?>mines<?php } elseif ($ActualDamage['HasCDs']) { ?>combat drones<?php } else { ?>scout drones<?php }
							} elseif ($WeaponDamage['Armour'] > 0) {
								?> which is deflected by the <?php
								if ($ActualDamage['HasMines']) { ?>mines<?php } elseif ($ActualDamage['HasCDs']) { ?>combat drones<?php } else { ?>scout drones<?php } ?> shields<?php
							} else {
								?> but they cannot do any damage<?php
							}
						} else {
							$DamageTypes = 0;
							if ($ActualDamage['NumMines'] > $WeaponDamage['Kamikaze']) { $DamageTypes = $DamageTypes + 1; }
							if ($ActualDamage['NumCDs'] > 0) { $DamageTypes = $DamageTypes + 1; }
							if ($ActualDamage['NumSDs'] > 0) { $DamageTypes = $DamageTypes + 1; }

							if ($WeaponDamage['Kamikaze'] == 0) {
								?> destroying <?php
							} else {
								?> of which <span class="cds"><?php echo $WeaponDamage['Kamikaze'] ?></span> kamikaze against <span class="red"><?php echo $WeaponDamage['Kamikaze'] ?></span> mines<?php
								if ($DamageTypes > 0) {
									?> whilst the others destroy <?php
								}
							}
							if ($ActualDamage['NumMines'] > $WeaponDamage['Kamikaze']) {
								?><span class="red"><?php echo number_format($ActualDamage['NumMines']) ?></span> mines<?php
								$this->doDamageTypeReductionDisplay($DamageTypes);
							}
							if ($ActualDamage['NumCDs'] > 0) {
								?><span class="red"><?php echo number_format($ActualDamage['NumCDs']) ?></span> combat drones<?php
								$this->doDamageTypeReductionDisplay($DamageTypes);
							}
							if ($ActualDamage['NumSDs'] > 0) {
								?><span class="red"><?php echo number_format($ActualDamage['NumSDs']) ?></span> scout drones<?php
							}
						}
					}
				}?>.
				<br />
				<?php if ($ActualDamage['KillingShot']) {
					?>Forces are <span class="red">DESTROYED!</span><br /><?php
				}
			}
		}
		echo $ShootingPlayer->getDisplayName();
		if ($TotalDamage > 0) {
			?> hits for a total of <span class="red"><?php echo $TotalDamage ?></span> damage in this round of combat<?php
		} else {
			?> does no damage this round.<?php
			if (!$TraderResults['DeadBeforeShot']) {
				?> Maybe they should go back to the academy<?php
			}
		} ?>.<br /><br /><?php
	}
}
$TotalDamage = $TraderTeamCombatResults['TotalDamage']; ?>
This fleet <?php if ($TotalDamage > 0) { ?>hits for a total of <span class="red"><?php echo $TotalDamage ?></span> damage in this round of combat<?php } else { ?>does no damage this round. You call that a fleet? They need a better recruiter<?php } ?>.
