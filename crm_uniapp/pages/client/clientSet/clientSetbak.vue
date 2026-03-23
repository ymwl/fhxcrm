<template>
	<view>
		<view class="set-box" v-if="showForm">
			<u-form :model="form" :rules="rules" ref="uForm" :error-type="errorType">
				<u-form-item :label-position="labelPosition" label="客户名称:" required label-width="160"  prop="name">
					<u-input v-model="form.name" :border="true" placeholder="请输入客户名称" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="联系手机:" label-width="160" prop="mobile">
					<u-input v-model="form.mobile" :border="true" type="number" placeholder="请输入联系手机" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="联系电话:" label-width="160" >
					<u-input v-model="form.telephone" :border="true" type="tel"  placeholder="请输入联系电话" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="客户等级:" label-width="160" >
					<u-input type="select" :border="true" :select-open="levelShow" v-model="levelName" placeholder="选择等级" @click="levelShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="客户行业:" label-width="160" >
					<u-input type="select" :border="true" :select-open="industryShow" v-model="industryName" placeholder="选择行业" @click="industryShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="客户来源:" label-width="160" >
					<u-input type="select" :border="true" :select-open="sourceShow" v-model="sourceName" placeholder="选择来源" @click="sourceShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="客户标签:" label-width="160" >
					<u-input type="select" :border="true" :select-open="tagsShow" v-model="form.tags" placeholder="添加标签" @click="tagsShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="地址:" label-width="160" >
					<u-input type="select" :border="true" :select-open="addressShow" v-model="addressName" placeholder="请选择地址" @click="addressShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="详细地址:"  label-width="160" >
					<u-input v-model="form.detail_address" type="textarea"  :border="true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="下次跟进 :"  label-width="160" v-if="type == 'add'">
					<u-input type="select" :border="true" :select-open="createTimeShow" v-model="form.next_time" placeholder="选择跟进时间" @click="createTimeShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="备注 :"  label-width="160" >
					<u-input @blur="textareaBlur" :value="form.remark"  type="textarea"  :border="true" />
				</u-form-item>
				<!-- 自定义字段组件 -->
				<fa-fields :fields="fields" :form="form" v-model="form" :rules="rules" :labelPosition="labelPosition" :border="border"></fa-fields>
			</u-form>
			<!-- 底部浮动按钮 -->
			<view class="bottom-btn u-border-top" >
				<u-button type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true" >提交</u-button>
			</view>
		</view>
		<!-- 添加标签弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="tagsShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">添加标签</text> 
				<view class="" @click="tagsShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<scroll-view scroll-y style="height: 660rpx;width: 100%;">
				<view class="list">
					<view class="u-flex">
						<u-input class="u-flex-1 u-m-r-15" v-model="tagVal" :border="true" />
						<u-button type="primary" size="medium" @click="addTag" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">添加</u-button>
					</view> 
					<view class="u-flex u-m-t-50 u-flex-wrap">
						<view class="u-m-b-20 u-m-r-15" v-for="(item,index) in tagList" :key="index">
							<u-tag :text="item"  closeable @close="tagClick(index)" /> 
						</view>
					</view>
				</view>
			</scroll-view>
			<view class="bottom_btn u-border-top">
				<u-button type="primary"  @click="chosen" size="medium" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定</u-button>
			</view>
		</u-popup>
		<!-- 选择客户等级 -->
		<u-action-sheet :list="levelList" v-model="levelShow" @click="levelClick"></u-action-sheet>
		<!-- 选择客户行业 -->
		<u-action-sheet :list="industryList" v-model="industryShow" @click="industryClick"></u-action-sheet>
		<!-- 选择客户来源 -->
		<u-action-sheet :list="sourceList" v-model="sourceShow" @click="sourceClick"></u-action-sheet>
		<!-- 跟进时间选择 -->
		<u-picker v-model="createTimeShow" :hour="true" mode="time" :params="params" @confirm="createTimeChange"></u-picker>
		<!-- 选择地区 -->
		<u-select v-model="addressShow" mode="mutil-column-auto" label-name="name" :list="regionList" @confirm="addressChange" ></u-select>
	</view>
</template>

<script>
	import { processingImages,getImgUrl} from '@/common/mUtils'
	import {baseUrl,api_v1} from '@/common/config'
	import { formRule } from '@/common/fa.mixin.js'
	export default {
		mixins: [formRule],
		data() {
			return {
				showForm: false,
				labelPosition: 'left',
				border: true,
				customer_id: '',
				customerData: '',
				tagsShow: false,
				levelShow: false,
				industryShow: false,
				sourceShow: false,
				createTimeShow: false,
				addressShow: false,
				showPicker: false,
				city_field: '',
				params: {
					year: true,
					month: true,
					day: true,
					hour: true,
					minute: true,
					second: false
				},
				time_field: '',
				fields: [],
				column0Index: '',
				column1Index: '',
				regionList: [],
				levelName: '',
				industryName: '',
				sourceName: '',
				addressName: '',
				tagVal: '',
				tagList: [],
				form: {},
				type: 'add',
				timeText: '',
				errorType: ['message','toast'],
				levelList: [],
				industryList: [],
				sourceList: [],
				rules: {}
			};
		},
		onLoad(e) {
			// 获取城市区数据
			this.getAllarea();
			if(e.id) {
				this.customer_id = e.id
				this.getCustomer()
			} else {
				// 获取自定义字段
				this.getFields()
				// 获取配置字段
				this.getBaseConfig()
			}
			this.type = e.type
			if(this.type == "edit") {
				uni.setNavigationBarTitle({
					title: '编辑客户'
				});
			}
		},
		// 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
		onReady() {
			// this.$refs.uForm.setRules(this.rules)
		},
		methods: {
			// 优化微信小程序input、textarea快速删除时光标会跳到最后 处理：改用 textarea 失去焦点触发修改
			textareaBlur(val) {
				this.form.remark = val
			},
			// 格式化时间
			timeFormats(val) {
				if(val){
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM')
				} else {
					return '--'
				}
			},
			// 获取客户详情
			getCustomer() {
				this.$u.api.getCustomer({id: this.customer_id}).then(res => {
					if(res.code == 1 ) {
						this.customerData = res.data
						// 标签数据赋值
						if(this.customerData.tags) {
							this.tagList = this.customerData.tags.split(',')
						}
						// 获取自定义字段
						this.getFields()
						// 获取配置字段
						this.getBaseConfig()
						// 地区获取赋值
						this.addProvince()
					}
				})
			},
			// 地区获取赋值
			addProvince() {
				let name = ''
				// 获取省
				this.$u.api.getArea({province: '',city:''}).then((res) => {
					if(res.code == 1){
						res.data.forEach((item,index)=>{
							if(item.value == this.customerData.province){
								name = item.name
								// 获取市
								this.$u.api.getArea({province: item.value,city:''}).then((resc) => {
									if(res.code == 1){
										resc.data.forEach((i,idx)=>{
											if(i.value == this.customerData.city){
												name = name + i.name
												// 获取区
												this.$u.api.getArea({province: item.value,city: i.value}).then((resd) => {
													if(res.code == 1){
														resd.data.forEach((c,idc)=>{
															if(c.value == this.customerData.area){
																name = name + c.name
																this.addressName = name
															}
														})
													}
												})
											}
										})
									}
								})
							}
						})
					}
				})
			},
			// 确定
			chosen() {
				this.form.tags = this.tagList.join(",")
				this.tagsShow = false
			},
			// 选择客户等级
			levelClick(index) {
				this.levelName = this.levelList[index].text
				this.form.level = this.levelList[index].id
			},
			// 选择客户行业
			industryClick(index){
				this.industryName = this.industryList[index].text
				this.form.industry = this.industryList[index].id
			},
			// 选择时间
			createTimeChange(e){
				this.form.next_time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
			},
			// 地区选择
			addressChange(e) {
				console.log(e)
				this.form.province = e[0].value
				this.form.city = e[1].value
				this.form.area = e[2].value
				this.addressName = e[0].label + e[1].label + e[2].label
			},
			// 来源选择
			sourceClick(index) {
				this.sourceName = this.sourceList[index].text
				this.form.source = this.sourceList[index].id
			},
			// 添加标签
			addTag(){
				if(this.tagVal) {
					this.tagList.push(this.tagVal)
				}
			},
			// 移除标签
			tagClick(index) {
				this.tagList.splice(index,1)
				this.form.tags = this.tagList.join(',')
			},
			// 自定义字段
			getFields() {
				let arr = []
				this.$u.api.getFields({table: 'customer',id: ''}).then((res) => {
					if(res.code == 1){
						this.detail = res.data.info;
						this.fields = res.data.fields;
						//渲染自定义字段,默认字段
						let custom_form = {
							name: this.customer_id ? this.customerData.name : '',
							telephone: this.customer_id ? this.customerData.telephone : '',
							mobile: this.customer_id ? this.customerData.mobile : '',
							level: this.customer_id ? this.customerData.level : '',
							industry: this.customer_id ? this.customerData.industry : '',
							source: this.customer_id ? this.customerData.source : '',
							tags: this.customer_id ? this.customerData.tags : '',
							province: this.customer_id ? this.customerData.province : '',
							city: this.customer_id ? this.customerData.city : '',
							area: this.customer_id ? this.customerData.area : '',
							detail_address: this.customer_id ? this.customerData.detail_address : '',
							next_time: this.customer_id ? this.customerData.next_time : '',
							remark: this.customer_id ? this.customerData.remark : '',
						};
						let rules = {
							name: [
								{
									required: true,
									message: '请输入客户名称',
									// 可以单个或者同时写两个触发验证方式
									trigger: ['change', 'blur']
								}
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
									custom_form[item.name] = this.customerData[item.name]
								} else {
									custom_form[item.name] = this.customerData[item.name]
									item.value = this.customerData[item.name] // 默认值为已有的数据
								}
								//单图赋值
								if (item.type == 'image') {
									if (this.customerData[item.name]) {
										item.value = [
											{
												url: getImgUrl(this.customerData[item.name])
											}
										];
									} else {
										item.value = [];
									}
								}
								//多图赋值
								if (item.type == 'images') {
									if (this.customerData[item.name]) {
										let images = this.customerData[item.name].split(',');
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
									item.value = this.customerData[item.name] ? [this.customerData[item.name]] : [];
								}
								//多文件
								if (item.type == 'files') {
									if (this.customerData[item.name]) {
										item.value = this.customerData[item.name].split(',');
									} else {
										item.value = [];
									}
								}
							} else {
								// 新增场景 表单赋值
								if (item.type == 'number') {
									custom_form[item.name] = item.value || item.defaultvalue || '';
								} else {
									custom_form[item.name] = item.value || item.defaultvalue || '';
								}
								if(item.type == 'radio') {
									item.value = this.customerData[item.name]
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
						this.rules = rules;
						this.showForm = true;
						//设置表单验证规则
						//console.log(this.form, this.rules, this.fields);
						this.$nextTick(() => {
							this.$refs.uForm.setRules(this.rules);
						});
					}
				})
			},
			// 获取配置字段
			getBaseConfig() {
				this.$u.api.getBaseConfig().then((res) => {
					if(res.code == 1){
						this.levelList = this.onJson(res.data.levelList)
						this.industryList = this.onJson(res.data.industryList)
						this.sourceList = this.onJson(res.data.sourceList)
						// 编辑 数据赋值
						if(this.type == 'edit'){
							this.levelName = res.data.levelList[this.customerData.level]
							this.industryName = res.data.industryList[this.customerData.industry]
							this.sourceName = res.data.sourceList[this.customerData.source]
						}
					}
				})
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
			//获取所有省市区数据 用于下拉选择
			getAllarea(){
				let arrList = uni.getStorageSync('storage_getAllarea');
				if(this.$u.test.array(arrList)&&arrList.length>0) {
					this.regionList = arrList
				} else {
					this.$u.api.getAllarea().then((res) => {
						if(res.code == 1){
							this.regionList = (res.data);
							uni.setStorageSync('storage_getAllarea',res.data);
						}
					})
				}
			},
			// 修确认提交
			submit() {
				console.log(this.form)
				this.$refs.uForm.validate(valid => {
					if (valid) {
						// 提交
						this.onSubmit()
					} else {
						console.log('验证失败');
					}
				});
			},
			// 提交
			onSubmit() {
				if(this.type == 'add') {
					this.$u.api.onCustomerAdd(this.form).then((res) => {
						if(res.code == 1) {
							// 提示
							uni.showToast({
								title: '添加成功',
								icon: 'success',
								duration: 2000
							})
							setTimeout(() => {
								uni.navigateBack();
							}, 1000);
						}
					})
				} else {
					this.form.id = this.customer_id
					this.$u.api.onCustomerEdit(this.form).then((res) => {
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
						}
					})
				}
			}
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
		padding: 30rpx 26rpx;
		margin-bottom: 45rpx;
		
	}
	.bottom_btn {
		text-align: right;
		padding: 28rpx 10rpx 45rpx;
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
