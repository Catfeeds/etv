/*!
 * 自定义脚本方法
 */
//上传弹出提示
function mainAjaxMsg(status,msg){
	var style = "";
	var title = "";
	switch(status){
		case 0:
			style = "alert-danger";
			title = "错误：";
			break;
		case 1:
			style = "alert-success";
			title = "成功：";
			break;
		default:
			style = "alert-warning";
			title = "警告：";
			break;
	}
	var html = '<div class="alert '+style+'"><p><strong>'+title+'</strong>'+msg+'<button type="button" class="close" ><i class="ace-icon fa fa-times"></i></button></p></div>';
	if($("div.alert").length>0){
		$("div").detach(".alert");//移除被選元素 
	}
	$(".page-header").prepend(html);
	setTimeout(function(){
		// $("div").detach(".alert");
	},3000);

	$(".page-header").delegate('.close', 'click', function(event) {
		$("div").detach(".alert");
	});
}