var md = (function(){
  var md = {
    'comment': Prism['languages']['markup']['comment']
  };

  var inlines = {};
  var blocks = {};

  function inline(name, def){
    blocks[name] = inlines[name] = md[name] = def;
  }
  function block(name, def){
    blocks[name] = md[name] = def;
  }


  var langAliases = {
    'markup': [ 'markup', 'html', 'xml' ],
    'javascript': [ 'javascript', 'js' ]
  };

  for(var i in Prism['languages']){
    if(!Prism['languages'].hasOwnProperty(i)) continue;
    var l = Prism['languages'][i];
    if(typeof l === 'function') continue;

    var aliases = langAliases[i];
    var matches = aliases ? aliases.join('|') : i;

    block('code-block fenced ' + i, {
      'pattern': new RegExp('(^ {0,3}|\\n {0,3})(([`~])\\3\\3) *(' + matches + ')( [^`\n]*)? *\\n(?:[\\s\\S]*?)\\n {0,3}(\\2\\3*(?= *\\n)|$)', 'gi'),
      'lookbehind': true,
      'inside': {
        'code-language': {
          'pattern': /(^([`~])\2+)((?!\2)[^\2\n])+/,
          'lookbehind': true
        },
        'marker code-fence start': /^([`~])\1+/,
        'marker code-fence end': /([`~])\1+$/,
        'code-inner': {
          'pattern': /(^\n)[\s\S]*(?=\n$)/,
          'lookbehind': true,
          'alias': 'language-' + i,
          'inside': l
        }
      }
    });
  }


  block('code-block fenced untagged', {
    'pattern': /(^ {0,3}|\n {0,3})(([`~])\3\3)[^`\n]*\n(?:[\s\S]*?)\n {0,3}(\2\3*(?= *\n)|$)/g,
    'lookbehind': true,
    'inside': {
      'code-language': {
        'pattern': /(^([`~])\2+)((?!\2)[^\2\n])+/,
        'lookbehind': true
      },
      'marker code-fence start': /^([`~])\1+/,
      'marker code-fence end': /([`~])\1+$/,
      'code-inner': {
        'pattern': /(^\n)[\s\S]*(?=\n$)/,
        'lookbehind': true
      }
    }
  });


  block('heading setext-heading heading-1', {
    'pattern': /^ {0,3}[^\s].*\n {0,3}=+[ \t]*$/gm,
    'inside': {
      'marker heading-setext-line': {
        'pattern': /^( {0,3}[^\s].*\n) {0,3}=+[ \t]*$/gm,
        'lookbehind': true
      },
      'rest': inlines
    }
  });

  block('heading setext-heading heading-2', {
    'pattern': /^ {0,3}[^\s].*\n {0,3}-+[ \t]*$/gm,
    'inside': {
      'marker heading-setext-line': {
        'pattern': /^( {0,3}[^\s].*\n) {0,3}-+[ \t]*$/gm,
        'lookbehind': true
      },
      'rest': inlines
    }
  });

  var headingInside = {
    'marker heading-hash start': /^ *#+ */,
    'marker heading-hash end': / +#+ *$/,
    'rest': inlines
  };
  for(var i = 1; i <= 6; i += 1){
    block('heading heading-'+i, {
      'pattern': new RegExp('^ {0,3}#{'+i+'}(?!#).*$', 'gm'),
      'inside': headingInside
    });
  }



  var linkText = {
    'pattern': /^\[(?:\\.|[^\[\]]|\[[^\[\]]*\])*\]/,
    'inside': {
      'marker bracket start': /^\[/,
      'marker bracket end': /\]$/,
      'link-text-inner': {
        'pattern': /[\w\W]+/,
        'inside': inlines
      }
    }
  };

  var linkLabel = {
    'pattern': /\[(?:\\.|[^\]])*\]/,
    'inside': {
      'marker bracket start': /^\[/,
      'marker bracket end': /\]$/,
      'link-label-inner': /[\w\W]+/
    }
  };

  var imageText = {
    'pattern': /^!\[(?:\\.|[^\[\]]|\[[^\[\]]*\])*\]/,
    'inside': {
      'marker image-bang': /^!/,
      'marker bracket start': /^\[/,
      'marker bracket end': /\]$/,
      'image-text-inner': {
        'pattern': /[\w\W]+/,
        'inside': inlines
      }
    }
  };

  var linkURL = {
    'pattern': /^(\s*)(?!<)(?:\\.|[^\(\)\s]|\([^\(\)\s]*\))+/,
    'lookbehind': true
  };

  var linkBracedURL = {
    'pattern': /^(\s*)<(?:\\.|[^<>\n])*>/,
    'lookbehind': true,
    'inside': {
      'marker brace start': /^</,
      'marker brace end': />$/,
      'braced-href-inner': /[\w\W]+/
    }
  };

  var linkTitle = {
    'pattern': /('(?:\\'|[^'])+'|"(?:\\"|[^"])+")\s*$/,
    // 'lookbehind': true,
    'inside': {
      'marker quote start': /^['"]/,
      'marker quote end': /['"]$/,
      'title-inner': /[\w\W]+/
    }
  };

  var linkParams = {
    'pattern': /\( *(?:(?!<)(?:\\.|[^\(\)\s]|\([^\(\)\s]*\))*|<(?:[^<>\n]|\\.)*>)( +('(?:[^']|\\')+'|"(?:[^"]|\\")+"))? *\)/,
    'inside': {
      'marker bracket start': /^\(/,
      'marker bracket end': /\)$/,
      'link-params-inner': {
        'pattern': /[\w\W]+/,
        'inside': {
          'link-title': linkTitle,
          'href': linkURL,
          'braced-href': linkBracedURL
        }
      }
    }
  };




  block('hr', {
    'pattern': /^[\t ]*([*\-_])([\t ]*\1){2,}([\t ]*$)/gm,
    'inside': {
      'marker hr-marker': /[*\-_]/g
    }
  });

  block('urldef', {
    'pattern': /^( {0,3})\[(?:\\.|[^\]])+]: *\n? *(?:(?!<)(?:\\.|[^\(\)\s]|\([^\(\)\s]*\))*|<(?:[^<>\n]|\\.)*>)( *\n? *('(?:\\'|[^'])+'|"(?:\\"|[^"])+"))?$/gm,
    'lookbehind': true,
    'inside': {
      'link-label': linkLabel,
      'marker urldef-colon': /^:/,
      'link-title': linkTitle,
      'href': linkURL,
      'braced-href': linkBracedURL
    }
  });

  block('blockquote', {
    'pattern': /^[\t ]*>[\t ]?.+(?:\n(?!\n)|.)*/gm,
    'inside': {
      'marker quote-marker': /^[\t ]*>[\t ]?/gm,
      'blockquote-content': {
        'pattern': /[^>]+/,
        'rest': blocks
      }
    }
  });

  block('list', {
    'pattern': /^[\t ]*([*+\-]|\d+\.)[\t ].+(?:\n(?!\n)|.)*/gm,
    'inside': {
      'li': {
        'pattern': /^[\t ]*([*+\-]|\d+\.)[\t ].+(?:\n|[ \t]+[^*+\- \t].*\n)*/gm,
        'inside': {
          'marker list-item': /^[ \t]*([*+\-]|\d+\.)[ \t]/m,
          'rest': blocks
        }
      }
    }
  });

  block('code-block indented', {
    'pattern': /(^|(?:^|(?:^|\n)(?![ \t]*([*+\-]|\d+\.)[ \t]).*\n)\s*?\n)((?: {4}|\t).*(?:\n|$))+/g,
    'lookbehind': true
  });

  block('p', {
    'pattern': /[^\n](?:\n(?!\n)|.)*[^\n]/g,
    'inside': inlines
  });

  inline('image', {
    'pattern': /(^|[^\\])!\[(?:\\.|[^\[\]]|\[[^\[\]]*\])*\]\(\s*(?:(?!<)(?:\\.|[^\(\)\s]|\([^\(\)\s]*\))*|<(?:[^<>\n]|\\.)*>)(\s+('(?:[^']|\\')+'|"(?:[^"]|\\")+"))?\s*\)/,
    'lookbehind': true,
    'inside': {
      'link-text': imageText,
      'link-params': linkParams
    }
  });

  inline('link', {
    'pattern': /(^|[^\\])\[(?:\\.|[^\[\]]|\[[^\[\]]*\])*\]\(\s*(?:(?!<)(?:\\.|[^\(\)\s]|\([^\(\)\s]*\))*|<(?:[^<>\n]|\\.)*>)(\s+('(?:[^']|\\')+'|"(?:[^"]|\\")+"))?\s*\)/,
    'lookbehind': true,
    'inside': {
      'link-text': linkText,
      'link-params': linkParams
    }
  });

  inline('image image-ref', {
    'pattern': /(^|[^\\])!\[(?:\\.|[^\[\]]|\[[^\[\]]*\])*\] ?\[(?:\\.|[^\]])*\]/,
    'lookbehind': true,
    'inside': {
      'link-text': imageText,
      'link-label': linkLabel
    }
  });
  inline('link link-ref', {
    'pattern': /(^|[^\\])\[(?:\\.|[^\[\]]|\[[^\[\]]*\])*\] ?\[(?:\\.|[^\]])*\]/,
    'lookbehind': true,
    'inside': {
      'link-text': linkText,
      'link-label': linkLabel
    }
  });

  inline('image image-ref shortcut-ref', {
    'pattern': /(^|[^\\])!\[(?:\\.|[^\[\]]|\[[^\[\]]*\])*\]/,
    'lookbehind': true,
    'inside': {
      'marker image-bang': /^!/,
      'link-text': linkText
    }
  });
  inline('link link-ref shortcut-ref', {
    'pattern': /(^|[^\\])\[(?:\\.|[^\[\]]|\[[^\[\]]*\])*\]/,
    'lookbehind': true,
    'inside': {
      'link-text': linkText
    }
  });


  inline('code', {
    'pattern': /(^|[^\\])(`+)([^\r]*?[^`])\2(?!`)/g,
    'lookbehind': true,
    'inside': {
      'marker code-marker start': /^`/,
      'marker code-marker end': /`$/,
      'code-inner': /[\w\W]+/
    }
  });

  inline('strong', {
    'pattern': /(^|[^\\*_]|\\[*_])([_\*])\2(?:\n(?!\n)|.)+?\2{2}(?!\2)/g,
    // 'pattern': /(^|[^\\])(\*\*|__)(?:\n(?!\n)|.)+?\2/,
    'lookbehind': true,
    'inside': {
      'marker strong-marker start': /^(\*\*|__)/,
      'marker strong-marker end': /(\*\*|__)$/,
      'strong-inner': {
        'pattern': /[\w\W]+/,
        'inside': inlines
      }
    }
  });

  inline('em', {
    // 'pattern': /(^|[^\\])(\*|_)(\S[^\2]*?)??[^\s\\]+?\2/g,
    'pattern': /(^|[^\\*_]|\\[*_])(\*|_)(?:\n(?!\n)|.)+?\2(?!\2)/g,
		'lookbehind': true,
    'inside': {
      'marker em-marker start': /^(\*|_)/,
      'marker em-marker end': /(\*|_)$/,
      'em-inner': {
        'pattern': /[\w\W]+/,
        'inside': inlines
      }
    }
  });

  inline('strike', {
    'pattern': /(^|\n|(?!\\)\W)(~~)(?=\S)([^\r]*?\S)\2/gm,
    'lookbehind': true,
    'inside': {
      'marker strike-marker start': /^~~/,
      'marker strike-marker end': /~~$/,
      'strike-inner': {
        'pattern': /[\w\W]+/,
        'inside': inlines
      }
    }
  });

  inline('comment', Prism['languages']['markup']['comment']);

  var tag = Prism['languages']['markup']['tag'];
  var tagMatch = tag['pattern'];

  inline('tag', {
    'pattern': new RegExp("(^|[^\\\\])" + tagMatch.source, 'i'),
    'lookbehind': true,
    'inside': tag['inside']
  });
  inline('entity', Prism['languages']['markup']['entity']);

  return md;
})();
