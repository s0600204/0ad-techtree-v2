<?php

/*
 * Load Data : Units
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

/* Load Unit Data */
function load_unit ($unitCode) {
	
	$unitInfo = load_template($unitCode);
	if (!$unitInfo)
		return false;
	
	$myCiv = fetchValue($unitInfo, "Identity/Civ");
	
	$unit = Array(
			"genericName"	=> fetchValue($unitInfo, "Identity/GenericName")
		,	"specificName"	=> fetchValue($unitInfo, "Identity/SpecificName")
		,	"civ"			=> $myCiv
		,	"icon"			=> checkIcon(fetchValue($unitInfo, "Identity/Icon"), $unitInfo["mod"])
		,	"sourceMod"		=> $unitInfo["mod"]
		,	"cost"			=> Array(
					"food"		=> fetchValue($unitInfo, "Cost/Resources/food")
				,	"wood"		=> fetchValue($unitInfo, "Cost/Resources/wood")
				,	"stone"		=> fetchValue($unitInfo, "Cost/Resources/stone")
				,	"metal"		=> fetchValue($unitInfo, "Cost/Resources/metal")
				,	"time"		=> fetchValue($unitInfo, "Cost/BuildTime")
				)
		,	"stats"			=> loadUnitStats($unitCode)
		,	"tooltip"		=> fetchValue($unitInfo, "Identity/Tooltip")
		);
	
	if (isset($unitInfo["Identity"]["RequiredTechnology"])) {
		$unit["reqTech"] = $unitInfo["Identity"]["RequiredTechnology"];
	}
	
	global $g_StructureList;
	foreach (fetchValue($unitInfo, "Builder/Entities", true) as $build) {
		if (!in_array($build, $g_StructureList[$myCiv])) {
			
			if (strpos($build, "{civ}")) {
				// keep these if statements separate
				if (!in_array(str_ireplace("{civ}", $myCiv, $build), $g_StructureList[$myCiv])) {
					$g_StructureList[$myCiv][] = str_ireplace("{civ}", $myCiv, $build);
				}
			} else {
				$g_StructureList[$myCiv][] = $build;
				
			}
		}
	}
	
	/* send to output */
	return $unit;
}


function loadUnitStats ($unitCode) {
	
	$unitInfo = load_template($unitCode);
	if (!$unitInfo) {
		warn("No unit info");
		return Array();
	}
	
	$attackMethods = Array( "Melee", "Ranged", "Charge" );
	$attackDamages = Array( "Crush", "Hack", "Pierce", "MinRange", "MaxRange", "RepeatTime" );
	
	$stats = Array();
	$stats[0] = Array(
			"health"	=> fetchValue($unitInfo, "Health/Max")
		,	"attack"	=> Array()
		,	"armour"	=> fetchValue($unitInfo, "Armour")
		);
	
	$healer = fetchValue($unitInfo, "Heal");
	if (count($healer) > 0) {
		$stats[0]["healer"] = Array(
				"Range"	=> (isset($healer["Range"])) ? (int) $healer["Range"] : 0
			,	"HP"	=> (isset($healer["HP"])) ? (int) $healer["HP"] : 0
			,	"Rate"	=> (isset($healer["Rate"])) ? (int) $healer["Rate"] : 0
			);
	}
	
	foreach ($attackMethods as $meth) {
		$attack = Array();
		$keep = false;
		foreach ($attackDamages as $dama) {
			$attack[$dama] = fetchValue($unitInfo, "Attack/".$meth."/".$dama);
			if (!is_array($attack[$dama])) {
				$keep = true;
			} else {
				$attack[$dama] = 0;
			}
		}
		if ($keep) {
			$stats[0]["attack"][$meth] = $attack;
		}
	}
	
	$rank = fetchValue($unitInfo, "Identity/Rank");
	if (!is_array($rank)) {
		$stats[0]["rank"] = $rank;
	}
	
/*	if (array_key_exists("Promotion", $unitInfo)
		&& array_key_exists("Entity", $unitInfo["Promotion"])) {
		$stats = array_merge($stats, loadUnitStats($unitInfo["Promotion"]["Entity"]));
	}	*/
	
	return $stats;
}


?>
