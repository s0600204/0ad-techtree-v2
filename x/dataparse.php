<?php

global $g_output;
global $g_args;
$g_output["report"] = Array();
$g_output["debug"] = Array();

/*
 * Set arguments
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */
$g_args = Array();
$g_args["debug"] = ($_POST["debug"] === "true") ? true : false;
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

/*
 * Load and parse data JSON
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */
$GLOBALS['recurse'] = true;
$modules = scandir("./modules", 0);
foreach ($modules as $module) {
	if (substr($module,0,1) == "." || preg_match("/.php/i", $module) != 1) {
		continue;
	}
	include_once "./modules/".$module;
}

/*
 * Output data
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */
echo json_encode($g_output);


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


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

function recurseThru ($path, $subpath, &$store, $mod) {
	$files = scandir($path.$subpath, 0);
//	global $pattern;
	foreach ($files as $file) {
		if (substr($file,0,1) == ".") {
			continue;
		}
		if (is_dir($path.$subpath.$file)) {
			if ($GLOBALS['recurse'] == true) {
				recurseThru($path, $subpath.$file."/", $store, $mod);
			} else {
				continue;
			}
		} else {
			load_file($path, $subpath.$file, $store, $mod);
		}
	}
}

function load_file ($path, $file, &$store, $mod) {
	if (preg_match("/.json/i", $file) == 1) {
		$fcontents = json_decode(file_get_contents($path.$file), true);
	} else if (preg_match("/.xml/i", $file) == 1) {
		$fcontents = xml2array(file_get_contents($path.$file));
	} else {
		continue;
	}
	$fname = substr($file, 0, strrpos($file, '.'));
	if ($fcontents !== NULL) {
		$store[$fname] = $fcontents;
		$store[$fname]["mod"] = $mod;
	} else {
		report($path.$file . " is not a valid JSON or XML file!", "error");
	}
}

function depath ($str) {
	return (strpos($str, "/")) ? substr($str, strrpos($str, '/')+1) : $str;
}

function getDependencies ($modName) {
	$modPath = "../mods/" . $modName . "/mod.json";
	if ($modName == "0ad" || !file_exists($modPath)) {
		return Array();
	}
	$modFile = JSON_decode(file_get_contents($modPath), true);
	$modDeps = Array();
	foreach ($modFile["dependencies"] as $mod) {
		$mod = explode("=", $mod);
		$modDeps = array_merge($modDeps, getDependencies($mod[0]));
		$modDeps[] = $mod[0];
	}
	return $modDeps;
}

function xml2array ($xml) {
	return json_decode(json_encode((array) simplexml_load_string($xml)), true);
}

function report ($content, $type = "log") {
	global $g_args;
	if ($g_args["debug"] || $type == "warn" || $type == "error") {
		global $g_output;
		$g_output["report"][] = Array($type, $content);
	}
}

function info($content) {
	report($content, "info");
}

function warn($content) {
	report($content, "warn");
}

function err($content) {
	report($content, "error");
}


?>
