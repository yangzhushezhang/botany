//
global_requestAddress = "";

//临时token
// global_token = "ZQBfdzecQbjkK8DSiGX6Stid7R4JBQez";

//登录接口
global_request_login="/user/login";

//添加植物账号接口
global_request_add_account_number="/user/add_account_number";

//查询植物账号列表接口
global_request_get_account_numberInformation="/user/get_account_numberInformation";

//更新能量接口
global_request_updated_leWallet="/user/updated_leWallet";

//刷新最新农场信息
global_request_refresh_botany="/user/refresh_botany";

//获取农场信息列表
global_request_get_farmInformation="/user/get_farmInformation";

//获取所有农场信息列表
global_request_get_farmAccountInformation="/user/get_farmAccountInformation";

//刷新自己工具
global_request_refresh_tools="/user/refresh_tools";

//获取工具内容
global_request_get_tools="/user/get_tools";

//获取所有日志列表
global_request_getLogger="/user/getLogger";

//更新种子(树苗或向日葵)
global_request_update_sunflowers="/user/update_sunflowers";






//首页-获取数据分析 和历史数据
js_global_requestAddress_get_Statistics = "http://8.136.97.179/centralism/get_Statistics"

//获取登录日志接口
js_global_requestAddress_Admin_for_log = "http://8.136.97.179/centralism/Admin_for_log"

//用户管理, 获取玩家列表
js_global_requestAddress_get_players ="http://8.136.97.179/centralism/get_players"

//用户管理, 获取余额变动记录
js_global_requestAddress_get_list ="http://8.136.97.179/centralism/get_list"

//用户管理, 管理员给用户下分
js_global_requestAddress_initiative_below ="http://8.136.97.179/centralism/initiative_below"

//控制台手动刷新请求
js_global_requestAddress_set_todayStatistics = "http://8.136.97.179/centralism/set_todayStatistics"

//获取机器人列表
js_global_requestAddress_setRobot ="http://8.136.97.179/centralism/setRobot"

//修改 玩家的 封禁 活着 解封
js_global_requestAddress_change_players_status ="http://8.136.97.179/centralism/change_players_status"

//修改密码
js_global_requestAddress_change_password ="http://8.136.97.179/centralism/change_password"

//账单,充值
js_global_requestAddress_recharge_for_one ="http://8.136.97.179/centralism/recharge_for_one"

// 修改金额
js_global_requestAddress_reduce_for_one ="http://8.136.97.179/centralism/reduce_for_one"

//获取账单列表
js_global_requestAddress_get_record_lists ="http://8.136.97.179/centralism/get_record_lists"

//提现待处理 确认和驳回请求地址
js_global_requestAddress_withdrawal_audit ="http://8.136.97.179/centralism/withdrawal_audit"

//获取流水等级 会员
js_global_requestAddress_get_consumption_level ="http://8.136.97.179/centralism/get_consumption_level"

//投注记录
js_global_requestAddress_getBetLists ="http://8.136.97.179/centralism/getBetLists"


//获取赔率 列表
js_global_requestAddress_get_LossPerCents ="http://8.136.97.179/centralism/get_LossPerCents"

//添加 赔率
js_global_requestAddress_add_LossPerCentse ="http://8.136.97.179/centralism/add_LossPerCents"

//修改赔率
js_global_requestAddress_change_LossPerCents ="http://8.136.97.179/centralism/change_LossPerCents"

//获取全部玩法
js_global_requestAddress_get_playKinds ="http://8.136.97.179/centralism/get_playKinds"

//聊天室设置
js_global_requestAddress_get_play_kinds_controller ="http://8.136.97.179/centralism/get_play_kinds_controller"

//获取期数
js_global_requestAddress_get_periods ="http://8.136.97.179/centralism/get_periods"

//期数结果
js_global_requestAddress_closeAnAccount ="http://8.136.97.179/centralism/closeAnAccount"

//修改期数结果
js_global_requestAddress_change_resultForPeriods ="http://8.136.97.179/centralism/change_resultForPeriods"

//提前封盘和本期无效请求结果
js_global_requestAddress_invalid_bet ="http://8.136.97.179/centralism/invalid_bet"

//玩法总类添加|修改|删除
js_global_requestAddress_add_playKinds ="http://8.136.97.179/centralism/add_playKinds"

//添加具体玩法项目添加|修改|删除
js_global_requestAddress_add_play_detail_one ="http://8.136.97.179/centralism/add_play_detail_one"

//获取具体玩法项目的详情
js_global_requestAddress_play_detail_for_one ="http://8.136.97.179/centralism/play_detail_for_one"

//系统设置

js_global_requestAddress_get_configs ="http://8.136.97.179/centralism/get_configs"

//添加公告
js_global_requestAddress_addNotify ="http://8.136.97.179/centralism/addNotify"












var getRootPath_webStr = getRootPath_web();

//获取目录路径方法
function getRootPath_web() {

		//获取当前网址，如： http://localhost:8888/eeeeeee/aaaa/vvvv.html
		var curWwwPath = window.document.location.href;
		//获取主机地址之后的目录，如： uimcardprj/share/meun.jsp
		var pathName = window.document.location.pathname;
		var pos = curWwwPath.indexOf(pathName);
		//获取主机地址，如： http://localhost:8888
		var localhostPaht = curWwwPath.substring(0, pos);
		//获取带"/"的项目名，如：/abcd
		var projectName = pathName.substring(0, pathName.substr(1).indexOf('/') + 1);

		// return (localhostPaht + projectName);


		// console.log("当前网址:"+curWwwPath);
		// console.log("主机地址后的目录:"+pos+"----"+pathName);
		// console.log("主机地址:"+localhostPaht);
		// console.log("项目名:"+projectName);


		return projectName;
}



//时间戳转日期时间型工具类
function formatDateTime(inputTime) {
	var date = new Date(inputTime);
	var y = date.getFullYear();
	var m = date.getMonth() + 1;
	m = m < 10 ? ('0' + m) : m;
	var d = date.getDate();
	d = d < 10 ? ('0' + d) : d;
	var h = date.getHours();
	h = h < 10 ? ('0' + h) : h;
	var minute = date.getMinutes();
	var second = date.getSeconds();
	minute = minute < 10 ? ('0' + minute) : minute;
	second = second < 10 ? ('0' + second) : second;
	return y + '-' + m + '-' + d+' '+h+':'+minute+':'+second;
}


function toDecimal2(x) {//金额处理两位小数点
	var f = parseFloat(x);
	if (isNaN(f)) {
		return false;
	}
	var f = Math.round(x*100)/100;
	var s = f.toString();
	var rs = s.indexOf('.');
	if (rs < 0) {
		rs = s.length;
		s += '.';
	}
	while (s.length <= rs + 2) {
		s += '0';
	}
	return s;
}


/**
 * 数字转整数 如 100000 转为10万
 * @param {需要转化的数} num
 * @param {需要保留的小数位数} point
 */
function tranNumber(num, point) {



	let numStr = num.toString()

	// console.log(numStr.length);
	// 一万以内直接返回
	if (numStr.length <=4) {
		return numStr;
	}
	//大于6位数是十万 (以10W分割 10W以下全部显示)
	else if (numStr.length > 4) {
		let decimal = numStr.substring(numStr.length - 4, numStr.length - 4 + point)
		// return parseFloat(parseInt(num / 10000) + ‘.’ + decimal) + ‘万’;
		return parseFloat(parseInt(num / 10000) + '.' + decimal) + '万';
	}
}




//验证是否为数字
function isNumber(value) { //验证是否为数字

	var patrn = /^(-)?\d+(\.\d+)?$/;

	if (patrn.exec(value) == null || value == "") {
		return false

	} else {
		return true

	}

}

/**
 * 获取指定的URL参数值
 * URL:http://www.xxxxx.com/index?name=tyler
 * 参数：paramName URL参数
 * 调用方法:getParam("name")
 * 返回值:tyler
 */
function getParam(paramName) {
	paramValue = "", isFound = !1;
	if (this.location.search.indexOf("?") == 0 && this.location.search.indexOf("=") > 1) {
		arrSource = unescape(this.location.search).substring(1, this.location.search.length).split("&"), i = 0;
		while (i < arrSource.length && !isFound) arrSource[i].indexOf("=") > 0 && arrSource[i].split("=")[0].toLowerCase() == paramName.toLowerCase() && (paramValue = arrSource[i].split("=")[1], isFound = !0), i++
	}
	return paramValue == "" && (paramValue = null), paramValue
}



/*
* 获得时间差,时间格式为 年-月-日 小时:分钟:秒 或者 年/月/日 小时：分钟：秒
* 其中，年月日为全格式，例如 ： 2010-10-12 01:00:00
* 返回精度为：秒，分，小时，天
*/

function GetDateDiff(startTime, endTime, diffType) {
	//将xxxx-xx-xx的时间格式，转换为 xxxx/xx/xx的格式
	startTime = startTime.replace(/\-/g, "/");
	endTime = endTime.replace(/\-/g, "/");

	//将计算间隔类性字符转换为小写
	diffType = diffType.toLowerCase();
	var sTime = new Date(startTime);      //开始时间
	var eTime = new Date(endTime);  //结束时间
	//作为除数的数字
	var divNum = 1;
	switch (diffType) {
		case "second":
			divNum = 1000;
			break;
		case "minute":
			divNum = 1000 * 60;
			break;
		case "hour":
			divNum = 1000 * 3600;
			break;
		case "day":
			divNum = 1000 * 3600 * 24;
			break;
		default:
			break;
	}
	return parseInt((eTime.getTime() - sTime.getTime()) / parseInt(divNum));
}
