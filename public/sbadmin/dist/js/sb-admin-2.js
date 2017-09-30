$(function() {

    $('#side-menu').metisMenu();

});

//Loads the correct sidebar on window load,
//collapses the sidebar on window resize.
// Sets the min-height of #page-wrapper to window size
$(function() {
    $(window).bind("load resize", function() {
        topOffset = 50;
        width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
        if (width < 768) {
            $('div.navbar-collapse').addClass('collapse');
            topOffset = 100; // 2-row-menu
        } else {
            $('div.navbar-collapse').removeClass('collapse');
        }

        height = ((this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height) - 1;
        height = height - topOffset;
        if (height < 1) height = 1;
        if (height > topOffset) {
            $("#page-wrapper").css("min-height", (height) + "px");
        }
    });

    var url = window.location;
    var element = $('.sidebar ul.nav a').filter(function() {
        return this.href == url || url.href.indexOf(this.href) == 0;
    }).addClass('active').parent().parent().addClass('in').parent();
    if (element.is('li')) {
        element.addClass('active');
    }
	
	changePlayerType(GetQueryString('_playerType'));
	//$("#sg_player_id").val(GetQueryString('_playerId'));
});

/*
 *ajax请求返回json
 *postData：不为空时使用post方式，其余使用get方式
 */
function send_request_json(urladdress, postData, callback, onerror){
	if(typeof(postData) == 'undefined'){
		var method = 'GET';
	}else{
		var method = 'POST';
	}
	$.ajax({
		type: method,
		url: urladdress,
		data: postData,
		cache: false,
		dataType: 'json',
		success: function(d){
			callback(d);
		},
		error: function(){
			runfunc(onerror);
		}
	});
}

function send_request(callback, urladdress){
	$.ajax({
		type: 'get',
		url: urladdress,
		cache: false,
		success: function(d){
			callback(d);
		}/*,
		error: function(){
			runfunc(onerror);
		}*/
	});
}

function isnull(obj){
	if(typeof(obj) == 'undefined')
		return true;
	return false;
}

function runfunc(func, d){
	if(typeof(func) != 'undefined'){
		if(typeof(func) == 'function'){
			func(d);
		}else {
			eval(func);
		}
	}
}

function changePlayerType(type){
	if(isnull(type) || !type){
		var d = $('#sg_player_type').closest("[data-toggle=dropdown]").next(".dropdown-menu").find("li:first a");
	}else{
		var d = $('#sg_player_type').closest("[data-toggle=dropdown]").next(".dropdown-menu").find("li a[value="+type+"]");
	}
	var type = d.attr("value");
	var name = d.html();
	$('#sg_player_type').attr('value', type).html(name);
}

function linkPage(link, data, isNewWindow){
	if(!isnull(data) && data){
		var param = '&'+$.param(data);
	}else{
		var param = '';
	}
	if(!isnull(isNewWindow) && isNewWindow){
		window.open('/'+link+'?_playerType='+$("#sg_player_type").attr("value")+'&_playerId='+$("#sg_player_id").val()+param);
	}else{
		location.href = '/'+link+'?_playerType='+$("#sg_player_type").attr("value")+'&_playerId='+$("#sg_player_id").val()+param;
	}
}

function linkPlayer(type, id, isNewWindow){
	changePlayerType(type);
	$("#sg_player_id").val(id);
	linkPage('admin/playerInfo', '', isNewWindow);
}

function GetQueryString(name){
	var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
	var r = window.location.search.substr(1).match(reg);
	//console.log(unescape(window.location.search));
	if(r!=null)return  unescape(r[2]); return null;
}

function modifyPwd(){
	$("#modifyPwdModalErr").hide().html('');
	var op = $("#modifypwd_oldpassword").val();
	var p = $("#modifypwd_password").val();
	var p2 = $("#modifypwd_password2").val();
	if(p != p2){
		showModifyPwdErr('两次输入新密码不一致');
		return;
	}
	send_request_json('/admin/modifyPwd', 
		{modify_oldpassword:op, modify_password:p}, 
		function(ret){
		if(ret.errmsg == 'ok'){
			$("#modifyPwdModal").modal('hide');
			alertOk('修改成功');
		}else{
			$("#modifyPwdModalErr").show().html(ret.errmsg);
		}
	});
}

function showModifyPwdErr(err){
	$("#modifyPwdModalErr").html(err).show();
}

function alertOk(txt, cb){
	$("#commonModal").modal();
	$("#commonModalOk").html(txt).show();
	$("#commonModalErr").html('').hide();
	$("#commonModal .modal-footer button").unbind();
	if(typeof(cb) == 'function'){
		$("#commonModal .modal-footer button").click(function(){
			runfunc(cb);
		});
	}
}

function alertErr(txt, cb){
	$("#commonModal").modal();
	$("#commonModalErr").html(txt).show();
	$("#commonModalOk").html('').hide();
	if(typeof(cb) == 'function'){
		$("#commonModal .modal-footer button").click(function(){
			runfunc(cb);
		});
	}
}

function changeSkin(){
	var skin = '';
	if(!$.cookie('sg2admin_skin')){
		skin = '';
	}else{
		skin = $.cookie('sg2admin_skin');
	}
	if(skin == ''){
		skin = 'black';
	}else{
		skin = '';
	}
	$.cookie('sg2admin_skin', skin, { expires: 365, path: '/' });
	showskin();
}

function showskin(){
	if(!$.cookie('sg2admin_skin')){
		skin = '';
	}else{
		skin = $.cookie('sg2admin_skin');
	}
	$("body").attr('class', skin);
}

function checkBrowser(){
	var Sys = {};
	var ua = navigator.userAgent.toLowerCase();
	var s;
	(s = ua.match(/rv:([\d.]+)\) like gecko/)) ? Sys.ie = s[1] :
	(s = ua.match(/msie ([\d.]+)/)) ? Sys.ie = s[1] :
	(s = ua.match(/firefox\/([\d.]+)/)) ? Sys.firefox = s[1] :
	(s = ua.match(/chrome\/([\d.]+)/)) ? Sys.chrome = s[1] :
	(s = ua.match(/opera.([\d.]+)/)) ? Sys.opera = s[1] :
	(s = ua.match(/version\/([\d.]+).*safari/)) ? Sys.safari = s[1] : 0;

	if (Sys.ie){
		alert('IE已经被请出了太阳系，请使用火狐浏览器或谷歌浏览器！');
		location.href = 'http://www.firefox.com.cn/download/';
	}
	/*if (Sys.firefox) document.write('Firefox: ' + Sys.firefox);
	if (Sys.chrome) document.write('Chrome: ' + Sys.chrome);
	if (Sys.opera) document.write('Opera: ' + Sys.opera);
	if (Sys.safari) document.write('Safari: ' + Sys.safari);*/
}

function getItemTranslate(str, cb){
	send_request_json('/admin/ajaxTransDropstr', 
	{
		dropstr: str
	}, 
	function(ret){
		cb(ret.data);
	});
}

function commonSend(action, param, okwords, reload, cb){
	showLoading();
	send_request_json('/admin/'+action, 
	param, 
	function(ret){
		if(ret.err == 'ok'){
			if(isnull(okwords) || okwords == ''){
				if(!isnull(reload) && reload){
					location.reload();
				}
				runfunc(cb, ret);
			}else{//有反馈弹框
				alertOk(okwords, function(){
					if(!isnull(reload) && reload){
						location.reload();
					}
					runfunc(cb, ret);
				});
			}
			hideLoading();
		}else{
			alertErr(ret.err);
			hideLoading();
		}
	});
}



	
function showLoading(){
	resizeLoading();
	$("#loading_mask").show();
}
function hideLoading(){
	$("#loading_mask").hide();
}
function resizeLoading(){
	$("#loading_mask").height($(window).height())
}
$(window).resize(function(){
  resizeLoading();
});