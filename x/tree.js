
// Page structure
var g_canvas;
var g_canvasParts = { };

// Fetched from Server
var g_techs;	// { }
var g_phases;	// { }
var g_techPairs;	// { }
var g_phaseList;	// [ ]
var g_civs;			// { }
var g_availMods;	// { }
var g_structures;	// { }
var g_units;	// { }

// User Input
var g_selectedCiv;	// " "

// Calculated
var g_treeHeads	= [ ];
var g_treeCols	= { };
var g_bonuses	= [ ];

/* Runs on Page Load */
function init(settings)
{
	g_canvas = SVG('svg_canvas');
	
	document.getElementById('renderBanner').innerHTML = "Aquiring Data from Server...";
	
	server.load(function () {
		g_techs			= server.out["techs"];
		g_phases		= server.out["phases"];
		g_techPairs		= server.out["pairs"];
		g_phaseList		= server.out["phaseList"];
		g_civs			= server.out["civs"];
		g_availMods		= server.out["availMods"];
		g_structures	= server.out["structures"];
		g_units			= server.out["units"];
		
		populateModSelect();
		populateCivSelect();
		selectCiv(document.getElementById('civSelect').value);
	});
}

// Fetch the data from the server
server = {
	
	out: null,
	serverArgs: null,
	userCallback: null,
	
	load: function (cb) {
		if (cb !== undefined && typeof(cb) == "function") {
			this.userCallback = cb;
		}
		this._populateArgs();
		this._http_request();
	},
	
	_callback: function () {
		for (var report in server.out['report']) {
			report = server.out['report'][report];
			if (g_args["debug"] || report[0] == "error") {
				report[1] = "(server) "+report[1];
				if (report[0] == "info" || report[0] == "warn" || report[0] == "error" || report[0] == "log") {
					console[report[0]](report[1]);
				} else {
					console.log(report[1]);
				}
			}
		}
		
		if (this.userCallback !== null) {
			this.userCallback();
		}
	},
	
	_populateArgs: function () {
		try {
			this.serverArgs = new FormData();
			for (var arg in g_args)
			{
				if (Array.isArray(g_args[arg])) {
					for (var subArg in g_args[arg]) {
						this.serverArgs.append(arg+"[]", g_args[arg][subArg]);
					}
				} else {
					this.serverArgs.append(arg, g_args[arg]);
				}
			}
		} catch (err) {
			document.getElementById('renderBanner').innerHTML = "I'm sorry, but your browser is not capable of displaying this. Please update your browser.";
			throw err;
		}
	},
	
	_http_request: function () {
		
		server.out = "";	
		
		http_request = new XMLHttpRequest();
		http_request.onreadystatechange = function () {
			if (http_request.readyState === 4) {
				if (http_request.status === 200) {
					try {
						server.out = JSON.parse(http_request.responseText);
					} catch (e) {
						document.getElementById('renderBanner').innerHTML = "Hmm... something went wrong. Try again later, and hopefully I'll be fixed!";
						console.log(http_request.responseText);
					}
					server._callback();
				} else {
					alert ('There was a problem with the request.');
					server.out = false;
				}
			}
		}
		http_request.open('POST', 'http://127.0.0.1:88/0ad/civtree/x/dataparse.php', true);
		http_request.send(this.serverArgs);
	}
}

// Called when user selects civ from dropdown
function selectCiv(code)
{
	
	g_selectedCiv = code;
//	compileHeads ();
	
	draw();
	
	console.log("(civ select) '"+code+"' selected.");
}

function prep () {
	
}

function getPhase (techCode)
{
	var reqs = getReqs(techCode, false);
	if (reqs.length > 0 && reqs[0].slice(0, 5) == "phase")
	{
		return reqs[0];
	}
	return g_phaseList[0];
}

function getReqs (techCode, noPhase)
{
	if (noPhase === undefined) { noPhase = true; }
	
	var reqs = g_techs[techCode].reqs;
	if (reqs[g_selectedCiv] !== undefined)
	{
		reqs = reqs[g_selectedCiv];
	}
	else if (reqs["generic"] !== undefined)
	{
		reqs = reqs["generic"];
	}
	else
	{
		return false;
	}
	
	if (noPhase && reqs.length > 0 && reqs[0].slice(0, 5) == "phase") {
		reqs = reqs.slice(1);
	}
	
	return reqs;
}

/*
function hasCivSpecificOverride (techCode)
{
	var matches = Object.keys(g_techs).filter(function (code) {
			return code.indexOf(techCode) > 0;
		});
	for (var match in matches)
	{
		var civs = Object.keys(g_techs[matches[match]].reqs);
		if (civs.indexOf(g_selectedCiv) > -1)
		{
			return true;
		}
	}
	return false;
}
*/

function sortTechByName (a,b)
{
	a = (hasSpecificName(a)) ? g_techs[a].name.specific[g_selectedCiv] : g_techs[a].name.generic;
	b = (hasSpecificName(b)) ? g_techs[b].name.specific[g_selectedCiv] : g_techs[b].name.generic;
	if (a < b)
		return -1;
	else if (a > b)
		return 1;
	else
		return 0;
}

function hasSpecificName (techCode)
{
	if (techCode.slice(0,5) == "phase") {
		return (typeof(g_phases[techCode].name.specific) == "object"
			&& typeof(g_phases[techCode].name.specific[g_selectedCiv]) == "string");
	} else {
		return (typeof(g_techs[techCode].name.specific) == "object"
			&& typeof(g_techs[techCode].name.specific[g_selectedCiv]) == "string");
	}
}

function getPhaseTech (phase) {
	if (typeof(g_phases[phase+"_"+g_selectedCiv]) == "object")
	{
		return phase+"_"+g_selectedCiv;
	}
	else if (typeof(g_phases[phase+"_generic"]) == "object")
	{
		return phase+"_generic";
	}
	else
	{
		return phase;
	}
}

function populateModSelect () {
	var modSelect = document.getElementById('modSelect');
	var modAttr = document.getElementById('modURLs');
	
	var tpltCheck = document.createElement('input');
	var tpltLabel = document.createElement('label');
	var tpltBR = document.createElement('br');
	var tpltP = document.createElement('p');
	tpltCheck.type = "checkbox";
	tpltP.appendChild(document.createElement('a'));
	tpltP.firstChild.target = "_new";
	
	for (var mod in g_availMods)
	{
		mod = g_availMods[mod];
		
		var newP = tpltP.cloneNode(true);
		newP.firstChild.innerHTML = mod.name;
		newP.firstChild.href = mod.url;
		modAttr.appendChild(newP);
		
		// This follows the spec in the 0ad mod selector code
		// type: "content"|"functionality"|"mixed/mod-pack"
		if (mod.type.indexOf("functionality") > -1) {
			continue;
		}
		
		var newCheck = tpltCheck.cloneNode();
		newCheck.id = "mod__"+mod.code;
		newCheck.value = mod.code;
		
		var newLabel = tpltLabel.cloneNode();
		newLabel.innerHTML = mod.label + " (<i>" + mod.name + "</i>)"; //+ " [" + mod.type + "]";
		newLabel.setAttribute("for", "mod__"+mod.code);
		
		if (g_args.mod !== null && g_args.mod.indexOf(mod.code) > -1) {
			newCheck.checked = true;
		}
		
		modSelect.appendChild(newCheck);
		modSelect.appendChild(newLabel);
		modSelect.appendChild(tpltBR.cloneNode());
	}
}

function toggleDivs (div) {
	var modDiv = document.getElementById('modDiv');
	var attrDiv = document.getElementById('attrDiv');
	
	modDiv.style.display = (div == "mod" && window.getComputedStyle(modDiv).display == "none") ?  "block" : "none";
	attrDiv.style.display = (div == "attr" && window.getComputedStyle(attrDiv).display == "none") ?  "block" : "none";
	
}

function readModSelect () {
	var modOpts = document.getElementById('modSelect').childNodes;
	var modSelection = [];
	for (var ele=0; modOpts.length > ele; ele++) {
		if (modOpts[ele].nodeType == 1) {
			if (modOpts[ele].type == "checkbox" && modOpts[ele].checked == true) {
				modSelection.push(modOpts[ele].value);
			}
		}
	}
	return modSelection;
}

function clearModSelect () {
	var modOpts = document.getElementById('modSelect').childNodes;
	for (var ele=0; modOpts.length > ele; ele++) {
		if (modOpts[ele].nodeType == 1) {
			if (modOpts[ele].type == "checkbox") {
				modOpts[ele].checked = false;
			}
		}
	}
}

function selectMod () {
	var modSelection = readModSelect();
	
	var modString = "";
	for (var mod in modSelection) {
		modString += "mod[]=" + modSelection[mod] + "&";
	}
	modString = modString.slice(0, -1);
	
	var paras = window.location.search;
	if (paras == "") {
		paras = "?" + modString;
	} else {
		while (pos = paras.indexOf("mod") > -1) {
			var pos = paras.indexOf("mod");
			var end = paras.indexOf("&", pos);
			end = (end == -1) ? end = paras.length : end + 1;
			paras = paras.slice(0, pos) + paras.slice(end);
		}
		if (modString.length > 1) {
			if (paras.length > 1) {
				paras += "&";
			}
			paras += modString;
		}
	}
	window.location = paras;
}

function populateCivSelect () {
	var civList = [];
	var civSelect = document.getElementById('civSelect');
	for (var civ in g_civs) {
		civList.push({
			"name": g_civs[civ].name
		,	"code":	civ
		,	"culture": g_civs[civ].culture
		});
	}
	civList.sort(sortNameIgnoreCase);
	for (var civ in civList) {
		civ = civList[civ];
		var newOpt = document.createElement('option');
		newOpt.text = civ.name;
		newOpt.value = civ.code;
		civSelect.appendChild(newOpt);
	}
	document.getElementById('selectDiv').style.display = "block";
}

function draw ()
{
	console.log("(draw) Drawing tech tree.");
	document.getElementById('renderBanner').innerHTML = "Drawing...";
	document.getElementById('renderBanner').style.display = "block";
	g_canvas.node.style.display = "none";
	
	// Clear canvas
	g_canvas.clear();
	g_canvasParts["banner"] = g_canvas.group();
	g_canvasParts["banner"].attr('id', "tree__banner");
	g_canvasParts["tree"] = g_canvas.group();
	g_canvasParts["tree"].attr('id', "tree__tree").y(80+8);
	
	g_canvas.gradient('radial', function (stop) {
		stop.at({
			"offset" : 0
		,	"color"  : "rgb(0, 0, 0)"
		,	"opacity": 0
		});
		stop.at({
			"offset" : 1
		,	"color"  : "rgb(0, 0, 0)"
		,	"opacity": 0.8
		});
	}).radius(0.9).attr({"id": "gradient__box"});
	
	
	// Title
	if (g_civs[g_selectedCiv].emblem == "placeholder") {
		var civEmblem = "./ui/emblem_placeholder.png";
	} else {
		var civEmblem = "./mods/"+g_civs[g_selectedCiv].sourceMod+"/art/textures/ui/"+g_civs[g_selectedCiv].emblem;
	}
	civEmblem = g_canvasParts["banner"].image(civEmblem);
	civEmblem.attr({
		'x': -16
	,	'y': -32
//	,	'height': 80
//	,	'width': 80
	});
	var civName = g_canvasParts["banner"].text(g_civs[g_selectedCiv].name);
	civName.attr({
		'x': 120
	,	'y': 24
	,	'font-size': 24
	,	'leading': 1
	,	'fill': '#fff'
	});
	
	
	var treeHeight = 0;
	var treeWidth = 0;
	var colGap = 32;
	var fullWid = 0;
	
//	box_structure(g_civs[g_selectedCiv].startBuilding, 4, 4, colWid-colGap, g_canvasParts["tree"]);
	
	for (var phase in g_phaseList) {
		var phaseStr = g_phaseList[phase];
		g_canvasParts["tree"][phaseStr] = g_canvasParts["tree"].group().attr('id', 'tree__'+phaseStr).move(16, treeHeight);
		g_canvasParts["tree"][phaseStr]["bands"] = [];
		
		// phase icon
		g_canvasParts["tree"][phaseStr].icon(g_phases[phaseStr].icon, 52, 'phase').move(
			8
		,	36
		);
		
		// horizontal banding
		for (var p = (+phase+1); p < g_phaseList.length; p++) {
			g_canvasParts["tree"][phaseStr]["bands"].push(g_canvasParts["tree"][phaseStr].rect(1024, 28). attr({
				'x': 40
			,	'y': 95 + (p-phase) * 30
			,	'stroke-opacity': 0
			,	'fill': '#888'
			,	'fill-opacity': 0.3
			}));
			
		//	if (p != phase) {
				g_canvasParts["tree"][phaseStr].icon(g_phases[g_phaseList[p]].icon, 24, 'phase').move(
					3 + 40
				,	3 + (p-phase) * 30 + 95
				);
		//	}
		}
		
		var rowWidth = 0;
		
		// buildings
		for (build in g_civs[g_selectedCiv].buildList[phaseStr]) {
			build = g_civs[g_selectedCiv].buildList[phaseStr][build];
			
		/*	if (build == g_civs[g_selectedCiv].startBuilding) {
				continue;
			}	*/
			
			var box = g_canvasParts["tree"][phaseStr].building(build).move(rowWidth+80, 0);
			
			rowWidth += box.width + colGap;
		}
		
		if (rowWidth > treeWidth) {
			treeWidth = rowWidth;
		}
		
		if (box !== undefined) {
			treeHeight += box.height + 16;
		} else {
			treeHeight += 192;
		}
	}
	
	// set band widths
	for (var phase in g_phaseList) {
		
		for (var band in g_canvasParts["tree"][g_phaseList[phase]]["bands"])
		{
			g_canvasParts["tree"][g_phaseList[phase]]["bands"][band].width(treeWidth+32);
		}
	}
	
	g_canvas.node.style.display = "block";
	document.getElementById('renderBanner').style.display = "none";
	resizeDrawing();
}

SVG.UI_Building = SVG.invent({
	create: function (id) {
		this.constructor.call(this, SVG.create('g'));
		this.attr('id', 'box__'+id);
		
		var info = g_structures[id];
		var fontSize = 14;
		var h = 2;
		var w = 32 * 2 + 128;
		var prodIconDimen = 24;
		var prodIconPadd = 4;
		
		var frame = this.rect().attr({
			'y': 16
		,	'width': w
		,	'height': 64
		,	'class': 'building_frame'
		,	'fill': 'url(#gradient__box)'
		});
		
		var ui_bar = this.image("./ui/gui2/border.png").y(12);
		
		var ui_specificName = this.title(info.specificName);
		h = 32;
		
		var ui_icon = this.icon(info.icon, 48, 'building').y(h);
		h += 48;
		
		var ui_genericName = this.text((g_args.debug)?id:info.genericName).attr({
			'leading': 0.7
		,	'class': 'building_subtitle'
		}).font({
			'anchor': 'middle'
		}).y(h);
		h += 16;
		
		h += 2;
		
		var prodGroup = this.group().y(h).attr({
			'id': "box__"+id+"__production"
		});
		prodGroup.rows = [];
		prodGroup.width = 0;
		
		var sph = g_phaseList.indexOf(info.phase);
		for (var ph = sph; ph < g_phaseList.length; ph++) {
			phStr = g_phaseList[ph];
			
			prodGroup.rows[ph] = {
				"count": 0,
				"row": prodGroup.group().y((ph-sph) * (prodIconDimen + 6)).attr('id', "row__"+id+"__"+phStr)
			};
			
			/* Set unit icons */
			if (Object.keys(info.production.units).length > 0 && info.production.units[phStr] !== undefined) {
				for (var u in info.production.units[phStr]) {
					
					var uStr = info.production.units[phStr][u];
					var uInfo = g_units[uStr];
					
					if (uInfo === undefined) {
						console.log(uStr);
					}
					
					prodGroup.rows[ph].row.icon(uInfo.icon, prodIconDimen, 'unit').x(
						prodGroup.rows[ph].count * (prodIconDimen + prodIconPadd)
					);
					
					prodGroup.rows[ph].count++;
				}
			}
			
			// WallSet icons - We only show gate and tower
			if (info.wallset !== undefined && info.phase == phStr) {
				
				var pos = info.wallset["Gate"].indexOf("/") + 1;
				
				var sStr = info.wallset["Gate"].slice(pos);
				var sInfo = g_structures[sStr];
				
				prodGroup.rows[ph].row.icon(sInfo.icon, prodIconDimen, 'struct').x(
					prodGroup.rows[ph].count * (prodIconDimen + prodIconPadd)
				);
				
				prodGroup.rows[ph].count++;
				
				var sStr = info.wallset["Tower"].slice(pos);
				var sInfo = g_structures[sStr];
				
				prodGroup.rows[ph].row.icon(sInfo.icon, prodIconDimen, 'struct').x(
					prodGroup.rows[ph].count * (prodIconDimen + prodIconPadd)
				);
				
				prodGroup.rows[ph].count++;
				
			}
			
			/* Set tech icons */
			if (Object.keys(info.production.technology).length > 0 && info.production.technology[phStr] !== undefined) {
				for (var t in info.production.technology[phStr]) {
					
					var tStr = info.production.technology[phStr][t];
					if (tStr.slice(0, 5) === "phase") {
						var tInfo = g_phases[tStr];
					} else {
						var tInfo = g_techs[tStr];
					}
					
					if (tInfo === undefined) {
						console.log(tStr);
					}
					
					prodGroup.rows[ph].row.icon(tInfo.icon, prodIconDimen, 'tech').x(
						prodGroup.rows[ph].count * (prodIconDimen + prodIconPadd)
					);
					prodGroup.rows[ph].count++;
				};
			}
			
			if (prodGroup.rows[ph].count > prodGroup.width)
				prodGroup.width = prodGroup.rows[ph].count;
		}
		
		// centre production rows and widen box if neccesary
		prodGroup.width = prodGroup.width * (prodIconDimen+prodIconPadd) + prodIconPadd;
		for (var row in prodGroup.rows) {
			row = prodGroup.rows[row];
			var rw = row.count * (prodIconDimen+prodIconPadd) - prodIconPadd;
			rw = (prodGroup.width - rw) / 2;
			row.row.x(rw);
		}
		if (prodGroup.width > w) {
			this.width = prodGroup.width;
			frame.width(this.width);
		} else {
			this.width = w;
		}
		var m = (this.width - prodGroup.width) / 2;
		prodGroup.x(m);
		
	//	ui_bar.width(this.width).height(8);
		ui_bar.transform({
			'scaleX': this.width / 2048
		,	'scaleY': 1
		});
		
		m = (this.width - (26*2 + 128)) / 2;
		ui_specificName.x(m);
		
		ui_genericName.x(this.width/2);
		
		m = (this.width - 48) / 2;
		ui_icon.x(m);
		
		// set final box height
		h += (g_phaseList.length - g_phaseList.indexOf(info.phase)) * (prodIconDimen + 6);
		frame.height(h-16);
		this.height = h;
	},
	inherit: SVG.G,
	construct: {
			building: function (struct) {
				return this.put(new SVG.UI_Building(struct));
			}
		}
});

SVG.UI_Title = SVG.invent({
	create: function (text) {
		this.constructor.call(this, SVG.create('g'));
		
		this.image("./ui/gui2/titlebar-middle.png").x(26);
		this.image("./ui/gui2/titlebar-left.png");
		this.image("./ui/gui2/titlebar-left.png").x(-(26+122)).transform({'scaleX': -1, 'x':32});
		
		this.text(text).font({
			'anchor': 'middle'
		}).attr(
			'class', 'building_title'
		).x(90);
	},
	inherit: SVG.G,
	construct: {
		title: function (text) {
			return this.put(new SVG.UI_Title(text));
		}
	}
});

SVG.Icon = SVG.invent({
	create: function (img, dimen, col) {
		this.constructor.call(this, SVG.create('g'));
		this.attr('id', 'icon__'+img[0].slice(img[0].indexOf("/")+1, img[0].indexOf(".")));
		dimen -= 2;
		this.rect(dimen, dimen).attr({
			'class' : 'icon icon_' + col
		});
		this.deriveIcon(img[1], img[0]);
		this.image(this.icon, dimen);
	},
	inherit: SVG.G,
	extend: {
		deriveIcon: function (mod, img) {
			if (img === "placeholder") {
				this.icon = "./ui/icon_placeholder.png";
			} else {
				this.icon = "./mods/"+ mod +"/art/textures/ui/session/portraits/"+ img;
			}
			return this;
		},
		setIcon: function (img) {
			this.deriveImage(img[1], img[0]);
			this._children[1].load(this.icon);
			return this;
		}
	},
	construct: {
			icon: function (img, dimen, col) {
				return this.put(new SVG.Icon(img, dimen, col));
			}
		}
});

function dePath (techCode) {
	var ret = "";
	if (techCode.indexOf("/") === -1) {
		ret = techCode;
	} else {
		ret = techCode.slice(techCode.indexOf("/")+1);
	}
	console.log(ret);
	return ret;
}

function resizeDrawing ()
{
	var bbox = g_canvas.bbox();
	g_canvas.node.style.width = ((bbox.x2 > window.innerWidth-16) ? Math.round(bbox.x2) + 2 : window.innerWidth-16) + "px";
	g_canvas.node.style.height = Math.round(bbox.y2) + 2 + "px";
//	document.body.style.width = g_canvas.node.style.width;
}

// A sorting function for arrays of objects with 'name' properties, ignoring case
function sortNameIgnoreCase(x, y)
{
	var lowerX = x.name.toLowerCase();
	var lowerY = y.name.toLowerCase();
	
	if (lowerX < lowerY)
		return -1;
	else if (lowerX > lowerY)
		return 1;
	else
		return 0;
}
