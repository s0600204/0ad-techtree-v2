<?php

/*
 * Load Data : Technologies
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

global $g_TechnologyData;

function load_techJSON ($tech) {
	global $g_TechnologyData;
	global $g_currentMod;
	
	if (!isset($g_TechnologyData[$tech])) {
		$deps = getDependencies($g_currentMod);
		$deps[] = $g_currentMod;
		foreach (array_reverse($deps) as $dep) {
			$path = "../mods/" . $dep . "/simulation/data/technologies/";
			if (file_exists($path.$tech.".json")) {
				load_file($path, $tech.".json", $g_TechnologyData, $dep);
				return $g_TechnologyData[$tech];
			}
		}
		return false;
	}
	return $g_TechnologyData[$tech];
}

function load_tech ($techCode) {
	
	$techInfo = load_techJSON($techCode);
	
	/* Set basic branch information */
	$tech = Array(
			"reqs"			=> Array()
	//	,	"unlocks"		=> Array()
		,	"name"			=> Array(
					"generic"	=> $techInfo["genericName"]
				)
		,	"icon"			=> (isset($techInfo["icon"])) ? checkIcon("technologies/".$techInfo["icon"], $techInfo["mod"]) : ""
		,	"cost"			=> (isset($techInfo["cost"])) ? $techInfo["cost"] : ""
		,	"tooltip"		=> (isset($techInfo["tooltip"])) ? $techInfo["tooltip"] : ""
		);
	
	if (isset($techInfo["pair"])) {
		$tech["pair"] = $techInfo["pair"];
	}
	if (isset($techInfo["specificName"])) {
		if (is_array($techInfo["specificName"])) {
			foreach ($techInfo["specificName"] as $sn_civ => $sn_value) {
				$tech["name"][$sn_civ] = $sn_value;
			}
		} else {
			$tech["name"]["specific"] = $techInfo["specificName"];
		}
	}
	if (isset($techInfo["autoResearch"])) {
		$tech["autoResearch"] = $techInfo["autoResearch"];
	}
	if (isset($techInfo["researchTime"])) {
		$tech["cost"]["time"] = $techInfo["researchTime"];
	}
	
	/* Reqs, part 1: the requirements field */
	if (isset($techInfo["requirements"])) {
		
		foreach ($techInfo["requirements"] as $op => $val) {
			
			$reqs = calcReqs($op, $val);
			
			switch ($op) {
				case "tech":
					$tech["reqs"]["generic"] = Array( $reqs );
					break;
				
				case "civ":
					$tech["reqs"][$reqs] = Array();
					break;
				
				case "any":
					if (count($reqs[0]) > 0) {
						foreach ($reqs[0] as $r => $v) {
							if (is_numeric($r)) {
								$tech["reqs"][$v] = Array();
							} else {
								$tech["reqs"][$r] = $v;
							}
						}
					}
					if (count($reqs[1]) > 0) {
						$tech["reqs"]["generic"] = $reqs[1];
					}
					break;
				
				case "all":
					foreach ($reqs[0] as $r) {
						$tech["reqs"][$r] = $reqs[1];
					}
					break;
			}
		}
	}
	
	if (isset($techInfo["supersedes"])) {
	/*	if (substr(depath($techInfo["supersedes"]), 0, 4) == "pair") { // training_conscription, much?
			$g_techPairs[$techInfo["supersedes"]]["unlocks"][] = $techCode;
		} else {	*/
			if (isset($tech["reqs"]["generic"])) {
				$tech["reqs"]["generic"][] = $techInfo["supersedes"];
			} else {
				foreach (array_keys($tech["reqs"]) as $civkey) {
					$tech["reqs"][$civkey][] = $techInfo["supersedes"];
				}
			}
		//	$g_techs[$techInfo["supersedes"]]["unlocks"][] = $techCode;
	//	}
	}
	
	if ($GLOBALS["g_args"]["debug"])
		$tech["sourceMod"] = $techInfo["mod"];
	
	return $tech;
	
}

function load_phase ($techCode) {
	
	$techInfo = load_techJSON($techCode);
	
	$phase = Array(
			"name"			=> Array(
					"generic"	=> $techInfo["genericName"]
				)
		,	"cost"			=> (isset($techInfo["cost"])) ? $techInfo["cost"] : Array()
		,	"actualPhase"	=> ""
		,	"tooltip"		=> (isset($techInfo["tooltip"])) ? $techInfo["tooltip"] : ""
		);
	
	if (isset($techInfo["specificName"])) {
		if (is_array($techInfo["specificName"])) {
			foreach ($techInfo["specificName"] as $sn_civ => $sn_value) {
				$phase["name"][$sn_civ] = $sn_value;
			}
		} else {
			$phase["name"]["specific"] = $techInfo["specificName"];
		}
	}
	if (isset($techInfo["icon"])) {
		$phase["icon"] = checkIcon("technologies/".$techInfo["icon"], $techInfo["mod"]);
	} else {
		$icon = strpos($techCode, "_");
		$icon = substr($techCode, $icon+1) . "_" . substr($techCode, 0, $icon);
		$phase["icon"] = checkIcon("technologies/".$icon.".png", $techInfo["mod"]);
	}
	
	if ($GLOBALS["g_args"]["debug"])
		$phase["sourceMod"] = $techInfo["mod"];
	
	return $phase;
}

function load_pair ($techCode) {
	
	$techInfo = load_techJSON($techCode);
	
	return Array(
			"techs"	=> Array( $techInfo["top"], $techInfo["bottom"] )
		,	"req"	=> (isset($techInfo["supersedes"])) ? $techInfo["supersedes"] : ""
		);
	
}

function unravel_phases ($techs) {
	
	$phaseList = Array();
	
	foreach ($techs as $techCode => $data)
	{	
		if (isset($data["reqs"]["generic"]) && count($data["reqs"]["generic"]) > 1)
		{
			$reqTech = $techs[$techCode]["reqs"]["generic"][1];
			
			if (!isset($techs[$reqTech]["reqs"]["generic"]))
				continue;
			
			$reqPhase = $techs[$reqTech]["reqs"]["generic"][0];
			$myPhase = $techs[$techCode]["reqs"]["generic"][0];
			
			if ($reqPhase == $myPhase
				|| substr(depath($reqPhase), 0, 5) !== "phase"
				|| substr(depath($myPhase), 0, 5) !== "phase")
					continue;
			
			$reqPhasePos = array_search($reqPhase, $phaseList);
			$myPhasePos = array_search($myPhase, $phaseList);
			
			if (count($phaseList) == 0)
			{
				$phaseList = Array( $reqPhase, $myPhase );
			}
			else if ($reqPhasePos === false && $myPhasePos > -1)
			{
				array_splice($phaseList, $myPhasePos, 0, $reqPhase);
			}
			else if ($myPhasePos === false && $reqPhasePos > -1)
			{
				array_splice($phaseList, $reqPhasePos+1, 0, $myPhase);
			}
		}
	}
	
	return $phaseList;
}

function calcReqs ($op, $val)
{
	switch ($op)
	{
	case "civ":
	case "tech":
		return $val;
	
	case "all":
	case "any":
		$t = Array();
		$c = Array();
		foreach ($val as $nv)
		{
			foreach ($nv as $o => $v)
			{
				$r = calcReqs($o, $v);
				switch ($o)
				{
				case "civ":
					$c[] = $r;
					break;
					
				case "tech":
					$t[] = $r;
					break;
					
				case "any":
					$c = array_merge($c, $r[0]);
					$t = array_merge($t, $r[1]);
					break;
					
				case "all":
					foreach ($r[0] as $ci) {
						$c[$ci] = $r[1];
					}
					$t = $t;
				}
				
			}
		}
		return Array( $c, $t );
	}
}

?>
