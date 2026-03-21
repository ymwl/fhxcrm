import {baseUrl,api_v1} from './config'


// 这里的vm，就是我们在vue文件里面的this，所以我们能在这里获取vuex的变量，比如存放在里面的token
// 同时，我们也可以在此使用getApp().globalData，如果你把token放在getApp().globalData的话，也是可以使用的
const install = (Vue, vm) => {
	var isRefreshing=false;
	Vue.prototype.$u.http.setConfig({
		baseUrl:  baseUrl + api_v1,
		// 如果将此值设置为true，拦截回调中将会返回服务端返回的所有数据response，而不是response.data
		// 设置为true后，就需要在this.$u.http.interceptor.response进行多一次的判断，请打印查看具体值
		originalData: true,
		// 设置自定义头部content-type
		header: {
			'content-type': 'application/x-www-form-urlencoded',
			"Accept": "application/json",
			// "Content-Type": "application/json; charset=UTF-8"
			'Access-Control-Allow-Origin': '*',
			'Access-Control-Allow-Methods': 'GET,POST,PUT,DELETE,OPTIONS',
			'Access-Control-Allow-Headers': 'Content-Type, X-Requested-With, Authorization'
		},
	});
	// 请求拦截，配置Token等参数
	Vue.prototype.$u.http.interceptor.request = (config) => {
		// 方式一，存放在vuex的token，假设使用了uView封装的vuex方式，见：https://uviewui.com/components/globalVariable.html
		config.header.token = vm.vuex_token;

		// 方式二，如果没有使用uView封装的vuex方法，那么需要使用$store.state获取
		// config.header.token = vm.$store.state.token;

		// 方式三，如果token放在了globalData，通过getApp().globalData获取
		// config.header.token = getApp().globalData.username;

		// 方式四，如果token放在了Storage本地存储中，拦截是每次请求都执行的，所以哪怕您重新登录修改了Storage，下一次的请求将会是最新值
		// const token = uni.getStorageSync('token');
		// config.header.token = token;

		return config;
	}
	// 响应拦截，判断状态码是否通过
	Vue.prototype.$u.http.interceptor.response = (res) => {


		// 如果把originalData设置为了true，这里得到将会是服务器返回的所有的原始数据
		// 判断可能变成了res.statueCode，或者res.data.code之类的，请打印查看结果
		// console.log(res)

		// // 未登录
		// if(res.statusCode == 401) {
		// 	// 跳转登录
		// 	vm.$reuse.doLogin()
		// }
		// if (res.statusCode !== 200 || typeof res.data !== 'object') {
		// 	vm.$reuse.showError(res.data.msg)
		// 	return false;
		// }
		// if(res.statusCode == 200) {
		// 	// 如果把originalData设置为了true，这里return回什么，this.$u.post的then回调中就会得到什么
		// 	if(res.data.code == 0 && res.data.data == null) {
		// 		vm.$reuse.showError(res.data.msg)
		// 	}
		// 	if(res.data.code == 401) {
		// 		// 跳转登录
		// 		vm.$reuse.doLogin()
		// 	}
		// 	return res.data;
		// } else return false;

		if(res.statusCode == 200) {
			/*if(!isRefreshing &&
				!vm.$u.test.contains(vm.$u.http.options.url,'login/config') &&
				!vm.$u.test.contains(vm.$u.http.options.url,'yzm/index') &&
				!vm.$u.test.contains(vm.$u.http.options.url,'login/index') &&
				!vm.$u.test.contains(vm.$u.http.options.url,'ajax/refreshtoken')){
				let expire=vm.vuex_admin.expire;
				var timestamp = Date.parse(new Date());
				if(expire<(timestamp/1000-600)){
					isRefreshing=true;
					vm.$u.post('ajax/refreshtoken').then(res => {
						vm.$u.vuex('vuex_token', res.data.token)
						vm.$u.vuex('vuex_admin', res.data);//存储登录的信息
					}).finally(() => {
						isRefreshing = false
					})
				}
			}*/

			// res为服务端返回值，可能有code，result等字段
			// 这里对res.result进行返回，将会在this.$u.post(url).then(res => {})的then回调中的res的到
			// 如果配置了originalData为true，请留意这里的返回值

			//返回 code为0 数据为空进行提示
			if(res.data.code == 0 && res.data.data == null) {
				vm.$u.toast(res.data.msg);
			}
			return res.data;
		} else if(res.statusCode == 401) {
			uni.setStorageSync('fullPath401', vm.$route.fullPath);
			// 401为token失效或未登录，这里跳转登录
			vm.$u.toast('验证失败，请重新登录');
			// 清除token
			vm.$u.vuex('vuex_token','')
			vm.$u.throttle(
				// 跳转登录
				vm.$reuse.doLogin
			,1000,false)// 节流 防止多次跳转
			return false;
		} else if(res.statusCode == 500) {
			// 如果返回false，则会调用Promise的reject回调，
			// 并将进入this.$u.post(url).then().catch(res=>{})的catch回调中，res为服务端的返回值
			vm.$reuse.showError("网络请求出错")
			return false;
		} else if (res.statusCode == 403){
			// 没有权限
			vm.$reuse.showError(res.data.msg)
		} else {
			// 如果返回false，则会调用Promise的reject回调，
			// 并将进入this.$u.post(url).then().catch(res=>{})的catch回调中，res为服务端的返回值
			return false;
		}
	}
}

export default {
	install
}
