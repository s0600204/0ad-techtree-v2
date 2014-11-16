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
	
	$myCiv = $structInfo["Identity"]["Civ"];
	
	$structure = Array(
			"name"			=> Array(
					"generic"	=> fetchValue($structInfo, "Identity/GenericName")
				,	"specific"	=> (isset($structInfo["Identity"]["SpecificName"]) ? $structInfo["Identity"]["SpecificName"] : "-")
				)
		,	"phase"			=> false
		,	"civ"			=> $myCiv
		,	"icon"			=> checkIcon(fetchValue($structInfo, "Identity/Icon"), $structInfo["mod"])
		,	"production"	=> Array(
					"technology"	=> fetchValue($structInfo, "ProductionQueue/Technologies", true)
				,	"units"			=> Array()
				)
		,	"cost"			=> Array(
					"food"		=> fetchValue($structInfo, "Cost/Resources/food")
				,	"wood"		=> fetchValue($structInfo, "Cost/Resources/wood")
				,	"stone"		=> fetchValue($structInfo, "Cost/Resources/stone")
				,	"metal"		=> fetchValue($structInfo, "Cost/Resources/metal")
				,	"time"		=> fetchValue($structInfo, "Cost/BuildTime")
				)
		,	"stats"			=> Array(
					"health"	=> fetchValue($structInfo, "Health/Max")
				,	"attack"	=> fetchValue($structInfo, "Attack")
				,	"armour"	=> fetchValue($structInfo, "Armour")
				)
		,	"tooltip"		=> fetchValue($structInfo, "Identity/Tooltip")
		);
	
	$reqTech = fetchValue($structInfo, "Identity/RequiredTechnology");
	if (is_string($reqTech) && substr($reqTech, 0, 5) == "phase") {
		$structure["phase"] = $reqTech;
	} else if (is_string($reqTech) || count($reqTech) > 0) {
		$structure["reqTech"] = $reqTech;
	}
	
	foreach (fetchValue($structInfo, "ProductionQueue/Entities", true) as $unitCode) {
		$structure["production"]["units"][] = str_replace("{civ}", $myCiv, $unitCode);
	}
	
	$foundation = array_search("Foundation", array_keys($structure["stats"]["armour"]));
	if ($foundation) {
		array_splice($structure["stats"]["armour"], $foundation);
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
	
	if ($GLOBALS["g_args"]["debug"])
		$structure["sourceMod"] = $structInfo["mod"];
	
	/* send to output */
	return $structure;
}

?>
