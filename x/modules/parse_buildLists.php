<?php

/*
 * Parse Build List : Determine build lists of civs
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 */

require_once "./modules/load_civs.php";
require_once "./modules/load_structures.php";

foreach ($g_CivCodes as $civ) {
	
	$buildList = Array();
	
	// create lists
	foreach ($g_UnitBuilds[$civ] as $build) {
		
		$build = depath($build);
		$myPhase = $g_output["structures"][$build]["phase"];
		
		if (!array_key_exists($myPhase, $buildList)) {
			$buildList[$myPhase] = Array();
		}
		$buildList[$myPhase][] = $build;
	}
	
	// todo: sort lists
	
	// output
	$g_output["civs"][$civ]["buildList"] = $buildList;
}



?>
