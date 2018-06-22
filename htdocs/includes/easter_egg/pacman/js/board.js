var BOARD_CANVAS_CONTEXT = null;

function initBoard() { 
	var canvas = document.getElementById('canvas-board');
	canvas.setAttribute('width', '550');
	canvas.setAttribute('height', '550');
	if (canvas.getContext) { 
		BOARD_CANVAS_CONTEXT = canvas.getContext('2d');
	}
}

function getBoardCanevasContext() { 
	return BOARD_CANVAS_CONTEXT;
}

function drawBoard(alternate) { 
	var ctx = getBoardCanevasContext();
	
	if (alternate && alternate === true) { 
		ctx.strokeStyle = "#fff";
	} else { 
		ctx.strokeStyle = "#193fff";
	}
	
	ctx.lineWidth = "2";
	ctx.beginPath();
	
	// UP 
	ctx.moveTo(0, 231);
	ctx.lineTo(97, 231);
	ctx.arcTo(99, 231, 99, 228, 5);
	ctx.lineTo(99, 179);
	ctx.arcTo(99, 176, 97, 176, 5);
	ctx.lineTo(12, 176);		
	ctx.arcTo(1, 176, 1, 165, 12);
	ctx.lineTo(1, 10);		
	ctx.arcTo(1, 1, 10, 1, 12);
	ctx.lineTo(536, 1);
	ctx.arcTo(547, 1, 547, 12, 12);
	ctx.lineTo(547, 165);
	ctx.arcTo(547, 176, 536, 176, 12);
	ctx.lineTo(452, 176);
	ctx.arcTo(449, 176, 449, 179, 5);
	ctx.lineTo(449, 228);
	ctx.arcTo(449, 231, 451, 231, 5);
	ctx.lineTo(550, 231);
	ctx.stroke();
	
	ctx.moveTo(0, 238);
	ctx.lineTo(98, 238);
	ctx.arcTo(106, 238, 106, 232, 12);
	ctx.lineTo(106, 179);
	ctx.arcTo(106, 169, 101, 169, 12);
	ctx.lineTo(15, 169);
	ctx.arcTo(9, 169, 9, 158, 5);
	ctx.lineTo(9, 15);
	ctx.arcTo(9, 7, 18, 7, 5);
	ctx.lineTo(259, 7);
	ctx.arcTo(266, 7, 266, 11, 5);
	ctx.lineTo(266, 73);
	ctx.arcTo(266, 78, 269, 78, 5);
	ctx.lineTo(280, 78);
	ctx.arcTo(283, 78, 283, 75, 5);
	ctx.lineTo(283, 15);
	ctx.arcTo(283, 7, 286, 7, 5);
	ctx.lineTo(533, 7);
	ctx.arcTo(540, 7, 540, 11, 5);
	ctx.lineTo(540, 165);
	ctx.arcTo(540, 169, 533, 169, 5);
	ctx.lineTo(452, 169);
	ctx.arcTo(442, 169, 442, 172, 12);
	ctx.lineTo(442, 230);
	ctx.arcTo(442, 238, 445, 238, 12);
	ctx.lineTo(550, 238);
	ctx.stroke();
	
	// DOWN 
	ctx.moveTo(0, 283);
	ctx.lineTo(97, 283);
	ctx.arcTo(99, 283, 99, 286, 5);
	ctx.lineTo(99, 335);
	ctx.arcTo(99, 338, 96, 338, 5);
	ctx.lineTo(9, 338);
	ctx.arcTo(1, 338, 1, 341, 12);
	ctx.lineTo(1, 540);
	ctx.arcTo(1, 548, 9, 548, 12);
	ctx.lineTo(539, 548);
	ctx.arcTo(547, 548, 547, 540, 12);
	ctx.lineTo(547, 345);
	ctx.arcTo(547, 337, 539, 337, 12);
	ctx.lineTo(453, 337);
	ctx.arcTo(450, 337, 450, 334, 5);
	ctx.lineTo(450, 286);
	ctx.arcTo(450, 283, 453, 283, 5);
	ctx.lineTo(550, 283);
	ctx.stroke();
	
	ctx.moveTo(0, 276);
	ctx.lineTo(99, 276);
	ctx.arcTo(107, 276, 107, 282, 12);
	ctx.lineTo(107, 335);
	ctx.arcTo(107, 345, 101, 345, 12);
	ctx.lineTo(12, 345);
	ctx.arcTo(9, 345, 9, 348, 5);
	ctx.lineTo(9, 432);
	ctx.arcTo(9, 435, 12, 435, 5);
	ctx.lineTo(44, 435);
	ctx.arcTo(47, 435, 47, 438, 5);
	ctx.lineTo(47, 448);
	ctx.arcTo(47, 451, 44, 451, 5);
	ctx.lineTo(12, 451);
	ctx.arcTo(9, 451, 9, 454, 5);
	ctx.lineTo(9, 538);
	ctx.arcTo(9, 541, 12, 541, 5);
	ctx.lineTo(536, 541);
	ctx.arcTo(539, 541, 539, 538, 5);
	ctx.lineTo(539, 455);
	ctx.arcTo(539, 451, 536, 451, 5);
	ctx.lineTo(505, 451);
	ctx.arcTo(502, 451, 502, 448, 5);
	ctx.lineTo(502, 439);
	ctx.arcTo(502, 436, 505, 436, 5);
	ctx.lineTo(536, 436);
	ctx.arcTo(539, 433, 539, 430, 5);
	ctx.lineTo(539, 349);
	ctx.arcTo(539, 345, 536, 345, 5);
	ctx.lineTo(455, 345);
	ctx.arcTo(442, 345, 442, 342, 12);
	ctx.lineTo(442, 288);
	ctx.arcTo(442, 276, 448, 276, 12);
	ctx.lineTo(550, 276);
	ctx.stroke();
	
	// LEFT
	ctx.roundRect(50, 45, 107, 79, 5);
	ctx.stroke();
	ctx.roundRect(50, 116, 107, 132, 5);
	ctx.stroke();
	ctx.roundRect(148, 45, 224, 79, 5);
	ctx.stroke();
	ctx.roundRect(148, 382, 224, 398, 5);
	ctx.stroke();
	ctx.roundRect(148, 276, 165, 345, 5);
	ctx.stroke();
	
	ctx.moveTo(56, 504);
	ctx.lineTo(220, 504);
	ctx.arcTo(223, 504, 223, 501, 5);
	ctx.lineTo(223, 492);
	ctx.arcTo(223, 489, 220, 489, 5);
	ctx.lineTo(168, 489);
	ctx.arcTo(165, 489, 165, 486, 5);
	ctx.lineTo(165, 439);		
	ctx.arcTo(165, 436, 162, 436, 5);
	ctx.lineTo(152, 436);
	ctx.arcTo(149, 436, 149, 439, 5);
	ctx.lineTo(149, 486);		
	ctx.arcTo(149, 489, 146, 489, 5);
	ctx.lineTo(54, 489);
	ctx.arcTo(51, 489, 51, 492, 5);
	ctx.lineTo(51, 501);
	ctx.arcTo(51, 504, 54, 504, 5);
	ctx.stroke();
	
	ctx.moveTo(55, 382);
	ctx.lineTo(104, 382);
	ctx.arcTo(107, 382, 107, 385, 5);
	ctx.lineTo(107, 447);
	ctx.arcTo(107, 450, 104, 450, 5);
	ctx.lineTo(93, 450);
	ctx.arcTo(90, 450, 90, 447, 5);
	ctx.lineTo(90, 401);
	ctx.arcTo(90, 398, 87, 398, 5);
	ctx.lineTo(55, 398);
	ctx.arcTo(52, 398, 52, 395, 5);
	ctx.lineTo(52, 385);
	ctx.arcTo(52, 382, 55, 382, 5);
	ctx.stroke();
	
	ctx.moveTo(148, 121);
	ctx.lineTo(148, 236);		
	ctx.arcTo(148, 239, 151, 239, 5);
	ctx.lineTo(162, 239);
	ctx.arcTo(165, 239, 165, 236, 5);
	ctx.lineTo(165, 188);
	ctx.arcTo(165, 185, 168, 185, 5);
	ctx.lineTo(221, 185);
	ctx.arcTo(224, 185, 224, 182, 5);
	ctx.lineTo(224, 173);
	ctx.arcTo(224, 170, 221, 170, 5);
	ctx.lineTo(168, 170);
	ctx.arcTo(165, 170, 165, 169, 5);
	ctx.lineTo(165, 120);
	ctx.arcTo(165, 117, 162, 117, 5);
	ctx.lineTo(151, 117);
	ctx.arcTo(148, 117, 148, 120, 5);
	ctx.stroke();
	
	// RIGHT
	ctx.roundRect(442, 45, 498, 79, 5);
	ctx.stroke();
	ctx.roundRect(442, 116, 498, 132, 5);
	ctx.stroke();
	ctx.roundRect(324, 45, 400, 79, 5);
	ctx.stroke();
	ctx.roundRect(324, 382, 400, 398, 5);
	ctx.stroke();
	ctx.roundRect(383, 276, 400, 345, 5);
	ctx.stroke();
	
	ctx.moveTo(330, 504);
	ctx.lineTo(494, 504);
	ctx.arcTo(497, 504, 497, 501, 5);
	ctx.lineTo(497, 492);
	ctx.arcTo(497, 489, 494, 489, 5);
	ctx.lineTo(403, 489);
	ctx.arcTo(400, 489, 400, 486, 5);
	ctx.lineTo(400, 441);
	ctx.arcTo(397, 436, 394, 436, 5);
	ctx.lineTo(386, 436);
	ctx.arcTo(383, 436, 383, 439, 5);
	ctx.lineTo(383, 486);
	ctx.arcTo(383, 489, 380, 489, 5);
	ctx.lineTo(328, 489);
	ctx.arcTo(325, 489, 325, 492, 5);
	ctx.lineTo(325, 500);
	ctx.arcTo(325, 504, 328, 504, 5);
	ctx.stroke();
	
	ctx.moveTo(495, 382);
	ctx.lineTo(445, 382);
	ctx.arcTo(442, 382, 442, 385, 5);
	ctx.lineTo(442, 447);
	ctx.arcTo(442, 450, 445, 450, 5);
	ctx.lineTo(456, 450);
	ctx.arcTo(459, 450, 459, 447, 5);
	ctx.lineTo(459, 401);
	ctx.arcTo(459, 398, 462, 398, 5);
	ctx.lineTo(495, 398);
	ctx.arcTo(498, 398, 498, 395, 5);
	ctx.lineTo(498, 385);
	ctx.arcTo(498, 382, 495, 382, 5);
	ctx.stroke();
	
	ctx.moveTo(400, 121);
	ctx.lineTo(400, 236);
	ctx.arcTo(400, 239, 397, 239, 5);
	ctx.lineTo(386, 239);
	ctx.arcTo(383, 239, 383, 236, 5);
	ctx.lineTo(383, 188);
	ctx.arcTo(383, 185, 380, 185, 5);
	ctx.lineTo(328, 185);
	ctx.arcTo(325, 185, 325, 182, 5);
	ctx.lineTo(325, 173);
	ctx.arcTo(325, 170, 328, 170, 5);
	ctx.lineTo(380, 170);
	ctx.arcTo(383, 170, 383, 169, 5);
	ctx.lineTo(383, 120);
	ctx.arcTo(383, 117, 385, 117, 5);
	ctx.lineTo(397, 117);
	ctx.arcTo(400, 117, 400, 120, 5);
	ctx.stroke();
	
	// CENTER
	ctx.moveTo(212, 117);
	ctx.lineTo(338, 117);
	ctx.arcTo(341, 120, 341, 123, 5);
	ctx.lineTo(341, 129);
	ctx.arcTo(341, 132, 338, 132, 5);
	ctx.lineTo(286, 132);
	ctx.arcTo(283, 132, 283, 135, 5);
	ctx.lineTo(283, 182);
	ctx.arcTo(283, 185, 280, 185, 5);
	ctx.lineTo(269, 185);
	ctx.arcTo(266, 185, 266, 182, 5);
	ctx.lineTo(266, 135);
	ctx.arcTo(266, 132, 262, 132, 5);
	ctx.lineTo(211, 132);
	ctx.arcTo(208, 132, 208, 129, 5);
	ctx.lineTo(208, 120);
	ctx.arcTo(208, 117, 211, 117, 5);
	ctx.stroke();

	ctx.moveTo(212, 329);
	ctx.lineTo(338, 329);
	ctx.arcTo(341, 332, 341, 335, 5);
	ctx.lineTo(341, 341);
	ctx.arcTo(341, 344, 338, 344, 5);
	ctx.lineTo(286, 344);
	ctx.arcTo(283, 344, 283, 347, 5);
	ctx.lineTo(283, 394);
	ctx.arcTo(283, 397, 280, 397, 5);
	ctx.lineTo(269, 397);
	ctx.arcTo(266, 397, 266, 394, 5);
	ctx.lineTo(266, 347);
	ctx.arcTo(266, 344, 262, 344, 5);
	ctx.lineTo(211, 344);
	ctx.arcTo(208, 344, 208, 129, 5);
	ctx.lineTo(208, 332);
	ctx.arcTo(208, 329, 211, 329, 5);
	ctx.stroke();

	ctx.moveTo(212, 436);
	ctx.lineTo(338, 436);
	ctx.arcTo(341, 439, 341, 442, 5);
	ctx.lineTo(341, 448);
	ctx.arcTo(341, 451, 338, 451, 5);
	ctx.lineTo(286, 451);
	ctx.arcTo(283, 451, 283, 454, 5);
	ctx.lineTo(283, 501);
	ctx.arcTo(283, 503, 280, 503, 5);
	ctx.lineTo(269, 503);
	ctx.arcTo(266, 503, 266, 501, 5);
	ctx.lineTo(266, 454);
	ctx.arcTo(266, 451, 262, 451, 5);
	ctx.lineTo(211, 451);
	ctx.arcTo(208, 451, 208, 236, 5);
	ctx.lineTo(208, 439);
	ctx.arcTo(208, 436, 211, 436, 5);
	ctx.stroke();
	
	ctx.moveTo(254, 223);
	ctx.lineTo(207, 223);
	ctx.lineTo(207, 292);
	ctx.lineTo(342, 292);
	ctx.lineTo(342, 223);
	ctx.lineTo(296, 223);
	ctx.lineTo(296, 230);
	ctx.lineTo(334, 230);
	ctx.lineTo(334, 284);
	ctx.lineTo(214, 284);
	ctx.lineTo(214, 230);
	ctx.lineTo(254, 230);
	ctx.lineTo(254, 223);
	ctx.stroke();
	
	ctx.closePath();
}

function drawBoardDoor() { 
	var ctx = getBoardCanevasContext();
	
	ctx.strokeStyle = "white";
	ctx.lineWidth = "5";
	
	ctx.beginPath();
	ctx.moveTo(255, 226);
	ctx.lineTo(295, 226);
	ctx.stroke();
	
	ctx.closePath();
}
function eraseBoardDoor() { 
	var ctx = getBoardCanevasContext();
	//ctx.translate(FRUITS_POSITION_X - (FRUITS_SIZE / 2), FRUITS_POSITION_Y - (FRUITS_SIZE / 2));
	//ctx.save();
	//ctx.globalCompositeOperation = "destination-out";
	
	//ctx.beginPath();
	//ctx.translate(FRUITS_POSITION_X - (FRUITS_SIZE / 2), FRUITS_POSITION_Y - (FRUITS_SIZE / 2));
	ctx.clearRect(255, 220, 40, 10);
	//ctx.fill();
	//ctx.closePath();
	
	//ctx.restore();
}