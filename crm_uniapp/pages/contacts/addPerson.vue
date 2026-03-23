<template>
	<view>
		<view class="set-box" v-if="showForm">
			<u-form :model="form" ref="uForm" label-position="top" :error-type="errorType">
				<u-form-item :label-position="labelPosition" label="客户:"  label-width="160" >
					<u-input type="select" :border="true" :select-open="selectShow" :disabled="type == 'edit' ? true : false" v-model="customerName" placeholder="选择客户" @click="type == 'edit' ? selectShow = false : selectShow = true" />
				</u-form-item>
				<u-form-item required :label-position="labelPosition" label="姓名:"  label-width="160" prop="name">
					<u-input v-model="form.name" :border="true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="联系手机:" label-width="160" prop="mobile">
					<u-input v-model="form.mobile" type="number" :border="true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="联系电话:" label-width="160" >
					<u-input v-model="form.telephone"  type="number" :border="true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="邮箱:" label-width="160" >
					<u-input v-model="form.email" :border="true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="微信:" label-width="160" >
					<u-input v-model="form.wechat" :border="true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="职务:" label-width="160" >
					<u-input v-model="form.post" :border="true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="生日:" label-width="160" >
					<u-input type="select" :border="true" :select-open="dateShow" v-model="form.birthday" placeholder="选择生日" @click="dateShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="性别:" label-width="160" >
					<u-radio-group v-model="form.sex" >
						<u-radio v-for="(item, index) in sexList"  :key="index"  :name="item.name">
							{{ item.text }}
						</u-radio>
					</u-radio-group>
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="决策人:" label-width="160" >
					<u-radio-group v-model="form.decision" >
						<u-radio v-for="(item, index) in typeList"  :key="index" :name="item.name">
							{{ item.text }}
						</u-radio>
					</u-radio-group>
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="地址:" label-width="160" prop="total_num">
					<u-input class="u-flex-1 u-m-r-15" :border="true" v-model="form.detail_address" placeholder="请填写地址" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="下次联系时间 :"  label-width="160" prop="sort">
					<u-input type="select" :border="true" :select-open="nextTimeShow" v-model="form.next_time" placeholder="选择下次联系时间" @click="nextTimeShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="备注 :"  label-width="160" prop="sort">
					<u-input @blur="textareaBlur" type="textarea" :value="form.remark"  :border="true" />
				</u-form-item>
				<!-- 自定义字段组件 -->
				<fa-fields :fields="fields" :form="form" v-model="form" :rules="rules" :labelPosition="labelPosition" :border="border"></fa-fields>
			</u-form>
			<!-- 底部浮动按钮 -->
			<view class="bottom-btn u-border-top" >
				<u-button type="success"  @click="submit"  :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor,fontSize: '30rpx',fontWeight: '600'}" :ripple="true" >提交</u-button>
			</view>
		</view>
		<u-modal v-model="show" :content="content"></u-modal>
		<u-calendar v-model="dateShow" :min-date="minDate" :max-date="maxDate" mode="date" @change="change"></u-calendar>
		<!-- 选择客户弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="selectShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择客户</text> 
				<view class="" @click="selectShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入客户名称搜索" @change="onSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="reachBottom">
				<view class="list">
					<block v-if="customerList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in customerList" :key="index" @click="onItem(item,index)">
								<view class="title">{{item.name}}</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="listStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
		<!-- 跟进时间选择 -->
		<u-picker v-model="nextTimeShow" :hour="true" mode="time" :params="params" @confirm="nextTimeChange"></u-picker>
	</view>
</template>

<script>
	import { processingImages,getImgUrl,get_date} from '@/common/mUtils'
	import {baseUrl,api_v1} from '@/common/config'
	import { formRule } from '@/common/fa.mixin.js'

	export default {
		mixins: [formRule],
		data() {
			return {
				showForm: false,
				labelPosition: 'top',
				border: true,
				selectShow:false,
				nextTimeShow: false,
				city_field: '',
				listStatus: 'loadmore',
				page: 1,
				pageSize: 10,
				keyword: '',
				lastPage: false,
				customerList: [],
				customerName: '',
				type: '',
				contacts_id: '',
				customer_id: '',
				minDate: '',
				maxDate: '',
				dateShow: false,
				show: false,
				content: '',
				time_field: '',
				fields: [],
				contactsData: {},
				form: {},
				params: {
					year: true,
					month: true,
					day: true,
					hour: true,
					minute: true,
					second: false
				},
				errorType: ['message','toast'],
				sexList: [
					{
						name: -1,
						text: '未知',
					},
					{
						name: 1,
						text: '男',
					},
					{
						name: 0,
						text: '女',
					},
				],
				typeList: [
					{
						name: -1,
						text: '未知',
					},
					{
						name: 1,
						text: '是',
					},
					{
						name: 0,
						text: '否',
					},
				],
				rules: {}
			};
		},
		onLoad(e) {
			this.contacts_id = e.id ? e.id : '';
			this.customer_id = e.customer_id ? e.customer_id : '' // 默认选中的客户id
			this.type = e.type
			if(this.type == "edit") {
				uni.setNavigationBarTitle({
					title: '编辑联系人'
				});
				this.getInfo()
			} else {
				this.getFields()
			}
			this.getData()
		},
		methods: {
			// 优化微信小程序input、textarea快速删除时光标会跳到最后 处理：改用 textarea 失去焦点触发修改
			textareaBlur(val) {
				this.form.remark = val
			},
			// 搜索
			onSearch() {
				this.page = 1
				this.lastPage = false
				this.getData()
			},
			// 获取联系人详情
			getInfo() {
				if(this.contacts_id){
					this.$u.api.getContactsEdit({id: this.contacts_id}).then(res => {
						if(res.code == 1 ) {
							this.contactsData = res.data
							this.getFields()
						}
					})
				}
			},
			// 获取自定义字段
			getFields() {
				let arr = []
				this.$u.api.getFields({table: 'customer_contacts',id: ''}).then((res) => {
					if(res.code == 1){
						this.fields = res.data.fields;
						//渲染自定义字段,默认字段
						let custom_form = {
							customer_id: this.contacts_id ? this.contactsData.customer_id : '',
							name: this.contacts_id ? this.contactsData.name : '',
							telephone: this.contacts_id ? this.contactsData.telephone : '',
							mobile: this.contacts_id ? this.contactsData.mobile : '',
							email: this.contacts_id ? this.contactsData.email : '',
							wechat: this.contacts_id ? this.contactsData.wechat : '',
							post: this.contacts_id ? this.contactsData.post : '',
							birthday: this.contacts_id ? this.contactsData.birthday : '',
							sex: this.contacts_id ? this.contactsData.sex : -1,
							decision: this.contacts_id ? this.contactsData.decision : -1,
							// province: this.contacts_id ? this.contactsData.province : '',
							// city: this.contacts_id ? this.contactsData.city : '',
							// area: this.contacts_id ? this.contactsData.area : '',
							detail_address: this.contacts_id ? this.contactsData.detail_address : '',
							next_time: this.contacts_id ? this.$u.timeFormat(this.contactsData.next_time, 'yyyy-mm-dd hh:MM') : '',
							remark: this.contacts_id ? this.contactsData.remark : '',
						};
						let rules = {
							name: [
								{
									required: true,
									message: '请输入联系人名字',
									trigger: ['change','blur']
								},
							],
							mobile: [
								{
									// 自定义验证函数，见上说明
									validator: (rule, value, callback) => {
										if(value == '') {
											return true
										}
										return this.$u.test.mobile(value);
									},
									message: '手机号码不正确',
									// 触发器可以同时用blur和change
									trigger: ['change','blur'],
								}
							]
						};
						this.fields.map(item => {
							// 编辑场景
							if(this.type == 'edit') {
								//表单赋值
								if (item.type == 'number') {
									custom_form[item.name] = this.contactsData[item.name]
								} else {
									custom_form[item.name] = this.contactsData[item.name]
									item.value = this.contactsData[item.name] // 默认值为已有的数据
								}
								//单图赋值
								if (item.type == 'image') {
									if (this.contactsData[item.name]) {
										item.value = [
											{
												url: getImgUrl(this.contactsData[item.name])
											}
										];
									} else {
										item.value = [];
									}
								}
								//多图赋值
								if (item.type == 'images') {
									if (this.contactsData[item.name]) {
										let images = this.contactsData[item.name].split(',');
										let urls = [];
										images.forEach(it => {
											urls.push({
												url: getImgUrl(it)
											});
										});
										item.value = urls;
									} else {
										item.value = [];
									}
								}
								//单文件
								if (item.type == 'file') {
									item.value = this.contactsData[item.name] ? [this.contactsData[item.name]] : [];
								}
								//多文件
								if (item.type == 'files') {
									if (this.contactsData[item.name]) {
										item.value = this.contactsData[item.name].split(',');
									} else {
										item.value = [];
									}
								}
							} else {
								// 新增场景
								//表单赋值
								if (item.type == 'number') {
									custom_form[item.name] = item.value || item.defaultvalue || '';
								} else {
									custom_form[item.name] = item.value || item.defaultvalue || '';
								}
								if(item.type == 'radio') {
									item.value = this.contactsData[item.name]
								}
								//单图赋值
								if (item.type == 'image') {
									if (item.value) {
										item.value = [
											{
												url: getImgUrl(item.value)
											}
										];
									} else {
										item.value = [];
									}
								}
								//多图赋值
								if (item.type == 'images') {
									if (item.value) {
										let images = item.value.split(',');
										let urls = [];
										images.forEach(it => {
											urls.push({
												url: getImgUrl(it)
											});
										});
										item.value = urls;
									} else {
										item.value = [];
									}
								}
								//单文件
								if (item.type == 'file') {
									item.value = item.value ? [item.value] : [];
								}
								//多文件
								if (item.type == 'files') {
									if (item.value) {
										item.value = item.value.split(',');
									} else {
										item.value = [];
									}
								}
							}
							//追加自定义表单验证
							rules[item.name] = this.getRules(item)
						});
						this.form = custom_form // 表单字段数据合并
						// 添加默认客户
						if(this.customer_id){
							this.form.customer_id =this.customer_id
						}
						this.rules = rules;
						this.showForm = true;
						//设置表单验证规则
						console.log(this.form, this.rules, this.fields);
						this.$nextTick(() => {
							this.$refs.uForm.setRules(this.rules);
						});
					}
				})
			},
			// 获取客户列表
			getData(isNextPage,pages) {
				// 筛选参数
				let obj = {
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.keyword,
					keyField: 'id',
					showField: 'name',
					"q_word": this.keyword,
					"searchField": "name"
				}
				if(this.customer_id) {
					obj = {
						keyField: 'id',
						showField: 'name',
						"q_word": this.customer_id,
						"searchField": "id"
					}
				}
				this.$u.api.getCustomerSelectpage(obj).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastPage = true
						} 
						//不够一页
						if (res.data.list.length < this.pageSize) {
							this.listStatus = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.customerList = this.customerList.concat(res.data.list)
							return 
						}
						this.customerList = res.data.list
						if(this.customer_id) {
							this.customerList.forEach((item,index) => {
								if(this.customer_id == item.id) {
									item.checked = true
									this.customerName = item.name
								} else {
									item.checked = false
								}
							})
						}
					}
				})
			},
			// 滚动到底部加载更多
			reachBottom() {
				if(this.lastPage || this.listStatus == 'loading') return ;
				this.listStatus = 'loading'
				setTimeout(() => {
					if(this.lastPage) return ;
					this.getData(true,++this.page)
					if(this.customerList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 选择客户
			onItem(val,i) {
				this.customerList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
						val.name=val.name?val.name:item.name;
					} else {
						item.checked = false
					}
				})
				this.form.customer_id = val.id
				this.customerName = val.name ? val.name : ''
				this.selectShow = false
			},
			// 选择时间
			nextTimeChange(e){
				this.form.next_time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
				console.log(	this.form.next_time)
			},
			// 修确认提交
			submit() {
				this.$refs.uForm.validate(valid => {
					if (valid) {
						if(this.type == 'add') {
							// 添加
							this.$u.api.onContactsAdd(this.form).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: "添加成功",
										icon: 'success',
										duration: 2000
									})
									setTimeout(() => {
										uni.navigateBack();
									}, 1000);
								}
							})
						} else {
							//修改
							this.form.id = this.contacts_id
							this.$u.api.onContactsEdit(this.form).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: "修改成功",
										icon: 'success',
										duration: 2000
									})
									setTimeout(() => {
										uni.navigateBack();
									}, 1000);
								}
							})
						}
					} else {
						console.log('验证失败');
					}
				});
			},
			// 选择生日时间变化
			change(e) {
				this.form.birthday = e.result
			},
		},
	}
</script>

<style lang="scss">
.set-box {
	padding: 0rpx 22rpx 250rpx;
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
.bottom-btn {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 25rpx 30rpx 45rpx;
	background-color: #fff;
	z-index: 100;
}
</style>
