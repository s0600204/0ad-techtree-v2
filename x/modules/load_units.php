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
	
	$unit["stats"]["speed"] = Array(
			"Walk"	=> (int) fetchValue($unitInfo, "UnitMotion/WalkSpeed")
		,	"Run"	=> (int) fetchValue($unitInfo, "UnitMotion/Run/Speed")
		);
	$unit["cost"]["population"] = fetchValue($unitInfo, "Cost/Population");
	
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
	
	$healer = fetchValue($unitInfo, "Heal");
	if (count($healer) > 0) {
		$unit["stats"]["healer"] = Array(
				"Range"	=> (isset($healer["Range"])) ? (int) $healer["Range"] : 0
			,	"HP"	=> (isset($healer["HP"])) ? (int) $healer["HP"] : 0
			,	"Rate"	=> (isset($healer["Rate"])) ? (int) $healer["Rate"] : 0
			);
	}
	
	global $g_args;
	if ($g_args["debug"])
		$unit["sourceMod"] = $unitInfo["mod"];
	
	/* send to output */
	return $unit;
}
