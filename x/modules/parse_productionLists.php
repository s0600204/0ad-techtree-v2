<?php

/*
 * Parse Production Lists : Group by phase
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

require_once "./modules/load_structures.php";
require_once "./modules/load_techs.php";
require_once "./modules/load_units.php";

// iterate through the structures
foreach ($g_output["structures"] as $structCode => $structInfo) {
	
	$prodTech = $structInfo["production"]["technology"];
	$prodUnits = $structInfo["production"]["units"];
	$civ = $structInfo["civ"];
	
	/* Expand tech pairs */
	foreach ($prodTech as $prod) {
		if (substr($prod, 0, 4) == "pair" || strpos($prod, "/pair")) {
			$pt = array_search($prod, $prodTech);
			array_splice($prodTech, $pt, 1, $g_techPairs[$prod]["techs"]);
		}
	}
	
	/* Sort Techs by Phase */
	$newProdTech = Array();
	foreach ($prodTech as $prod) {
		
		if (substr($prod, 0, 5) == "phase")
		{
			$phase = array_search($g_phases[$prod]["actualPhase"], $g_phaseList);
			if ($phase > 0) {
				$phase = $g_phaseList[$phase - 1];
			} else {
				report($prod." has an invalid phase!", "warn");
			}
		}
		else if (isset($g_output["techs"][$prod]["reqs"][$civ]))
		{
			$phase = $g_output["techs"][$prod]["reqs"][$civ][0];
		}
		else if (isset($g_output["techs"][$prod]["reqs"]["generic"]))
		{
			$phase = $g_output["techs"][$prod]["reqs"]["generic"][0];
		}
		else
		{
			report($prod." doesn't possess a phase! (".$civ.")", "warn");
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
		
		$prod = substr($prod, strpos($prod, "/")+1);
		$prod = str_replace("{civ}", $civ, $prod);
		
		if (!isset($g_output["units"][$prod])) {
			report($prod." doesn't exist! (".$structCode.")", "warn");
			continue;
		}
		$unit = $g_output["units"][$prod];
		
		if (isset($unit["reqTech"])) {
			$reqTech = $unit["reqTech"];
			if (substr($reqTech, 0, 5) == "phase") {
				$phase = $unit["reqTech"];
			} else if (isset($g_output["techs"][$reqTech]["reqs"][$civ])) {
				$phase = $g_output["techs"][$reqTech]["reqs"][$civ][0];
			} else {
				$phase = $g_output["techs"][$reqTech]["reqs"]["generic"][0];
			}
		} else {
			// hack so it works with civil centres
			if (strpos($structCode, "civil_centre")) {
				$phase = $g_phaseList[0];
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

?>
