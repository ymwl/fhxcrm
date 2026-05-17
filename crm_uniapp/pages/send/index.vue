<template>
	<view >
		<view class="set-box">
			<u-form :model="form" ref="uForm" :error-type="errorType">
				<u-form-item :label=" '发' + (options.type == 'email' ? '邮件' : '信息') + '给' + ':'" :label-position="labelPosition"  label-width="180" prop="name">
					<u-input v-model="form.name" disabled :border="border" />
				</u-form-item>
				<!-- 短信 -->
				<block v-if=" options.type == 'note' ">
					<u-form-item label="选择短息模板：" required :label-position="labelPosition"  label-width="180">
						<u-input v-model="selectName" type="select" :border="border"  :select-open="selectShow" placeholder="请选择短息模板"  @click="selectShow = true"/>
					</u-form-item>
					<u-form-item label="目标手机：" required :label-position="labelPosition"  label-width="180" prop="mobile">
						<u-input v-model="form.mobile"  :border="border"   placeholder="请输入目标手机号" />
					</u-form-item>
					<u-form-item label="短息预览：" :label-position="labelPosition" label-width="180" prop="content">
						<u-input type="textarea" :border="border" height="200" :auto-height="true" v-model="form.content" />
					</u-form-item>
				</block>
				<!-- 邮件 -->
				<block v-else>
					<u-form-item label="选择邮件模板：" required :label-position="labelPosition"  label-width="180">
						<u-input v-model="selectName" type="select" :border="border"  :select-open="selectShow" placeholder="请选择邮件模板"  @click="selectShow = true"/>
					</u-form-item>
					<u-form-item label="目标邮箱：" required :label-position="labelPosition"  label-width="180" prop="email">
						<u-input v-model="form.email"  :border="border"   placeholder="请输入邮箱" />
					</u-form-item>
					<u-form-item label="邮件标题：" :label-position="labelPosition"  label-width="180" prop="record_type">
						<u-input v-model="form.title"  :border="border"   placeholder="请输入邮箱标题" />
					</u-form-item>
					<u-form-item label="邮件内容：" :label-position="labelPosition" label-width="180" prop="content">
						<fa-editor v-model="form.content" :html="html"></fa-editor>
						<!-- <u-input type="textarea" :border="border" height="200" :auto-height="true" v-model="form.content" /> -->
					</u-form-item>
				</block>
				
			</u-form>
			<view class="u-m-t-80" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">发送</u-button>
			</view>
		</view>
    <!-- 选择模板弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="selectShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择模板</text> 
				<view class="" @click="selectShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入模板名称搜索" @change="onSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="reachBottom">
				<view class="list">
					<block v-if="selectList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in selectList" :key="index" @click="onItem(item,index)">
								<view class="title">{{item.describe}}</view>
								<view class="check-icon">
									<u-icon v-if="item.id == form.selectId" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="has_more ? listStatus : 'nomore'" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
	</view>
</template>

<script>
	import {baseUrl,api_v1} from '@/common/config'
	export default {
		data() {
			return {
				html: '',
        labelPosition: 'top',
        border: true,
        keyword: '',
				options: {
					type: '',
					id: '',
					types: '',
				},
        type: '',
				showUploadList: false,
				data: {},
				selectShow: false,
				nextTimeShow: false,
				selectList: [],
        page: 1,
        pageSize: 10,
        has_more: false,
        listStatus: 'loadmore',
				selectName: '',
				form: {
					name: '',
					selectId: '',
					email: '',
					mobile: '',
					title: '',
					content: '',
				},
				customer_id: '',
				errorType: ['message','toast'],
				rules: {
					selectId: [
						{
							required: true,
							message: '请选择模板',
							trigger: ['change','blur']
						},
					],
					
				}
			};
		},
		// 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
		onReady() {
			this.$refs.uForm.setRules(this.rules)
		},
		onLoad(e) {
			// 字段email和mobile验证切换
			if(this.options.type == 'email'){
				this.rules.email = [
						{
							required: true,
							message: '请填写目标邮箱',
							trigger: ['change','blur']
						},
						{
							validator: (rule, value, callback) => {
								return this.$u.test.email(value);
							},
							message: '邮箱不正确',
							// 触发器可以同时用blur和change
							trigger: ['change','blur'],
						}
					]
				
			} else {
				this.rules.mobile = [
						{
						required: true,
						message: '请输入目标手机号',
						trigger: ['change','blur']
					},
					{
						validator: (rule, value, callback) => {
							return this.$u.test.mobile(value);
						},
						message: '手机号不正确',
						// 触发器可以同时用blur和change
						trigger: ['change','blur'],
					}
				]
			}
			this.options = e
      uni.setNavigationBarTitle({
        title: '发送' + (this.options.type == 'email' ? '邮件' : '信息')
      });
			this.getData()
			this.getSelectList()
		},
		methods: {
			// 获取详情
			getData() {
				switch (this.options.types) {
					case 'customer':
						// 客户
						this.$u.get('crm.customer/edit', {
							id: this.options.id
						}).then(res => {
							if(res.code == 1 ) {
								this.data = res.data
								this.form.name = this.data.name
								this.form.mobile = this.data.mobile ? this.data.mobile : ''
							}
						})
						break;
					case 'business':
						// 商机
						this.$u.get('crm.business.index/edit', {
							id: this.options.id
						}).then(res => {
							if(res.code == 1 ) {
								this.data = res.data
								this.form.name = this.data.name
								this.form.mobile = this.data.mobile ? this.data.mobile : ''
							}
						})
						break;
					case 'clues':
						// 线索
						this.$u.get('crm.clues.index/edit', {
							ids: this.options.id
						}).then(res => {
							if(res.code == 1 ) {
								this.data = res.data
								this.form.name = this.data.name
								this.form.mobile = this.data.mobile ? this.data.mobile : ''
							}
						})
						break;
					default:
						break;
				}
				
			},
      // 搜索
      onSearch(){
        this.page = 1
        this.getSelectList()
      },
			// 获取模板列表
			getSelectList(isNextPage) {
				// 邮箱模板
				if(this.options.type == 'email'){
					this.$u.get('crm.apps.email/selectpage', {
						q_word: this.keyword,
						pageNumber: this.page,
						pageSize: this.pageSize,
						keyField: "id",
						searchField: "describe",
						describe: this.keyword,
						showField: 'describe',
						searchField: 'describe',
						describe: 89
					}).then((res) => {
						if(res.code == 1){
							if(isNextPage) {
								this.selectList = this.selectList.concat(res.data.list)
							} else {
								this.selectList = res.data.list
							}
							// 是否是最后一页
							this.has_more = this.selectList.length < res.data.total
						}
					})
					return
				}
				// 短信模板
				this.$u.get('crm.apps.sms/selectpage', {
          q_word: this.keyword,
          pageNumber: this.page,
          pageSize: this.pageSize,
          keyField: "id",
          searchField: "describe",
          describe: this.keyword,
        }).then((res) => {
					if(res.code == 1){
            if(isNextPage) {
              this.selectList = this.selectList.concat(res.data.list)
            } else {
              this.selectList = res.data.list
            }
            // 是否是最后一页
					  this.has_more = this.selectList.length < res.data.total
					}
				})
			},
      // 选择模板
      onItem(item,index){
        this.selectName = item.describe
        this.form.selectId = item.id
        this.selectShow = false
				switch (this.options.type) {
					case 'email':
						// 获取邮件模板详情
						this.$u.get('crm.apps.email/send', {email_id: item.id}).then((res) => {
							console.log(res)
							if(res.code == 1){
								this.form.title = res.data.row.describe
								this.form.content = res.data.row.values.content
								this.html = res.data.row.values.content
							}
						})
						break;
					case 'note':
						// 获取短息模板详情
						this.$u.get('crm.apps.sms/send', {sms_id: item.id}).then((res) => {
							console.log(res)
							if(res.code == 1){
								this.form.content = res.data.row.values.tpl_content
							}
						})
						break
				
					default:
						break;
				}
      },
      // 滚动到底部加载更多
			reachBottom() {
        if (this.has_more) {
          this.listStatus = 'loading';
          this.page++;
          this.getSelectList(true);
        }
			},
			// 提交
			submit() {
				if(this.$u.test.isEmpty(this.form.selectId)) {
					this.$u.toast('请选择模板');
					return
				}
				// 进行必须填数据验证
				this.$refs.uForm.validate(valid => {
					if (valid) {
						// 提交
						this.onSubmit()
					} else {
						console.log('验证失败');
					}
				});
			},
			// 开始提交
			onSubmit(){
				let param = {} 
				
				if(this.options.type == 'note'){
					param = {
						data: JSON.stringify({typesid: this.options.id,types:this.options.types}),
						'sms_id': this.form.selectId,
						'row[mobile]': this.form.mobile,
						"row[content]": this.form.content
					} 
					console.log(param)
					this.$u.post('crm.apps.sms/send', param).then((res) => {
						console.log(res)
						if(res.code == 1){
							// 提示
							uni.showToast({
								title: '发送成功',
								icon: 'success',
								duration: 2000
							})
							setTimeout(() => {
								uni.navigateBack();
							}, 1500);
						}
					})
				}
				if(this.options.type == 'email'){
					param = {
						data: JSON.stringify({typesid: this.options.id,types:this.options.types}),
						'email_id': this.form.selectId,
						'row[email]': this.form.email,
						'row[title]': this.form.title,
						"row[content]": this.form.content
					} 
					console.log(param)
					this.$u.post('crm.apps.email/send', param).then((res) => {
						console.log(res)
						if(res.code == 1){
							// 提示
							uni.showToast({
								title: '发送成功',
								icon: 'success',
								duration: 2000
							})
							setTimeout(() => {
								uni.navigateBack();
							}, 1500);
						}
					})
				}
			},
			
		},
	}
</script>

<style lang="scss">
.set-box {
    padding: 0rpx 22rpx;
    padding-bottom: 80rpx;
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
.slot-btn {
	position: relative;
	width: 200rpx;
	height: 200rpx;
	display: flex;
	justify-content: center;
	flex-direction: column;
	align-items: center;
	background: rgb(244, 245, 246);
	border-radius: 10rpx;
	.text {
		font-size: 26rpx;
		margin-top: 20rpx;
    line-height: 40rpx;
	}
}

.slot-btn__hover {
	background-color: rgb(235, 236, 238);
}
.delete-icon {
	position: absolute;
	top: 10rpx;
	right: 10rpx;
	z-index: 10;
	background-color: #fa3534;
	border-radius: 100rpx;
	width: 44rpx;
	height: 44rpx;
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
}   
.popup-content {
	.popup-title {
		display: flex;
		align-items: center;
		justify-content: space-between;
		position: relative;
		font-size: 35rpx;
		font-weight: 600;
		text-align: center;
		height: 50px;
		padding-right: 25rpx;
	}
	.list {
		padding-bottom: 45rpx;
		.item {
			padding: 0 25rpx;
			justify-content: space-between;
			height: 55px;
			.title {
				flex: 1;
				font-size: 28rpx;
				font-weight: 600;
			}
			.check-icon {
				text-align: center;
				width: 100rpx;
			}
		}
	}
	.bottom_btn {
		display: flex;
		justify-content: flex-end;
		padding: 28rpx 10rpx 45rpx;
	}
}
</style>
