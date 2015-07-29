function UndoManager(editor){
  this.editor = editor;

  this.undoStack = [];
  this.redoStack = [];
}

UndoManager.prototype.action = function(a){
  /// sanity?

  if(this.undoStack.length && this.canCombine(this.undoStack[this.undoStack.length-1], a)){
    this.undoStack.push(this.combine(this.undoStack.pop(), a));
  }else{
    this.undoStack.push(a);
  }
  this.redoStack = [];
};

UndoManager.prototype.canCombine = function(a, b){
  return (
    !a.action && !b.action &&
    !Array.isArray(a) && !Array.isArray(b) &&
    !(a.del && b.add) && !(a.add && b.del) &&
    !(a.add && !b.add) && !(!a.add && b.add) &&
    !(a.add && a.del) &&
    !(b.add && b.del) &&
    a.start + a.add.length === b.start + b.del.length
  );
};

UndoManager.prototype.combine = function(a, b){
  return {
    add: a.add + b.add,
    del: b.del + a.del,
    start: Math.min(a.start, b.start)
  };
};

UndoManager.prototype.undo = function(){
  if(!this.undoStack.length) return;

  var a = this.undoStack.pop();
  this.redoStack.push(a);

  this.applyInverse(a);
};

UndoManager.prototype.redo = function(){
  if(!this.redoStack.length) return;

  var a = this.redoStack.pop();
  this.undoStack.push(a);

  this.apply(a);
};

UndoManager.prototype.apply = function apply(a){
  if(Array.isArray(a)){
    a.forEach(apply.bind(this));
    return;
  }

  if(a.action){
    this.editor.action(a.action, {
      inverse: a.inverse,
      start: a.start,
      end: a.end,
      noHistory: true
    });
  }else{
    this.editor.apply(a);
  }
};

UndoManager.prototype.applyInverse = function inv(a){
  if(Array.isArray(a)){
    a.forEach(inv.bind(this));
    return;
  }

  if(a.action){
    this.editor.action(a.action, {
      inverse: !a.inverse,
      start: a.start,
      end: a.end,
      noHistory: true
    });
  }else{
    this.editor.apply({
      start: a.start,
      end: a.end,
      del: a.add,
      add: a.del
    });
  }
};
