<template>
	<view>
		<view class="set-box" >
			<u-form :model="form" v-model="form" :rules="rules" ref="uForm" :error-type="errorType">
				<!-- 自定义字段组件 -->
				<fa-fields :fields="fields" :form="form" :model="form"  v-model="form" :rules="rules" :labelPosition="labelPosition" :border="border" ></fa-fields>
			</u-form>
			<!-- 底部浮动按钮 -->
			<view class="bottom-btn u-border-top" >
				<u-button type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true" >提交</u-button>
			</view>
		</view>


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
    watch:{
		  form:{
		    handler(val){
        },
        deep:true
      }
    },
		onLoad(e) {
			// 获取城市区数据
			// this.getAllarea();
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
						// this.addProvince()
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
				let arr = [];let source='addForm';this.type == 'edit' ? source='editForm' : source='addForm';
				this.$u.api.getFields({table: 'crm_customer',source: source}).then((res) => {
					if(res.code == 1){
						this.detail = res.data.info;
						this.fields = res.data.fields;
						//渲染自定义字段,默认字段
						let custom_form = {
						};
						let rules = {
							/*name: [
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
							]*/
						};
						this.fields.map(item => {
							// 编辑场景
							if(this.type == 'edit') {
								//表单赋值
								if (item.type == 'number') {
									custom_form[item.field] = this.customerData[item.field]
								} else {
									custom_form[item.field] = this.customerData[item.field]
									item.value = this.customerData[item.field] // 默认值为已有的数据
								}
								//单图赋值
								if (item.type == 'image') {
									if (this.customerData[item.field]) {
										item.value = [
											{
												url: getImgUrl(this.customerData[item.field])
											}
										];
									} else {
										item.value = [];
									}
								}
								//多图赋值
								if (item.type == 'images') {
									if (this.customerData[item.field]) {
										let images = this.customerData[item.field].split(',');
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
									item.value = this.customerData[item.field] ? [this.customerData[item.field]] : [];
								}
								//多文件
								if (item.type == 'files') {
									if (this.customerData[item.field]) {
										item.value = this.customerData[item.field].split(',');
									} else {
										item.value = [];
									}
								}
							} else {
								// 新增场景 表单赋值
								if (item.type == 'number') {
									custom_form[item.field] = item.value || item.default || 0;
								} else {
									custom_form[item.field] = item.value || item.default || '';
								}
								if(item.type == 'radio') {
									item.value = this.customerData[item.field]
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
							rules[item.field] = this.getRules(item)
						});
						this.form = custom_form // 表单字段数据合并
						this.rules = rules;
						this.showForm = true;
						//设置表单验证规则

            console.log('this.form=',this.form)
            console.log('this.rules=',this.rules)
            console.log('this.fields=', this.fields)
						this.$nextTick(() => {
							this.$refs.uForm.setRules(this.rules);
						});
					}
				})
			},
			// 获取配置字段
			getBaseConfig() {
				/*this.$u.api.getBaseConfig().then((res) => {
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
				})*/
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
				console.log('提交数据=',this.form)
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
						}else{
              this.$reuse.showError(res.msg)
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
