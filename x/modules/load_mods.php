<?php

/*
 * Load Data : Available Mods
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

global $g_ModData;
$g_output["availMods"] = Array();

/* Load Mod Info from Files */
$g_ModData = Array();
foreach (scandir("../mods", 0) as $fsp) {
	if (is_dir("../mods/".$fsp) && file_exists("../mods/".$fsp."/mod.json")) {
		$g_ModData[$fsp] = json_decode(file_get_contents("../mods/".$fsp."/mod.json"), true);
	}
}

foreach ($g_ModData as $modCode => $modInfo) {
	
	if ($modCode == "0ad")
		continue;
	
	$mod = Array(
			"name"			=> $modInfo["name"]
		,	"label"			=> $modInfo["label"]
		,	"code"			=> $modCode
		,	"type"			=> $modInfo["type"]
		,	"url"			=> (substr($modInfo["url"],0,4) == "http") ? $modInfo["url"] : "http://".$modInfo["url"]
		);
	
	/* send to output */
	$g_output["availMods"][$modCode] = $mod;
	
}

?>
