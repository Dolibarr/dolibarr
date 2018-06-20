var PACMAN_DIRECTION = 3;
var PACMAN_DIRECTION_TRY = -1;
var PACMAN_DIRECTION_TRY_TIMER = null;
var PACMAN_DIRECTION_TRY_CANCEL = 1000;
var PACMAN_POSITION_X = 276;
var PACMAN_POSITION_Y = 416;
var PACMAN_POSITION_STEP = 2;
var PACMAN_MOUNTH_STATE = 3;
var PACMAN_MOUNTH_STATE_MAX = 6;
var PACMAN_SIZE = 16;
var PACMAN_MOVING = false;
var PACMAN_MOVING_TIMER = -1;
var PACMAN_MOVING_SPEED = 15;
var PACMAN_CANVAS_CONTEXT = null;
var PACMAN_EAT_GAP = 15;
var PACMAN_GHOST_GAP = 20;
var PACMAN_FRUITS_GAP = 15;
var PACMAN_KILLING_TIMER = -1;
var PACMAN_KILLING_SPEED = 70;
var PACMAN_RETRY_SPEED = 2100;
var PACMAN_DEAD = false;

function initPacman() { 
	var canvas = document.getElementById('canvas-pacman');
	canvas.setAttribute('width', '550');
	canvas.setAttribute('height', '550');
	if (canvas.getContext) { 
		PACMAN_CANVAS_CONTEXT = canvas.getContext('2d');
	}
}
function resetPacman() { 
	stopPacman();

	PACMAN_DIRECTION = 3;
	PACMAN_DIRECTION_TRY = -1;
	PACMAN_DIRECTION_TRY_TIMER = null;
	PACMAN_POSITION_X = 276;
	PACMAN_POSITION_Y = 416;
	PACMAN_MOUNTH_STATE = 3;
	PACMAN_MOVING = false;
	PACMAN_MOVING_TIMER = -1;
	PACMAN_KILLING_TIMER = -1;
	PACMAN_DEAD = false;
	PACMAN_SUPER = false;
}
function getPacmanCanevasContext() { 
	return PACMAN_CANVAS_CONTEXT;
}

function stopPacman() { 
	if (PACMAN_MOVING_TIMER != -1) { 
		clearInterval(PACMAN_MOVING_TIMER);
		PACMAN_MOVING_TIMER = -1;
		PACMAN_MOVING = false;
	}
	if (PACMAN_KILLING_TIMER != -1) { 
		clearInterval(PACMAN_KILLING_TIMER);
		PACMAN_KILLING_TIMER = -1;
	}
}

function pausePacman() { 
	if (PACMAN_DIRECTION_TRY_TIMER != null) { 
		PACMAN_DIRECTION_TRY_TIMER.pause();
	}
	
	if ( PACMAN_MOVING_TIMER != -1 ) { 
		clearInterval(PACMAN_MOVING_TIMER);
		PACMAN_MOVING_TIMER = -1;
		PACMAN_MOVING = false;
	}
}
function resumePacman() { 
	if (PACMAN_DIRECTION_TRY_TIMER != null) { 
		PACMAN_DIRECTION_TRY_TIMER.resume();
	}
	movePacman();
}

function tryMovePacmanCancel() { 
	if (PACMAN_DIRECTION_TRY_TIMER != null) { 
		PACMAN_DIRECTION_TRY_TIMER.cancel();
		PACMAN_DIRECTION_TRY = -1;
		PACMAN_DIRECTION_TRY_TIMER = null;
	}
}
function tryMovePacman(direction) { 
	PACMAN_DIRECTION_TRY = direction;
	PACMAN_DIRECTION_TRY_TIMER = new Timer('tryMovePacmanCancel()', PACMAN_DIRECTION_TRY_CANCEL);
}

function movePacman(direction) {

	if (PACMAN_MOVING === false) { 
		PACMAN_MOVING = true;
		drawPacman();
		PACMAN_MOVING_TIMER = setInterval('movePacman()', PACMAN_MOVING_SPEED);
	}
	
	var directionTry = direction;
	var quarterChangeDirection = false;
	
	if (!directionTry && PACMAN_DIRECTION_TRY != -1) { 
		directionTry = PACMAN_DIRECTION_TRY;
	}
	
	if ((!directionTry || PACMAN_DIRECTION !== directionTry)) { 
	
		if (directionTry) { 
			if (canMovePacman(directionTry)) { 
				if (PACMAN_DIRECTION + 1 === directionTry || PACMAN_DIRECTION - 1 === directionTry || PACMAN_DIRECTION + 1 === directionTry || (PACMAN_DIRECTION === 4 && directionTry === 1) || (PACMAN_DIRECTION === 1 && directionTry === 4) ) { 
					quarterChangeDirection = true;
				}
				PACMAN_DIRECTION = directionTry;
				tryMovePacmanCancel();
			} else { 
				if (directionTry !== PACMAN_DIRECTION_TRY) { 
					tryMovePacmanCancel();
				}
				if (PACMAN_DIRECTION_TRY === -1) { 
					tryMovePacman(directionTry);
				}
			}
		}

		if (canMovePacman(PACMAN_DIRECTION)) { 
			erasePacman();
			
			if (PACMAN_MOUNTH_STATE < PACMAN_MOUNTH_STATE_MAX) { 
				PACMAN_MOUNTH_STATE ++; 
			} else { 
				PACMAN_MOUNTH_STATE = 0; 
			}
						
			var speedUp = 0;
			if (quarterChangeDirection) { 
				speedUp = 6;
			}
			
			if ( PACMAN_DIRECTION === 1 ) { 
				PACMAN_POSITION_X += PACMAN_POSITION_STEP + speedUp;
			} else if ( PACMAN_DIRECTION === 2 ) { 
				PACMAN_POSITION_Y += PACMAN_POSITION_STEP + speedUp;
			} else if ( PACMAN_DIRECTION === 3 ) { 
				PACMAN_POSITION_X -= PACMAN_POSITION_STEP + speedUp;
			} else if ( PACMAN_DIRECTION === 4 ) { 
				PACMAN_POSITION_Y -= (PACMAN_POSITION_STEP + speedUp);
			}
			
			if ( PACMAN_POSITION_X === 2 && PACMAN_POSITION_Y === 258 ) { 
				PACMAN_POSITION_X = 548;
				PACMAN_POSITION_Y = 258;
			} else if ( PACMAN_POSITION_X === 548 && PACMAN_POSITION_Y === 258 ) { 
				PACMAN_POSITION_X = 2;
				PACMAN_POSITION_Y = 258;
			}
			
			drawPacman();
			
			if ((PACMAN_MOUNTH_STATE) === 0 || (PACMAN_MOUNTH_STATE) === 3) { 
				testBubblesPacman();
				testGhostsPacman();
				testFruitsPacman();
			}
		} else { 
			stopPacman();
		}
	} else if (direction && PACMAN_DIRECTION === direction) { 
		tryMovePacmanCancel();
	}
}

function canMovePacman(direction) { 
	
	var positionX = PACMAN_POSITION_X;
	var positionY = PACMAN_POSITION_Y;
	
	if (positionX === 276 && positionY === 204 && direction === 2) return false;
	
	if ( direction === 1 ) { 
		positionX += PACMAN_POSITION_STEP;
	} else if ( direction === 2 ) { 
		positionY += PACMAN_POSITION_STEP;
	} else if ( direction === 3 ) { 
		positionX -= PACMAN_POSITION_STEP;
	} else if ( direction === 4 ) { 
		positionY -= PACMAN_POSITION_STEP;
	}
	
	for (var i = 0, imax = PATHS.length; i < imax; i ++) { 
	
		var p = PATHS[i];
		var c = p.split("-");
		var cx = c[0].split(",");
		var cy = c[1].split(",");
	
		var startX = cx[0];
		var startY = cx[1];
		var endX = cy[0];
		var endY = cy[1];

		if (positionX >= startX && positionX <= endX && positionY >= startY && positionY <= endY) { 
			return true;
		}
	}
	
	return false;
}

function drawPacman() { 

	var ctx = getPacmanCanevasContext();
	
	ctx.fillStyle = "#fff200";
	ctx.beginPath();
	
	var startAngle = 0;
	var endAngle = 2 * Math.PI;
	var lineToX = PACMAN_POSITION_X;
	var lineToY = PACMAN_POSITION_Y;
	if (PACMAN_DIRECTION === 1) { 
		startAngle = (0.35 - (PACMAN_MOUNTH_STATE * 0.05)) * Math.PI;
		endAngle = (1.65 + (PACMAN_MOUNTH_STATE * 0.05)) * Math.PI;
		lineToX -= 8;
	} else if (PACMAN_DIRECTION === 2) { 
		startAngle = (0.85 - (PACMAN_MOUNTH_STATE * 0.05)) * Math.PI;
		endAngle = (0.15 + (PACMAN_MOUNTH_STATE * 0.05)) * Math.PI;
		lineToY -= 8;
	} else if (PACMAN_DIRECTION === 3) { 
		startAngle = (1.35 - (PACMAN_MOUNTH_STATE * 0.05)) * Math.PI;
		endAngle = (0.65 + (PACMAN_MOUNTH_STATE * 0.05)) * Math.PI;
		lineToX += 8;
	} else if (PACMAN_DIRECTION === 4) { 
		startAngle = (1.85 - (PACMAN_MOUNTH_STATE * 0.05)) * Math.PI;
		endAngle = (1.15 + (PACMAN_MOUNTH_STATE * 0.05)) * Math.PI;
		lineToY += 8;
	}
	ctx.arc(PACMAN_POSITION_X, PACMAN_POSITION_Y, PACMAN_SIZE, startAngle, endAngle, false);
	ctx.lineTo(lineToX, lineToY);
	ctx.fill();
	ctx.closePath();
}

function erasePacman() { 

	var ctx = getPacmanCanevasContext();
	ctx.clearRect( (PACMAN_POSITION_X - 2) - PACMAN_SIZE, (PACMAN_POSITION_Y - 2) - PACMAN_SIZE, (PACMAN_SIZE * 2) + 5, (PACMAN_SIZE * 2) + 5);
}

function killPacman() { 
	playDieSound();

	LOCK = true;
	PACMAN_DEAD = true;
	stopPacman();
	stopGhosts();
	pauseTimes();
	stopBlinkSuperBubbles();
	PACMAN_KILLING_TIMER = setInterval('killingPacman()', PACMAN_KILLING_SPEED);
}
function killingPacman() { 
	if (PACMAN_MOUNTH_STATE > -12) { 
		erasePacman();
		PACMAN_MOUNTH_STATE --;
		drawPacman();
	} else { 
		clearInterval(PACMAN_KILLING_TIMER);
		PACMAN_KILLING_TIMER = -1;
		erasePacman();
		if (LIFES > 0) { 
			lifes(-1);
			setTimeout('retry()', (PACMAN_RETRY_SPEED));
		} else { 
			gameover();
		}
	}
}

function testGhostsPacman() { 
	testGhostPacman('blinky');
	testGhostPacman('pinky');
	testGhostPacman('inky');
	testGhostPacman('clyde');

}
function testGhostPacman(ghost) { 
	eval('var positionX = GHOST_' + ghost.toUpperCase() + '_POSITION_X');
	eval('var positionY = GHOST_' + ghost.toUpperCase() + '_POSITION_Y');
		
	if (positionX <= PACMAN_POSITION_X + PACMAN_GHOST_GAP && positionX >= PACMAN_POSITION_X - PACMAN_GHOST_GAP && positionY <= PACMAN_POSITION_Y + PACMAN_GHOST_GAP && positionY >= PACMAN_POSITION_Y - PACMAN_GHOST_GAP ) { 
		eval('var state = GHOST_' + ghost.toUpperCase() + '_STATE');
		if (state === 0) { 
			killPacman();
		} else if (state === 1) { 
			startEatGhost(ghost);
		}
	}
}
function testFruitsPacman() { 
	
	if (FRUIT_CANCEL_TIMER != null) { 
		if (FRUITS_POSITION_X <= PACMAN_POSITION_X + PACMAN_FRUITS_GAP && FRUITS_POSITION_X >= PACMAN_POSITION_X - PACMAN_FRUITS_GAP && FRUITS_POSITION_Y <= PACMAN_POSITION_Y + PACMAN_FRUITS_GAP && FRUITS_POSITION_Y >= PACMAN_POSITION_Y - PACMAN_FRUITS_GAP ) { 
			eatFruit();
		}
	}
}
function testBubblesPacman() { 
	
	var r = { x: PACMAN_POSITION_X - ( PACMAN_SIZE / 2 ), y: PACMAN_POSITION_Y - ( PACMAN_SIZE / 2 ) , width: ( PACMAN_SIZE * 2 ), height: ( PACMAN_SIZE * 2 ) };
		
	for (var i = 0, imax = BUBBLES_ARRAY.length; i < imax; i ++) { 
		var bubble = BUBBLES_ARRAY[i];
		
		var bubbleParams = bubble.split( ";" );
		var testX = parseInt(bubbleParams[0].split( "," )[0]);
		var testY = parseInt(bubbleParams[0].split( "," )[1]);
		var p = { x: testX, y: testY };
		
		if ( isPointInRect( p, r ) ) { 
			
			if ( bubbleParams[4] === "0" ) { 
				var type = bubbleParams[3];
							
				eraseBubble( type, testX, testY );
				BUBBLES_ARRAY[i] = bubble.substr( 0, bubble.length - 1 ) + "1"
				
				if ( type === "s" ) { 
					setSuperBubbleOnXY( testX, testY, "1" );
					score( SCORE_SUPER_BUBBLE );
					playEatPillSound();
					affraidGhosts();
				} else { 
					score( SCORE_BUBBLE );
					playEatingSound();
				}
				BUBBLES_COUNTER --;
				if ( BUBBLES_COUNTER === 0 ) { 
					win();
				}
			} else { 
				stopEatingSound();
			}
			return;
		}
	}
}