<?php

/*
 * Load Data : Xtras
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * These are certain items not automatically loaded by earlier scripts
 */

require_once "./modules/load_templates.php";
require_once "./modules/load_units.php";
require_once "./modules/load_structures.php";

$path = "../mods/".$mod."/simulation/templates/";

/* gaia_sheep - required by all corrals */
load_file($path, "gaia/fauna_sheep.xml", $g_TemplateData, "0ad");
$unitInfo = $g_TemplateData["gaia/fauna_sheep"];
$unit = Array(
		"genericName"	=> fetchValue($unitInfo, "Identity/GenericName")
	,	"specificName"	=> fetchValue($unitInfo, "Identity/SpecificName")
	,	"icon"			=> fetchValue($unitInfo, "Identity/Icon")
	,	"sourceMod"		=> $unitInfo["mod"]
//	,	"cost"			=> fetchValue($unitInfo, "Cost/Resources")
//	,	"time"			=> fetchValue($unitInfo, "Cost/BuildTime")
	);
$g_output["units"]["fauna_sheep"] = $unit;


/* rome_infantry_spearman_a - required because these units are built advanced by default, but a basic form exists */
$unitInfo = $g_TemplateData["units/rome_infantry_spearman_a"];
$unit = Array(
		"genericName"	=> fetchValue($unitInfo, "Identity/GenericName")
	,	"specificName"	=> fetchValue($unitInfo, "Identity/SpecificName")
	,	"civ"			=> $myCiv
	,	"icon"			=> fetchValue($unitInfo, "Identity/Icon")
	,	"sourceMod"		=> $unitInfo["mod"]
//	,	"cost"			=> fetchValue($unitInfo, "Cost/Resources")
//	,	"time"			=> fetchValue($unitInfo, "Cost/BuildTime")
	);
$g_output["units"]["rome_infantry_spearman_a"] = $unit;


/* palisade wallset - buildable by every civ except rome */
load_file($path, "other/wallset_palisade.xml", $g_TemplateData, "0ad");
load_file($path, "other/palisades_rocks_tower.xml", $g_TemplateData, "0ad");
load_file($path, "other/palisades_rocks_gate.xml", $g_TemplateData, "0ad");
/*load_file($path, "other/palisades_rocks_long.xml", $g_TemplateData, "0ad");
load_file($path, "other/palisades_rocks_medium.xml", $g_TemplateData, "0ad");
load_file($path, "other/palisades_rocks_short.xml", $g_TemplateData, "0ad");*/
$palisade = Array("wallset_palisade", "palisades_rocks_tower", "palisades_rocks_gate");
foreach ($palisade as $structCode) {
	$structInfo = $g_TemplateData["other/".$structCode];
	
	$structure = Array(
		"genericName"	=> fetchValue($structInfo, "Identity/GenericName")
	,	"specificName"	=> $structInfo["Identity"]["SpecificName"]
	,	"phase"			=> $g_phaseList[0]
	,	"civ"			=> $structInfo["Identity"]["Civ"]
	,	"icon"			=> fetchValue($structInfo, "Identity/Icon")
	,	"sourceMod"		=> $structInfo["mod"]
	,	"production"	=> Array(
				"technology"	=> fetchValue($structInfo, "ProductionQueue/Technologies", true)
			,	"units"			=> fetchValue($structInfo, "ProductionQueue/Entities", true)
			)
//	,	"cost"			=> fetchValue($structInfo, "Cost/Resources")
//	,	"time"			=> fetchValue($structInfo, "Cost/BuildTime")
	);
	
	if (array_key_exists("WallSet", $structInfo)) {
		$structure["wallset"] = $structInfo["WallSet"]["Templates"];
	}
	
	$g_output["structures"][$structCode] = $structure;
}

?>
