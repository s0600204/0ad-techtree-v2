<!DOCTYPE html>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
	
	<script src="x/svg.min.js"></script>
	<script src="x/tree.js"></script>
	
	
	<script>
<?php
	$args = Array(
		'mod' => ''
	,	'debug' => false
	,	'redraw' => false
	);
	foreach ($_GET as $arg => $val) {
		$arg = strtolower($arg);
		switch ($arg)
		{
			case "mod":
				$args["mod"] = (is_array($val)) ? $val : Array($val);
				break;
			
			case "debug":
				$args["debug"] = true;
				break;
			
			case "redraw":
				$args["redraw"] = true;
				break;
		}
	}
	foreach ($args as $arg => $val) {
		echo "\tvar g_args = " . JSON_encode($args) . ";\n";
	}
?>
	</script>
	
	<link href="./ui/tree.css" rel="stylesheet"></link>
	<link href="./ui/biolinum/biolinum.css" rel="stylesheet"></link>
	<link href="./ui/mfgicon/mfglabs_iconset.css" rel="stylesheet"></link>
</head>

<body onload="init()">

<div id="selectDiv">
	<select id="civSelect" onChange="selectCiv(event.target.value);" onclick="toggleDivs('')"></select>
	<span class="ico mfgicon icon-settings" onclick="toggleDivs('mod')"></span>
	<span class="ico mfgicon icon-white_question" onclick="toggleDivs('attr')"></span>
</div>

<div id="modDiv">
	<fieldset id="modSelect">
		<legend><b>Available Mods</b></legend>
	</fieldset>
	<input type="button" value="Show" onclick="selectMod()" />
	<input type="button" value="Clear" onclick="clearModSelect()" />
</div>

<div id="attrDiv">
	<fieldset>
		<legend><b>Description</b></legend>
		<p>These are generated-on-the-fly diagrams showing the current technology tree of civilisations in <a href="http://play0ad.com/" target="_new">0AD : Empires Ascendant</a>.</p>
		<p>The source code can be found at <a href="https://github.com/s0600204/0ad-civtree" target="_new">GitHub</a>.</p>
	</fieldset>
	<fieldset id="modURLs">
		<legend><b>Mods</b></legend>
		<p>Links to individual mods' webpages:<p>
	</fieldset>
	<fieldset>
		<legend><b>Attribution</b></legend>
		<p>The font used is <b>Biolinum</b> by the <a href="http://www.linuxlibertine.org/" target="_new">Libertine Open Fonts Project</a>.</p>
		<p>The Iconset used in the UI is by <a href="http://mfglabs.github.io/mfglabs-iconset/" target="_new">MFG Labs</a>.</p>
	</fieldset>
	<a rel="license" id="license" href="http://creativecommons.org/licenses/by-sa/3.0/" target="_new">
		<img alt="Creative Commons License" src="./ui/cc-by-sa_80x15.png" />
	</a>
</div>

<svg xmlns="http://www.w3.org/2000/svg" version="1.1" id="svg_canvas"></svg>

<div id="renderBanner">If you see this, it may mean something has gone wrong...</div>

</body>
</html>
