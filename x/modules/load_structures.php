<?php

/*
 * Load Data : Structures
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

require_once "./modules/load_civs.php";
require_once "./modules/load_techs.php";
require_once "./modules/load_templates.php";
require_once "./modules/load_units.php";
global $g_StructureList;
$g_output["structures"] = Array();

/* Load Structure Data from Files */
foreach ($g_args["mods"] as $mod) {
	$path = "../mods/".$mod."/simulation/templates/";
	if (file_exists($path."structures/")) {
		recurseThru($path, "structures/", $g_TemplateData, $mod);
	
		$files = scandir($path."structures/", 0);
		foreach ($files as $file) {
			if (substr($file,0,1) == ".") {
				continue;
			}
			$g_StructureList[] = substr($file, 0, strrpos($file, '.'));
		}
	}
}

/* Collate wall bits from wallsets because we need them included */
$wallSegments = Array();
foreach ($g_CivCodes as $civ) {
	foreach ($g_UnitBuilds[$civ] as $buildable) {
		
		if (!array_key_exists($buildable, $g_TemplateData)) {
			report($buildable." does not exist in templates array!", "warn");
			continue;
		}
		$buildInfo = $g_TemplateData[$buildable];
		
		if (array_key_exists("WallSet", $buildInfo))
		{
			foreach ($buildInfo["WallSet"]["Templates"] as $wCode)
			{
				$wallSegments[] = $wCode;
			}
		}
	}
}

/* Acquire structures */
foreach ($g_StructureList as $structCode) {
	
	$structInfo = $g_TemplateData["structures/".$structCode];
	
	/* Only include structure if it belongs to a playable civ */
	if (!in_array($structInfo["Identity"]["Civ"], $g_CivCodes)) {
		continue;
	}
	
	$myCiv = $structInfo["Identity"]["Civ"];
	
	/* Only include structure if it can actually be built by a unit */
	if (!in_array("structures/".$structCode, $g_UnitBuilds[$myCiv])
		&& !in_array("structures/".$structCode, $wallSegments))
	{
		continue;
	}
	
//	report($structCode);
	$structure = Array(
			"genericName"	=> fetchValue($structInfo, "Identity/GenericName")
		,	"specificName"	=> (array_key_exists("SpecificName", $structInfo["Identity"]) ? $structInfo["Identity"]["SpecificName"] : "-")
		,	"phase"			=> $g_phaseList[0]
		,	"civ"			=> $myCiv
		,	"icon"			=> checkIcon(fetchValue($structInfo, "Identity/Icon"), $structInfo["mod"])
		,	"sourceMod"		=> $structInfo["mod"]
		,	"production"	=> Array(
					"technology"	=> fetchValue($structInfo, "ProductionQueue/Technologies", true)
				,	"units"			=> fetchValue($structInfo, "ProductionQueue/Entities", true)
				)
	//	,	"cost"			=> fetchValue($structInfo, "Cost/Resources")
	//	,	"time"			=> fetchValue($structInfo, "Cost/BuildTime")
		);
	
	$reqTech = fetchValue($structInfo, "Identity/RequiredTechnology");
	if (is_string($reqTech) && substr($reqTech, 0, 5) == "phase") {
		$structure["phase"] = $reqTech;
	} else if (is_string($reqTech) || count($reqTech) > 0) {
		$structure["reqTech"] = $reqTech;
	}
	
	if (array_key_exists("WallSet", $structInfo)) {
		$structure["wallset"] = $structInfo["WallSet"]["Templates"];
		
		// Collate any techs from components in set
		foreach ($structure["wallset"] as $wCode) {
			$wPart = $g_TemplateData[$wCode];
			$structure["production"]["technology"] = array_merge(
					$structure["production"]["technology"],
					fetchValue($wPart, "ProductionQueue/Technologies", true)
				);
		}
	}
	
//	$structure["cost"]["time"] = fetchValue($structInfo, "Cost/BuildTime");
	
	/* send to output */
	$g_output["structures"][$structCode] = $structure;
}

?>
