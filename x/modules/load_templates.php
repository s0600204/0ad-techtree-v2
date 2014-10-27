<?php

/*
 * Load Data : Templates
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

global $g_TemplateData;

/* Load Structure Data from Array/File */
function load_template ($template) {
	global $g_TemplateData;
	
	if (!isset($g_TemplateData[$template])) {
		$path = "../mods/".$GLOBALS["g_currentMod"]."/simulation/templates/";
		
		if (file_exists($path.$template.".xml")) {
			load_file($path, $template.".xml", $g_TemplateData);
		} else {
			return false;
		}
	}
	return $g_TemplateData[$template];
}

function collateValues ($template, $keypath) {
	return fetchValue($template, $keypath, true);
}

function explode_tokens ($tokens){
	if (is_array($tokens)) {
		// rome_tent.xml has a section for technology tokens, but no tokens.
		// This has led to $tokens ending up as an array containing a
		//   two-dimensional array with the key: @attributes
		return Array();
	}
	$ret = explode("\n", trim($tokens));
	for ($rvi=0; $rvi < count($ret); $rvi++)
	{
		$ret[$rvi] = trim($ret[$rvi]);
	}
	return $ret;
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
	
	if (is_string($template))
	{
		$tText = $template;
		$template = load_template($template);
		if (!$template) {
			warn($tText . " does not exist in templates!");
			return "DNE";
		}
		if (isset($template["@attributes"])) {
			$parent = $template["@attributes"]["parent"];
		} else {
			$parent = false;
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

?>
