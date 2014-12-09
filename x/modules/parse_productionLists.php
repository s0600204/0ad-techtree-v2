<?php

/*
 * Parse Production Lists : Group by phase
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

// iterate through the structures
foreach ($g_output["structures"] as $structCode => $structInfo) {
	
	$prodTech = $structInfo["production"]["technology"];
	$prodUnits = $structInfo["production"]["units"];
	$civ = $structInfo["civ"];
	
	/* Expand tech pairs */
	foreach ($prodTech as $prod) {
		if (substr($prod, 0, 4) == "pair" || strpos($prod, "/pair")) {
			$pos = array_search($prod, $prodTech);
			array_splice($prodTech, $pos, 1, $techPairs[$prod]["techs"]);
		}
	}
	
	/* Sort Techs by Phase */
	$newProdTech = Array();
	foreach ($prodTech as $prod) {
		
		if (substr($prod, 0, 5) == "phase")
		{
			$phase = array_search($g_output["phases"][$prod]["actualPhase"], $g_output["phaseList"]);
			if ($phase > 0)
				$phase = $g_output["phaseList"][$phase - 1];
		}
		else if (isset($g_output["techs"][$prod]["reqs"][$civ]))
		{
			if (isset($g_output["techs"][$prod]["reqs"][$civ][0]))
				$phase = $g_output["techs"][$prod]["reqs"][$civ][0];
		}
		else if (isset($g_output["techs"][$prod]["reqs"]["generic"]))
		{
			$phase = $g_output["techs"][$prod]["reqs"]["generic"][0];
		}
		
		if (!isset($phase) || substr(depath($phase), 0, 5) !== "phase")
		{
			report($prod." doesn't have a specific phase set (".$structCode.",".$civ.")", "info");
			$phase = $structInfo["phase"];
		}
		
		if (!isset($newProdTech[$phase])) {
			$newProdTech[$phase] = Array();
		}
		
		$newProdTech[$phase][] = $prod;
		
	}
	
	/* Determine phase for units */
	$newProdUnits = Array();
	foreach ($prodUnits as $prod) {
		
		$prod = depath($prod);
		
		if (!isset($g_output["units"][$prod])) {
			report($prod." doesn't exist! (".$structCode.")", "warn");
			continue;
		}
		$unit = $g_output["units"][$prod];
		
		if (isset($unit["reqTech"])) {
			$reqTech = $unit["reqTech"];
			if (is_array($reqTech)) {
				foreach ($reqTech as $rt) {
					if (substr($rt, 0, 5) == "phase") {
						$phase = $rt;
					}
				}
			} else {
				if (substr($reqTech, 0, 5) == "phase") {
					$phase = $unit["reqTech"];
				} else if (isset($g_output["techs"][$reqTech]["reqs"][$civ])) {
					$phase = $g_output["techs"][$reqTech]["reqs"][$civ][0];
				} else {
					$phase = $g_output["techs"][$reqTech]["reqs"]["generic"][0];
				}
			}
		} else {
			// hack so it works with civil centres
			if (strpos($structCode, "civil_centre") || $structInfo["phase"] === false) {
				$phase = $g_output["phaseList"][0];
			} else {
				$phase = $structInfo["phase"];
			}
		}
		
		if (!isset($newProdUnits[$phase])) {
			$newProdUnits[$phase] = Array();
		}
		
		$newProdUnits[$phase][] = $prod;
		
	}
	
	$g_output["structures"][$structCode]["production"] = Array(
			"technology"	=> $newProdTech
		,	"units"			=> $newProdUnits
		);
	
}
