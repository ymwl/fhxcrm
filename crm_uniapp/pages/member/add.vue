<template>
	<view>
		<view class="set-box">
			<u-form :model="form" ref="uForm" label-position="top" :error-type="errorType" autocomplete="off">
				<u-form-item required label="所属组别:" label-width="160">
					<u-input type="select" :border="true" :select-open="selectShow" v-model="groupName" placeholder="选择组别" @click="selectShow = true" />
				</u-form-item>
				<u-form-item required label="角色:" label-width="160">
					<u-input type="select" :border="true" :select-open="roleSelectShow" v-model="roleName" placeholder="选择角色" @click="roleSelectShow = true" />
				</u-form-item>
				<u-form-item required label="用户名:" label-width="160" prop="username">
					<u-input v-model="form.username" :border="true" autocomplete="off" />
				</u-form-item>

        <u-form-item :required="type == 'add' ? true : false" label="密码:" label-width="160" prop="pwd">
          <view style="width:100%">
            <u-input v-model="form.pwd" type="password" :placeholder="type == 'add' ? '请输入密码' : '更改信息密码可以为空'" :border="true" autocomplete="new-password" />
            <view class="hint" v-if="type == 'edit'">留空即不修改密码</view>
          </view>
        </u-form-item>

				<u-form-item label="昵称:" label-width="160" >
					<u-input v-model="form.realname" :border="true" />
				</u-form-item>
				<u-form-item label="Email:" label-width="160" prop="email">
					<u-input v-model="form.email" :border="true" autocomplete="off" />
				</u-form-item>

				<u-form-item label="电话:" label-width="160" prop="phone">
					<u-input v-model="form.phone" :border="true" maxlength="20" />
				</u-form-item>
				<u-form-item label="联系微信:" label-width="160" >
					<u-input v-model="form.wechat" :border="true" />
				</u-form-item>
				<u-form-item label="直属上级:" label-width="160">
					<u-input type="select" :border="true" :select-open="parentSelectShow" v-model="parentAdminName" placeholder="选择直属上级" @click="openParentSelect" />
				</u-form-item>
				<u-form-item label="月目标:" label-width="160" >
					<u-input v-model="form.mubiao" type="number" :border="true" placeholder="业绩月目标金额" />
				</u-form-item>
				<u-form-item label="提成点数:" label-width="160" >
					<u-input v-model="form.ticheng" type="number" :border="true" placeholder="提成百分比" />
				</u-form-item>
				<u-form-item label="状态:" label-width="160" >
					<u-radio-group v-model="form.status" >
						<u-radio v-for="(item, index) in statusList"  :key="index" :name="item.name">
							{{ item.text }}
						</u-radio>
					</u-radio-group>
				</u-form-item>
			</u-form>
			<view class="u-m-t-40 u-m-b-80" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">提交</u-button>
			</view>
		</view>
		<u-modal v-model="show" :content="content"></u-modal>
		<u-calendar v-model="dateShow" :min-date="minDate" :max-date="maxDate" mode="range" @change="change"></u-calendar>
		<!-- 组别选择弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="selectShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
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
		<!-- 角色选择弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="roleSelectShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
				</view>
				<text class="">选择角色</text> 
				<view class="" @click="roleSelectShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<scroll-view scroll-y style="height: 660rpx;width: 100%;">
				<view class="list">
					<block v-if="roleList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in roleList" :key="index" @click="onRoleItem(item,index)">
								<view class="title">{{item.text}}</view>
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
		<!-- 直属上级选择弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="parentSelectShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
				</view>
				<text class="">选择直属上级</text> 
				<view class="" @click="parentSelectShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="parentKeyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="onParentSearch"></u-search>
			<scroll-view scroll-y style="height: 660rpx;width: 100%;" @scrolltolower="parentReachBottom">
				<view class="list">
					<block v-if="parentAdminList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in parentAdminList" :key="index" @click="onParentItem(item,index)">
								<view class="title">{{item.username}}</view>
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
				lastPage: false,
				adminList: [],
				selectList: [],
				// 角色相关
				roleSelectShow: false,
				roleName: '',
				roleList: [],
				// 直属上级相关
				parentSelectShow: false,
				parentAdminName: '',
				parentAdminList: [],
				parentKeyword: '',
				parentPage: 1,
				parentLastPage: false,
				parentStatus: 'loadmore',
				type: '',
				admin_id: '',
				minDate: '',
				maxDate: '',
				dateShow: false,
				show: false,
				content: '',
				form: {
					group_id: '',
					role_id: '',
					username: '',
					email: '',
					realname: '',
					pwd: '',
					phone: '',
					wechat: '',
					parent_id: 0,
					mubiao: '',
					ticheng: '',
					admin_id: '',
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
						{
							required: true,
							message: '请输入用户名',
							trigger: ['change','blur']
						},
					],
					email: [
						{
							validator: (rule, value, callback) => {
								if(value == '') {
									return true
								}
								return this.$u.test.email(value)
							},
							message: '邮箱不正确',
							trigger: ['change','blur'],
						}
					],
					phone: [
						{
							validator: (rule, value, callback) => {
								if(value == '') {
									return true
								}
								return this.$u.test.mobile(value)
							},
							message: '手机号格式不正确',
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
				this.getRoledata()
			}
			
			this.type = e.type
			if(this.type == "edit") {
					uni.setNavigationBarTitle({
						title: '编辑人员'
					});
				} else {
					this.rules.pwd = [
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
				this.$u.get('auth/getGroupdata', {
					sort_by: 'id',
					sort_order: 'desc',
					page: pages,
					limit: this.pageSize,
					filter: JSON.stringify({username: this.keyword}),
					op: JSON.stringify({username: 'LIKE'})
				}).then(res => {
					console.log(res)
					if(res.code == 1 ) {
						if(this.type == 'edit') {
							this.groupName = res.data[this.form.group_id].replace(/&nbsp;/ig, "")
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
			// 获取角色列表
			getRoledata() {
				this.$u.get('auth/getRoledata', {}).then(res => {
					if(res.code == 1) {
						let arr = this.onJson(res.data)
						this.roleList = arr
						// 编辑模式回显角色名
						if(this.type == 'edit' && this.form.role_id) {
							const roleNameRaw = res.data[this.form.role_id]
							this.roleName = roleNameRaw ? roleNameRaw : ''
						}
					}
				})
			},
			// 获取直属上级列表
			getParentAdminList(isNextPage, pages) {
				this.$u.get('auth/adminList', {
					sort_by: 'admin_id',
					sort_order: 'asc',
					page: pages,
					limit: this.pageSize,
					filter: JSON.stringify({username: this.parentKeyword}),
					op: JSON.stringify({username: 'LIKE'})
				}).then(res => {
					if(res.code == 1) {
						let arr = (res.data || []).map(item => {
							return {
								admin_id: item.admin_id,
								username: item.username,
								checked: (item.admin_id == this.form.parent_id)
							}
						})
						// 如果是编辑模式，在第一页数据中回显
						if(this.type == 'edit' && !isNextPage && this.form.parent_id) {
							arr.forEach(item => {
								if(item.admin_id == this.form.parent_id) {
									this.parentAdminName = item.username
								}
							})
						}
						if(res.data.length == 0) {
							this.parentLastPage = true
						}
						if(res.data.length < this.pageSize) {
							this.parentStatus = 'nomore'
						}
						if(isNextPage) {
							this.parentAdminList = this.parentAdminList.concat(arr)
							return
						}
						this.parentAdminList = arr
					}
				})
			},
			// 打开直属上级选择器
			openParentSelect() {
				this.parentSelectShow = true
				if(this.parentAdminList.length == 0) {
					this.getParentAdminList()
				}
			},
			// 搜索直属上级
			onParentSearch() {
				this.parentPage = 1
				this.parentLastPage = false
				this.getParentAdminList()
			},
			// 直属上级滚动到底部
			parentReachBottom() {
				if(this.parentLastPage || this.parentStatus == 'loading') return;
				this.parentStatus = 'loading'
				setTimeout(() => {
					if(this.parentLastPage) return;
					this.getParentAdminList(true, ++this.parentPage)
					if(this.parentAdminList.length >= 10) this.parentStatus = 'loadmore';
					else this.parentStatus = 'loading';
				}, 1200)
			},
			// 选择直属上级
			onParentItem(val, i) {
				this.parentAdminList.forEach((item,index) => {
					item.checked = (val.admin_id == item.admin_id)
				})
				this.form.parent_id = val.admin_id
				this.parentAdminName = val.username
				this.parentSelectShow = false
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
				this.form.group_id = val.id
				this.groupName = val.text.replace(/&nbsp;/ig, "")
				this.selectShow = false
			},
			// 选择角色
			onRoleItem(val, i) {
				this.roleList.forEach((item,index) => {
					item.checked = (val.id == item.id)
				})
				this.form.role_id = val.id
				this.roleName = val.text
				this.roleSelectShow = false
			},
			// 获取配置
			getAdminEdit() {
				this.$u.get('auth/adminEdit', {admin_id: this.admin_id}).then((res) => {
					if(res.code == 1){
						if(this.$u.test.object(res.data)){
							const row = res.data.row
							this.form.username = row.username || ''
							this.form.status = row.status || 'normal'
							this.form.realname = row.realname || ''
							this.form.email = row.email || ''
							this.form.group_id = row.group_id || ''
							this.form.admin_id = row.admin_id || ''
							this.form.role_id = row.role_id || ''
							this.form.parent_id = row.parent_id || 0
							this.form.mubiao = row.mubiao || ''
							this.form.ticheng = row.ticheng || ''
							this.form.phone = row.phone || ''
							this.form.wechat = row.wechat || ''
						}
						this.getData()
						this.getRoledata()
						// 如果parent_id > 0 才加载上级列表
						if(this.form.parent_id > 0) {
							this.getParentAdminList()
						}
					}
				})
			},
			// 修确认提交
			submit() {
				console.log(this.form)
				this.$refs.uForm.validate(valid => {
					if (valid) {
						if(this.type == 'add') {
							this.$u.post('auth/adminAdd', this.form).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: res.msg,
										icon: 'success',
										duration: 2000
									})
									uni.$emit('refreshMemberList')
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
							this.$u.post('auth/adminEdit', this.form).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: '修改成功',
										icon: 'success',
										duration: 2000
									})
									uni.$emit('refreshMemberList')
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
	padding: 0rpx 22rpx 1rpx;
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
