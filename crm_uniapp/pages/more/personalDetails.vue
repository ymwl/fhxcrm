<template>
	<view >
		<view class="set-box">
			<u-form :model="form" ref="uForm" :error-type="errorType" label-position="left" autocomplete="off">
				<!-- 头像 -->
				<u-form-item label="头像" label-width="180">
					<view class="avatar-box" @click="selectFile">
						<u-avatar :src="displayAvatar" size="120" :key="avatarKey"></u-avatar>
						<view class="avatar-tip">点击更换头像</view>
					</view>
				</u-form-item>
        
				<!-- 用户名(只读) -->
				<u-form-item label="用户名" label-width="180">
					<u-input v-model="form.username" :disabled="true" placeholder="用户名" />
				</u-form-item>
				<!-- 防浏览器自动填充的诱饵字段 -->
				<view style="position:absolute;opacity:0;width:0;height:0;overflow:hidden;pointer-events:none;">
					<input type="text" name="prevent_autofill_username" tabindex="-1" autocomplete="username" />
					<input type="password" name="prevent_autofill_password" tabindex="-1" autocomplete="current-password" />
				</view>
				<!-- 当前密码 -->
				<u-form-item label="当前密码" label-width="180" prop="oldpwd">
					<u-input v-model="form.oldpwd" :type="pwdInputType" placeholder="修改密码必须填写" autocomplete="off" auto-complete="off" @focus="onPwdFocus"/>
				</u-form-item>
				<!-- 新密码 -->
				<u-form-item label="新密码" label-width="180" prop="newpwd">
					<u-input v-model="form.newpwd" :type="pwdInputType" placeholder="不改密码无需填写" autocomplete="off" auto-complete="off" @focus="onPwdFocus"/>
				</u-form-item>
				<!-- 确认新密码 -->
				<u-form-item label="确认新密码" label-width="180" prop="newpwd2">
					<u-input v-model="form.newpwd2" :type="pwdInputType" placeholder="不改密码无需填写确认新密码" autocomplete="off" auto-complete="off" @focus="onPwdFocus"/>
				</u-form-item>
				<!-- 邮箱 -->
				<u-form-item label="邮箱" label-width="180" prop="email">
					<u-input v-model="form.email" placeholder="请输入用户邮箱" />
				</u-form-item>
				<!-- 电话 -->
				<u-form-item label="电话" label-width="180" prop="phone">
					<u-input v-model="form.phone" placeholder="请输入手机号" />
				</u-form-item>
				<!-- 联系微信 -->
				<u-form-item label="联系微信" label-width="180" prop="wechat">
					<u-input v-model="form.wechat" placeholder="请填写联系微信" />
				</u-form-item>
			</u-form>
			<view class="u-m-t-80" style="text-align: center;">
				<u-button class="u-m-l-15" type="success" @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确认</u-button>
			</view>
		</view>
	</view>
</template>

<script>
	import {baseUrl,api_v1} from '@/common/config'
	import {tools} from '@/common/fa.mixin.js'
	export default {
		mixins: [tools],
		computed: {
			// 头像展示URL：以 / 开头的相对路径需拼接 CDN 域名前缀，才能在手机端正常展示
			displayAvatar() {
				const avatar = this.form.avatar;
      
				return this.cdnurl(avatar);

			}
		},
		data() {
			return {
				action: baseUrl + api_v1 + '/ajax/upload',
				param: {
					token: '',
				},
				avatarKey: 0,
				pwdInputType: 'text', // 初始为text防止浏览器自动填充检测，mounted后切换为password
				form: {
					username: '',
					avatar: '',
					oldpwd: '',
					newpwd: '',
					newpwd2: '',
					email: '',
					phone: '',
					wechat: '',
				},
				errorType: ['message','toast'],
				rules: {
					email: [
						{
							type: 'email',
							message: '请输入正确的邮箱格式',
							trigger: ['change','blur']
						},
					],
					phone: [
						{
							pattern: /^1[3-9]\d{9}$/,
							message: '请输入正确的手机号',
							trigger: ['change','blur']
						},
					],
				}
			};
		},
		onLoad(e) {
			this.param.token = this.vuex_token
			this.getData()
			// 延迟切换密码字段类型，避免浏览器在DOM加载时检测到password字段触发自动填充
			setTimeout(() => {
				this.pwdInputType = 'password'
			}, 500)
		},
		methods: {
			// 密码字段获取焦点时确保类型为password
			onPwdFocus() {
				if (this.pwdInputType !== 'password') {
					this.pwdInputType = 'password'
				}
			},
			// 获取用户信息
			getData() {
				this.$u.get('general/profile').then(res => {
					if(res.code == 1 && res.data) {
						const info = res.data;
						this.form.username = info.username || '';
						this.form.avatar = info.avatar || '';
						this.form.email = info.email || '';
						this.form.phone = info.phone || '';
						this.form.wechat = info.wechat || '';
						this.form.oldpwd = '';
						this.form.newpwd = '';
						this.form.newpwd2 = '';
						this.avatarKey = Date.now();
					}
				})
			},
			// 选择图片上传
			selectFile() {
				let _this = this
				uni.chooseImage({
					count: 1,
					sizeType: ['original', 'compressed'],
					sourceType: ['album'],
					success: function (res) {
						uni.uploadFile({
							url: _this.action,
							filePath: res.tempFilePaths[0],
							name: 'file',
							header: {
								token: _this.vuex_token,
								"Accept": "application/json",
							},
							success: res => {
								let data = _this.$u.test.jsonString(res.data) ? JSON.parse(res.data) : res.data;
								if ([200, 201, 204].includes(res.statusCode)) {
									if(data.code == 1) {
										_this.form.avatar = data.data.url;

									}
								}
							},
							fail: e => {
								console.error('上传失败', e)
							},
						});
					}
				});
			},
			// 提交
			submit() {
				let _this = this;
				// 密码修改校验
				if (_this.form.newpwd) {
					if (!_this.form.oldpwd) {
						uni.showToast({ title: '修改密码时当前密码必须填写', icon: 'none', duration: 2000 })
						return
					}
					if (_this.form.newpwd !== _this.form.newpwd2) {
						uni.showToast({ title: '两次密码输入不一致', icon: 'none', duration: 2000 })
						return
					}
				}

				_this.$u.post('general/profile', this.form).then((res) => {
					if(res.code == 1){
						// 更新vuex中的用户信息
						_this.$u.vuex('vuex_admin.avatar', _this.form.avatar);
						_this.$u.vuex('vuex_admin.email', _this.form.email);
						_this.$u.vuex('vuex_admin.phone', _this.form.phone);
						_this.$u.vuex('vuex_admin.wechat', _this.form.wechat);

						uni.showToast({
							title: '修改成功',
							icon: 'success',
							duration: 1500
						})

						setTimeout(() => {
							if(_this.form.newpwd){
								// 修改密码则退出登录
								_this.$u.vuex('vuex_token', null);
								_this.$u.route('pages/login/index');
							}
						}, 1500);
					} else {
						uni.showToast({ title: res.msg || '修改失败', icon: 'none', duration: 2000 })
					}
				})
			},
		},
	}
</script>

<style lang="scss">
.set-box {
    padding: 0rpx 22rpx;
    margin-bottom: 80rpx;
    .cif-title {
      font-size: 30rpx;
      font-weight: 700;
      padding: 22rpx 0;
   }
  .option {
    .text {
      color: #747474;
      font-size: 26rpx;
      text-align: justify;
      padding-bottom: 15rpx;
    }
    .input-box {
      width: 200rpx;
      margin-right: 15rpx;
    }
  }
}

.avatar-box {
	display: flex;
	flex-direction: row;
	align-items: center;
	.avatar-tip {
		margin-left: 20rpx;
		font-size: 26rpx;
		color: #909399;
	}
}

.slot-btn__hover {
	background-color: rgb(235, 236, 238);
}
  
</style>
