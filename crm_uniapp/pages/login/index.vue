<template>
	<view class="container" :style="{backgroundColor: vuex_theme.color,color: vuex_theme.bgColor}">
		<view class="image-content">
			<image style="max-width: 300rpx; max-height: 300rpx; "  :src="$getResUrl(vuex_system.web_logo)" />
		</view>
		<view class="u-m-t-45 u-m-b-60 title">{{vuex_system.name}}</view>
		<view class="u-m-b-15 u-f">
			<u-icon name="star" color="#F2F2F2" size="13"></u-icon>
			<text class="u-p-l-15 u-p-r-15">客户管理</text>

			<u-icon name="star" color="#F2F2F2" size="13"></u-icon>
		</view>
    <view class="u-m-b-15 u-f">
      <u-icon name="star" color="#F2F2F2" size="13"></u-icon>
      <text class="u-p-l-15 u-p-r-15">订单管理</text>


      <u-icon name="star" color="#F2F2F2" size="13"></u-icon>
    </view>
    <view class="u-m-b-15 u-f">
      <u-icon name="star" color="#F2F2F2" size="13"></u-icon>
      <text class="u-p-l-15 u-p-r-15">合同管理</text>
      <u-icon name="star" color="#F2F2F2" size="13"></u-icon>
    </view>
    <view class="u-m-b-15 u-f">
      <u-icon name="star" color="#F2F2F2" size="13"></u-icon>
      <text class="u-p-l-15 u-p-r-15">业绩管理</text>
      <u-icon name="star" color="#F2F2F2" size="13"></u-icon>
    </view>
		<!-- 底部按钮 -->
		<view class="bottom">

			<view class="u-m-b-25">
				<u-button type="success" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor,fontSize: '30rpx',fontWeight: '600'}" :ripple="true" @click="accountLogin">
					<view class="u-flex">
						<u-icon class="u-m-r-10" name="lock-fill" size="50"></u-icon>
						<text>账号登录</text>
					</view>
				</u-button>
			</view>
		</view>
	</view>
</template>
<script>
	import {processingImages,getImgUrl,isWeiXinBrowser} from '@/common/mUtils'
	import {loginfunc} from '@/common/fa.mixin.js'
	export default {
		mixins:[loginfunc],
		data() {
			return {
				dealeruser: {},
				isThreeLogin: false,
			}
		},
		filters: {
			//图片地址url 拼接
			changImg(val) {
				if (val) {
					return getImgUrl(val)
				} else {
					return ''
				}
			},
		},
		components: {

    },
		onLoad(e) {
			this.type = e.type
			// #ifdef MP-WEIXIN
			this.isThreeLogin = true;
			// #endif

			// #ifdef H5
			if (isWeiXinBrowser()) {
				this.isThreeLogin = true;
			}
			// #endif
		},
		onShow(){
			// 小程序顶部样式修改
			uni.setNavigationBarColor({
				frontColor: this.vuex_theme.bgColor,
				backgroundColor: this.vuex_theme.color,
				animation: {
					duration: 0,
					timingFunc: 'easeIn'
				}
			})
		},
		methods: {
			goThreeLogin: async function() {
				// #ifdef MP-WEIXIN
					// this.$u.route('/pages/login/wxlogin?index=3');
					const sinfo = uni.getSystemInfoSync();
					let _this = this
					if (sinfo.environment == 'wxwork') { //企业微信端
						//企业微信端逻辑处理
						wx.qy.login({
							success: function(res) {
								console.log(res)
								if (res.code) {
									//发起网络请求
									uni.getUserInfo({
										success: function(e) {
											uni.showLoading({
												title: '请稍后',
												mask: true
											});
											_this.$u.api.onQywxMp({
												code: res.code,
												encrypted_data: e.encryptedData,
												iv: e.iv,
											}).then(res => {
												console.log(res)
												if(res.code == 1) {
													// vuex储存 token
													_this.$u.vuex('vuex_token', res.data.token)
													uni.showToast({
														title: res.msg,
														icon: 'success',
														duration: 2000
													})
													// 登录成功 跳转首页
													setTimeout(() => {
														uni.switchTab({
															url: '/pages/index/index'
														});
													}, 1000)
												} else {
													console.log(res.data)
													// vuex储存 openid usertoken
													if(res.data) {
														uni.showModal({
															title: '提示',
															content: res.msg,
															success: function (r) {
																if (r.confirm) {
																	// 跳转注册
																	_this.$u.route('pages/login/apply',{refresh_token: res.data.refresh_token})
																} else if (r.cancel) {
																	console.log('取消')
																}
															}
														})
													}
												}
												uni.hideLoading();
											})
										}
									})
								} else {
									console.log('登录失败！' + res.errMsg)
								}
							}
						});
					} else {
						//微信端逻辑处理
						this.onLogin()
					}
				// #endif

				// #ifdef H5
					this.goAuth();
				// #endif

			},
			// 关闭提示
			colse() {
				this.referrerShow = !this.referrerShow
			},
			// 账号登录
			accountLogin() {
				this.$u.route('pages/login/apply',{
					type: this.type
				});
			},
			// 微信小程序登录
			onLogin() {
				let _this = this
				// 进行登录
				uni.login({
					provider: 'weixin',
					success: function (res) {
						console.log(res)
						uni.getUserInfo({
							success: function(e) {
								uni.showLoading({
									title: '请稍后',
									mask: true
								});
								_this.$u.api.onWxlogin({
									code: res.code,
									encrypted_data: e.encryptedData,
									iv: e.iv,
								}).then(res => {
									console.log(res)
									if(res.code == 1) {
										// vuex储存 token
										_this.$u.vuex('vuex_token', res.data.token)
										uni.showToast({
											title: res.msg,
											icon: 'success',
											duration: 2000
										})
										// 登录成功 跳转首页
										setTimeout(() => {
											uni.switchTab({
												url: '/pages/index/index'
											});
										}, 1000)
									} else {
										console.log(res.data)
										// vuex储存 openid usertoken
										if(res.data) {
											uni.showModal({
												title: '提示',
												content: res.msg,
												success: function (r) {
													if (r.confirm) {
														// 跳转注册
														_this.$u.route('pages/login/apply',{refresh_token: res.data.refresh_token})
													} else if (r.cancel) {
														console.log('取消')
													}
												}
											})
										}
									}
									uni.hideLoading();
								})
							}
						})
					}
				})
			},
		}
	}
</script>

<style lang="scss" scoped>

.container {
	position: relative;
	height: 100vh;
	z-index: 0;
	overflow: hidden;
	text-align: center;
}
.container::after {
	content: '';
	width: 140%;
	position: absolute;
	left: -20%;
	height: 580rpx;
	bottom: 0;
	z-index: -1;
	border-radius: 50% 50% 0 0;
	background-color: #fff;
}
.image-content {
	padding-top: 100rpx;
}
.title {
	font-size: 48rpx;
	font-weight: 600;
}
.u-f {
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 35rpx;
}
.bottom {
	position: absolute;
	bottom: 15%;
	left: 24%;
	right: 24%;
}
.hover {
	color: #fff;
	background-color: rgba(0, 0, 0, 0.15)
}

</style>
