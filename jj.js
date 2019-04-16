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
		alert("----原始长度宽度"+width+"    ,"+height);
		//1994880
		//alert("dadad "+width * height/ 1000000);
		var bili = 1;
		if(width>480){
			bili = 480/width;
		}else{
			if(height>640){
				bili = 640/height;
			}else{
				bili=1;
			}
		}
		//如果图片大于四百万像素，计算压缩比并将大小压至400万以下
		var ratio;
		if ((ratio = width * height / 8000000) > 1) {
			ratio = Math.sqrt(ratio);
			width /= ratio;
			height /= ratio;
		} else {
			ratio = 1;
		}
		canvas.width = width;
		canvas.height = height;
		// 铺底色
		ctx.fillStyle = "#fff";
		ctx.fillRect(0, 0, canvas.width, canvas.height);
	
		//如果图片像素大于100万则使用瓦片绘制
		var count;
		//alert(width * height / 1000000);
		if ((count = width * height / 2000000) > 1) {
			count = ~~(Math.sqrt(count) + 1); //计算要分成多少块瓦片
			//计算每块瓦片的宽和高
			var nw = ~~(width / count);
			var nh = ~~(height / count);
			alert("count-----:"+count);
			alert("nw-----nh:"+nw+"   ,"+nh);
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
			alert("width---1--height:"+width+"   ,"+height);
			ctx.drawImage(img, 0, 0, width, height);
		}
		//进行最小压缩
		var ndata = canvas.toDataURL('image/jpeg', bili);
		tCanvas.width = tCanvas.height = canvas.width = canvas.height = 0;
		////alert("width---2--height:"+tCanvas.width+"   ,"+canvas.height);
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
 