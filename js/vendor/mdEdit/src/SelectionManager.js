function SelectionManager(elt){
	this.elt = elt;
}

SelectionManager.prototype.getStart = function(){
	var selection = getSelection();

	if(!selection.rangeCount) return 0;

	var range = selection.getRangeAt(0);
	var el = range.startContainer;
	var container = el;
	var offset = range.startOffset;

	if(!(this.elt.compareDocumentPosition(el) & 0x10)){
		// selection is outside this element.
		return 0;
	}

	do{
		while((el = el.previousSibling)){
			if(el.textContent){
				offset += el.textContent.length;
			}
		}

		el = container = container.parentNode;
	}while(el && el !== this.elt);

	return offset;
};

SelectionManager.prototype.getEnd = function(){
	var selection = getSelection();

	if(!selection.rangeCount) return 0;

	return this.getStart() + String(selection.getRangeAt(0)).length;
};

SelectionManager.prototype.setRange = function(start, end, noscroll){
	var range = document.createRange();
	var startOffset = findOffset(this.elt, start);
	var endOffset = startOffset;
	if(end && end !== start){
		endOffset = findOffset(this.elt, end);
	}else{
		if(noscroll !== false) scrollToCaret.call(this, endOffset.element, endOffset.offset);
	}

	range.setStart(startOffset.element, startOffset.offset);
	range.setEnd(endOffset.element, endOffset.offset);

	var selection = getSelection();
	selection.removeAllRanges();
	selection.addRange(range);
};



var caret = document.createElement('span');
caret.style.position = 'absolute';
caret.innerHTML = '|';

function scrollToCaret(el, offset){
	var t = el.textContent;
	var p = el.parentNode;
	var before = t.slice(0, offset);
	var after = t.slice(offset);

	el.textContent = after;
	var b4 = document.createTextNode(before);
	p.insertBefore(caret, el);
	p.insertBefore(b4, caret);

	// caret.scrollIntoViewIfNeeded();
	var tp = caret.offsetTop;
	var h = caret.offsetHeight;
	var ch = this.elt.offsetHeight;
	var st = this.elt.scrollTop;

	el.textContent = t;
	p.removeChild(caret);
	p.removeChild(b4);

	if(tp - st < 0){
		this.elt.scrollTop = tp;
	}else if(tp - st + h > ch){
		this.elt.scrollTop = tp + h - ch;
	}
}




function findOffset(root, ss) {
	if(!root) {
		return null;
	}

	var offset = 0,
		element = root;

	do {
		var container = element;
		element = element.firstChild;

		if(element) {
			do {
				var len = element.textContent.length;

				if(offset <= ss && offset + len > ss) {
					break;
				}

				offset += len;
			} while(element = element.nextSibling);
		}

		if(!element) {
			// It's the container's lastChild
			break;
		}
	} while(element && element.hasChildNodes() && element.nodeType != 3);

	if(element) {
		return {
			element: element,
			offset: ss - offset
		};
	}
	else if(container) {
		element = container;

		while(element && element.lastChild) {
			element = element.lastChild;
		}

		if(element.nodeType === 3) {
			return {
				element: element,
				offset: element.textContent.length
			};
		}
		else {
			return {
				element: element,
				offset: 0
			};
		}
	}

	return {
		element: root,
		offset: 0,
		error: true
	};
}
