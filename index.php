<?php
include("config/setup.inc");
?><!doctype html>
<html>
	<head>
		<title><?php echo SETTINGS_AWS_BUCKET; ?></title>
		<link rel="stylesheet" type="text/css" href="css/main.css" />
		<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="js/jquery-ui-1.8.17.custom.min.js"></script>
	</head>
	<body>

		<div id="tools">
			<div id="file-path-current">
				<span class="file-path-current-icon"></span>
				<span class="file-path-current-text"></span>
				<span class="file-path-current-ui"></span>
			</div>
			<ul id="file-path-list"></ul>
			<button class="icon-btn" id="nav-new-dir-btn" onclick="uploader.newFolder();"></button>			
			
			<input type="text" id="current_link" readonly />
			
			<span id="loader"></span>
		</div>
		
		<div id="file-headers">
			<span class="file-header-name">Filename</span>
			<span class="file-header-modified">Modified</span>
			<span class="file-header-size">Size</span>
		</div>
		<div id="files"></div>
		<div id="uploads"></div>
		<div id="drop_area">DROP IT!</div>
		<script type="text/javascript">
/* 		var finput = document.getElementById('file_input'); */
		var uploadCheck = null;
		var isEncoding = false;
		var dropArea = document.getElementById('drop_area');

		var uploader = {
			maskon: false,
			uploadQueue: [],
			isUploading: false,
			qIndex: 0,
			current_upload: null,
			updateTimeout: null,
			current_path: '/',
			path_history: [],
			bucket_string: '<?php echo SETTINGS_AWS_BUCKET; ?>',
			init: function(){
				this.getFiles('/');
			},
			newFolder: function(){
				var fname = prompt("Folder name:");
				if(fname){
					$.ajax({
						url: 'ajax/new-dir.php',
						data: {name:fname,path:uploader.current_path},
						type: 'POST',
						success: function(data){
							console.log(data);
							clearTimeout(uploader.updateTimeout);
							uploader.updateTimeout = setTimeout('uploader.getFiles("'+uploader.current_path+'");', 200);
						}
					});	
				}
			},
			addFiles: function(files){
				for(var i=0;i<files.length;i++){
					var thisid = 'upload-'+rnd();
					$ele = $('<div id="'+thisid+'" class="upload upload-queued">'+
					'<span class="upload-filename">'+
					files[i].fileName+
					'</span>'+
					'<span class="upload-progress">'+
					'<span class="progress-holder" style="display:none;"></span>' +
					'<span class="progress-text">Queued</span>'+
					'</span>'+
					'</div>');
					var item = {
						file: files[i],
						id: thisid,
						$e: $ele
					};
					this.uploadQueue.push(item);
					$("#uploads").append(item.$e);
				}
				console.log(this.isUploading);
				if(this.isUploading==false){
					$("#uploads").slideDown();
					uploader.nextQueue();
				}
			},
			nextQueue: function(){
				this.isUploading=true;
				this.current_upload = this.uploadQueue[this.qIndex];
				uploader.uploadFile(function(resp){

					uploader.current_upload.$e.fadeOut('fast');
					uploader.current_upload.$e.removeClass('upload-now');
					uploader.qIndex++;
					uploader.isUploading=false;
					
					clearTimeout(uploader.updateTimeout);
					uploader.updateTimeout = setTimeout('uploader.getFiles("'+uploader.current_path+'");', 1200);

					if(uploader.qIndex < (uploader.uploadQueue.length)){
						uploader.nextQueue();
					}else{
						$("#uploads").animate({height:0});
					}

				});
			},
			uploadFile: function(callback){
				console.log(uploader.current_path);
				this.isUploading=true;
				var file = uploader.current_upload.file;
				var xhr = new XMLHttpRequest;
				uploader.current_upload.$e.removeClass('upload-queued');
				uploader.current_upload.$e.addClass('upload-now');
				uploader.current_upload.$e.find('.progress-holder').fadeIn('fast');
				xhr.open('post', 'ajax/handler.php', true);
				
				xhr.upload.onprogress = function(e){ 
					var per = Math.round((e.loaded/e.total)*100);
					var loaded = bytesToSize(e.loaded);
					var total = bytesToSize(e.total);
					var maxW = uploader.current_upload.$e.find('.progress-holder').width();
					var ww = Math.round( (maxW * (e.loaded/e.total)*10))/10;
					ww = ww - 300;
					uploader.current_upload.$e.find('.progress-holder').css('background-position-x', ww+'px');
					uploader.current_upload.$e.find('.progress-text').html(loaded + ' of ' + total);
				}
				xhr.onreadystatechange = function () {
					if(this.readyState==4){
						callback(JSON.parse(this.responseText));
					}
				}
				xhr.setRequestHeader('Content-Type', 'multipart/form-data');
				xhr.setRequestHeader('X-File-Name', file.name);
				xhr.setRequestHeader('X-File-Size', file.size);
				xhr.setRequestHeader('X-File-Type', file.type);
				xhr.setRequestHeader('X-File-Path', uploader.current_path);
				xhr.send(file);
			},
			getFiles: function(dir){

				this.current_path = dir;
				var last = uploader.bucket_string;
				if(this.current_path!='/'){
					var paths = this.current_path.substr(0,this.current_path.length-1).split('/');
					paths[paths.length] = uploader.bucket_string;
					console.log(paths);
					var list_html = [];
					var full = '';
					for(var i in paths){
						if(paths[i]!=uploader.bucket_string){
							full += '/'+paths[i];
							list_html.push('<li data-path="'+full+'" class="file-path-list-item">'+paths[i]+'</li>');
						}
					}
					console.log(list_html);
					list_html.reverse();
					list_html.push('<li data-path="/" class="file-path-list-item home-path">'+uploader.bucket_string+'</li>');
					$("#file-path-list").html('');
					var cur = $(list_html.shift()).text();
					for(var i in list_html){
						$("#file-path-list").append(list_html[i]);
					}
					//$("#file-path-list").html('');
					$("#file-path-current").removeClass('disabled_path');
					$(".file-path-current-text").text(cur);
				}else{
					$("#file-path-list").html('');
					$("#file-path-current").addClass('disabled_path');
					$(".file-path-current-text").text(uploader.bucket_string);
				}
				
				
				
				
				this.path_history.push(dir);
				if(dir.length > 1){
					$("#nav-back-btn").removeAttr('disabled');
				}else{
					$("#nav-back-btn").attr('disabled','disabled');
				}
				
//				$("#current_path").val(this.current_path);
				this.showLoader();
				

				$.get('ajax/get-files.php',{path:dir},function(data){
					console.log(data);

					$ul = $('<ul></ul>');
					for(var i in data){
						if(data[i].time>0){
							var date = new Date(data[i].time*1000).format('M j, Y g:i A');
						}else{
							var date = '-';
						}
						var cclass = 'item-'+data[i].type;
						var clickType = 'click-'+data[i].type;
						if(data[i].type=='file'){
							var parts = data[i].name.split('.');
							var ext = parts[parts.length-1].toLowerCase();
							switch(ext){
								case 'png':
								case 'gif':
								case 'jpg':
								case 'jpeg':
								case 'tiff':
									cclass='item-image';
									break;
								case 'zip':
									cclass='item-zip';
									break;
								case 'psd':
									cclass='item-psd';
									break;
							}
						}
						var html = '<li class="file-item '+clickType+'" data-path="'+data[i].path+'">'+
						'<span class="file-icon '+cclass+'"></span>'+
						'<span class="file-name">'+data[i].name+'</span>'+
						'<span class="file-time">'+date+'</span>'+
						'<span class="file-size">'+bytesToSize(data[i].size)+'</span>'+
						'</li>';
						$ul.append(html);
					}
					$("#files").html($ul);
					uploader.setupDraggable();
					uploader.hideLoader();

				});
			},
			setupDraggable: function(){
				$(".file-item").draggable({
					containment: 'window',
					cursor: 'move',
					left: 5,
					bottom: 5,
					helper: function(){
						var txt = $(this).find('.file-name').text();
						var cl = $(this).find('.file-icon').attr('class');
						return $('<div id="file-dragger"><span class="file-icon '+cl+'"></span>'+txt+'</div>');
					}
				});
				$(".click-dir").droppable({
					activeClass: 'ui-state-active',
					hoverClass: 'ui-state-hovering',
					tolerance: 'intersect',
					drop: function(ev,ui){
						var path1 = $(ui.draggable).attr('data-path');
						var path2 = $(this).attr('data-path') + path1.split('/').pop();
						uploader.moveFile(path1,path2);
					}
				});
			},
			moveFile: function(from,to){
				$.ajax({
					url: 'ajax/move-file.php',
					data: {from:from,to:to},
					type: 'POST',
					success: function(data){
						uploader.getFiles(uploader.current_path);
					}
				});
			},
			removeFile: function(ele){
				if(confirm("Are you sure you want to permanently delete this?")){
					var path = unescape($(ele).attr('data-path'));
					$.get('ajax/remove-file.php',{path:path},function(data){
						clearTimeout(uploader.updateTimeout);
						uploader.updateTimeout = setTimeout('uploader.getFiles("'+uploader.current_path+'")', 100);
					});
				}
			},
			deleteFile: function(path){
				this.showLoader();
				$.get('ajax/remove-file.php',{path:path},function(data){
					console.log(data);
					clearTimeout(uploader.updateTimeout);
					uploader.updateTimeout = setTimeout('uploader.getFiles("'+uploader.current_path+'")', 100);
				});			
			},
			downloadFile: function(ele){
				var url = unescape($(ele).attr('data-link'));
				window.open('dl.php?path='+url);
				return false;
			},
			showLoader: function(){
				$("#loader").stop(true,true).fadeIn('fast');
			},
			hideLoader: function(){
				$("#loader").stop(true,true).fadeOut('fast');							
			}
		};
		$(function(){
			uploader.init();
			$(".file-item").live('click', function(e){
				if(!e.metaKey){
					$(".file-item").removeClass('selected-row');			
				}
				var path = $(this).attr('data-path');
				$("#current_link").val('http://drop.adrd.co/'+escape(path));
				$(this).addClass('selected-row');
			});
			$("#current_link").click(function(e){
				this.select();
			});
			$(".click-dir").live('dblclick', function(e){
				var dir = $(this).attr('data-path');
				uploader.getFiles(dir);
			});
			$(".click-file").live('dblclick', function(e){
				var path = $(this).attr('data-path');
				window.location='dl.php?path='+path;
			});
/*
			$("#current_path").live('keypress', function(e){
				if(e.which==13){
					$(this).blur();
					uploader.getFiles($(this).val());
				}
			});
*/
			$("#file-path-current").live('click', function(e){
				if($(".file-path-list-item").length>0){
					$("#file-path-list").stop(true,true).slideToggle('fast');
					e.stopPropagation();
					$(window).bind('click',function(e){
						$("#file-path-list").stop(true,true).slideUp('fast');
						$(window).unbind('click');
					});
				}
			});
			$(".file-path-list-item").live('click', function(e){
				var path = $(this).attr('data-path');
				uploader.getFiles(path.substr(1)+'/');
				$("#file-path-list").stop(true,true).slideUp('fast');
				$(window).unbind('click');
			});
			$(window).keydown(function(e){
				if($("#current_path").is(':focus')){
				
				}else{
					if(e.which==8){
						if($(".selected-row").length > 0){
							var conf = confirm('Are you sure you want to delete the selected files?');
							if(conf){
								$(".selected-row").each(function(i,e){
									var path = $(e).attr('data-path');
									uploader.deleteFile(path);								
								});
							}
						}
						return false;				
					}
				}
			});
			document.addEventListener('dragenter', function(e){
				if(uploader.maskon==false){
					uploader.maskon=true;
					$(dropArea).stop(true,true).fadeIn('fast');
				}
				e.stopPropagation();
				e.preventDefault();		
			}, false);

			dropArea.addEventListener('dragover', function(e){
				e.stopPropagation();
				e.preventDefault();
			}, false);
			dropArea.addEventListener('dragleave', function(e){
				$(dropArea).stop(true,true).fadeOut();
				uploader.maskon=false;
				e.stopPropagation();
				e.preventDefault();
			}, false);
			dropArea.addEventListener('drop', function(e){
				uploader.addFiles(e.dataTransfer.files);
				$(dropArea).stop(true,true).fadeOut();
				uploader.maskon=false;
				e.stopPropagation();
				e.preventDefault();
			}, false);

		});

		function rnd(){
			var d = new Date().getTime();
			return d + "_" + Math.round(Math.random()*111);
		}
		
		function round_number(num, dec){
			return Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
		}
		function bytesToSize(fs) {
		  if (fs == 0)          { return '-'; }
		  if (fs >= 1073741824) { return round_number(fs / 1073741824, 2) + ' GB'; }
		  if (fs >= 1048576)    { return round_number(fs / 1048576, 2) + ' MB'; }
		  if (fs >= 1024)       { return round_number(fs / 1024, 0) + ' KB'; }
		  return fs + ' B';
		}

Date.prototype.format=function(format){var returnStr='';var replace=Date.replaceChars;for(var i=0;i<format.length;i++){var curChar=format.charAt(i);if(i-1>=0&&format.charAt(i-1)=="\\"){returnStr+=curChar}else if(replace[curChar]){returnStr+=replace[curChar].call(this)}else if(curChar!="\\"){returnStr+=curChar}}return returnStr};Date.replaceChars={shortMonths:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],longMonths:['January','February','March','April','May','June','July','August','September','October','November','December'],shortDays:['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],longDays:['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],d:function(){return(this.getDate()<10?'0':'')+this.getDate()},D:function(){return Date.replaceChars.shortDays[this.getDay()]},j:function(){return this.getDate()},l:function(){return Date.replaceChars.longDays[this.getDay()]},N:function(){return this.getDay()+1},S:function(){return(this.getDate()%10==1&&this.getDate()!=11?'st':(this.getDate()%10==2&&this.getDate()!=12?'nd':(this.getDate()%10==3&&this.getDate()!=13?'rd':'th')))},w:function(){return this.getDay()},z:function(){var d=new Date(this.getFullYear(),0,1);return Math.ceil((this-d)/86400000)}, W:function(){var d=new Date(this.getFullYear(),0,1);return Math.ceil((((this-d)/86400000)+d.getDay()+1)/7)},F:function(){return Date.replaceChars.longMonths[this.getMonth()]},m:function(){return(this.getMonth()<9?'0':'')+(this.getMonth()+1)},M:function(){return Date.replaceChars.shortMonths[this.getMonth()]},n:function(){return this.getMonth()+1},t:function(){var d=new Date();return new Date(d.getFullYear(),d.getMonth(),0).getDate()},L:function(){var year=this.getFullYear();return(year%400==0||(year%100!=0&&year%4==0))},o:function(){var d=new Date(this.valueOf());d.setDate(d.getDate()-((this.getDay()+6)%7)+3);return d.getFullYear()},Y:function(){return this.getFullYear()},y:function(){return(''+this.getFullYear()).substr(2)},a:function(){return this.getHours()<12?'am':'pm'},A:function(){return this.getHours()<12?'AM':'PM'},B:function(){return Math.floor((((this.getUTCHours()+1)%24)+this.getUTCMinutes()/60+this.getUTCSeconds()/ 3600) * 1000/24)}, g:function(){return this.getHours()%12||12},G:function(){return this.getHours()},h:function(){return((this.getHours()%12||12)<10?'0':'')+(this.getHours()%12||12)},H:function(){return(this.getHours()<10?'0':'')+this.getHours()},i:function(){return(this.getMinutes()<10?'0':'')+this.getMinutes()},s:function(){return(this.getSeconds()<10?'0':'')+this.getSeconds()},u:function(){var m=this.getMilliseconds();return(m<10?'00':(m<100?'0':''))+m},e:function(){return"Not Yet Supported"},I:function(){return"Not Yet Supported"},O:function(){return(-this.getTimezoneOffset()<0?'-':'+')+(Math.abs(this.getTimezoneOffset()/60)<10?'0':'')+(Math.abs(this.getTimezoneOffset()/60))+'00'},P:function(){return(-this.getTimezoneOffset()<0?'-':'+')+(Math.abs(this.getTimezoneOffset()/60)<10?'0':'')+(Math.abs(this.getTimezoneOffset()/60))+':00'},T:function(){var m=this.getMonth();this.setMonth(0);var result=this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/,'$1');this.setMonth(m);return result},Z:function(){return-this.getTimezoneOffset()*60},c:function(){return this.format("Y-m-d\\TH:i:sP")},r:function(){return this.toString()},U:function(){return this.getTime()/1000}};

		</script>
	</body>
</html>
