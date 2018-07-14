var __resp_msg__ = { '200': '成功', '300': '用户名已存在', '301': '验证码错误', '302': '用户名或密码错误',"303":"年份和月份不能为空" };

function getRespMsg(code) {
	return __resp_msg__[code];
}
