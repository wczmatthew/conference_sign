function upyunPicUpload(domid,width,height,cate){
	art.dialog.data('width', width);
	art.dialog.data('height', height);
	art.dialog.data('domid', domid);
	art.dialog.data('lastpic', $('#'+domid).val());
	//art.dialog.open('?g=Admin&m=Upyun&a=upload&cate='+cate+'&width='+width,{lock:true,title:'上传图片',width:600,height:400,yesText:'关闭',background: '#000',opacity: 0.45});
	art.dialog.open(site_url+'/cate/'+cate+'/width/'+width, {lock:true, title:'上传图片',width:600,height:400,yesText:'关闭',background:'#000',opacity:0.45});
}
function viewImg(domid){
	if($('#'+domid).val()){
		var html='<img src="'+$('#'+domid).val()+'"/>';
	}else{
		var html='没有图片';
	}
	art.dialog({title:'图片预览',content:html,lock:true,background: '#000',opacity: 0.45});
}