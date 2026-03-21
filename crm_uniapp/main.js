import Vue from 'vue'
import App from './App'

import {getQueryVariable} from '@/common/mUtils'
import {baseUrl} from '@/common/config'


/**
 * tabBar页面路径列表 (用于链接跳转时判断)
 * tabBarLinks为常量, 无需修改
 */
const tabBarLinks = [
    'pages/index/index',
    'pages/client/index',
		'pages/business/index',
		'pages/clues/list',
		'pages/presentation/index',
];

Vue.config.productionTip = false

App.mpType = 'app'

// 引入全局uView
import uView from 'uview-ui'
Vue.use(uView);

// 此处为演示vuex使用，非uView的功能部分
import store from '@/store'

// 引入uView提供的对vuex的简写法文件
let vuexStore = require('@/store/$u.mixin.js')
Vue.mixin(vuexStore)

const app = new Vue({
    store,
    ...App
})



// http拦截器，将此部分放在new Vue()和app.$mount()之间，才能App.vue中正常使用
import httpInterceptor from '@/common/http.interceptor.js'
Vue.use(httpInterceptor, app)

// http接口API抽离，免于写url或者一些固定的参数
import httpApi from '@/common/http.api.js'
Vue.use(httpApi, app)

app.$mount()


	/**
	 * 获取tabBar页面路径列表
	 */
	const getTabBarLinks = () => {
		return tabBarLinks;
	}

	//路径转化
const getPath = (path) => {
	if (path.indexOf('?') != -1) {
		let arr = path.split('?');
		return arr[0];
	}
	return path;
}

	/**
	 * 跳转到指定页面
	 * 支持tabBar页面
	 */
	const navigationTo = (url) => {
		// console.log('跳转URL：',url)
		if (!url || url.length == 0) {
			return false;
		}
		let tabBarLinks = getTabBarLinks();
		// tabBar页面
		if (tabBarLinks.indexOf(url) > -1) {
			uni.switchTab({
				url: '/' + url
			});
		} else {
			// 是否是跳转直播间
			if(url.indexOf('room_id')>=0) {
				// 普通页面
				uni.navigateTo({
					url: url
				});
				return false;
			}

			// https网页
			if(url.indexOf('https')>=0 || url.indexOf('http')>=0) {
				window.location.replace(url);
				return false;
			}
			// 拨打手机号
			let reg = /^1[3456789]\d{9}$/;
			if (reg.test(url)) {
				console.log(url)
				uni.makePhoneCall({
					phoneNumber: url
				})
				return false
			}
			// 地理位置跳转
			if(url.indexOf('map')>=0){
				const latitude = parseFloat(getQueryVariable('lat',url)) 
				const longitude = parseFloat(getQueryVariable('lng',url))
				uni.openLocation({
					latitude,
					longitude,
					name: getQueryVariable('name',url),
					address: getQueryVariable('name',url),
					success: function (res) {
						console.log('success',res);
				},
				fail: function (res) {
					console.log(res);
					}
				},)
				return false
			}
			console.log(url,'已触发跳转')
			// 普通页面
			uni.navigateTo({
				url: '/' + url
			});
		}
	}

	// 错误提示
	const showError = (msg,) => {
		uni.showModal({
			title: '提示',
			content: msg,
			showCancel: false,
			success: function (res) {
				if (res.confirm) {
					console.log('用户点击确定');
				} else if (res.cancel) {
					console.log('用户点击取消');
				}
			}
		});
	}

	// 执行用户登录
	const doLogin = () => {
		// return
		// 保存当前页面
		// let pages = getCurrentPages(); // 获取当前打开过的页面路由数组
		// if (pages.length) {
		// 	let currentPage = pages[pages.length - 1]
		// 	let pagesFullPath = pages[pages.length - 1].__page__.fullPath //获取当前页面路由和参数
		// 	"pages/login/login" != currentPage.route &&
		// 		uni.setStorageSync("pagesFullPath", pagesFullPath);
		// }
		// 跳转授权页面
		
		uni.navigateTo({
			url: "/pages/login/index"
		});
	}

	// 订阅消息（微信小程序）
	const subscriptionInfo = (type) => {
		let tmplIds = [],subscribe = uni.getStorageSync('lifeData').vuex_notice_tpl;
		Vue.prototype.$u.api.getNoticeTpl({type:type}).then(res => {
			if(res.code == 1) {
				subscribe=res.data;
				// 是否有存储订阅消息数据
				if(!subscribe) return
				subscribe.forEach((item,index) => {
					tmplIds.push(item.values.tpl_id) 
				});
				// 是否有存储订阅消息数据
				if(!subscribe) return
				// 没有订阅模板 退出
				if(tmplIds.length == 0) return
				// 调用订阅消息API
				// #ifdef MP-WEIXIN
				uni.requestSubscribeMessage({
					tmplIds: tmplIds,
					success (res) { 
						console.log(res.errMsg)
					},
					fail (res) {
						console.log(res)
					},
					complete (res) {
					}
				})
				//#endif
				// console.log(res)
			}
		})	
	}

	Vue.prototype.$reuse = {navigationTo,showError,doLogin,subscriptionInfo,getPath};

	/**
	 * 获取资源完整URL
	 * @param {string} url - 资源路径
	 * @returns {string} 完整URL
	 * 优先级：vuex_system.domain > config.baseUrl
	 */
	Vue.prototype.$getResUrl = function(url) {
		console.log(this.vuex_system);
		if (!url) return '';
		// 已经是完整URL，直接返回
		if (url.substr(0, 7).toLowerCase() === 'http://' || url.substr(0, 8).toLowerCase() === 'https://') {
			return url;
		}
		// 获取域名：优先使用后台配置的 domain
		let domain = '';
		try {
			domain = this.vuex_system && this.vuex_system.domain ? this.vuex_system.domain : baseUrl;
		} catch (e) {
			domain = baseUrl;
		}
		// 确保路径以 / 开头
		if (url.charAt(0) !== '/') {
			url = '/' + url;
		}
		return domain + url;
	}