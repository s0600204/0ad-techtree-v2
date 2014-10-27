<?php

/*
 * Load Data : Civs
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

global $g_CivData;
$g_output["civs"] = Array();

/* Load Civ Data from Files */
function fetch_civs () {
	$civs = Array();
	$path = "../mods/".$GLOBALS['g_currentMod']."/civs/";
	if (file_exists($path)) {
		$filenames = ls($path);
		
		foreach ($filenames as $civ) {
			$civ = substr($civ, 0, strrpos($civ, '.'));
			$civInfo = load_civJSON($civ);
			if ($civInfo && $civInfo["SelectableInGameSetup"])
				$civs[] = $civ;
		}
	}
	return $civs;
}

function load_civJSON ($civ) {
	global $g_CivData;
	
	if (!isset($g_CivData[$civ])) {
		$path = "../mods/".$GLOBALS['g_currentMod']."/civs/";
		
		if (file_exists($path.$civ.".json")) {
			load_file($path, $civ.".json", $g_CivData);
		} else {
			return false;
		}
	}
	return $g_CivData[$civ];
}

/* Iterate through and acquire needed info */
function load_civ ($civCode) {
	
	$civInfo = load_civJSON($civCode);
	
	if (!$civInfo || !$civInfo["SelectableInGameSetup"])
		return false;
	
	$civ = Array(
			"name"			=> $civInfo["Name"]
		,	"culture"		=> $civInfo["Culture"]
		,	"emblem"		=> checkEmblem($civInfo["Emblem"], $civInfo["mod"])
		,	"sourceMod"		=> $civInfo["mod"]
		,	"startBuilding"	=> ""
		,	"buildList"		=> Array()
		);
	
	foreach ($civInfo["StartEntities"] as $ents) {
		if (substr($ents["Template"], 0, 6) == "struct") {
			$civ["startBuilding"] = substr($ents["Template"], 11);
		} else {
			$GLOBALS['g_UnitList'][$civCode][] = $ents["Template"];
		}
	}
	
	/* send to output */
	return $civ;
	
}

?>
