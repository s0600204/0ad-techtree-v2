<?php

require_once "./modules/util_functions.php";
global $g_output;
global $g_args;
global $g_debug;
$g_debug["report"] = Array();

/*
 * Set arguments
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */
$g_args = Array();
$g_args["debug"] = ($_POST["debug"] === "true") ? true : false;
$g_args["redraw"] = ($_POST["redraw"] === "true") ? true : false;
if ($_POST['mod'] === "") {
	$g_args["mods"] = Array("0ad");
} else {
	$g_args["mods"] = Array();
	foreach ($_POST['mod'] as $mod) {
		foreach (getDependencies($mod) as $dep) {
			if (!in_array($dep, $g_args["mods"])) {
				$g_args["mods"][] = $dep;
			}
		}
		$g_args["mods"][] = $mod;
	}
}
info(print_r($g_args["mods"], true));

/*
 * Check for Cache
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */
$cachefile = $g_args["mods"];
asort($cachefile);
$cachefile = "../cache/".md5(implode("_", $cachefile));
/*if (!$g_args["redraw"] && file_exists($cachefile)) {
	echo file_get_contents($cachefile);
	return;
}	*/

/*
 * Initialise Globals
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

global $g_CivList;
global $g_UnitList;
global $g_StructureList;
global $g_TechList;
$g_CivList = Array();
$g_UnitList = Array();
$g_StructureList = Array();
$g_TechList = Array();

global $g_currentMod;
$g_currentMod = "0ad";

/*
 * Load data
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

/* Load Mod Selection */
include_once "./modules/load_mods.php";

/* Civ Load Code */
include_once "./modules/load_civs.php";

/* Units and Structure Load Code */
include_once "./modules/load_templates.php";
include_once "./modules/load_units.php";
include_once "./modules/load_structures.php";
$g_output["units"] = Array();
$g_output["structures"] = Array();
$uCount = 0;

foreach ($g_args["mods"] as $mod) {
	$g_currentMod = $mod;
	
	foreach (fetch_civs() as $civCode) {
		$g_CivList[] = $civCode;
		$civData = load_civ($civCode);
		if (!$civData)
			continue;
		
		$g_output["civs"][$civCode] = $civData;
		
		/* Load Units and Structures */
		$g_StructureList[$civCode] = Array();
		do {
			
			foreach ($g_UnitList[$civCode] as $unitCode) {
				if (!in_array(depath($unitCode), $g_output["units"])) {
					$newUnit = load_unit($unitCode);
					$unitCode = depath($unitCode);
					if ($newUnit)
						$g_output["units"][$unitCode] = $newUnit;
				}
			}
			$uCount = count($g_UnitList[$civCode]);
			
			foreach ($g_StructureList[$civCode] as $structCode) {
				if (!in_array(depath($structCode), $g_output["structures"])) {
					$newStruct = load_structure($structCode);
					$structCode = depath($structCode);
					if ($newStruct) {
						$g_output["structures"][$structCode] = $newStruct;
						
						foreach ($newStruct["production"]["units"] as $unitCode) {
							if (!in_array($unitCode, $g_UnitList[$civCode])) {
								$g_UnitList[$civCode][] = $unitCode;
							}
						}
						foreach ($newStruct["production"]["technology"] as $techCode) {
							if (!in_array($techCode, $g_TechList)) {
								$g_TechList[] = $techCode;
							}
						}
					}
				}
			}
			
		} while ($uCount < count($g_UnitList[$civCode]));
	}

	/* Load techs */
	include_once "./modules/load_techs.php";
	$techPairs = Array();
	foreach ($g_TechList as $techCode) {
		
		$realCode = depath($techCode);
		
		if (substr($realCode, 0, 4) == "pair") {
			$techPairs[$techCode] = load_pair($techCode);
		} else if (substr($realCode, 0, 5) == "phase") {
			$g_output["phases"][$techCode] = load_phase($techCode);
		} else {
			$g_output["techs"][$techCode] = load_tech($techCode);
		}
	}
	foreach ($techPairs as $pairCode => $pairInfo) {
		foreach ($pairInfo["techs"] as $techCode) {
			$newTech = load_tech($techCode);
			
			if ($pairInfo["req"] !== "") {
				if (isset($newTech["reqs"]["generic"])) {
					$newTech["reqs"]["generic"] = array_merge($newTech["reqs"]["generic"], $techPairs[$pairInfo["req"]]["techs"]);
				} else {
					foreach (array_keys($newTech["reqs"]) as $civkey) {
						$newTech["reqs"][$civkey] = array_merge($newTech["reqs"][$civkey], $techPairs[$pairInfo["req"]]["techs"]);
					}
				}
			}
			
			$g_output["techs"][$techCode] = $newTech;
		}
	}
}
$g_currentMod = $g_args["mods"][0];

/*
 * Parse Data
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

/* Unravel phase order */
$g_output["phaseList"] = unravel_phases($g_output["techs"]);

/* Load actual phase for phases */
foreach ($g_output["phaseList"] as $phaseCode) {
	$phaseInfo = load_techJSON($phaseCode);
	$g_output["phases"][$phaseCode] = load_phase($phaseCode);
	
	if (isset($phaseInfo["requirements"])) {
		foreach ($phaseInfo["requirements"] as $op => $val) {
			if ($op == "any") {
				foreach ($val as $v) {
					$k = array_keys($v);
					$k = $k[0];
					$v = $v[$k];
					if ($k == "tech" && isset($g_output["phases"][$v])) {
						$g_output["phases"][$v]["actualPhase"] = $phaseCode;
					}
				}
			}
		}
	}
}

/* Sort production (of structures) and build lists (of civs) */
include_once "./modules/parse_productionLists.php";
include_once "./modules/parse_buildLists.php";


/*
 * Save Cache
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */
try {
	$g_output["debug"] = Array("report" => Array(Array("info", "Cached Content")));
	file_put_contents($cachefile, json_encode($g_output));
} catch (Exception $e) {
	warn("Unable to save a cache file: ".$e->getMessage());
}

/*
 * Output parsed data
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */
$g_output["debug"] = $g_debug;
echo json_encode($g_output);
//echo print_r($g_output, true);

?>
