<?php

/*
 * Load Data : Technologies
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

global $g_TechData;
global $g_techs;
global $g_techPairs;
global $g_phases;
global $g_phaseList;
/*$g_output["techs"] = Array();
$g_output["phases"] = Array();
$g_output["phaseList"] = Array();
$g_output["pairs"] = Array();	*/

/* Load Tech Data from Files */
foreach ($g_args["mods"] as $mod) {
	$path = "../mods/".$mod."/simulation/data/technologies/";
	if (file_exists($path)) {
		recurseThru($path, "", $g_TechData, $mod);
	}
}

foreach ($g_TechData as $techCode => $techInfo) {
	
	$realCode = depath($techCode);
	
	if (substr($realCode, 0, 4) == "pair") {
		$g_techPairs[$techCode] = Array(
				"techs"		=> Array()
			,	"unlocks"	=> Array()
			);
	
	} else if (substr($realCode, 0, 5) == "phase") {
		$g_phases[$techCode] = Array(
				"name"			=> Array(
						"generic"	=> $techInfo["genericName"],
						"specific"	=> Array()
					)
			,	"cost"			=> (isset($techInfo["cost"])) ? $techInfo["cost"] : Array()
		//	,	"actualPhase"	=> ""
			,	"sourceMod"		=> $techInfo["mod"]
			,	"tooltip"		=> (isset($techInfo["tooltip"])) ? $techInfo["tooltip"] : ""
			);
		
		if (isset($techInfo["specificName"])) {
			$g_phases[$techCode]["name"]["specific"] = $techInfo["specificName"];
		}
		if (isset($techInfo["icon"])) {
			$g_phases[$techCode]["icon"] = checkIcon("technologies/".$techInfo["icon"], $techInfo["mod"]);
		} else {
			$icon = strpos($techCode, "_");
			$icon = substr($techCode, $icon+1) . "_" . substr($techCode, 0, $icon);
			$g_phases[$techCode]["icon"] = checkIcon("technologies/".$icon.".png", $techInfo["mod"]);
		}
		
	} else {
		
		/* Set basic branch information */
		$g_techs[$techCode] = Array(
				"reqs"			=> Array()
			,	"unlocks"		=> Array()
			,	"name"			=> Array(
						"generic"	=> $techInfo["genericName"],
						"specific"	=> Array()
					)
			,	"icon"			=> (isset($techInfo["icon"])) ? checkIcon("technologies/".$techInfo["icon"], $techInfo["mod"]) : ""
			,	"cost"			=> (isset($techInfo["cost"])) ? $techInfo["cost"] : ""
			,	"sourceMod"		=> $techInfo["mod"]
			,	"tooltip"		=> (isset($techInfo["tooltip"])) ? $techInfo["tooltip"] : ""
			);
		
		if (isset($techInfo["pair"])) {
			$g_techs[$techCode]["pair"] = $techInfo["pair"];
		}
		if (isset($techInfo["specificName"])) {
			$g_techs[$techCode]["name"]["specific"] = $techInfo["specificName"];
		}
		if (isset($techInfo["autoResearch"])) {
			$g_techs[$techCode]["autoResearch"] = $techInfo["autoResearch"];
		}
		if (isset($techInfo["researchTime"])) {
			$g_techs[$techCode]["cost"]["time"] = $techInfo["researchTime"];
		}
		
		/* Reqs, part 1: the requirements field */
		if (isset($techInfo["requirements"])) {
			
			foreach ($techInfo["requirements"] as $op => $val) {
				
				$ret = calcReqs($op, $val);
				
				switch ($op) {
					case "tech":
						$g_techs[$techCode]["reqs"]["generic"] = Array( $ret );
						break;
					
					case "civ":
						$g_techs[$techCode]["reqs"][$ret] = Array();
						break;
					
					case "any":
						if (count($ret[0]) > 0) {
							foreach ($ret[0] as $r => $v) {
								if (is_numeric($r)) {
									$g_techs[$techCode]["reqs"][$v] = Array();
								} else {
									$g_techs[$techCode]["reqs"][$r] = $v;
								}
							}
						}
						if (count($ret[1]) > 0) {
							$g_techs[$techCode]["reqs"]["generic"] = $ret[1];
						}
						break;
					
					case "all":
						foreach ($ret[0] as $r) {
							$g_techs[$techCode]["reqs"][$r] = $ret[1];
						}
						break;
				}
			}
		}
	}
}

/* Unravel pair chains */
foreach ($g_techPairs as $pair => $data) {
	$techInfo = $g_TechData[$pair];
	
	$g_techPairs[$pair]["techs"] = Array( $techInfo["top"], $techInfo["bottom"] );
	
	if (isset($techInfo["supersedes"])) {
		$g_techPairs[$techInfo["supersedes"]]["unlocks"] = $g_techPairs[$pair]["techs"];
	}
}

/* Reqs, part 2: supersedes */
foreach ($g_techs as $techCode => $data) {
	$techInfo = $g_TechData[$techCode];
	
	/* Direct tech-to-tech superseding */
	if (isset($techInfo["supersedes"])) {
		if (substr(depath($techInfo["supersedes"]), 0, 4) == "pair") { // training_conscription, much?
			$g_techPairs[$techInfo["supersedes"]]["unlocks"][] = $techCode;
		} else {
			if (isset($g_techs[$techCode]["reqs"]["generic"])) {
				$g_techs[$techCode]["reqs"]["generic"][] = $techInfo["supersedes"];
			} else {
				foreach (array_keys($g_techs[$techCode]["reqs"]) as $civkey) {
					$g_techs[$techCode]["reqs"][$civkey][] = $techInfo["supersedes"];
				}
			}
			$g_techs[$techInfo["supersedes"]]["unlocks"][] = $techCode;
		}
	}
	
	/* Via pair-tech superseding */
	if (isset($data["data"])) {
		$pair = $data["pair"];
		if (isset($g_techPairs[$pair])) {
			$pair = $g_techPairs[$pair]["unlocks"];
			$g_techs[$techCode]["unlocks"] = array_merge($g_techs[$techCode]["unlocks"], $pair);
			foreach ($pair as $tech) {
				if (isset($g_techs[$tech]["reqs"]["generic"])) {
					$g_techs[$tech]["reqs"]["generic"][] = $techCode;
				} else {
					foreach (array_keys($g_techs[$tech]["reqs"]) as $civkey) {
						$g_techs[$tech]["reqs"][$civkey][] = $techCode;
					}
				}
			}
		} else {
//			echo $techCode ." is trying to use non-existant ". $pair ." as a pair\n";
		}
	}
}

/* Derive the phase of a phase */
foreach ($g_phases as $phaseCode => $data) {
	$phaseInfo = $g_TechData[$phaseCode];
	
	if (isset($phaseInfo["requirements"])) {
		foreach ($phaseInfo["requirements"] as $op => $val) {
			if ($op == "any") {
				foreach ($val as $v) {
					$k = array_keys($v);
					$k = $k[0];
					$v = $v[$k];
					if ($k == "tech" && isset($g_phases[$v])) {
						$g_phases[$v]["actualPhase"] = $phaseCode;
					}
				}
			}
		}
	}
	
}

/* Unravel phase order */
$g_phaseList = Array();
foreach ($g_techs as $techCode => $data) {
	$techInfo = $g_TechData[$techCode];
	
	if (isset($data["reqs"]["generic"]) && count($data["reqs"]["generic"]) > 1)
	{
		$reqTech = $g_techs[$techCode]["reqs"]["generic"][1];
		
		if (!isset($g_techs[$reqTech]["reqs"]["generic"])) {
			continue;
		}
		$reqPhase = $g_techs[$reqTech]["reqs"]["generic"][0];
		$myPhase = $g_techs[$techCode]["reqs"]["generic"][0];
		
		if ($reqPhase == $myPhase) {
			continue;
		}
		$reqPhasePos = array_search($reqPhase, $g_phaseList);
		$myPhasePos = array_search($myPhase, $g_phaseList);
		
		if (count($g_phaseList) == 0)
		{
			$g_phaseList = Array( $reqPhase, $myPhase );
		}
		else if ($reqPhasePos === false && $myPhasePos > -1)
		{
			array_splice($g_phaseList, $myPhasePos, 0, $reqPhase);
		}
		else if ($myPhasePos === false && $reqPhasePos > -1)
		{
			array_splice($g_phaseList, $reqPhasePos+1, 0, $myPhase);
		}
	}
}

/* send to output */
$g_output["techs"] = $g_techs;
$g_output["phases"] = $g_phases;
$g_output["phaseList"] = $g_phaseList;
$g_output["pairs"] = $g_techPairs;

?>
