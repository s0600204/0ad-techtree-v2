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
		
		if (!isset($g_TemplateData[$buildable])) {
			report($buildable." does not exist in templates array!", "warn");
			continue;
		}
		$buildInfo = $g_TemplateData[$buildable];
		
		if (isset($buildInfo["WallSet"]))
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
		,	"specificName"	=> (isset($structInfo["Identity"]["SpecificName"]) ? $structInfo["Identity"]["SpecificName"] : "-")
		,	"phase"			=> $g_phaseList[0]
		,	"civ"			=> $myCiv
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
	
	$reqTech = fetchValue($structInfo, "Identity/RequiredTechnology");
	if (is_string($reqTech) && substr($reqTech, 0, 5) == "phase") {
		$structure["phase"] = $reqTech;
	} else if (is_string($reqTech) || count($reqTech) > 0) {
		$structure["reqTech"] = $reqTech;
	}
	
	if (isset($structInfo["WallSet"])) {
		$structure["wallset"] = $structInfo["WallSet"]["Templates"];
		
		// Collate techs and costs from components in set
		foreach ($structure["wallset"] as $wTempl => $wCode) {
			$wPart = $g_TemplateData[$wCode];
			
			$structure["production"]["technology"] = array_merge(
					$structure["production"]["technology"],
					fetchValue($wPart, "ProductionQueue/Technologies", true)
				);
			
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
	
	/* send to output */
	$g_output["structures"][$structCode] = $structure;
}

?>
