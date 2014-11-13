//单个用户
$(".button").click(function() {
	var mydate = new Date();
	var index = $("#index").val();
	//var uid = $(".button").val();
	var uid = $(this).attr("value");
	var index2 = $("#index2").val();
	var postData = {
		uid: uid,
		index: index,
		index2: index2,
		actnum: $("#actnum").val(),
	};
	//加载数据显示
	$("#load").css('display', 'block');
	$.ajax({
		type: 'POST',
		url: 'analyze.php?time='+mydate.getTime(),
		data: postData,
		success: function(result){
			$(".im").empty();
			var obj = JSON.parse(result);
			for(var i in obj){
				var o = obj[i];
				$(".im").append(
					"<img src='img/"+o.name+'.png'+'?'+mydate.getTime()+"' name='vimg' width='500' height='250'/>"
				);
			}
			//返回成功后置顶
			$('body,html').animate({scrollTop:0},1000);
			//加载数据提示
			$("#load").css('display', 'none');
			$("#load-over").css('display', 'block');
			$("#load-over").fadeOut(1500);
			/*
			$("#vimg").attr('src', 'img/'+index+'-'+index2+'-'+uid+'.png'+'?'+mydate.getTime());
			*/
		}
	});
});

//多个用户
$(".button_top").change(function() {
	var mydate = new Date();
	var userNum = $(this).attr("value");
	var postData = {
		userNum: userNum,
	}
	//加载数据显示
	$("#load").css('display', 'block');
	$.ajax({
		type: 'POST',
		url: 'analyze.php?time='+mydate.getTime(),
		data: postData,
		success: function(result){
			$(".im").empty();
			var obj = JSON.parse(result);
			for(var i in obj){
				var o = obj[i];
				$(".im").append(
					"<img src='img/"+o.name+'.png'+'?'+mydate.getTime()+"' name='vimg' width='500' height='250'/>"
				);
			}
			//返回成功后置顶
			$('body,html').animate({scrollTop:0},1000);
			//加载数据提示
			$("#load").css('display', 'none');
			$("#load-over").css('display', 'block');
			$("#load-over").fadeOut(1500);
			/*
			$("#vimg").attr('src', 'img/'+index+'-'+index2+'-'+uid+'.png'+'?'+mydate.getTime());
			*/
		}
	});
});

//返回顶部按钮
$(function(){  
        //当滚动条的位置处于距顶部100像素以下时，跳转链接出现，否则消失  
        $(function () {  
            $(window).scroll(function(){  
                if ($(window).scrollTop()>100){  
                    $("#back-to-top").fadeIn(1500);  
                }  
                else  
                {  
                    $("#back-to-top").fadeOut(1500);  
                }  
            });  
  
            //当点击跳转链接后，回到页面顶部位置  
  
            $("#back-to-top").click(function(){  
                $('body,html').animate({scrollTop:0},1000);  
                return false;  
            });  
        });  
});  
