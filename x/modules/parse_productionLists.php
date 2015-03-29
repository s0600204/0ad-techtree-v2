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
		if (substr($prod, 0, 4) == "pair" || strpos($prod, "/pair") || strpos($prod, "_pair")) {
			$pos = array_search($prod, $prodTech);
			array_splice($prodTech, $pos, 1, $techPairs[$prod]["techs"]);
		}
	}
	
	/* Sort Techs by Phase */
	$newProdTech = Array();
	foreach ($prodTech as $prod) {
		$phase = "";
		
		if (substr(depath($prod), 0, 5) == "phase")
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
			foreach ($g_output["techs"][$prod]["reqs"]["generic"] as $req)
			{
				if (substr(depath($req), 0, 5) === "phase") {
					$phase = $req;
				}
			}
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
		
		if ($unit["phase"] !== false) {
			$phase = $unit["phase"];
			if (in_array($phase, $g_output["phaseList"]) === false)
				$phase = $g_output["phases"][$phase]["actualPhase"];
		}
		else if (isset($unit["reqTech"])) {
			$reqTech = $unit["reqTech"];
			if (isset($g_output["phases"][$reqTech])) {
				$phase = $g_output["phases"][$reqTech]["actualPhase"];
			} else {
				$reqs = $g_output["techs"][$reqTech]["reqs"];
				if (isset($reqs[$civ])) {
					$phase = $reqs[$civ][0];
				} else {
					$phase = $reqs["generic"][0];
				}
			}
		} else {
			if ($structInfo["phase"] === false) {
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
