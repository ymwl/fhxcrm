import {baseUrl,api_v1} from "@/common/config"
var jweixin = require('jweixin-module')
export default {
	//判断是否在微信中    
	isWechat: function () {
		var ua = window.navigator.userAgent.toLowerCase()
		if (ua.match(/micromessenger/i) == 'micromessenger') {
			//console.log('是微信客户端')  
			return true
		} else {
			//console.log('不是微信客户端')  
			return false
		}
	},
	initJssdk: function (data, callback) {
		//注入config权限配置  
		var uri = window.location.href.split('#')[0]//获取当前url然后传递给后台获取授权和签名信息  
		uni.request({
			method: 'POST',
			url: baseUrl + api_v1 + '/' + 'third/share',//你的接口地址 
			header: {
				'content-type': 'application/x-www-form-urlencoded', 
			}, 
			data: {
				url: uri,
				debug: false,
				m_id: data.m_id
			},
			success: (res) => {
				//返回需要的参数appId,timestamp,noncestr,signature等  
				//注入config权限配置  
				jweixin.config({
					debug: false,
					appId	: res.data.data.appId,
					timestamp: res.data.data.timestamp,
					nonceStr: res.data.data.nonceStr,
					signature: res.data.data.signature,
					jsApiList: res.data.data.jsApiList
				})
				if (callback) {
					callback(data)
				}
			}
		})
	},
	//在需要定位页面调用  
	getlocation: function (callback) {
		if (!this.isWechat()) {
			//console.log('不是微信客户端')  
			return
		}
		this.initJssdk(function (res) {
			jweixin.ready(function () {
				jweixin.getLocation({
					type: 'gcj02', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'  
					success: function (res) {
						// console.log(res)  
						callback(res)
					},
					fail: function (res) {
						console.log(res)
					},
					// complete:function(res){  
					//     console.log(res)  
					// }  
				})
			})
		})
	},
	openlocation: function (data, callback) {//打开位置  
		if (!this.isWechat()) {
			console.log('不是微信客户端')
			return
		}
		this.initJssdk(function (res) {
			jweixin.ready(function () {
				jweixin.openLocation({//根据传入的坐标打开地图  
					latitude: data.latitude,
					longitude: data.longitude
				})
			})
		})
	},
	chooseImage: function (callback) {//选择图片  
		if (!this.isWechat()) {
			//console.log('不是微信客户端')  
			return
		}
		//console.log(data)  
		this.initJssdk(function (res) {
			jweixin.ready(function () {
				jweixin.chooseImage({
					count: 1,
					sizeType: ['compressed'],
					sourceType: ['album'],
					success: function (rs) {
						callback(rs)
					}
				})
			})
		})
	},
	//微信支付  
	wxpay: function (data, callback,failback) {
		if (!this.isWechat()) {
			//console.log('不是微信客户端')  
			return
		}
		this.initJssdk(data,function (val) {
			jweixin.ready(function (res) {
				jweixin.chooseWXPay({
					timestamp: val.timeStamp, // 支付签名时间戳，注意微信jssdk中的所有使用timestamp字段均为小写。但最新版的支付后台生成签名使用的timeStamp字段名需大写其中的S字符  
					nonceStr: val.nonceStr, // 支付签名随机串，不长于 32 位  
					package: val.package, // 统一支付接口返回的prepay_id参数值，提交格式如：prepay_id=\*\*\*）  
					signType: val.signType, // 签名方式，默认为'SHA1'，使用新版支付需传入'MD5'  
					paySign: val.paySign, // 支付签名  
					success: function (res) {
						// 支付成功回调
						callback(res)
					},
					fail: function (res) {
						// 支付失败回调
						failback(res)
					},
					// complete:function(res){  
					//     console.log(res)  
					// }  
				})
			})
		})
	},
	//在需要分享页面调用  
	onShareFriend: function (data,callback) {
		// console.log(data)
		if (!this.isWechat()) {
			//console.log('不是微信客户端')  
			return
		}
		this.initJssdk(data,function (val) {
			console.log(val)
			jweixin.ready(function (res) {
				console.log(res)
				jweixin.onMenuShareAppMessage({
					title: val.title, // 分享标题
					desc: val.desc, // 分享描述
					link: val.link, // 分享链接
					imgUrl: val.imgUrl, // 分享图标
					type: '', // 分享类型,music、video或link，不填默认为link
					dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
					success: function (res) { 
						// 用户确认分享后执行的回调函数
						callback(res)
					},
					cancel: function () { 
							// 用户取消分享后执行的回调函数
					}
				})
			})
		})
	},
}  