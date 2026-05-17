<template>
	<view class="container">
		<view class="title u-m-b-45">
			<text class="u-p-r-10">{{refresh_token ? '绑定账号' : '账号登录'}}</text>
		</view>
		<view class="input-box">
			<view class="item">
				<u-input v-model="name" required :error-message="errorName" :custom-style="inputColor" placeholder="账号" />
			</view>
			<view class="item">
				<u-input v-model="password" type="password" required :error-message="errorPassword" :custom-style="inputColor" placeholder="密码" />
			</view>
			<view class="item u-flex" v-if="vuex_system.code">
				<u-input class="u-flex-1" v-model="captcha" :custom-style="inputColor" placeholder="验证码" ></u-input>
				<view class="code_image" @click="changeImgCode">
					<image class="img" :src="code_image"/>
				</view>
			</view>
		</view>
		<view class="bottom-bt">
			<view class="space">
				<u-button type="success" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor,fontSize: '30rpx',fontWeight: '600'}" :ripple="true"  @click="onLogin">{{refresh_token ? '绑定登录' : '登录'}}</u-button>
			</view>
		</view>
	</view>
</template>

<script>
	import { processingImages,getImgUrl} from '@/common/mUtils'
	import { baseUrl} from '@/common/config'
  import {api_v1} from "../../common/config";
	export default {
		data() {
			return {
				refresh_token: '',
				inputColor: {
					backgroundColor: "#F7F7F7",
				},
				errorName: '',
				errorPassword: '',
				name: '',
				password: '',
				captcha:'',
				code_image: '',
				type: '',
				sid: '',
			};
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
		onLoad(e) {
			this.type = e.type ? e.type : ''
			// 是否是绑定账号
			if(e.refresh_token){
				this.refresh_token = e.refresh_token
			}
			this.changeImgCode();
		},
		onShow() {
			// 是否有token，有token直接跳转首页
			if(!this.$u.test.isEmpty(this.vuex_token) && this.$u.test.isEmpty(this.type)) {
				uni.switchTab({
					url: '/pages/index/index'
				});
			}
		},
		methods: {
			changeImgCode() {
				var rand = Math.round(new Date()/1000);
				var code_image = baseUrl+api_v1+"/yzm/index?rand=" + rand;
				if(this.sid){
          this.code_image=code_image +"&sid="+this.sid;
        }else{
          uni.$u.http.get(code_image,{}).then(res => {
            this.sid=res.data.sid;
            this.code_image=code_image +"&sid="+this.sid;
          }).catch(err => {
          });
        }

			},
			onLogin(e) {
				let _this = this
				// 验证
        if (!_this.name) {
					_this.$u.toast('请输入登录账号');
          return false;
        }
        if (!_this.password) {
					_this.$u.toast('请输入登录密码');
          return false;
        }
				uni.showLoading({
					title: "请稍后...",
					mask: true
				});
				if(this.refresh_token) {
				  //绑定登录处理
					let platform = ''
					// #ifdef MP-WEIXIN
						platform = 'admin_min'
					// #endif

					// #ifdef H5
						platform = 'admin_mp'
					// #endif
					let obj = {
						username: this.name,
						password: this.password,
						refresh_token: this.refresh_token,
						platform: platform,
						sid:this.sid,
					}
					// 是否需要验证码
					if(this.vuex_system.code){
						obj.captcha = this.captcha
					}
					// 绑定原来的账号
					_this.$u.post('index/bind', obj).then(res => {
						if(res.code == 1) {
							// vuex储存 token
							_this.$u.vuex('vuex_token', res.data.token)
							uni.hideLoading();
							uni.showToast({
								title: res.msg,
								icon: 'success',
								duration: 2000
							})
							setTimeout(() => {
								uni.switchTab({
									url: '/pages/index/index'
								});
							}, 1000)
						}else{
							this.changeImgCode();
						}
					})
				} else {
					let obj = {
						username: this.name,
						password: this.password,
						sid:this.sid,
					}
					// 是否需要验证码
					if(this.vuex_system.code){
						obj.captcha = this.captcha
					}
          // 账号密码登录
          uni.request({
            method: 'POST',
            url: baseUrl + api_v1 + '/' + 'login/index',//你的接口地址
            header: {
              'content-type': 'application/json',
            },
            data: obj,
            success: (res) => {
              res=res.data;
              console.log('登录处理返回信息',res);
              if(res.code == 1) {
                // vuex储存 token
                _this.$u.vuex('vuex_token', res.data.token)
                _this.$u.vuex('vuex_admin', res.data);//存储登录的信息
                uni.hideLoading();
                uni.showToast({
                  title: res.msg,
                  icon: 'success',
                  duration: 2000
                })
                let tz = uni.getStorageSync('fullPath401');

                if (!tz || tz=='/pages/login/index' || tz=='/pages/login/apply') {
                  tz='/pages/index/index';
                }
                setTimeout(() => {
                  uni.removeStorageSync('fullPath401');
                  // tabBar 页面列表（从配置动态获取）
                  const tabBarPages = (_this.vuex_config && _this.vuex_config.tabbar && _this.vuex_config.tabbar.list && Array.isArray(_this.vuex_config.tabbar.list))
                      ? _this.vuex_config.tabbar.list.map(item => item.path)
                      : [];
                  // 判断是否是 tabBar 页面，使用对应的跳转方式
                  console.log(tabBarPages);
                  if (tabBarPages.indexOf(tz) > -1) {
                    uni.switchTab({ url:tz });
                  } else {
                    this.$u.route(tz);
                  }
                }, 1000)
              }else{
                _this.$u.toast(res.msg);
                this.changeImgCode();
              }
            }
          })

				}
			},
			// 提示验证
			verify() {
				this.errorMobile = ''
				this.errorName = ''
			},
			look() {
				this.$u.route('pages/login/clause');
			},
			// 关闭提示
			colse() {
				this.referrerShow = !this.referrerShow
			},
		}
	}
</script>

<style lang="scss" scoped>
.container {
	position: relative;
	padding: 45rpx 60rpx;
	min-height: 100vh;
}
.name {
	font-size: 32rpx;
}
.title {
	font-size: 42rpx;
	font-weight: 600;
	text-align: center;
}
.item {
	background: #F7F7F7;
	padding: 15rpx 28rpx 15rpx 35rpx;
	margin-bottom: 25rpx;
	.code_image {
		width: 180rpx;
		height: 70rpx;
		.img {
			width: 100%;
			height: 100%;
		}
	}
}
.bottom-bt {
	text-align: center;
	padding: 45rpx 0;
}
</style>
