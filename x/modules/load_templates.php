<?php

/*
 * Load Data : Templates
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

global $g_TemplateData;

/* Load Structure Data from Array/File */
function load_template ($template) {
	global $g_TemplateData;
	global $g_currentMod;
	
	if (!isset($g_TemplateData[$template])) {
		$deps = getDependencies($g_currentMod);
		$deps[] = $g_currentMod;
		foreach (array_reverse($deps) as $dep) {
			$path = "../mods/" . $dep . "/simulation/templates/";
			if (file_exists($path.$template.".xml")) {
				load_file($path, $template.".xml", $g_TemplateData, $dep);
				return $g_TemplateData[$template];
			}
		}
		return false;
	}
	return $g_TemplateData[$template];
}

function explode_tokens ($tokens) {
	if (is_array($tokens)) {
	//	warn(print_r($tokens,true));
		return Array();
	}
	return preg_split("/\s+/", $tokens, -1, PREG_SPLIT_NO_EMPTY);
}

function merge_tokens ($arr1, $arr2) {
	$ret = Array();
	for ($ti = 0; $ti < count($arr1); $ti++) {
		if (!in_array("-".$arr1[$ti], $arr2) && !in_array(substr($arr1[$ti],1), $arr2)) {
			$ret[] = $arr1[$ti];
		}
	}
	for ($ti = 0; $ti < count($arr2); $ti++) {
		if (!in_array("-".$arr2[$ti], $arr1) && !in_array(substr($arr2[$ti],1), $arr1)) {
			$ret[] = $arr2[$ti];
		}
	}
	return $ret;
}

/*
 * Traverses the templates to fetch a value
 * Also collates token strings
 */
function fetchValue ($template, $keypath, $collate = false) {
	global $g_TemplateData;
	$keys = explode("/", $keypath);
	$ret = Array();
	$parent = false;
	
	if (is_string($template))
	{
		$tText = $template;
		$template = load_template($template);
		if (!$template) {
			warn($tText . " does not exist in templates!");
			return Array();
		}
		if (isset($template["@attributes"])) {
			$parent = $template["@attributes"]["parent"];
		}
	}
	else if (is_array($template))
	{
		$parent = $template["@attributes"]["parent"];
	}
	else
	{
		warn($template);
		return Array();
	}
	
	// Navigate through until we reach the desired point
	for ($k=0; $k < count($keys); $k++)
	{
		if (isset($template[$keys[$k]]))
		{
			if ($k == count($keys) - 1)
			{
				// If we have come to the end of the key-path, we must be at our wanted value
				// Add it to the collection if we're collating tokens, or return it if not
				if ($collate) {
					$ret = merge_tokens($ret, explode_tokens($template[$keys[$k]]));
					if ($parent !== false)
					{
						$ret = merge_tokens($ret, fetchValue($parent, $keypath, $collate));
					}
					break;
				} else {
					return $template[$keys[$k]];
				}
			}
			else
			{
				// Else, continue following the key-path
				$template = $template[$keys[$k]];
			}
		}
		else
		{
			// If the key-path doesn't exist in this Template, try the Template's parent, if it has one
			if ($parent !== false)
			{
				if ($collate === false)
				{
					return fetchValue($parent, $keypath);
				}
				$ret = merge_tokens($ret, fetchValue($parent, $keypath, $collate));
			}
			break;
		}
	}
	return $ret;
}

function getArmourValues ($entityInfo) {
	$armours = Array();
	$armourResists = Array( "Crush", "Hack", "Pierce" );
	foreach ($armourResists as $resist) {
		$armours[$resist] = (int) fetchValue($entityInfo, "Armour/".$resist);
	}
	return $armours;
}

function getAttackValues ($entityInfo) {
	$attacks = Array();
	$atkMethods = Array( "Melee", "Ranged", "Charge" );
	$atkDamages = Array( "Crush", "Hack", "Pierce", "MinRange", "MaxRange", "RepeatTime" );
	foreach ($atkMethods as $meth) {
		$atk = Array();
		$keep = false;
		foreach ($atkDamages as $dama) {
			$atk[$dama] = (int) fetchValue($entityInfo, "Attack/".$meth."/".$dama);
			if ($atk[$dama] > 0)
				$keep = true;
		}
		if ($keep)
			$attacks[$meth] = $atk;
	}
	return $attacks;
}

function load_common_fromEnt ($entityData) {
	
	$myCiv = fetchValue($entityData, "Identity/Civ");
	
	$entity = Array(
			"name"		=> Array(
					"generic"	=> fetchValue($entityData, "Identity/GenericName")
				,	"specific"	=> fetchValue($entityData, "Identity/SpecificName")
				)
		,	"civ"		=> $myCiv
		,	"icon"		=> checkIcon(fetchValue($entityData, "Identity/Icon"), $entityData["mod"])
		,	"cost"		=> Array(
					"food"		=> fetchValue($entityData, "Cost/Resources/food")
				,	"wood"		=> fetchValue($entityData, "Cost/Resources/wood")
				,	"stone"		=> fetchValue($entityData, "Cost/Resources/stone")
				,	"metal"		=> fetchValue($entityData, "Cost/Resources/metal")
				,	"time"		=> fetchValue($entityData, "Cost/BuildTime")
				)
		,	"tooltip"	=> fetchValue($entityData, "Identity/Tooltip")
		,	"stats"		=> Array(
					"health"	=> fetchValue($entityData, "Health/Max")
				,	"attack"	=> getAttackValues($entityData)
				,	"armour"	=> getArmourValues($entityData)
				)
		,	"phase"		=> false
		);
	
	$reqTech = fetchValue($entityData, "Identity/RequiredTechnology");
	if (is_string($reqTech) && substr($reqTech, 0, 5) == "phase") {
		$entity["phase"] = $reqTech;
	} else if (is_string($reqTech) || count($reqTech) > 0) {
		$entity["reqTech"] = $reqTech;
	}
	
	$auras = fetchValue($entityData, "Auras");
	if (count($auras) > 0) {
		$entity["auras"] = Array();
		foreach ($auras as $auraID => $aura) {
			$entity["auras"][] = Array(
					"name"			=> (isset($aura["AuraName"])) ? $aura["AuraName"] : "Aura"
				,	"description"	=> (isset($aura["AuraDescription"])) ? $aura["AuraDescription"] : "?"
				);
		}
	}
	
	return $entity;
	
}
