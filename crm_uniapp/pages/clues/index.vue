<template>
	<view>
		<view class="set-box" v-if="showForm">
			<u-form :model="form" :rules="rules" ref="uForm" :error-type="errorType">
				<u-form-item required label="归属人:" label-width="160" v-if="type == 'transform'">
					<u-input type="select" :border="border" :select-open="adminShow" v-model="adminName" placeholder="选择归属人" @click="adminShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="名称:" required label-width="160"  prop="name">
					<u-input v-model="form.name" :border="true" placeholder="请输入名称" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="联系手机:" label-width="160" prop="mobile">
					<u-input v-model="form.mobile" :border="true" type="number" placeholder="请输入联系手机" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="联系电话:" label-width="160" >
					<u-input v-model="form.telephone" :border="true" type="tel"  placeholder="请输入联系电话" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="等级:" label-width="160" >
					<u-input type="select" :border="true" :select-open="levelShow" v-model="levelName" placeholder="选择等级" @click="levelShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="行业:" label-width="160" >
					<u-input type="select" :border="true" :select-open="industryShow" v-model="industryName" placeholder="选择行业" @click="industryShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="来源:" label-width="160" >
					<u-input type="select" :border="true" :select-open="sourceShow" v-model="sourceName" placeholder="选择来源" @click="sourceShow = true" />
				</u-form-item>
				<u-form-item :label-position="labelPosition" label="标签:" label-width="160" >
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
					<u-input @blur="textareaBlur" type="textarea" :value="form.remark" :border="true" />
				</u-form-item>
				<!-- 自定义字段组件 -->
				<fa-fields :fields="fields" :form="form" v-model="form" :rules="rules" :labelPosition="labelPosition" :border="border"></fa-fields>
			</u-form>
			<view class="u-m-t-40" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit"  :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">提交</u-button>
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
				<u-button class="u-m-l-15" type="primary"  @click="chosen" size="medium" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定</u-button>
			</view>
		</u-popup>
		<!-- 选择归属人 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="adminShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择归属人</text> 
				<view class="" @click="adminShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="adminkeyword" :show-action="false" :clearabled="true"  placeholder="输入归属人名称搜索" @change="adminSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="adminBottom">
				<view class="adminList">
					<block v-if="adminList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in adminList" :key="index" @click="onAdmin(item,index)">
								<view class="title">{{item.realname}}</view>
								<view class="check-icon">
									<u-icon v-if="item.id == form.owner_user_id" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="adminStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
			<!-- <view class="bottom_btn u-border-top">
				<u-button size="medium" @click="selected = true">查看已选</u-button> 
				<u-button class="u-m-l-15" type="primary"  @click="chosen" size="medium">选好了</u-button>
			</view> -->
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
				adminShow: false,
				showForm: false,
				labelPosition: 'left',
				border: true,
				clues_id: '',
				cluesData: '',
				adminName: '',
				adminPage: 1,
				pageSize: 20,
				lastAdmin: false,
				adminStatus: 'loadmore',
				adminList: [],
				adminkeyword: '',
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
			this.type = e.type
			switch (e.type) {
				case 'add':
					// 获取自定义字段
					this.getFields()
					// 获取配置字段
					this.getBaseConfig()
					break;
				case 'edit':
					this.clues_id = e.id
					this.getClues()
					uni.setNavigationBarTitle({
						title: '编辑线索'
					});
					break;
				case 'transform':
					this.clues_id = e.id
					this.getClues()
					this.onSelectpage()
					uni.setNavigationBarTitle({
						title: '转化成客户'
					});
					break;
				default:
					break;
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
			// 归属人搜索
			adminSearch() {
				this.adminPage = 0
				this.onSelectpage()
			},
			// 获取归属人
			onSelectpage(isNextPage,pages) {
				this.$u.api.getAllAdmin({
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.adminkeyword,
					keyField: 'id',
					showField: 'realname',
					"q_word": this.adminkeyword,
				}).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastAdmin = true
						} 
						//不够一页
						if (res.data.list.length < this.pageSize) {
							this.adminStatus = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.adminList = this.adminList.concat(res.data.list)
							return 
						}
						this.adminList = res.data.list
					}
				})
			},
			// 滚动到底部加载更多
			adminBottom() {
				if(this.lastAdmin || this.adminStatus == 'loading') return ;
				this.adminStatus = 'loading'
				setTimeout(() => {
					if(this.lastAdmin) return ;
					this.onSelectpage(true,++this.adminPage)
					if(this.adminList.length >= 10) this.adminStatus = 'loadmore';
					else this.adminStatus = 'loading';
				}, 1200)
			},
			// 选择归属人
			onAdmin(val,i) {
				this.adminList.forEach((item,index) => {
					if(val.id == item.id) {
						this.adminName = item.realname
						this.form.owner_user_id = item.id
					}
				})
				this.adminShow = false
			},
			// 获取详情
			getClues() {
				this.$u.api.getCluesEdit({ids: this.clues_id}).then(res => {
					if(res.code == 1 ) {
						this.cluesData = res.data
						console.log(this.cluesData)
						// 标签数据赋值
						if(this.cluesData.tags) {
							this.tagList = this.cluesData.tags.split(',')
						}
						// 获取自定义字段
						this.getFields()
						// 获取配置字段
						this.getBaseConfig()
						// 地区获取赋值
						this.addProvince()
						// 获取归属人数据
						this.$u.api.getAllAdmin({
							keyField: 'id',
							keyValue: res.data.owner_user_id,
							showField: 'realname',
						}).then(res => {
							if(res.code == 1 ) {
								if(res.data.list.length > 0) {
									this.adminName = res.data.list[0].realname
									this.form.owner_user_id = res.data.list[0].id
								}
							}
						})
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
							if(item.value == this.cluesData.province){
								name = item.name
								// 获取市
								this.$u.api.getArea({province: item.value,city:''}).then((resc) => {
									if(res.code == 1){
										resc.data.forEach((i,idx)=>{
											if(i.value == this.cluesData.city){
												name = name + i.name
												// 获取区
												this.$u.api.getArea({province: item.value,city: i.value}).then((resd) => {
													if(res.code == 1){
														resd.data.forEach((c,idc)=>{
															if(c.value == this.cluesData.area){
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
				this.$u.api.getFields({table: this.type == 'transform' ? 'customer' : 'clues',id: ''}).then((res) => {
					if(res.code == 1){
						this.detail = res.data.info;
						this.fields = res.data.fields;
						//渲染自定义字段,默认字段
						let custom_form = {
							name: this.clues_id ? this.cluesData.name : '',
							telephone: this.clues_id ? this.cluesData.telephone : '',
							mobile: this.clues_id ? this.cluesData.mobile : '',
							level: this.clues_id ? this.cluesData.level : '',
							industry: this.clues_id ? this.cluesData.industry : '',
							source: this.clues_id ? this.cluesData.source : '',
							tags: this.clues_id ? this.cluesData.tags : '',
							province: this.clues_id ? this.cluesData.province : '',
							city: this.clues_id ? this.cluesData.city : '',
							area: this.clues_id ? this.cluesData.area : '',
							detail_address: this.clues_id ? this.cluesData.detail_address : '',
							next_time: this.clues_id ? this.cluesData.next_time : '',
							remark: this.clues_id ? this.cluesData.remark : '',
						};
						let rules = {
							name: [
								{
									required: true,
									message: '请输入名称',
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
									custom_form[item.name] = this.cluesData[item.name] 
								} else {
									custom_form[item.name] = this.cluesData[item.name]
									item.value = this.cluesData[item.name] // 默认值为已有的数据
								}
								//单图赋值
								if (item.type == 'image') {
									if (this.cluesData[item.name]) {
										item.value = [
											{
												url: getImgUrl(this.cluesData[item.name])
											}
										];
									} else {
										item.value = [];
									}
								}
								//多图赋值
								if (item.type == 'images') {
									if (this.cluesData[item.name]) {
										let images = this.cluesData[item.name].split(',');
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
									item.value = this.cluesData[item.name] ? [this.cluesData[item.name]] : [];
								}
								//多文件
								if (item.type == 'files') {
									if (this.cluesData[item.name]) {
										item.value = this.cluesData[item.name].split(',');
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
									item.value = this.cluesData[item.name]
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
						if(this.type == 'edit' || this.type == 'transform'){
							this.levelName = res.data.levelList[this.cluesData.level]
							this.industryName = res.data.industryList[this.cluesData.industry]
							this.sourceName = res.data.sourceList[this.cluesData.source]
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
				let arrList = uni.getStorageSync('storage_getAllarea')
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
				let params = {}
				for (const key in this.form) {
					if (Object.hasOwnProperty.call(this.form, key)) {
						if(key == 'province' || key == 'city' || key == 'area') {
							params[key] = this.form[key]
						} else {
							params['row[' + key + ']'] = this.form[key]
						}
					}
				}
				switch (this.type) {
					case 'add':
						this.$u.api.onCluesAdd(params).then((res) => {
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
						break;
					case 'edit':
						params.ids = this.clues_id
						this.$u.api.onCluesEdit(params).then((res) => {
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
						break;
					case 'transform':
						params['row[ids]'] = this.clues_id
						this.$u.api.postCluesTransform(params).then((res) => {
							if(res.code == 1) {
								// 提示
								uni.showToast({
									title: '转化成功',
									icon: 'success',
									duration: 2000
								})
								setTimeout(() => {
									uni.navigateBack();
								}, 1000);
							}
						})
						break;
				
					default:
						break;
				}
			}
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
	.adminList {
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
</style>
