<?php

/*
 * Load Data : Units
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

require_once "./modules/load_civs.php";
require_once "./modules/load_templates.php";
global $g_UnitList;
global $g_UnitBuilds;
$g_UnitBuilds = Array();
$g_output["units"] = Array();

/* Load Unit Data from Files */
foreach ($g_args["mods"] as $mod) {
	$path = "../mods/".$mod."/simulation/templates/";
	if (file_exists($path)) {
		recurseThru($path, "units/", $g_TemplateData, $mod);
		recurseThru($path, "special_units/", $g_TemplateData, $mod);
	}
	
	$files = scandir($path."units/", 0);
	foreach ($files as $file) {
		if (substr($file,0,1) == ".") {
			continue;
		}
		$g_UnitList[] = substr($file, 0, strrpos($file, '.'));
	}
}

/* Iterate through and resolve promotions */
foreach ($g_UnitList as $unitCode) {
	
	$unitInfo = $g_TemplateData["units/".$unitCode];
	
	if (!array_key_exists("Promotion", $unitInfo)
		|| !array_key_exists("Entity", $unitInfo["Promotion"]))
	{
		continue;
	}
	
	$promotion = $unitInfo["Promotion"]["Entity"];
	
	$g_TemplateData[$promotion]["Promotion"]["Previous"] = $unitCode;
	
}

foreach ($g_CivCodes as $civ) {
	$g_UnitBuilds[$civ] = Array();
};

/* Interate through and aquire required info */
foreach ($g_UnitList as $unitCode) {
	
	$unitInfo = $g_TemplateData["units/".$unitCode];
	
	/* Only include unit if it belongs to a playable civ */
	$myCiv = fetchValue($unitInfo, "Identity/Civ");
	if (!in_array($myCiv, $g_CivCodes)) {
		continue;
	}
	
	if (array_key_exists("Promotion", $unitInfo)
		&& array_key_exists("Previous", $unitInfo["Promotion"]))
	{
		continue;
	}
	
	$unit = Array(
			"genericName"	=> fetchValue($unitInfo, "Identity/GenericName")
		,	"specificName"	=> fetchValue($unitInfo, "Identity/SpecificName")
		,	"civ"			=> $myCiv
		,	"icon"			=> fetchValue($unitInfo, "Identity/Icon")
		,	"sourceMod"		=> $unitInfo["mod"]
	//	,	"cost"			=> fetchValue($unitInfo, "Cost/Resources")
	//	,	"time"			=> fetchValue($unitInfo, "Cost/BuildTime")
		);
	
	if (array_key_exists("RequiredTechnology", $unitInfo["Identity"])) {
		$unit["reqTech"] = $unitInfo["Identity"]["RequiredTechnology"];
	}
	
	foreach (fetchValue($unitInfo, "Builder/Entities", true) as $build) {
		if (!in_array($build, $g_UnitBuilds[$myCiv])) {
			
			if (strpos($build, "{civ}")) {
				// keep these if statements separate
				if (!in_array(str_ireplace("{civ}", $myCiv, $build), $g_UnitBuilds[$myCiv])) {
					$g_UnitBuilds[$myCiv][] = str_ireplace("{civ}", $myCiv, $build);
				}
			} else {
				$g_UnitBuilds[$myCiv][] = $build;
			}
		}
	}
	
//	$unit["cost"]["time"] = fetchValue($unitInfo, "Cost/BuildTime");
	
	/* send to output */
	$g_output["units"][$unitCode] = $unit;
	
}

?>
