<template>
	<view>
		<view class="set-box">
			<u-form :model="form" ref="uForm" label-position="top" :error-type="errorType">
				<u-form-item required label="所属组别:" label-width="160">
					<u-input type="select" :border="true" :select-open="selectShow" v-model="groupName" placeholder="选择组别" @click="selectShow = true" />
				</u-form-item>
				<u-form-item required label="用户名:" label-width="160" prop="username">
					<u-input v-model="form.username" :border="true" />
				</u-form-item>
				<u-form-item label="Email:" label-width="160" prop="email">
					<u-input v-model="form.email" :border="true" />
				</u-form-item>
				<u-form-item label="昵称:" label-width="160" >
					<u-input v-model="form.realname" :border="true" />
				</u-form-item>
				<u-form-item :required="type == 'add' ? true : false" label="密码:" label-width="160" prop="password">
					<view style="width:100%">
						<u-input v-model="form.password" type="password" :placeholder="type == 'add' ? '请输入密码' : '更改信息密码可以为空'" :border="true" />
						<view class="hint" v-if="type == 'edit'">留空即不修改密码</view>
					</view>
				</u-form-item>
				<u-form-item label="状态:" label-width="160" >
					<u-radio-group v-model="form.status" >
						<u-radio v-for="(item, index) in statusList"  :key="index" :name="item.name">
							{{ item.text }}
						</u-radio>
					</u-radio-group>
				</u-form-item>
			</u-form>
			<view class="u-m-t-40" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">提交</u-button>
			</view>
		</view>
		<u-modal v-model="show" :content="content"></u-modal>
		<u-calendar v-model="dateShow" :min-date="minDate" :max-date="maxDate" mode="range" @change="change"></u-calendar>
		<!-- 选择弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="selectShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择管理组</text> 
				<view class="" @click="selectShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="onSearch"></u-search>
			<scroll-view scroll-y style="height: 660rpx;width: 100%;" @scrolltolower="reachBottom">
				<view class="list">
					<block v-if="adminList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in adminList" :key="index" @click="onItem(item,index)">
								<u-parse :html="item.text"></u-parse>
								<!-- <view class="title">{{item.text}}</view> -->
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore status="nomore" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
	</view>
</template>

<script>
	import { processingImages,getImgUrl,get_date} from '@/common/mUtils'
	import {baseUrl,api_v1} from '@/common/config'


	export default {
		data() {
			return {
				keyword: '',
				selectShow:false,
				selected: false,
				status: 'loadmore',
				groupName: '',
				page: 1,
				pageSize: 10,
				lastPage: false,
				adminList: [],
				selectList: [],
				type: '',
				admin_id: '',
				minDate: '',
				maxDate: '',
				dateShow: false,
				show: false,
				content: '',
				form: {
					group: '',
					username: '',
					email: '',
					realname: '',
					password: '',
					status: 'normal',
				},
				timeText: '',
				errorType: ['message'],
				statusList: [
					{
						name: 'normal',
						text: '正常',
					},
					{
						name: 'hidden',
						text: '隐藏',
					},
				],
				rules: {
					username: [
						// 对first_consume字段进行必填验证
						{
							required: true,
							message: '请输入用户名',
							trigger: ['change','blur']
						},
					],
					email: [
						{
							// 自定义验证函数，见上说明
							validator: (rule, value, callback) => {
								if(value == '') {
									return true
								}
								return this.$u.test.email(value)
							},
							message: '邮箱不正确',
							// 触发器可以同时用blur和change
							trigger: ['change','blur'],
						}
					]
				}
			};
		},
		onLoad(e) {
			console.log(e)
			if(e.id) {
				this.admin_id = e.id
				this.getAdminEdit()
			} else {
				this.getData()
			}
			
			this.type = e.type
			if(this.type == "edit") {
					uni.setNavigationBarTitle({
						title: '编辑人员'
					});
				} else {
					this.rules.password = [
						{
							required: true,
							message: '请输入密码',
							trigger: ['change','blur']
						},
					]
				}
		},
		// 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
		onReady() {
			this.$refs.uForm.setRules(this.rules);
		},
		methods: {
			// 搜索
			onSearch() {

			},
			// 获取管理组列表
			getData(isNextPage,pages) {
				console.log(isNextPage,this.status)
				this.$u.api.getGroupdata({
					sort: 'id',
					order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({username: this.keyword}),
					op: JSON.stringify({username: 'LIKE'})
				}).then(res => {
					console.log(res)
					if(res.code == 1 ) {
						if(this.type == 'edit') {
							this.groupName = res.data[this.form.group].replace(/&nbsp;/ig, "")
						}
						res.data = this.onJson(res.data)
						// 最后一页
						if(res.data.length == 0) {
							this.lastPage = true
						} 
						//不够一页
						if (res.data.length < 10) {
							this.status = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.adminList = this.adminList.concat(res.data)
							return 
						}
						this.adminList = res.data
					}
				})
			},
			// 滚动到底部加载更多
			reachBottom() {
				return
				if(this.lastPage || this.status == 'loading') return;
				this.status = 'loading'
				setTimeout(() => {
					if(this.lastPage) return ;
					this.getData(true,++this.page)
					if(this.adminList.length >= 10) this.status = 'loadmore';
					else this.status = 'loading';
				}, 1200)
			},
			// json 转化
			onJson(data) {
				let arr = []
				for (const key in data) {
					if (Object.hasOwnProperty.call(data, key)) {
						let obj = {}
						obj.id = key
						obj.text = data[key]
						arr.push(obj)
					}
				}
				return arr
			},
			// 选择管理组
			onItem(val,i) {
				this.adminList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.form.group = val.id
				this.groupName = val.text.replace(/&nbsp;/ig, "")
				this.selectShow = false
			},
			// 获取配置
			getAdminEdit() {
				this.$u.api.getAdminEdit({id: this.admin_id}).then((res) => {
					if(res.code == 1){
						if(this.$u.test.object(res.data)){
							this.form.username = res.data.row.username
							this.form.status = res.data.row.status
							this.form.realname = res.data.row.realname
							this.form.email = res.data.row.email
							this.form.group = res.data.groupids[0]
							this.form.id = res.data.row.id
						}
						this.getData()
					}
				})
			},
			// 修确认提交
			submit() {
				console.log(this.form)
				this.$refs.uForm.validate(valid => {
					if (valid) {
						if(this.type == 'add') {
							this.$u.api.onAdminAdd(this.form).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: res.msg,
										icon: 'success',
										duration: 2000
									})
									setTimeout(() => {
										uni.navigateBack();
									}, 1000);
								} else {
									// 提示
									uni.showToast({
										title: res.msg,
										icon: 'none',
										duration: 2000
									})
								}
							})
						} else {
							this.$u.api.onAdminEdit(this.form).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: '修改成功',
										icon: 'success',
										duration: 2000
									})
									setTimeout(() => {
										uni.navigateBack();
									}, 1000);
								} else {
									uni.showToast({
										title: res.msg,
										icon: 'none',
										duration: 2000
									})
								}
							})
						}
					} else {
						console.log('验证失败');
					}
				});
				
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
.hint {
	color: #747474;
	font-size: 25rpx;
}
</style>
