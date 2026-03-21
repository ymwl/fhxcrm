<template>
	<view >
		<view class="set-box">
			<u-form :model="form" ref="uForm" :error-type="errorType">
				<u-form-item label="头像:"  label-width="180">
					<u-avatar class="u-flex" :src="form.avatar" size="120" @click="selectFile"></u-avatar>
				</u-form-item>
				<u-form-item label="真实姓名:"  label-width="180">
					<u-input v-model="form.realname" />
				</u-form-item>
				<u-form-item label="联系电话:"  label-width="180" >
					<u-input v-model="form.tel" />
				</u-form-item>
        <u-form-item label="邮箱:"  label-width="180" >
					<u-input v-model="form.email" />
				</u-form-item>
				<u-form-item label="密码:"  label-width="180">
					<u-input v-model="form.password" placeholder="不修改可以留空" :clearable="false" type="password" autocomplete="off" />
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
	export default {
		data() {
			return {
				action: baseUrl + api_v1 + '/ajax/upload',
				param: {
					token: '',
				},
				form: {
					avatar: '',
					full_avatar: '',
					realname: '',
          tel: '',
					email: '',
					password: '',
				},
				videoFilePath: '',
				customer_id: '',
				business_image: [],
				error_video: '',
				errorType: ['message','toast'],
				rules: {
					name: [
						{
							required: false,
							message: '请填写客户名称',
							trigger: ['change','blur']
						},
					],
					record_type: [
						{
							required: true,
							message: '请选择方式',
							trigger: ['change','blur']
						},
					],
					content: [
						{
							required: true,
							message: '请填写内容',
							trigger: ['change','blur']
						},
					],
				}
			};
		},
		// 只有onReady生命周期才能调用refs操作组件
		onReady() {
      console.log('this.form=',this.form);
			// 得到整个组件对象，内部图片列表变量为"lists"
			// this.lists = this.$refs.uUpload.lists;
		},
		onLoad(e) {
			// 上传文件参数
      console.log('this.form=',this.form);
			this.param.token = this.vuex_token
			this.getData()
		},
		methods: {
			// 获取用户信息
			getData() {
				this.$u.api.onGetInfo().then(res => {
					if(res.code == 1 ) {
						this.form.avatar = res.data.avatar
						this.form.realname = res.data.realname
						this.form.tel = res.data.tel
						this.form.email = res.data.email
            this.form.password='';
					}
				})
			},
			// 选择图片
			selectFile() {
        console.dir(uni.$u.http)
				let _this = this
				uni.chooseImage({
					count: 1, //默认9
					sizeType: ['original', 'compressed'], //可以指定是原图还是压缩图，默认二者都有
					sourceType: ['album'], //从相册选择
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
								// 判断是否json字符串，将其转为json格式
								let data = _this.$u.test.jsonString(res.data) ? JSON.parse(res.data) : res.data;
								if (![200, 201, 204].includes(res.statusCode)) {

								} else {
									if(data.code == 1) {
										// 上传成功
										_this.form.avatar = data.data.url
									}
								}
							},
							fail: e => {
								
							},
							complete: res => {
								
							}
						});
					}
				});
			},
			// 提交
			submit() {
				// 进行必须填数据验证
       let  _this=this;
        _this.$u.api.postProfile(this.form).then((res) => {
					if(res.code == 1){
            _this.$u.vuex('vuex_admin.avatar', _this.form.avatar);
            _this.$u.vuex('vuex_admin.realname', _this.form.realname);
            _this.$u.vuex('vuex_admin.tel', _this.form.tel);
            _this.$u.vuex('vuex_admin.email', _this.form.email);


						// 提示
						uni.showToast({
							title: '修改成功',
							icon: 'success',
							duration: 2000
						})

						setTimeout(() => {
              if(_this.form.password){
                _this.$u.vuex('vuex_token',null);
                _this.$u.route('pages/login/index');
              }
						}, 1500);
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

.slot-btn__hover {
	background-color: rgb(235, 236, 238);
}
  
</style>
