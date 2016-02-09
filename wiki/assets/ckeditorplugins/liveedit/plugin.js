CKEDITOR.plugins.add( 'liveedit', {
    init: function( editor ) {
		/**************************************************
		 * POLLING
		 **************************************************/
		var updatePollActive=true;
		var updateTimer=null;
		var lastModified=editor.config.lastModified;
										
		if (!editor.config.pollUrl) alert("Invalid configuration, you must provide configuration option pollUrl to the editor.");
		if (!editor.config.saveUrl) alert("Invalid configuration, you must provide configuration option saveUrl to the editor.");
		if (!editor.config.pollTimeOut) editor.config.pollTimeOut=1000;
		if (!editor.config.saveTimeOut) editor.config.saveTimeOut=3000;
										
		var startUpdatePoll = function(editor) {
			pollUrl=editor.config.pollUrl;
			var callUpdateCallBack=function(parts) {
				if (window[editor.config.updateCallBack]) window[editor.config.updateCallBack](parts);
			}
			if (updateTimer) clearTimeout(updateTimer);
			updateTimer=setTimeout(function() {
				if (updatePollActive) { 
					$.ajax(
						pollUrl + lastModified,
						{
							cache: false,
						}
					).done(function(content) {
						if (content) {
							var parts=content.trim().split(":::DT_MODIFIED:::");
							if (parts && parts.length > 1) {
								// save cursor position/selection
								var selection = editor.getSelection();
								var range = selection.getRanges()[0];
								if (range) {
										//call function, pass any element:
									var startPath=CSSelector(range.startContainer.$);
									var startPathParts=startPath.split('>');
									startPath=startPathParts.slice(0,startPathParts.length-1).join('>');
									var endPath=CSSelector(range.startContainer.$);
									var endPathParts=endPath.split('>');
									endPath=endPathParts.slice(0,endPathParts.length-1).join('>');
									var savedSelection={
										startPath : startPath,
										startOffset : range.startOffset,
										endPath : endPath,
										endOffset : range.endOffset
									
									};
									// modify text
									editor.setData(parts[1]);
									lastModified=parts[0];
									callUpdateCallBack(parts[1]);
									// restore selection
									editor.focus();
									var startElement=editor.document.findOne(savedSelection.startPath ).getFirst();
									var endElement=editor.document.findOne(savedSelection.endPath ).getFirst();
									// replace full selection
									if (startElement && endElement) { 
										var range = editor.createRange();
										try {
											range.setStart( startElement,savedSelection.startOffset );
											range.setEnd( startElement,savedSelection.endOffset );
											selection.selectRanges( [ range ] );
										} catch (e) {
											console.log(['FAIL REPLACE RANGE',e]);
										}
									}
								} else {
									// no selection exists so just modify text
									editor.setData(parts[1]);
									lastModified=parts[0];
									callUpdateCallBack(parts[1]);
								}
							}
						}	
					}).always(function() {
						startUpdatePoll(editor);											
					});										
				} else {
					startUpdatePoll(editor);
				}
			},editor.config.pollTimeOut);
		}
		/**************************************************
		 * SAVE ON KEYUP
		 **************************************************/
		function bindKeyUp(editor) {
			var saveUrl=editor.config.saveUrl;
			editor.on('contentDom', function() {
				var saveTimer=null;
				var editable = editor.editable();
				var callChangeCallBack=function() {
					if (window[editor.config.changeCallBack]) window[editor.config.changeCallBack]();
				}
				var callSaveCallBack=function() {
					if (window[editor.config.saveCallBack]) window[editor.config.saveCallBack]();
				}
				editable.attachListener( editor.document, 'keyup', function() {
					updatePollActive=false;
					callChangeCallBack();
					if (saveTimer) clearTimeout(saveTimer);
					saveTimer=setTimeout(function() {
						var doSave=function() {
							var val=editor.getData();
							var data={'body' : val };
							$.post(
								editor.config.saveUrl+lastModified,
								data,
								function(response) {
									var parts=response.split(":::DT_MODIFIED:::");
									if (parts && parts.length > 1) {
										// reload with other changes
										if (confirm('Your changes conflict with those made by another user. Click OK to reload with their changes or Cancel to force your changes?')) {
											editor.setData(parts[1]); 
											// force save with updated modified date
											$.post(
												editor.config.saveUrl + parts[0],
												data,
												function(response) {
													callSaveCallBack();
													updatePollActive=true;
													var iparts=response.split(":::DT_MODIFIED:::");
													lastModified=iparts[0];
												}
											);
											
											//
										// force these changes
										} else {
											lastModified=parts[0];
											$.post(
												editor.config.saveUrl + parts[0],
												data,
												function(response) {
													callSaveCallBack();
													var iparts=response.split(":::DT_MODIFIED:::");
													lastModified=iparts[0];
													updatePollActive=true;
													startUpdatePoll(editor);
												}
											);
											
										}
									} else {
										lastModified=parts[0];
										updatePollActive=true;
										startUpdatePoll(editor);
										callSaveCallBack();
									}
								}
							)
						}
						doSave();
					},editor.config.saveTimeOut);
				})
			});
		}
		 
		
		/***********************************
		 * INIT
		 ***********************************/
		startUpdatePoll(editor);
		bindKeyUp(editor);

    }
});




