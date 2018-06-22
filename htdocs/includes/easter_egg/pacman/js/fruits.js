var FRUITS_CANVAS_CONTEXT = null;
var LEVEL_FRUITS_CANVAS_CONTEXT = null;
var FRUITS_SIZE = 30;

var FRUITS_POSITION_X = 276;
var FRUITS_POSITION_Y = 310;

var FRUIT_MINIMUM_START = 15;
var FRUIT_CANCEL_TIMER = null;
var FRUIT_CANCEL_SPEED = 7500;
var FRUIT = null;


function initFruits() { 
	var canvas = document.getElementById('canvas-fruits');
	canvas.setAttribute('width', '550');
	canvas.setAttribute('height', '550');
	if (canvas.getContext) { 
		FRUITS_CANVAS_CONTEXT = canvas.getContext('2d');
	}
	
	var levelCanvas = document.getElementById('canvas-level-fruits');
	levelCanvas.setAttribute('width', '265');
	levelCanvas.setAttribute('height', '30');
	if (levelCanvas.getContext) { 
		LEVEL_FRUITS_CANVAS_CONTEXT = levelCanvas.getContext('2d');
	}
	
	var ctx = getLevelFruitsCanevasContext();
	ctx.clearRect(0, 0, 265, 30);
	
	var x = 245;
	var y = 14;
	var i = 0;
	
	if (LEVEL > 7) { 
		var l = LEVEL
		if (l > 13) l = 13;
		i = -(l - 7);
	}
	
	drawFruit(ctx, "cherry", x - ( i * 37), y, 27);
	i ++;
	
	if (LEVEL > 1) { 
		drawFruit(ctx, "strawberry", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 2) { 
		drawFruit(ctx, "orange", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 3) { 
		drawFruit(ctx, "orange", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 4) { 
		drawFruit(ctx, "apple", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 5) { 
		drawFruit(ctx, "apple", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 6) { 
		drawFruit(ctx, "melon", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 7) { 
		drawFruit(ctx, "melon", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 8) { 
		drawFruit(ctx, "galboss", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 9) { 
		drawFruit(ctx, "galboss", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 10) { 
		drawFruit(ctx, "bell", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 11) { 
		drawFruit(ctx, "bell", x - ( i * 37), y, 27);
		i ++;
	}
	if (LEVEL > 12) { 
		drawFruit(ctx, "key", x - ( i * 37), y, 27);
		i ++;
	}
}

function getFruitsCanevasContext() { 
	return FRUITS_CANVAS_CONTEXT;
}
function getLevelFruitsCanevasContext() { 
	return LEVEL_FRUITS_CANVAS_CONTEXT;
}

function eatFruit() { 
	playEatFruitSound();
	
	var s = 0;
	if (FRUIT === "cherry")  s = 100;
	else if (FRUIT === "strawberry")  s = 300;
	else if (FRUIT === "orange")  s = 500;
	else if (FRUIT === "apple")  s = 700;
	else if (FRUIT === "melon")  s = 1000;
	else if (FRUIT === "galboss")  s = 2000;
	else if (FRUIT === "bell")  s = 3000;
	else if (FRUIT === "key")  s = 5000;
	
	score(s, "fruit");
	cancelFruit();
}

function fruit() { 
	
	if (TIME_FRUITS === 2 && $("#board .fruits").length > 0) { 
		$("#board .fruits").remove();
	}
	if (TIME_FRUITS > FRUIT_MINIMUM_START) { 
		if (anyGoodIdea() > 3) { 
			oneFruit();
		}
	}
}
function oneFruit() { 
	if ( FRUIT_CANCEL_TIMER === null ) { 
		var ctx = getFruitsCanevasContext();
		
		if (LEVEL === 1) FRUIT = "cherry";
		else if (LEVEL === 2) FRUIT = "strawberry";
		else if (LEVEL === 3 || LEVEL === 4) FRUIT = "orange";
		else if (LEVEL === 5 || LEVEL === 6) FRUIT = "apple";
		else if (LEVEL === 7 || LEVEL === 8) FRUIT = "melon";
		else if (LEVEL === 9 || LEVEL === 10) FRUIT = "galboss";
		else if (LEVEL === 11 || LEVEL === 12) FRUIT = "bell";
		else if (LEVEL === 13) FRUIT = "key";
		
		drawFruit(ctx, FRUIT, FRUITS_POSITION_X, FRUITS_POSITION_Y, FRUITS_SIZE);
		FRUIT_CANCEL_TIMER = new Timer("cancelFruit()", FRUIT_CANCEL_SPEED);
	}
}
function cancelFruit() { 
	eraseFruit();
	FRUIT_CANCEL_TIMER.cancel();
	FRUIT_CANCEL_TIMER = null;
	TIME_FRUITS = 0;
}

function eraseFruit() { 

	var ctx = getFruitsCanevasContext();
	//ctx.translate(FRUITS_POSITION_X - (FRUITS_SIZE / 2), FRUITS_POSITION_Y - (FRUITS_SIZE / 2));
	//ctx.save();
	//ctx.globalCompositeOperation = "destination-out";
	
	//ctx.beginPath();
	//ctx.translate(FRUITS_POSITION_X - (FRUITS_SIZE / 2), FRUITS_POSITION_Y - (FRUITS_SIZE / 2));
	ctx.clearRect(FRUITS_POSITION_X - (FRUITS_SIZE), FRUITS_POSITION_Y - (FRUITS_SIZE), FRUITS_SIZE * 2, FRUITS_SIZE * 2);
	//ctx.fill();
	//ctx.closePath();
	
	//ctx.restore();
}

function drawFruit(ctx, f, x, y, size) {  
	ctx.save();

	if ( f === "cherry" ) drawCherry(ctx, x, y, size);
	else if ( f === "strawberry" ) drawStrawberry(ctx, x, y, size);
	else if ( f === "orange" ) drawOrange(ctx, x, y, size);
	else if ( f === "apple" ) drawApple(ctx, x, y, size);
	else if ( f === "melon" ) drawMelon(ctx, x, y + 7, size / 1.6);
	else if ( f === "galboss" ) drawGalboss(ctx, x, y, size);
	else if ( f === "bell" ) drawBell(ctx, x, y, size);
	else if ( f === "key" ) drawKey(ctx, x, y, size);
	
	ctx.restore();
}

function drawKey(ctx, x, y, size) { 
	ctx.translate(x - (size / 2), y - (size / 2));
	
	ctx.fillStyle = "#52c4cc";
	ctx.beginPath();
	ctx.rect(size / 3, 5, (size - (size / 3)), size / 3);
	ctx.rect((size / 6) * 3, 2, (size - ((size / 3) * 2)), size / (size / 3));
	ctx.fill();
	
	ctx.fillStyle = "#000";
	ctx.beginPath();
	ctx.rect((size / 6) * 3, (size / 6), (size - ((size / 3) * 2)), size / 10);
	ctx.fill();
	
	ctx.strokeStyle = "#ccc";
	ctx.lineWidth = "3";
	
	ctx.beginPath();
	ctx.moveTo((size / 2) + 2, size - 4);
	ctx.lineTo((size / 2) + 2, size / 2);
	ctx.stroke();
	
	ctx.beginPath();
	ctx.moveTo((size / 2) + 7, size - 4);
	ctx.lineTo((size / 2) + 7, size / 2);
	ctx.stroke();
	
	ctx.beginPath();
	ctx.moveTo((size / 2) + 4, size - 4);
	ctx.lineTo((size / 2) + 4, size - 1);
	ctx.stroke();
	
	ctx.beginPath();
	ctx.moveTo((size / 2) + 5, size - 4);
	ctx.lineTo((size / 2) + 5, size - 1);
	ctx.stroke();
	
	ctx.beginPath();
	ctx.moveTo((size / 2) + 9, (size / 2) + 2);
	ctx.lineTo((size / 2) + 9, (size / 2) + 5);
	ctx.stroke();
	
	ctx.beginPath();
	ctx.moveTo((size / 2) + 10, (size / 2) + 8);
	ctx.lineTo((size / 2) + 10, (size / 2) + 11);
	ctx.stroke();

	
	ctx.closePath();
}

function drawBell(ctx, x, y, size) { 

	ctx.translate(x - (size / 2), y - (size / 2));
	
	ctx.oval(size / 2, size / 2, size / 1, size - 5);
	ctx.fillStyle = "#fff200";
	ctx.fill();
	
	ctx.beginPath();
	ctx.rect(4, size - (size / 2.5) - 3, size - 8, (size / 2.5) - 1);
	ctx.fill();
	
	ctx.fillStyle = "#52c4cc";
	ctx.beginPath();
	ctx.rect(4 + 2, size - 6, (size - 12), 5);
	ctx.fill();
	
	ctx.fillStyle = "#8c8c8c";
	ctx.beginPath();
	ctx.rect(size / 2, size - 6, 5, 5);
	ctx.fill();
	
	ctx.closePath();
	
	ctx.strokeStyle = "#bbb";
	ctx.lineWidth = "2";
	ctx.beginPath();
	ctx.moveTo(15, 7);
	ctx.arcTo(8, 7, 8, 30, 9);
	ctx.stroke();
	
	ctx.closePath();
}
function drawGalboss(ctx, x, y, size) { 

	ctx.translate(x - (size / 2), y - (size / 2) + 1);

	ctx.strokeStyle = "#868df5";
	ctx.lineWidth = "5";
	
	ctx.beginPath();
	ctx.moveTo((size / 2), (size / 2) + (size / 4));
	ctx.arcTo(size - 1, (size / 2) + 4, size - 1, (size / 2) + 1, (size / 3));
	ctx.lineTo(size - 1, 4);
	ctx.stroke();
	ctx.closePath();
	
	ctx.beginPath();
	ctx.moveTo((size / 2), (size / 2) + (size / 4));
	ctx.arcTo(1, (size / 2) + 4, 1, (size / 2) + 1, (size / 3));
	ctx.lineTo(1, 4);
	ctx.stroke();
	ctx.closePath();
	
	ctx.strokeStyle = "#ffff00";
	ctx.lineWidth = "6";
	
	ctx.beginPath();
	ctx.moveTo(size / 2, (size / 2) - 2);
	ctx.lineTo(size / 2, size);
	ctx.stroke();
	
	ctx.fillStyle = "#ffff00";
	
	ctx.beginPath();
	ctx.arc((size / 2), size / 3.5, size / 2.5, 0, Math.PI * 1);
	ctx.fill();
	ctx.closePath();
	
	ctx.strokeStyle = "#000";
	ctx.lineWidth = "3";
	
	ctx.beginPath();
	ctx.moveTo(size / 2 - (size / 6), (size / 2) + 1);
	ctx.lineTo(size / 2 - (size / 6), size);
	ctx.stroke();
	
	ctx.beginPath();
	ctx.moveTo(size / 2 + (size / 6), (size / 2) + 1);
	ctx.lineTo(size / 2 + (size / 6), size);
	ctx.stroke();
	
	ctx.strokeStyle = "#ff3f3f";
	ctx.lineWidth = "4";
	
	ctx.beginPath();
	ctx.moveTo(size / 2, (size / 2));
	ctx.lineTo((size / 2), 2);
	ctx.stroke();
	
	ctx.moveTo((size / 2) + 1, 2);
	ctx.lineTo((size / 2) - 8, (size / 2) - (size / 6));
	ctx.stroke();
	
	ctx.moveTo((size / 2) - 1, 2);
	ctx.lineTo((size / 2) + 8, (size / 2) - (size / 6));
	ctx.stroke();
	
	ctx.closePath();
	
}
function drawMelon(ctx, x, y, size) { 

	ctx.translate(x - (size / 2), y - (size / 2));
	
	ctx.fillStyle = "#198122";
	ctx.beginPath();
	ctx.moveTo(size / 2, size / 6);
	ctx.arc(size / 2, size / 6, size / 1.15, 1.1, 2.5, true);
	ctx.fill();
	ctx.closePath();

	ctx.beginPath();
	ctx.fillStyle = "#ACFB77";
	ctx.moveTo(size / 2, size / 6);
	ctx.arc(size / 2, size / 6, size / 1.3, 1.1, 2.5, true);
	ctx.fill();
	ctx.closePath();

	ctx.beginPath();
	ctx.fillStyle = "#F92F2F";
	ctx.moveTo(size / 2, size / 6);
	ctx.arc(size / 2, size / 6, size / 1.7, 1.1, 2.5, true);
	ctx.fill();
	ctx.closePath();

	var mod = size / 23;
	ctx.beginPath();
	ctx.fillStyle = "black";
	ctx.moveTo(12 * mod, 9 * mod);
	ctx.arc(12 * mod, 9 * mod, size / 12, 1.1, 2.5, true);
	ctx.moveTo(13 * mod, 12 * mod);
	ctx.arc(13 * mod, 12 * mod, size / 12, 1.1, 2.5, true);
	ctx.moveTo(10.5 * mod, 12 * mod);
	ctx.arc(10.5 * mod, 12 * mod, size / 12, 1.1, 2.5, true);
	ctx.fill();
	ctx.closePath();
}
function drawApple(ctx, x, y, size) { 

	ctx.translate(x - (size / 2), y - (size / 2) - 2);
	
	ctx.fillStyle = "#ff0000";
	ctx.beginPath();
	ctx.arc(size / 2, size / 2 + size / 9, (size / 2.1), Math.PI * 2, -Math.PI * 2, true);
	ctx.fill();
	ctx.closePath();

	ctx.fillStyle = "#ff0000";
	ctx.beginPath();
	ctx.arc(9, size - 3, (size / 4.5), Math.PI * 2, -Math.PI * 2, true);
	ctx.arc(size - 8, size - 3, (size / 4.5), Math.PI * 2, -Math.PI * 2, true);
	ctx.fill();
	ctx.closePath();
	
	ctx.fillStyle = "black";
	ctx.beginPath();
	ctx.arc(size / 2, size / 6, (size / 7), Math.PI * 2, -Math.PI * 2, true);
	ctx.fill();
	ctx.closePath();

	var mod = size / 23;
	ctx.strokeStyle = "#24da1c";
	ctx.lineWidth = 2;
	ctx.beginPath();
	
	ctx.beginPath();
	ctx.moveTo(13 * mod + 2, (size / 9) + 4);
	ctx.lineTo( (13 * mod) - (size / 4), (size / 9) + 1);
	ctx.stroke();
	
	ctx.beginPath();
	ctx.moveTo(13 * mod + 2, (size / 9) + 4);
	ctx.lineTo( (13 * mod) - (size / 2.5), (size / 9) + 3);
	ctx.stroke();
	
	ctx.strokeStyle = "#bbb";
	ctx.lineWidth = "2";
	ctx.beginPath();
	ctx.moveTo(12, 11);
	ctx.arcTo(5, 11, 5, 30, 7);
	ctx.stroke();
	
	
	
	ctx.closePath();
}

function drawOrange(ctx, x, y, size) { 

	ctx.translate(x - (size / 2), y - (size / 2) - 1);

	ctx.fillStyle = "#fcb424";
	ctx.beginPath();
	ctx.arc(size / 2, size / 2 + size / 9, (size / 2.2), Math.PI * 2, -Math.PI * 2, true);
	ctx.fill();
	ctx.closePath();

	ctx.fillStyle = "black";
	ctx.beginPath();
	ctx.arc(size / 2, size / 6, (size / 7), Math.PI * 2, -Math.PI * 2, true);
	ctx.fill();
	ctx.closePath();

	var mod = size / 23;
	ctx.strokeStyle = "#24da1c";
	ctx.lineWidth = 2.5;
	ctx.beginPath();
	ctx.moveTo(size / 2, size / 3);
	ctx.lineTo(size / 2, size / 8);
	ctx.lineTo(9 * mod, size / 9);
	ctx.stroke();
	
	ctx.beginPath();
	ctx.moveTo(9 * mod, (size / 9));
	ctx.lineTo( (9 * mod) + (size / 3), (size / 9) - 2);
	ctx.stroke();
	
	ctx.closePath();
}

function drawStrawberry(ctx, x, y, size) { 
	
	ctx.translate(x - (size / 2), y - (size / 2) + 2);
	
	ctx.beginPath();
	ctx.fillStyle = "#ff0000";

	ctx.moveTo(size / 2, size - size / 18)
	ctx.bezierCurveTo(0, size / 1.3, 0, -size / 9, size / 2, size / 6)
	ctx.moveTo(size / 2, size - size / 18)
	ctx.bezierCurveTo(size, size / 1.3, size, -size / 9, size / 2, size / 6)
	
	ctx.fill();
	ctx.closePath();

	ctx.fillStyle = "white";

	ctx.fillRect(size / 4, size / 3, size / 18, size / 16)
	ctx.fillRect(size / 2, size / 4, size / 18, size / 16)
	ctx.fillRect(size - size / 3.5, size / 2.4, size / 18, size / 16)
	ctx.fillRect(size - size / 2.2, size / 2, size / 18, size / 16)
	ctx.fillRect(size / 2.6, size / 1.3, size / 18, size / 16)
	ctx.fillRect(size / 3, size / 1.8, size / 18, size / 16)
	ctx.fillRect(size / 1.6, size / 1.4, size / 18, size / 16)

	ctx.beginPath();
	ctx.fillStyle = "#24DA1D";

	var mod = size / 23;
	ctx.moveTo(6 * mod, 2 * mod);
	ctx.lineTo(1 * mod, 8 * mod);
	ctx.lineTo(6 * mod, 6 * mod);
	ctx.lineTo(11 * mod, 11 * mod);
	ctx.lineTo(16 * mod, 6 * mod);
	ctx.lineTo(21 * mod, 8 * mod);
	ctx.lineTo(17 * mod, 2 * mod);

	ctx.moveTo(size / 2, 2 * mod);
	ctx.lineTo(8 * mod, 0 * mod);
	ctx.lineTo(15 * mod, 0 * mod);
	ctx.lineTo(size / 2, 2 * mod);
	
	ctx.fill();
	ctx.closePath();
}
function drawCherry(ctx, x, y, size) {  
	
	ctx.translate(x - (size / 2), y - (size / 2) + 1);
	
	ctx.beginPath();
	ctx.fillStyle = "#ff0000";

	ctx.arc(size / 8, size - (size / 2.8), size / 4, Math.PI * 2, -Math.PI * 2, true);
	ctx.arc(size - size / 3, size - (size / 4), size / 4, Math.PI * 2, -Math.PI * 2, true);

	ctx.fill();
	ctx.closePath();

	ctx.beginPath();
	ctx.fillStyle = "#670303";

	ctx.arc(size / 7.2, size - (size / 2.25), size / 14, Math.PI * 2, -Math.PI * 2, true);
	ctx.arc(size - size / 3, size - (size / 3), size / 14, Math.PI * 2, -Math.PI * 2, true);

	ctx.fill();
	ctx.closePath();

	ctx.beginPath();
	ctx.strokeStyle = "#959817";
	ctx.lineWidth = 2;

	ctx.moveTo(size / 8, size - (size / 2));
	ctx.bezierCurveTo(size / 6, size / 1.5, size / 7, size / 4, size - size / 4, size / 8);
	ctx.moveTo(size - size / 2.5, size - size / 3);
	ctx.bezierCurveTo(size / 1.3, size / 1.5, size / 3, size / 2.5, size - size / 4, size / 8);

	ctx.stroke();
	ctx.closePath();

	ctx.fillStyle = "#959817";
	ctx.fillRect(size - size / 3, size / 12, size / 9, size / 9);
	ctx.closePath();
}
