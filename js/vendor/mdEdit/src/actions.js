
var actions = {
  'newline': function(state, options){
    var s = state.start;
    var lf = state.before.lastIndexOf('\n') + 1;
    var afterLf = state.before.slice(lf);
    var indent = afterLf.match(/^\s*/)[0];
    var add = indent;
    var clearPrevLine = false;

    if(/^ {0,3}$/.test(indent)){ // maybe list
      var l = afterLf.slice(indent.length);
      if(/^[*+\-]\s+/.test(l)){
        add += l.match(/^[*+\-]\s+/)[0];
        clearPrevLine = /^[*+\-]\s+$/.test(l);
      }else if(/^\d+\.\s+/.test(l)){
        add += l.match(/^\d+\.\s+/)[0]
                .replace(/^\d+/, function(n){ return +n+1; });
        clearPrevLine = /^\d+\.\s+$/.test(l);
      }else if(/^>/.test(l)){
        add += l.match(/^>\s*/)[0];
        clearPrevLine = /^>\s*$/.test(l);
      }
    }

    add = '\n' + add;

    var del = state.sel;
    state.sel = '';

    if(clearPrevLine){ // if prev line was actually an empty liste item, clear it
      del = afterLf + del;
      state.before = state.before.slice(0, lf);
      state.start -= afterLf.length;
      s -= afterLf.length;
      add = '\n';
    }

    state.before += add;
    state.start += add.length;
    state.end = state.start;

    return { add: add, del: del, start: s };
  },

  'indent': function(state, options){
    var lf = state.before.lastIndexOf('\n') + 1;

    // TODO deal with soft tabs

    if(options.inverse){
      if(/\s/.test(state.before.charAt(lf))){
        state.before = spliceString(state.before, lf, 1);
        state.start -= 1;
      }
      state.sel = state.sel.replace(/\r?\n(?!\r?\n)\s/, '\n');
    }else if(state.sel || options.ctrl){
      state.before = spliceString(state.before, lf, 0, '\t');
      state.sel = state.sel.replace(/\r?\n/, '\n\t');
      state.start += 1;
    }else{
      state.before += '\t';
      state.start += 1;
      state.end  += 1;

      return { add: '\t', del: '', start: state.start - 1 };
    }

    state.end = state.start + state.sel.length;

    return {
      action: 'indent',
      start: state.start,
      end: state.end,
      inverse: options.inverse
    };
  },

  'wrap': function(state, options){
    var match = {
      '(': ')',
      '[': ']',
      '{': '}',
      '<': '>'
    }[options.bracket] || options.bracket;

    state.before += options.bracket;
    state.after = match + state.after;
    state.start += 1;
    state.end += 1;

    return {
      add: options.bracket + state.sel + match,
      del: state.sel,
      start: state.start - 1,
      end: state.end - 1
    };
  }
};
