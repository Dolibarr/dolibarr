function isPointInRect(point, rect) { 
	if (rect.width <= 0 || rect.height <= 0) { 
		return false;
	} else { 
		return (point.x >= rect.x && point.x <= rect.x + rect.width && point.y >= rect.y && point.y <= rect.y + rect.height);
	}
}

function Timer(callback, delay) {
    var id, started, remaining = delay, running

    this.start = function() {
        running = true
        started = new Date()
        id = setTimeout(callback, remaining)
    }
	
    this.resume = function() {
        running = true
        started = new Date()
        id = setTimeout(callback, remaining)
    }
	
    this.cancel = function() {
        running = false
        clearTimeout(id)
        remaining = 0;
    }

    this.pause = function() {
        running = false
        clearTimeout(id)
        remaining -= new Date() - started
    }

    this.remain = function() {
        if (running) {
            this.pause()
            this.start()
        }

        return remaining
    }

    this.isRunning = function() {
        return running
    }

    this.start();
}

function oneAxe() { 
	return Math.floor( Math.random() * ( 2 - 1 + 1 ) + 1 );
}
function anyGoodIdea() { 
	return Math.floor( Math.random() * ( 4 - 1 + 1 ) + 1 );
}
function whatsYourProblem() { 
	return Math.floor( Math.random() * ( 6 - 1 + 1 ) + 1 );
}

CanvasRenderingContext2D.prototype.roundRect = function(sx, sy, ex, ey, r) {
    var r2d = Math.PI/180;
    if( ( ex - sx ) - ( 2 * r ) < 0 ) { r = ( ( ex - sx ) / 2 ); } 
    if( ( ey - sy ) - ( 2 * r ) < 0 ) { r = ( ( ey - sy ) / 2 ); } 
    this.beginPath();
    this.moveTo(sx+r,sy);
    this.lineTo(ex-r,sy);
    this.arc(ex-r,sy+r,r,r2d*270,r2d*360,false);
    this.lineTo(ex,ey-r);
    this.arc(ex-r,ey-r,r,r2d*0,r2d*90,false);
    this.lineTo(sx+r,ey);
    this.arc(sx+r,ey-r,r,r2d*90,r2d*180,false);
    this.lineTo(sx,sy+r);
    this.arc(sx+r,sy+r,r,r2d*180,r2d*270,false);
    this.closePath();
}

CanvasRenderingContext2D.prototype.oval = function(centerX, centerY, width, height) {
	
  this.beginPath();
  
  this.moveTo(centerX, centerY - height/2); 
  
  this.bezierCurveTo(
    centerX + width/2, centerY - height/2, 
    centerX + width/2, centerY + height/2, 
    centerX, centerY + height/2); 

  this.bezierCurveTo(
    centerX - width/2, centerY + height/2, 
    centerX - width/2, centerY - height/2, 
    centerX, centerY - height/2); 
}
