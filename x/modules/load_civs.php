<?php

/*
 * Load Data : Civs
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

global $g_CivData;
global $g_CivCodes;
$g_output["civs"] = Array();

/* Load Civ Data from Files */
foreach ($g_args["mods"] as $mod) {
	$path = "../mods/".$mod."/civs/";
	if (file_exists($path)) {
		recurseThru($path, "", $g_CivData, $mod);
	}
}

/* Iterate through and acquire needed info */
foreach ($g_CivData as $civCode => $civInfo) {
	
	if (!$civInfo["SelectableInGameSetup"]) {
		continue;
	}
	
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
		}
	}
	
	/* send to output */
	$g_output["civs"][$civCode] = $civ;
	
}

$g_CivCodes = array_keys($g_output["civs"]);

?>
