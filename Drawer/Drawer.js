class Drawer {
	drawing = 0;
	pathInitialized = false;
	paths = new Array();
	lastPath = null;
	pathNode = null;
	pathI = 0;
	currentColor = '#000000';
	canvas = document.querySelector("#canvas");
	tool = 'pencil';

	circleStartX = null;
	circleStartY = null;
	rectStartX = null;
	rectStartY = null;

	constructor() {
		this.allocateColors();
	}

	log(msg) {
        // TODO: Add filter for log level
		console.log(msg);
	}

	allocateColors() {
		let colors = ['#000000', '#FFFFFF', '#00FF00', '#0000FF', '#FF0000', '#FFFF00', '#00FFFF'];
		for(let i in colors) {
			let selector = document.createElement("div");
			selector.setAttribute("id", "color_" + colors[i].substring(1));
			selector.setAttribute("style", "width: 35px; height: 35px; border: 1px ridge #000000;clear:both;float:left;background:" + colors[i]);
			selector.setAttribute("onclick", "d.setColor('" + colors[i] + "')");
			selector.innerHTML = "&nbsp;";
			document.querySelector("#color_pane").appendChild(selector);
		}
	}

	setColor(color) {
		this.log("Called set color: " + color);
		this.currentColor = color;
	}

	setTool(setTool) {
		this.log("Called set tool: " + setTool);
		this.tool = setTool;
		let toolButtons = document.querySelector("#other_tools").getElementsByTagName("button");
		for(let i in toolButtons) {
			if(toolButtons.hasOwnProperty(i)) {
				let toolButtonTool = toolButtons[i].getAttribute("tool");

				// Set current tool as disabled, and removes disabled attribute from others if present
				if(toolButtonTool === setTool) {
					toolButtons[i].setAttribute("disabled", true);
				} else {
					if(toolButtons[i].getAttribute("disabled")) {
						toolButtons[i].removeAttribute("disabled");
					}
				}
			}
		}
	}

	removeLastPath() {
		let numberOfPaths = this.paths.length;
		if(numberOfPaths !== 0) {
			let lastPathNode = document.querySelector("#path_" + this.paths.pop());
			this.canvas.removeChild(lastPathNode);
			this.lastPath = this.paths[this.paths.length-1];
		}
	}

	draw(e) {
		let x = e.clientX;
		let y = e.clientY;
        if(this.tool === 'text') {
            this.drawText(x, y);
            this.drawing = 0;
        } else {
            this.drawing = 1;
        }
	}

	endDraw() {
        if(this.drawing === 1) {
            this.drawing = 0;
            this.commitPath();
        }
	}

	getElementOffset(x, y) {
        // TODO: Take in account the element's location if not 0,0
		return [x, y];
	}

	calculateDistance(x1, x2, y1, y2) {
		let xDiff = Math.max((x1 - x2), (x2 - x1));
		let yDiff = Math.max((y1 - y2), (y2 - y1));
		return Math.max(xDiff, yDiff);
	}

	trackMovement(e) {
		if(this.drawing) {
			let [x, y] = this.getElementOffset(e.clientX, e.clientY);
			if(this.tool === 'pencil') {
                this.drawFree(x, y);
			} else if(this.tool === 'circle') {
                this.drawCircle(x, y);
			} else if(this.tool === 'rectangle') {
                this.drawRectangle(x, y);
			} else if(this.tool === 'line') {
                this.drawLine(x, y);
			}
		}
	}

    drawFree(x, y) {
        if(!this.pathInitialized) {
            this.pathInitialized = true;
            this.pathNode = document.createElement("path");
            this.pathNode.setAttribute("id", "path_" + this.pathI);
            this.pathNode.setAttribute("d", "M" + x + " " + y);
            this.pathNode.setAttribute("style", "fill: none;stroke: " + this.currentColor + ";stroke-width: 3;");
            this.canvas.appendChild(this.pathNode);

        } else {
            this.pathNode.setAttribute("d", this.pathNode.getAttribute("d") + " L" + x + " " + y);
            this.lastPath = this.pathI;
            this.redraw();
        }
    }

    drawCircle(x, y) {
        if(!this.pathInitialized) {
            this.pathInitialized = true;
            this.circleStartX = x;
            this.circleStartY = y;
            this.pathNode = document.createElement("circle");
            this.pathNode.setAttribute("id", "path_" + this.pathI);
            this.pathNode.setAttribute("r", "1");
            this.pathNode.setAttribute("cx", x);
            this.pathNode.setAttribute("cy", y);
            this.pathNode.setAttribute("style", "fill: none;stroke: " + this.currentColor + ";stroke-width: 3;");
            this.canvas.appendChild(this.pathNode);

        } else {
            let radius = this.calculateDistance(this.circleStartX, x, this.circleStartY, y);
            this.pathNode.setAttribute("r", radius);
            this.lastPath = this.pathI;
            this.redraw();
        }
    }

    drawLine(x, y) {
        if(!this.pathInitialized) {
            this.pathInitialized = true;
            this.pathNode = document.createElement("line");
            this.pathNode.setAttribute("id", "path_" + this.pathI);
            this.pathNode.setAttribute("x1", x);
            this.pathNode.setAttribute("y1", y);
            this.pathNode.setAttribute("style", "fill: none;stroke: " + this.currentColor + ";stroke-width: 3;");
            this.canvas.appendChild(this.pathNode);

        } else {
            this.pathNode.setAttribute("x2", x);
            this.pathNode.setAttribute("y2", y);
            this.lastPath = this.pathI;
            this.redraw();
        }
    }

    drawRectangle(x, y) {
        if(!this.pathInitialized) {
            this.pathInitialized = true;
            this.rectStartX = x;
            this.rectStartY = y;
            this.pathNode = document.createElement("rect");
            this.pathNode.setAttribute("id", "path_" + this.pathI);
            this.pathNode.setAttribute("x", x);
            this.pathNode.setAttribute("y", y);
            this.pathNode.setAttribute("style", "fill: none;stroke: " + this.currentColor + ";stroke-width: 3;");
            this.canvas.appendChild(this.pathNode);

        } else {
            let rectWidth = x - this.rectStartX;
            let rectHeight = y - this.rectStartY;

            this.pathNode.setAttribute("width", rectWidth);
            this.pathNode.setAttribute("height", rectHeight);
            this.lastPath = this.pathI;
            this.redraw();
        }
    }

    drawText(x, y) {
        if(!this.pathInitialized) {
            let newText = prompt("Enter image text:");
            this.pathInitialized = true;
            this.pathNode = document.createElement("text");
            this.pathNode.setAttribute("id", "path_" + this.pathI);
            this.pathNode.setAttribute("x", x);
            this.pathNode.setAttribute("y", y);
            this.pathNode.setAttribute("stroke", this.currentColor);
            this.pathNode.setAttribute("fill", "none");
            this.pathNode.setAttribute("font-size", 36);
            this.pathNode.setAttribute("style", "stroke-width: 1;");
            this.pathNode.innerHTML = newText;
            this.canvas.appendChild(this.pathNode);
            this.commitPath();
        }
    }

	commitPath() {
		//	pathNode.setAttribute("d", pathNode.getAttribute("d") + " Z");
		this.paths.push(this.pathI);
		this.lastPath = this.pathI;
		this.pathI++;
		this.redraw();


		this.pathInitialized = false;
		this.pathNode = null;
		this.circleStartX = null;
		this.circleStartY = null;
        this.rectStartX = null;
        this.rectStartY = null;

	}

	redraw() {
        let canvasParent = this.canvas.parentNode;
		let tmpParentBody = canvasParent.innerHTML;
		canvasParent.innerHTML = "";
		canvasParent.innerHTML = tmpParentBody;
		this.canvas = document.querySelector("#canvas");
		this.pathNode = document.querySelector('#path_' + this.lastPath);
	}

    save() {
        let serializer = new XMLSerializer();
        let sourceStr = serializer.serializeToString(this.canvas);
        let url = "save_svg.php?data=" + encodeURIComponent(sourceStr);
        window.open(url, '_blank');
    }
}
