$(document).on("ready", function(){
	
	editor = {
		
		// variables
		fitHeightElements: $(".full-height"),
		wrappersMargin: $("#left_column > .wrapper:first").outerHeight(true) - $("#left_column > .wrapper:first").height(),
		markdownConverter: new Showdown.converter(),
		markdownSource: $("#markdown"),
		markdownPreview: $("#preview"),
		markdownTargets: $("#html, #preview"),
		markdownTargetsTriggers: $("#right_column .overlay .switch"),
		topPanels: $("#top_panels_container .top_panel"),
		topPanelsTriggers: $("#left_column .overlay .toppanel"),
		quickReferencePreText: $("#quick_reference pre"),
		
		// functions
		init: function(){
			this.onloadEffect(0);
			this.bind();
			this.switchToTarget('preview');
			this.fitHeight();
			this.convert();
			this.onloadEffect(1);
		},
		bind: function(){
			$(window).on("resize", function(){
				editor.fitHeight();
			});
			this.markdownSource.on("keyup change", function(){
				editor.convert();
			});
			this.markdownSource.on("cut paste drop", function(){
				setTimeout(function(){
					editor.convert();
				}, 1);
			});
			this.markdownTargetsTriggers.on("click", function(e){
				e.preventDefault();
				editor.switchToTarget($(this).data("switchto"));
			});
			this.topPanelsTriggers.on("click", function(e){
				e.preventDefault();
				editor.toggleTopPanel($(this).data("toppanel"));
			});
			this.topPanels.children(".close").on("click", function(e){
				e.preventDefault();
				editor.closeTopPanel();
			});
			this.quickReferencePreText.on("click", function(){
				editor.addToMarkdownSource($(this).text());
			});
		},
		fitHeight: function(){
			var newHeight = $(window).height() - this.wrappersMargin;
			this.fitHeightElements.each(function(){
				if($(this).closest("#left_column").length){
					var thisNewHeight = newHeight - $("#top_panels_container").outerHeight();
				} else {
					var thisNewHeight = newHeight;
				}
				$(this).css({ height: thisNewHeight +'px' });
			});
		},
		convert: function(){
			var markdown = this.markdownSource.val(),
				html = this.markdownConverter.makeHtml(markdown);
			$("#html").val(html);
			$("#preview").html(html);
		},
		addToMarkdownSource: function(markdown){
			var markdownSourceValue = this.markdownSource.val();
			if(markdownSourceValue != '') markdownSourceValue += '\n\n';
			this.markdownSource.val(markdownSourceValue + markdown);
			this.convert();
		},
		switchToTarget: function(which){
			var target = $("#"+ which),
				targetTrigger = this.markdownTargetsTriggers.filter("[data-switchto="+ which +"]");
			this.markdownTargets.not(target).hide();
			target.show();
			this.markdownTargetsTriggers.not(targetTrigger).removeClass("active");
			targetTrigger.addClass("active");
		},
		toggleTopPanel: function(which){
			var panel = $("#"+ which),
				panelTrigger = this.topPanelsTriggers.filter("[data-toppanel="+ which +"]");
			this.topPanels.not(panel).hide();
			panel.toggle();
			this.topPanelsTriggers.not(panelTrigger).removeClass("active");
			panelTrigger.toggleClass("active");
			this.fitHeight();
		},
		closeTopPanel: function(){
			this.topPanels.hide();
			this.topPanelsTriggers.removeClass("active");
			this.fitHeight();
		},
		onloadEffect: function(step){
			var theBody = $(document.body);
			switch(step){
				case 0:
					theBody.fadeTo(0, 0);
					break;
				case 1:
					theBody.fadeTo(1000, 1);
					break;
			}
		}
		
	};
	
	editor.init();
	
});