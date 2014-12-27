<?php

/*
 * Load Data : Structures
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

/* Acquire structures */
function load_structure ($structCode) {
	
	$structInfo = load_template($structCode);
	
	if (!$structInfo)
		return false;
	
	$structure = load_common_fromEnt($structInfo);
	$myCiv = $structure["civ"];
	
	$structure["production"] = Array(
			"technology"	=> fetchValue($structInfo, "ProductionQueue/Technologies", true)
		,	"units"			=> Array()
		);
	
	foreach (fetchValue($structInfo, "ProductionQueue/Entities", true) as $unitCode) {
		$structure["production"]["units"][] = str_replace("{civ}", $myCiv, $unitCode);
	}
	
	if (isset($structInfo["WallSet"])) {
		$structure["wallset"] = Array();
		
		// Collate techs and costs from components in set
		foreach ($structInfo["WallSet"]["Templates"] as $wTempl => $wCode) {
			$wPart = load_template($wCode);
			$structure["wallset"][$wTempl] = load_structure($wCode);
			$structure["wallset"][$wTempl]["code"] = $wCode;
			
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
	
	global $g_args;
	if ($g_args["debug"])
		$structure["sourceMod"] = $structInfo["mod"];
	
	/* send to output */
	return $structure;
}
