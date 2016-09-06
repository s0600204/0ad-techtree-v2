<?php

function load_dir_recurse ($path, $subpath, &$store) {
	$files = scandir($path.$subpath, 0);
	
	foreach ($files as $file) {
		if (substr($file,0,1) == ".") {
			continue;
		}
		if (is_dir($path.$subpath.$file)) {
			$subpath .= $file . "/";
			load_dir_recurse($path, $subpath, $store);
		} else {
			load_file($path, $subpath.$file, $store);
		}
	}
}

function load_dir ($path, &$store) {
	
	foreach (scandir($path, 0) as $file) {
		if (substr($file,0,1) == "." || is_dir($path.$file)) {
			continue;
		}
		load_file($path, $file, $store);
	}
}

function ls ($path) {
	$ls = Array();
	
	foreach (scandir($path, 0) as $file) {
		if (substr($file,0,1) == "." || is_dir($path.$file)) {
			continue;
		}
		$ls[] = $file;
	}
	return $ls;
}

function load_file ($path, $file, &$store, $sourceMod) {
	if (preg_match("/.json/i", $file) == 1) {
		$fcontents = json_decode(file_get_contents($path.$file), true);
	} else if (preg_match("/.xml/i", $file) == 1) {
		$fcontents = xml2array(file_get_contents($path.$file));
	} else {
		return;
	}
	$fname = substr($file, 0, strrpos($file, '.'));
	if ($fcontents !== NULL) {
		$store[$fname] = $fcontents;
		$store[$fname]["mod"] = $sourceMod;
	} else {
		report($path.$file . " is not a valid JSON or XML file!", "error");
	}
}

function depath ($str) {
	return (strpos($str, "/")) ? substr($str, strrpos($str, '/')+1) : $str;
}

function getDependencies ($modName) {
	$modPath = "../mods/" . $modName . "/mod.json";
	if (!file_exists($modPath)) {
		return Array();
	}
	$modFile = JSON_decode(file_get_contents($modPath), true);
	$modDeps = Array();
	foreach ($modFile["dependencies"] as $mod) {
		$mod = preg_split("/[=><]/", $mod);
		$modDeps = array_merge($modDeps, getDependencies($mod[0]));
		$modDeps[] = $mod[0];
	}
	return $modDeps;
}

function checkIcon ($icon, $mod) {
	if (is_array($icon)) {
		return "!";
	}
	$deps = getDependencies($mod);
	$deps[] = $mod;
	foreach (array_reverse($deps) as $dep) {
		$path = "../mods/" . $dep . "/art/textures/ui/session/portraits/";
		if (file_exists($path.$icon)) {
			return Array($icon, $dep);
		}
	}
	return Array("placeholder", "0ad");
}

function checkEmblem ($img, $mod) {
	$path = "../mods/" . $mod . "/art/textures/ui/";
	if (file_exists($path.$img)) {
		return $img;
	} else {
		return "placeholder";
	}
}

function xml2array ($xml) {
	return json_decode(json_encode((array) simplexml_load_string($xml)), true);
}

function report ($content, $type = "log") {
	global $g_args;
	if ($g_args["debug"] || $type == "warn" || $type == "error") {
		global $g_debug;
		$g_debug["report"][] = Array($type, $content);
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
