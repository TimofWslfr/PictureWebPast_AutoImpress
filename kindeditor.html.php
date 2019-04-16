<?php if($extView = $this->getExtViewFile(__FILE__)){include $extView; return helper::cd();}?>
<?php
$module = $this->moduleName;
$method = $this->methodName;
if(!isset($config->$module->editor->$method)) return;
$editor = $config->$module->editor->$method;
$editor['id'] = explode(',', $editor['id']);
$editorLangs  = array('en' => 'en', 'zh-cn' => 'zh_CN', 'zh-tw' => 'zh_TW');
$editorLang   = isset($editorLangs[$app->getClientLang()]) ? $editorLangs[$app->getClientLang()] : 'en';

/* set uid for upload. */
$uid = uniqid('');
?>
<script src='<?php echo $jsRoot;?>kindeditor/kindeditor.min.js'></script>
<script src='<?php echo $jsRoot;?>kindeditor/lang/<?php echo $editorLang;?>.js'></script>
<script>
(function($) {
    var kuid = '<?php echo $uid;?>';
    var editor = <?php echo json_encode($editor);?>;
    var K = KindEditor;

    var bugTools =
    [ 'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic','underline', '|',
    'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist', 'insertunorderedlist', '|',
    'emoticons', 'image', 'code', 'link', '|', 'removeformat','undo', 'redo', 'fullscreen', 'source', 'about'];
    var simpleTools =
    [ 'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic','underline', '|',
    'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist', 'insertunorderedlist', '|',
    'emoticons', 'image', 'code', 'link', '|', 'removeformat','undo', 'redo', 'fullscreen', 'source', 'about'];
    var fullTools =
    [ 'formatblock', 'fontname', 'fontsize', 'lineheight', '|', 'forecolor', 'hilitecolor', '|', 'bold', 'italic','underline', 'strikethrough', '|',
    'justifyleft', 'justifycenter', 'justifyright', 'justifyfull', '|',
    'insertorderedlist', 'insertunorderedlist', '|',
    'emoticons', 'image', 'insertfile', 'hr', '|', 'link', 'unlink', '/',
    'undo', 'redo', '|', 'selectall', 'cut', 'copy', 'paste', '|', 'plainpaste', 'wordpaste', '|', 'removeformat', 'clearhtml','quickformat', '|',
    'indent', 'outdent', 'subscript', 'superscript', '|',
    'table', 'code', '|', 'pagebreak', 'anchor', '|',
    'fullscreen', 'source', 'preview', 'about'];
    var editorToolsMap = {fullTools: fullTools, simpleTools: simpleTools, bugTools: bugTools};
    var imageLoadingEle = '<div class="image-loading-ele small" style="padding: 5px; background: #FFF3E0; width: 300px; border-radius: 2px; border: 1px solid #FF9800; color: #ff5d5d; margin: 10px 0;"><i class="icon icon-spin icon-spinner-indicator muted"></i> <?php echo $this->lang->pasteImgUploading?></div>';

    // Kindeditor default options
    var editorDefaults =
    {
        cssPath: [config.themeRoot + 'zui/css/min.css'],
        width: '100%',
        height: '200px',
        filterMode: true,
        bodyClass: 'article-content',
        urlType: 'absolute',
        uploadJson: createLink('file', 'ajaxUpload', 'uid=' + kuid),
        allowFileManager: true,
        langType: '<?php echo $editorLang?>',
    };

    window.editor = {};
    var nextFormControl = 'input:not([type="hidden"]), textarea:not(.ke-edit-textarea), button[type="submit"], select';

    // Init kindeditor
    var setKindeditor = function(element, options)
    {
        var $editor  = $(element);
        var pasted   = false;
        options      = $.extend({}, editorDefaults, $editor.data(), options);
        var editorID = $editor.attr('id');
        if(editorID === undefined)
        {
            editorID = 'kindeditor-' + $.zui.uuid();
            $editor.attr('id', editorID);
        }

        var editorTool  = editorToolsMap[options.tools || editor.tools] || simpleTools;
        var placeholder = $editor.attr('placeholder') || options.placeholder || '';

        /* Remove fullscreen in modal. */
        if(config.onlybody == 'yes')
        {
            var newEditorTool = new Array();
            for(i in editorTool)
            {
                if(editorTool[i] != 'fullscreen') newEditorTool.push(editorTool[i]);
            }
            editorTool = newEditorTool;
        }

        $.extend(options,
        {
            items: editorTool,
            afterChange: function(){$editor.change().hide();},
            afterCreate : function()
            {
                var frame = this.edit;
                var doc   = this.edit.doc;
                var cmd   = this.edit.cmd;
                pasted    = true;
                if(!K.WEBKIT && !K.GECKO)
                {
                    var pasted = false;
                    $(doc.body).bind('paste', function(ev)
                    {
                        pasted = true;
                        return true;
                    });
                    setTimeout(function()
                    {
                        $(doc.body).bind('keyup', function(ev)
                        {
                            if(pasted)
                            {
                                pasted = false;
                                return true;
                            }
                            if(ev.keyCode == 86 && ev.ctrlKey) alert('<?php echo $this->lang->error->pasteImg;?>');
                        })
                    }, 10);
                }
                if(pasted && placeholder.indexOf('<?php echo $this->lang->noticePasteImg?>') < 0)
                {
                    if(placeholder) placeholder += '<br />';
                    placeholder += ' <?php echo $this->lang->noticePasteImg?>';
                }

                var pasteBegin = function()
                {
                    $.enableForm(false, 0, 1);
                    $('body').one('click.ke' + kuid, function(){$.enableForm(true);});
                    cmd.inserthtml(imageLoadingEle);
                    keditor.readonly(true);
                };

                var pasteEnd = function(error)
                {
                    if(error)
                    {
                        if(error === true) error = '<?php echo $this->lang->pasteImgFail;?>';
                        $.zui.messager.danger(error, {placement: 'center'});
                    }
                    $.enableForm(true, 0, 1);
                    $('body').off('.ke' + kuid);
                    $(doc.body).find('.image-loading-ele').remove();
                    keditor.readonly(false);
                };

                var pasteUrl = createLink('file', 'ajaxPasteImage', 'uid=' + kuid);

                /* Paste in chrome.*/
                /* Code reference from http://www.foliotek.com/devblog/copy-images-from-clipboard-in-javascript/. */
                if(K.WEBKIT)
                {
                    $(doc.body).bind('paste', function(ev)
                    {
                        var $this    = $(this);
                        var original = ev.originalEvent;
                        var file     = original.clipboardData.items[0].getAsFile();
                        if(file)
                        {
                            pasteBegin();

                            var reader = new FileReader();
                            reader.onload = function(evt)
                            {
                                var result = evt.target.result;
								//钟靖 2019年04月16日 11:47
								var img_ = new Image();
								img_.src = result;
								var x_ = 0;
								while(img_.width == 0 && img_.height == 0){
									try{
										reader.readAsDataURL(file);
										}catch(err){
									
									}
									//result = evt.target.result; 
									img_.src = result; 
									x_++;
									if(x_>5){
										break;
									}
								}
								result = compress(img_);
								/////////////////
                                var arr    = result.split(",");
                                var data   = arr[1]; // raw base64
                                var contentType = arr[0].split(";")[0].split(":")[1];

                                html = '<img src="' + result + '" alt="" />';
                                $.post(pasteUrl, {editor: html}, function(data)
                                {
                                    cmd.inserthtml(data);
                                    pasteEnd();
                                }).error(function()
                                {
                                    pasteEnd(true);
                                });
                            };
							try{
										reader.readAsDataURL(file);
									}catch(err){
									
									}
                            //reader.readAsDataURL(file);
                        }
                    });
                }
                /* Paste in firefox and other firefox. */
                else
                {
                    K(doc.body).bind('paste', function(ev)
                    {
                        setTimeout(function()
                        {
                            var html = K(doc.body).html();
                            if(html.search(/<img src="data:.+;base64,/) > -1)
                            {
                                pasteBegin();
                                $.post(pasteUrl, {editor: html}, function(data)
                                {
                                    if(data.indexOf('<img') === 0) data = '<p>' + data + '</p>';
                                    frame.html(data);
                                    pasteEnd();
                                }).error(function()
                                {
                                    pasteEnd(true);
                                });
                            }
                        }, 80);
                    });
                }
                /* End */

                /* Add for placeholder. */
                $(this.edit.doc).find('body').after('<span class="kindeditor-ph" style="width:100%;color:#888; padding:5px 5px 5px 7px; background-color:transparent; position:absolute;z-index:10;top:2px;border:0;overflow:auto;resize:none; font-size:13px;"></span>');
                var $placeholder = $(this.edit.doc).find('.kindeditor-ph');
                $placeholder.html(placeholder);
                $placeholder.css('pointerEvents', 'none');
                $placeholder.click(function(){frame.doc.body.focus()});
                if(frame.html() != '') $placeholder.hide();
            },
            afterFocus: function()
            {
                var frame = this.edit;
                var $placeholder = $(frame.doc).find('.kindeditor-ph');
                if($placeholder.size() == 0)
                {
                    setTimeout(function(){$(frame.doc).find('.kindeditor-ph').hide();}, 50);
                }
                else
                {
                    $placeholder.hide();
                }
                $editor.prev('.ke-container').addClass('focus');
                $(document).trigger('mousedown'); // see http://pms.zentao.net/task-view-5115.html
            },
            afterBlur: function()
            {
                this.sync();
                $editor.prev('.ke-container').removeClass('focus');
                var frame = this.edit;
                if(K(frame.doc.body).html() == '') $(frame.doc).find('.kindeditor-ph').show();
            },
            afterTab: function(id)
            {
                var $next = $editor.next(nextFormControl);
                if(!$next.length) $next = $editor.parent().next().find(nextFormControl);
                if(!$next.length) $next = $editor.parent().parent().next().find(nextFormControl);
                $next = $next.first().focus();
                var keditor = $next.data('keditor');
                if(keditor) keditor.focus();
                else if($next.hasClass('chosen')) $next.trigger('chosen:activate');
            }
        });

        try
        {
            var keditor = K.create('#' + editorID, options);
            window.editor['#'] = window.editor[editorID] = keditor;
            $editor.data('keditor', keditor);
            return keditor;
        }
        catch(e){return false;}
    };

    // Init kindeditor with jquery way
    $.fn.kindeditor = function(options)
    {
        return this.each(function()
        {
            setKindeditor(this, options);
        });
    };

    // Init all kindeditor
    var initKindeditor = function(afterInit)
    {
        var $submitBtn = $('form :input[type=submit]');
        if($submitBtn.length)
        {
            $submitBtn.next('#uid').remove();
            $submitBtn.after("<input type='hidden' id='uid' name='uid' value=" + kuid + ">");
        }
        if($.isFunction(afterInit)) afterInit();
        $.each(editor.id, function(key, editorID)
        {
            setKindeditor('#' + editorID);
        });
    };

    // Init all kindeditors when document is ready
    $(initKindeditor);
}(jQuery));


//用于压缩图片的canvas
	var canvas = document.createElement("canvas");
	var ctx = canvas.getContext('2d');
	// 瓦片canvas
	var tCanvas = document.createElement("canvas");
	var tctx = tCanvas.getContext("2d");
	var maxsize = 100 * 1024;
	//使用canvas对大图片进行压缩
	function compress(img) {
		var initSize = img.src.length;
		var width = img.width;
		var height = img.height;
		//alert("----原始长度宽度"+width+"    ,"+height);
		//1994880
		////alert("dadad "+width * height/ 1000000);
		var bili = 1;
		if(width>height){
			if(height>640){
			bili = 640/height;
		}else{
			if(width>480){
				bili = 480/width;
			}else{
				bili=1;
			}
		}
		}else{
			if(width>480){
			bili = 480/width;
		}else{
			if(height>640){
				bili = 640/height;
			}else{
				bili=1;
			}
		}
		}
		//如果图片大于四百万像素，计算压缩比并将大小压至400万以下
		var ratio;
		if ((ratio = width * height / 4000000) > 1) {
			ratio = Math.sqrt(ratio);
			width /= ratio;
			height /= ratio;
		} else {
			ratio = 1;
		}
		canvas.width = width;
		canvas.height = height;
		// 铺底色
		//ctx.fillStyle = "#fff";
		ctx.fillRect(0, 0, canvas.width, canvas.height);
	
		//如果图片像素大于100万则使用瓦片绘制
		var count;
		////alert(width * height / 1000000);
		if ((count = width * height / 1000000) > 1) {
			count = ~~(Math.sqrt(count) + 1); //计算要分成多少块瓦片
			//计算每块瓦片的宽和高
			var nw = ~~(width / count);
			var nh = ~~(height / count);
			//alert("count-----:"+count);
			//alert("nw-----nh:"+nw+"   ,"+nh);
			tCanvas.width = nw;
			tCanvas.height = nh;
			for (var i = 0; i < count; i++) {
				for (var j = 0; j < count; j++) {
					//ratio = 1;
					tctx.drawImage(img, i * nw * ratio, j * nh * ratio, nw * ratio, nh * ratio, 0, 0, nw, nh);
					ctx.drawImage(tCanvas, i * nw, j * nh, nw, nh);
				}
			}
		} else {
			//alert("width---1--height:"+width+"   ,"+height);
			ctx.drawImage(img, 0, 0, width, height);
		}
		//进行最小压缩
		var ndata = canvas.toDataURL('image/jpeg', bili);
		tCanvas.width = tCanvas.height = canvas.width = canvas.height = 0;
		//alert("width---2--height:"+tCanvas.width+"   ,"+canvas.height);
		return ndata;
	}
	
	
	
	function dataURLtoBlob(dataurl) {
    var arr = dataurl.split(','),
    mime = arr[0].match(/:(.*?);/)[1],
    bstr = atob(arr[1]),
    n = bstr.length,
    u8arr = new Uint8Array(n);
    while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
    }
    return new Blob([u8arr], {
        type: mime
    });
}
 
</script>
