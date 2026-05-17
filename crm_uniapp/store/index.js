import Vue from 'vue'
import Vuex from 'vuex'
import {token_key, attBaseUrl} from '../common/config'
Vue.use(Vuex)

let lifeData = {};

try{
	// 尝试获取本地是否存在lifeData变量，第一次启动APP时是不存在的
	lifeData = uni.getStorageSync('lifeData');
}catch(e){
	
}

// 需要永久存储，且下次APP启动需要取出的，在state中的变量名
let saveStateKeys = [
	'vuex_user', 
	'vuex_admin',
	'vuex_token',
	'vuex_openid',
	'vuex_notice_tpl',
	'vuex_lasturl',
	'vuex_theme',
	'vuex_upload',
	'vuex_system',
	'vuex_config',
];

// 保存变量到本地存储中
const saveLifeData = function(key, value){
	// 判断变量名是否在需要存储的数组中
	if(saveStateKeys.indexOf(key) != -1) {
		// 获取本地存储的lifeData对象，将变量添加到对象中
		let tmp = uni.getStorageSync('lifeData');
		// 第一次打开APP，不存在lifeData变量，故放一个{}空对象
		tmp = tmp ? tmp : {};
		tmp[key] = value;
		// 执行这一步后，所有需要存储的变量，都挂载在本地的lifeData对象中
		uni.setStorageSync('lifeData', tmp);
	}
}
const store = new Vuex.Store({
	state: {
		// 如果上面从本地获取的lifeData对象下有对应的属性，就赋值给state中对应的变量
		// 加上vuex_前缀，是防止变量名冲突，也让人一目了然
		vuex_user: lifeData.vuex_user ? lifeData.vuex_user : {name: 'admin'}, // 用户信息参数
		vuex_token: lifeData.vuex_token ? lifeData.vuex_token : token_key, // 登录凭证参数 token
		vuex_openid: lifeData.vuex_openid ? lifeData.vuex_openid : '',// 微信openid
		vuex_notice_tpl: lifeData.vuex_notice_tpl ? lifeData.vuex_notice_tpl : [], // 订阅消息
		vuex_theme: lifeData.vuex_theme ? lifeData.vuex_theme : {}, // 主题肤色颜色
		vuex_admin: lifeData.vuex_admin ? lifeData.vuex_admin : {}, // 主题肤色颜色
		vuex_lasturl: lifeData.vuex_lasturl ? lifeData.vuex_lasturl : '/pages/index/index',
		// 如果vuex_version无需保存到本地永久存储，无需lifeData.vuex_version方式
		vuex_version: '1.0.1',
		vuex_selectProduct: {},//选择商品缓存数据
		vuex_filter: {},// 客户筛选数据
		vuex_bfilter: {},//商机筛选数据
		vuex_pfilter: {},//业绩设置页筛选数据
		vuex_rfilter: {},//合同排行筛选数据
		vuex_Afilter: {},//数据中心筛选数据
		vuex_Cfilter: {},//数据中心筛选数据
		vuex_Tfilter: {},//首页中心筛选数据
		vuex_cluesfilter: {},//线索列表筛选数据
		vuex_upload: { cdnurl: attBaseUrl },// 上传配置（cdnurl等，默认使用 config 中的 attBaseUrl，运行时由 login/config 接口覆盖）
		vuex_config: {},// 系统配置数据
		vuex_system: {},// 系统基础配置（web_logo, name等）
		vuex_payConfig: {},// 支付配置（从 login/config 获取）
		vuex_app_id: '',// 微信公众号 app_id（从 login/config 获取）
		vuex_back: false,//首页tabBar 页面是否显示返回按钮
	},
	mutations: {
		$uStore(state, payload) {
			// 判断是否多层级调用，state中为对象存在的情况，诸如user.info.score = 1
			let nameArr = payload.name.split('.');
			let saveKey = '';
			let len = nameArr.length;
			if(nameArr.length >= 2) {
				let obj = state[nameArr[0]];
				for(let i = 1; i < len - 1; i ++) {
					obj = obj[nameArr[i]];
				}
				obj[nameArr[len - 1]] = payload.value;
				saveKey = nameArr[0];
			} else {
				// 单层级变量，在state就是一个普通变量的情况
				state[payload.name] = payload.value;
				saveKey = payload.name;
			}
			// 保存变量到本地，见顶部函数定义
			saveLifeData(saveKey, state[saveKey])
		}
	}
})

export default store