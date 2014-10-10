<?php

/*
 * Parse Data : Check for unused technologies
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

if ($g_args["debug"] == false) {
	return;
}

require_once "./modules/load_civs.php";
require_once "./modules/load_units.php";
require_once "./modules/load_structures.php";
require_once "./modules/load_techs.php";

$researchables = Array();
$trainable = Array();
$unusedStruct = Array();
$unusedUnit = Array();
$unusedTech = Array();

// iterate through this list and acquire list of technologies from production lists
// also collates list of non-buildable structures
foreach ($g_StructureList as $build) {
	
	$build = "structures/".$build;
	
	if (!array_key_exists($build, $g_TemplateData)) {
		report($build." does not exist! 2", "warn");
		continue;
	}
	$buildInfo = $g_TemplateData[$build];
	
	if (!in_array($build, $g_UnitBuilds)) {
		
		$unusedStruct[] = $build;
	}
	
	foreach (fetchValue($buildInfo, "ProductionQueue/Technologies", true) as $research) {
		
		if (!in_array($research, $researchables)) {
			$researchables[] = $research;
			if (strpos($research, "pair_")) {
				$researchables[] = $g_TechData[$research]["top"];
				$researchables[] = $g_TechData[$research]["bottom"];
			}
		}
		
	}
	
	foreach (fetchValue($buildInfo, "ProductionQueue/Entities", true) as $train) {
		
		$train = depath($train);
		$train = str_replace("{civ}", $buildInfo["Identity"]["Civ"], $train);
		
		if (!in_array($train, $trainable)) {
			$trainable[] = $train;
		}
		
	}
	
}

// iterate through this list and acquire list of technologies not being used
foreach (array_keys($g_TechData) as $tech) {
	
	$autoResearch = (array_key_exists("autoResearch", $g_TechData[$tech])) ? $g_TechData[$tech]["autoResearch"]: FALSE;
	
	if (!in_array($tech, $researchables) && !$autoResearch) {
		$unusedTech[] = $tech;
	}
	
}

// iterate through this list and acquire list of units not being used
foreach (array_keys($g_output["units"]) as $unit) {
	
	if (!in_array($unit, $trainable)) {
		$unusedUnit[] = $unit;
	}
	
}

$g_output["debug"]["unresearchableTechs"] = $unusedTech;
$g_output["debug"]["unbuildableStructs"] = $unusedStruct;
$g_output["debug"]["untrainableUnits"] = $unusedUnit;

?>
