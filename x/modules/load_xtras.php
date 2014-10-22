<?php

/*
 * Load Data : Xtras
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * These are certain items not automatically loaded by earlier scripts
 */

require_once "./modules/load_templates.php";
require_once "./modules/load_units.php";
require_once "./modules/load_structures.php";

$path = "../mods/0ad/simulation/templates/";

/* gaia_sheep - required by all corrals */
load_file($path, "gaia/fauna_sheep.xml", $g_TemplateData, "0ad");
$unitInfo = $g_TemplateData["gaia/fauna_sheep"];
$unit = Array(
		"genericName"	=> fetchValue($unitInfo, "Identity/GenericName")
	,	"specificName"	=> fetchValue($unitInfo, "Identity/SpecificName")
	,	"icon"			=> checkIcon(fetchValue($unitInfo, "Identity/Icon"), $unitInfo["mod"])
	,	"sourceMod"		=> $unitInfo["mod"]
	,	"cost"			=> Array(
					"food"		=> fetchValue($unitInfo, "Cost/Resources/food")
				,	"wood"		=> fetchValue($unitInfo, "Cost/Resources/wood")
				,	"stone"		=> fetchValue($unitInfo, "Cost/Resources/stone")
				,	"metal"		=> fetchValue($unitInfo, "Cost/Resources/metal")
				,	"time"		=> fetchValue($unitInfo, "Cost/BuildTime")
				)
	);
$g_output["units"]["fauna_sheep"] = $unit;


/* rome_infantry_spearman_a - required because these units are built advanced by default, but a basic form exists */
$unitInfo = $g_TemplateData["units/rome_infantry_spearman_a"];
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
	);
$g_output["units"]["rome_infantry_spearman_a"] = $unit;


/* palisade wallset - buildable by every civ except rome */
load_file($path, "other/wallset_palisade.xml", $g_TemplateData, "0ad");
load_file($path, "other/palisades_rocks_tower.xml", $g_TemplateData, "0ad");
load_file($path, "other/palisades_rocks_gate.xml", $g_TemplateData, "0ad");
load_file($path, "other/palisades_rocks_long.xml", $g_TemplateData, "0ad");
load_file($path, "other/palisades_rocks_medium.xml", $g_TemplateData, "0ad");
load_file($path, "other/palisades_rocks_short.xml", $g_TemplateData, "0ad");
$palisade = Array("wallset_palisade", "palisades_rocks_tower", "palisades_rocks_gate");
foreach ($palisade as $structCode) {
	$structInfo = $g_TemplateData["other/".$structCode];
	
	$structure = Array(
		"genericName"	=> fetchValue($structInfo, "Identity/GenericName")
	,	"specificName"	=> $structInfo["Identity"]["SpecificName"]
	,	"phase"			=> $g_phaseList[0]
	,	"civ"			=> $structInfo["Identity"]["Civ"]
	,	"icon"			=> checkIcon(fetchValue($structInfo, "Identity/Icon"), $structInfo["mod"])
	,	"sourceMod"		=> $structInfo["mod"]
	,	"production"	=> Array(
				"technology"	=> fetchValue($structInfo, "ProductionQueue/Technologies", true)
			,	"units"			=> fetchValue($structInfo, "ProductionQueue/Entities", true)
			)
	,	"cost"			=> Array(
					"food"		=> fetchValue($structInfo, "Cost/Resources/food")
				,	"wood"		=> fetchValue($structInfo, "Cost/Resources/wood")
				,	"stone"		=> fetchValue($structInfo, "Cost/Resources/stone")
				,	"metal"		=> fetchValue($structInfo, "Cost/Resources/metal")
				,	"time"		=> fetchValue($structInfo, "Cost/BuildTime")
				)
	);
	
	if (isset($structInfo["WallSet"])) {
		$structure["wallset"] = $structInfo["WallSet"]["Templates"];
		
		// Collate and costs from components in set
		foreach ($structure["wallset"] as $wTempl => $wCode) {
			$wPart = $g_TemplateData[$wCode];
			
			if (substr($wTempl, 0, 4) == "Wall") {
				foreach (fetchValue($wPart, "Cost/Resources") as $cost => $q) {
					if (!isset($structure["cost"][$cost])) {
						$structure["cost"][$cost] = Array();
					}
					$structure["cost"][$cost][] = $q;
				}
				$structure["cost"]["time"][] = fetchValue($wPart, "Cost/BuildTime");
				arsort($structure["cost"]);
			}
		}
	}
	
	$g_output["structures"][$structCode] = $structure;
}

?>
