
var evt = {
  bind: function(el, evt, fn){
    el.addEventListener(evt, fn, false);
  }
};


function spliceString(str, i, remove, add){
  remove = +remove || 0;
  add = add || '';

  return str.slice(0,i) + add + str.slice(i+remove);
}
