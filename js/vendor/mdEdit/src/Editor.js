function Editor(el, opts){

  if(!(this instanceof Editor)){
    return new Editor(el, opts);
  }

  opts = opts || {};

  if(el.tagName === 'PRE'){
    this.el = el;
  }else{
    this.el = document.createElement('pre');
    el.appendChild(this.el);
  }

  var cname = opts['className'] || '';

  this.el.className = 'mdedit' + (cname ? ' ' + cname : '');
  this.el.setAttribute('contenteditable', true);

  this.selMgr = new SelectionManager(el);
  this.undoMgr = new UndoManager(this);

  evt.bind(el, 'cut', this.cut.bind(this));
  evt.bind(el, 'paste', this.paste.bind(this));
  evt.bind(el, 'keyup', this.keyup.bind(this));
  evt.bind(el, 'input', this.changed.bind(this));
  evt.bind(el, 'keydown', this.keydown.bind(this));
  evt.bind(el, 'keypress', this.keypress.bind(this));


  var changeCb = opts['change'];
  this.changeCb = changeCb || function(){};

  this.changed();
}

Editor.prototype.fireChange = function(){
  var prev = this._prevValue;
  var now = this.getValue();
  if(prev !== now){
    this.changeCb(now);
    this._prevValue = now;
  }
};

Editor.prototype['setValue'] = function(val){
  this.el.textContent = val;
  this.changed();
};

Editor.prototype['getValue'] = function(){
  return this.el.textContent;
};

Editor.prototype.keyup = function(evt){
  var keyCode = evt && evt.keyCode || 0,
      code = this.el.textContent;

  // if(keyCode < 9 || keyCode == 13 || keyCode > 32 && keyCode < 41) {
    // $t.trigger('caretmove');
  // }

  if([
    9, 91, 93, 16, 17, 18, // modifiers
    20, // caps lock
    13, // Enter (handled by keydown)
    112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, // F[0-12]
    27 // Esc
  ].indexOf(keyCode) > -1) {
    return;
  }

  if([
    33, 34, // PgUp, PgDn
    35, 36, // End, Home
    37, 39, 38, 40 // Left, Right, Up, Down
  ].indexOf(keyCode) === -1) {
    this.changed();
  }
};

Editor.prototype.changed = function(evt){
  var code = this.el.textContent;

  var ss = this.selMgr.getStart(),
    se = this.selMgr.getEnd();

  this.saveScrollPos();

  this.el.innerHTML = Prism['highlight'](code, md);
  // Prism.highlightElement(this); // bit messy + unnecessary + strips leading newlines :(

  if(!/\n$/.test(code)) {
    this.el.innerHTML = this.el.innerHTML + '\n';
  }

  this.restoreScrollPos();

  if(ss !== null || se !== null) {
    this.selMgr.setRange(ss, se);
  }

  this.fireChange();
};

Editor.prototype.saveScrollPos = function(){
  if(this.st === undefined) this.st = this.el.scrollTop;
  setTimeout(function(){
    this.st = undefined;
  }.bind(this), 500);
};

Editor.prototype.restoreScrollPos = function(){
  this.el.scrollTop = this.st;
  this.st = undefined;
};


Editor.prototype.keypress = function(evt){
  var ctrl = evt.metaKey || evt.ctrlKey;

  if(ctrl) return;

  var code = evt.charCode;

  if(!code) return;

  var start = this.selMgr.getStart();
  var end = this.selMgr.getEnd();

  var chr = String.fromCharCode(code);
  this.undoMgr.action({
    add: chr,
    del: start === end ? '' : this.el.textContent.slice(start, end),
    start: start
  });
};

Editor.prototype.keydown = function(evt){
  var cmdOrCtrl = evt.metaKey || evt.ctrlKey;

  switch(evt.keyCode) {
    case 8: // Backspace
    case 46: // Delete
      var start = this.selMgr.getStart();
      var end = this.selMgr.getEnd();
      var length = start === end ? 1 : Math.abs(end - start);
      start = evt.keyCode === 8 ? end - length : start;
      this.undoMgr.action({
        add: '',
        del: this.el.textContent.slice(start, start + length),
        start: start
      });
      break;
    case 9: // Tab
      if(!cmdOrCtrl) {
        this.action('indent', {
          inverse: evt.shiftKey
        });
        evt.preventDefault();
      }
      break;
    case 219: // [
    case 221: // ]
      if(cmdOrCtrl && !evt.shiftKey) {
        this.action('indent', {
          inverse: evt.keyCode === 219,
          ctrl: true
        });
        evt.preventDefault();
      }
      break;
    case 13:
      this.action('newline');
      evt.preventDefault();
      break;
    case 89:
      if(cmdOrCtrl){
        this.undoMgr.redo();
        evt.preventDefault();
      }
      break;
    case 90:
      if(cmdOrCtrl) {
        evt.shiftKey ? this.undoMgr.redo() : this.undoMgr.undo();
        evt.preventDefault();
      }

      break;
    case 191:
      // if(cmdOrCtrl && !evt.altKey) {
      //   that.action('comment', { lang: this.id });
      //   return false;
      // }

      break;
  }
};

Editor.prototype.apply = function(action){
  var e = this.el;

  e.textContent = spliceString(e.textContent, action.start, action.del.length, action.add);
  this.selMgr.setRange(action.start, action.start + action.add.length);
  this.changed();
};

Editor.prototype.action = function(act, opts){
  opts = opts || {};
  var text = this.el.textContent;
  var start = opts.start || this.selMgr.getStart();
  var end = opts.end || this.selMgr.getEnd();

  var state = {
    start: start,
    end: end,
    before: text.slice(0, start),
    after: text.slice(end),
    sel: text.slice(start, end)
  };

  var a = actions[act](state, opts);

  this.saveScrollPos();

  this.el.textContent = state.before + state.sel + state.after;

  if(a && !opts.noHistory){
    this.undoMgr.action(a);
  }
  this.selMgr.setRange(state.start, state.end, false);

  this.changed();

};

Editor.prototype.cut = function(){
  var start = this.selMgr.getStart();
  var end = this.selMgr.getEnd();
  if(start === end) return;

  this.undoMgr.action({
    add: '',
    del: this.el.textContent.slice(start, end),
    start: start
  });
};

Editor.prototype.paste = function(evt){
  var start = this.selMgr.getStart();
  var end = this.selMgr.getEnd();
  var selection = start === end ? '' : this.el.textContent.slice(start, end);

  if(evt.clipboardData){
    evt.preventDefault();

    var pasted = evt.clipboardData.getData('text/plain');

    this.apply({
      add: pasted,
      del: selection,
      start: start
    });

    this.undoMgr.action({
      add: pasted,
      del: selection,
      start: start
    });

    start += pasted.length;
    this.selMgr.setRange(start, start);
    this.changed();
  }
};
