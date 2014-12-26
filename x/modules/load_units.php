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
	
	$unit = load_common_fromEnt($unitInfo);
	$myCiv = $unit["civ"];
	
	$unit["stats"] = loadUnitStats($unitCode);
	$unit["cost"]["population"] = fetchValue($unitInfo, "Cost/Population");
	
	if (isset($unitInfo["Identity"]["RequiredTechnology"])) {
		$unit["reqTech"] = $unitInfo["Identity"]["RequiredTechnology"];
	}
	
	global $g_StructureList;
	global $g_currentCiv;
	if ($g_currentCiv !== $myCiv) {
		info("Unit's set Civ does not match Current: ".$myCiv."!=".$g_currentCiv." (".$unitCode.")");
	}
	foreach (fetchValue($unitInfo, "Builder/Entities", true) as $build) {
		
		$build = str_ireplace("{civ}", $g_currentCiv, $build);
		if (!in_array($build, $g_StructureList[$g_currentCiv])) {
			$g_StructureList[$g_currentCiv][] = $build;
		}
	}
	
	global $g_args;
	if ($g_args["debug"])
		$unit["sourceMod"] = $unitInfo["mod"];
	
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
