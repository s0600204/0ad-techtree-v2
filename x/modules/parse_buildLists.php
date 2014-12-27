<?php

/*
 * Parse Build List : Determine build lists of civs
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

foreach ($g_CivList as $civ) {
	
	$buildList = Array();
	
	// create lists
	foreach ($g_StructureList[$civ] as $build) {
		
		$build = depath($build);
		if (!isset($g_output["structures"][$build])) {
			warn($build . " doesn't seem to exist for ".$civ."! (Check the civ attr of the struct)");
			continue;
		}
		
		if ($g_output["structures"][$build]["phase"] === false)
			$g_output["structures"][$build]["phase"] = $g_output["phaseList"][0];
		
		$myPhase = $g_output["structures"][$build]["phase"];
		
		if (!isset($buildList[$myPhase])) {
			$buildList[$myPhase] = Array();
		}
		$buildList[$myPhase][] = $build;
	}
	
	// todo: sort lists
	
	// output
	$g_output["civs"][$civ]["buildList"] = $buildList;
}
