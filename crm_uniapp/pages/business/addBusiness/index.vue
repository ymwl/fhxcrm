<template>
	<view style="overflow:hidden">
		<view class="set-box" v-if="showForm">
			<u-form :model="form" ref="uForm" :error-type="errorType">
				<u-form-item required label="客户:" label-width="160" >
					<u-input type="select" :border="border" :select-open="selectShow" v-model="customerName" placeholder="选择客户" @click="selectShow = true" />
				</u-form-item>
				<u-form-item required label="商机名称:" label-width="160" prop="name">
					<u-input v-model="form.name" :border="border" />
				</u-form-item>
				<u-form-item label="预算金额:" label-width="160" >
					<u-input type="digit" v-model="form.money" :border="border" />
				</u-form-item>
				<u-form-item label="预计成交:" label-width="160" >
					<u-input type="select" :border="border" :select-open="dealTimeShow" v-model="form.deal_time" placeholder="选择成交时间" @click="dealTimeShow = true" />
				</u-form-item>
				<u-form-item label="下次跟进 :"  label-width="160" >
					<u-input type="select" :border="border" :select-open="nextTimeShow" v-model="form.next_time" placeholder="选择跟进时间" @click="nextTimeShow = true" />
				</u-form-item>
				<u-form-item label="备注:"  label-width="160" prop="sort">
					<u-input type="textarea"  @blur="textareaBlur" :value="form.remark"  :border="border" />
				</u-form-item>
				<!-- 自定义字段组件 -->
				<fa-fields :fields="fields" :form="form" :rules="rules" v-model="form" :labelPosition="labelPosition" :border="border"></fa-fields>
			</u-form>
			<!-- 产品 -->
			<view class="u-flex cif-title u-border-bottom" @click="selectProduct">
				<view class="u-flex-1 text">意向产品</view>
				<view class="" style="color:#2979ff">
					选择产品<u-icon name="arrow-right" color="#909399" size="30"></u-icon>
				</view>
				<!-- <u-button type="info" size="medium" plain>选择产品</u-button> -->
			</view>
			<view class="list">
				<block v-if="selectList.length > 0">
					<view class="item" v-for="(item,index) in selectList" :key="index">
						<view class="number u-flex">
							<view class="u-flex-1">序号: {{index+1}}</view>
							<u-number-box :min="1" v-model="item.nums" :long-press="false" @change="valChange"></u-number-box>
						</view>
						<view class="content u-flex">
							<view class="u-flex-1 name">{{item.name ? item.name : (item.info ? item.info.name : '' )}}</view>
							<view class="price u-flex">
								<view class="u-p-r-15" style="font-weight: 600;">￥</view>
								<u-input class="u-flex-1" :clearable="false" placeholder="请输入价格" v-model="item.price" @input="inputChang" :border="border" />
							</view>
						</view>
						<view class="brief u-flex">
							<view class="">备注：</view>
							<u-input class="u-flex-1" v-model="item.remarks" :border="border" />
						</view>
						<!-- 删除按钮 -->
						<view class="close" @click="remove(index)">
							<u-icon name="close"  color="#909399" size="30"></u-icon>
							<!-- <van-icon name="close"  size="20px"/> -->
						</view>
					</view>
				</block>
				<u-empty text="暂未选择产品" v-else  margin-top="100" mode="list"></u-empty>
			</view>
			<view class="u-flex u-m-b-15 u-m-t-15">
				<view class="u-m-r-15" style="width:150rpx">优惠率%：</view>
				<u-input class="u-flex-1" v-model="form.discount_rate" placeholder="请填写0到100的数" type='number' @input="inputChang" :border="border" />
			</view>
			<view class="u-flex u-m-b-15 u-m-t-15">
				<view class="u-m-r-15" style="width: 150rpx">总额：</view>
				<u-input class="u-flex-1" type="digit" v-model="form.total_price" :border="border" />
			</view>
			<view class="u-m-t-40" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定提交</u-button>
			</view>
		</view>
		<u-calendar v-model="dateShow" :min-date="minDate" :max-date="maxDate" mode="range" @change="change"></u-calendar>
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
						<view class="u-p-b-50">
							<u-loadmore :status="listStatus" ></u-loadmore>
						</view>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
			<!-- <view class="bottom_btn u-border-top">
				<u-button class="u-m-l-15" type="primary"  @click="chosen" size="medium">选好了</u-button>
			</view> -->
		</u-popup>
		<!-- 跟进时间选择 -->
		<u-picker v-model="nextTimeShow" :hour="true" mode="time" :params="params" @confirm="nextTimeChange"></u-picker>
		<!-- 成交时间选择 -->
		<u-picker v-model="dealTimeShow" :hour="true" mode="time" :params="params" @confirm="dealTimeChange"></u-picker>
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
				searchTimer: null,
				labelPosition: 'left',
				border: true,
				keyword: '',
				showForm: false,
				selectShow:false,
				nextTimeShow: false,
				dealTimeShow: false,
				selected: false,
				listStatus: 'loadmore',
				customerName: '',
				goodsName: '',
				page: 1,
				pageSize: 10,
				lastPage: false,
				customerList: [],
				selectList: [],
				businessData: {},
				type: '',
				params: {
					year: true,
					month: true,
					day: true,
					hour: true,
					minute: true,
					second: false
				},
				business_id: '',
				customer_id: '',
				minDate: '',
				maxDate: '',
				dateShow: false,
				show: false,
				content: '',
				form: {
					customer_id: '',
					name: '',
					next_time: '',
					deal_time: '',
					money: '',
					remark: '',
					discount_rate: '',
					total_price: '',
				},
				timeText: '',
				errorType: ['message','toast'],
				rules: {},
				fields: [],
			};
		},
		onLoad(e) {
			this.type = e.type
			// 默认选中客户
			if(e.customer_id){
				this.customer_id = e.customer_id
			}
			if(e.id) {
				this.business_id = e.id
				this.getBusiness()
				this.getData()
			} else {
				this.getData()
				// 获取自定义字段
				this.getFields()
			}
			if(this.type == "edit") {
				uni.setNavigationBarTitle({
					title: '编辑商机'
				});
			}
		},
		onUnload() {
			// 页面销毁清除已选商品数据
			this.$u.vuex('vuex_selectProduct', '')
		},
		onShow() {
			if(!this.$u.test.isEmpty(this.vuex_selectProduct)) {
				// 已选产品数据处理
				this.vuex_selectProduct.forEach((item,index)=>{
					let filter = this.selectList.find(i => {
						return item.id == i.id
					})
					//  没有的，添加到 list 列表
					if(filter == undefined) {
						this.selectList.push(item)
					}
				})
				// 计算价格
				this.count_price()
			}
		},
		// 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
		onReady() {
			// this.$refs.uForm.setRules(this.rules);
		},
		methods: {
			// 优化微信小程序input、textarea快速删除时光标会跳到最后 处理：改用 textarea 失去焦点触发修改
			textareaBlur(val) {
				this.form.remark = val
			},
			// 获取自定义字段
			getFields() {
				let arr = []
				this.$u.get('fields/get_fields', {table: 'business',id: ''}).then((res) => {
					if(res.code == 1){
						this.detail = res.data.info;
						this.fields = res.data.fields;
						//渲染自定义字段,默认字段
						let custom_form = {
							customer_id : this.business_id ? this.businessData.customer_id : '',
							name :  this.business_id ? this.businessData.name : '',
							money : this.business_id ? this.businessData.money : '',
							deal_time : this.business_id ? this.timeFormats( this.businessData.deal_time) : '',
							next_time : this.business_id ? this.timeFormats( this.businessData.next_time) : '',
							remark : this.business_id ? this.businessData.remark : '',
							discount_rate : this.business_id ?  this.businessData.discount_rate: '', 
							total_price :  this.business_id ? this.businessData.total_price : '',
						};
						let rules = {
							name: [
								{
									required: true,
									message: '请输入商机名称',
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
									custom_form[item.name] = this.businessData[item.name]
								} else {
									custom_form[item.name] = this.businessData[item.name]
									item.value = this.businessData[item.name] // 默认值为已有的数据
								}
								//单图赋值
								if (item.type == 'image') {
									if (this.businessData[item.name]) {
										item.value = [
											{
												url: getImgUrl(this.businessData[item.name])
											}
										];
									} else {
										item.value = [];
									}
								}
								//多图赋值
								if (item.type == 'images') {
									if (this.businessData[item.name]) {
										let images = this.businessData[item.name].split(',');
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
									item.value = this.businessData[item.name] ? [this.businessData[item.name]] : [];
								}
								//多文件
								if (item.type == 'files') {
									if (this.businessData[item.name]) {
										item.value = this.businessData[item.name].split(',');
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
									item.value = this.businessData[item.name]
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
						// 选中默认客户
						if(this.customer_id) {
							this.form.customer_id = this.customer_id
						}
						this.rules = rules;
						this.showForm = true;
						//设置表单验证规则
						// console.log(this.form, this.rules, this.fields);
						this.$nextTick(() => {
							this.$refs.uForm.setRules(this.rules);
						});
					}
				})
			},

			
			// 获取商机数据详情
			getBusiness() {
				this.$u.get('crm.business.index/edit', {id: this.business_id}).then(res => {
					if(res.code == 1 ) {
						this.businessData = res.data
						this.customerName = res.data.customer.name
						this.selectList = res.data.product
						// 获取自定义字段
						this.getFields()
					}
				})
			},
			// 格式化时间
			timeFormats(val) {
				if(val) {
					return this.$u.timeFormat(val, 'yyyy-mm-dd hh:MM');
				} else {
					return '--'
				}
			},
			// 搜索
			onSearch() {
				clearTimeout(this.searchTimer)
				this.searchTimer = setTimeout(() => {
					this.page = 0
					this.lastPage = false
					this.getData()
				}, 500)
			},
			// 选择时间
			nextTimeChange(e){
				this.form.next_time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
				console.log(	this.form.next_time)
			},
			dealTimeChange(e){
				this.form.deal_time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
			},
			// 去选择产品
			selectProduct(){
				this.$u.route('pages/business/addBusiness/selectProduct');
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
					"searchField": "name",
					'orderBy[0][0]':"id",
					'orderBy[0][1]':"desc",
				}
				if(this.customer_id) {
					obj = {
						keyField: 'id',
						showField: 'name',
						"q_word": this.customer_id,
						"searchField": "id"
					}
				}
				this.$u.post('crm.customer.index/selectpage', obj).then(res => {
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
						// 默认选中
						if(this.customer_id){
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
					} else {
						item.checked = false
					}
				})
				this.form.customer_id = val.id
				this.customerName = val.name
				this.selectShow = false
			},
			// 产品数量增加
			valChange() {
				// 计算价格
				this.count_price()
			},
			// 优惠率改变
			inputChang(){
				// 计算价格
				this.count_price()
			},
			// 移除已选产品
			remove(i) {
				this.selectList.splice(i, 1)
				// 计算价格
				this.count_price()
			},
			// 计算价格
			count_price() {
				// 获取商品列表数据
				let list = this.selectList;
				// 声明一个变量接收数组列表price
				let total = 0;
				// 循环列表得到每个数据
				for (let i = 0; i < list.length; i++) {
					// 所有价格加起来 count_money
					total += list[i].nums * list[i].price;
				}
				// 优惠率计算
				if(!this.$u.test.isEmpty(this.form.discount_rate)) {
					console.log(this.form.discount_rate/100)
					total = total - (this.form.discount_rate/100) * total
				}
				// 最后赋值到data中渲染到页面
				this.form.total_price = total.toFixed(2)
				
			},
			// 修确认提交
			submit() {
				console.log(this.form)
				if(this.$u.test.isEmpty(this.form.customer_id)) {
					// 提示
					uni.showToast({
						title: '请选择客户',
						icon: 'none',
						duration: 2000
					})
					return
				}
				
				// 已选商品数据处理
				let parame = {}
				this.selectList.forEach((item,index)=>{
					var product_id= item.id;
					if(item.product_id != undefined) {
						product_id =item.product_id;
					}
					parame['product['+ index + '][product_id]'] = product_id
					parame['product['+ index + '][nums]']= item.nums
					parame['product['+ index + '][price]'] = item.price
					parame['product['+ index + '][subtotal]'] = item.nums * item.price
					parame['product['+ index + '][remarks]'] = item.remarks
				})
				// 合并对象
				Object.assign(parame, this.form)
				console.log(parame,'最终结果')
				this.$refs.uForm.validate(valid => {
					if (valid) {
						if(this.type == 'add') {
							this.$u.post('crm.business.index/add', parame).then((res) => {
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
								}
							})
						} else {
							parame.id = this.business_id
							this.$u.post('crm.business.index/edit', parame).then((res) => {
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
		padding: 22rpx 0;
		.text {
			font-size: 30rpx;
			font-weight: 700;
		}
	}
  .list {
		margin: 40rpx 15rpx 60rpx;
    .item {
			position: relative;
			border-radius: 15rpx;
			box-shadow: 1px 0px 5px rgba(50, 50, 50, 0.3);
			padding: 25rpx 35rpx;
      margin-bottom: 45rpx;
			.number {
				margin-bottom: 20rpx;
			}
			.content {
				margin-bottom: 20rpx;
				.price {
					width: 245rpx;
					font-size: 28rpx;
					color: #fa3534;
				}
			}
			.brief {
				margin: 10rpx 0;
				font-size: 28rpx;
				color: $u-tips-color;
			}
			.close {
				position: absolute;
				right: -8px;
    		top: -30rpx;
				/* border: 1px solid; */
				background-color: #fff;
				/* padding: 25rpx; */
				border-radius: 50%;
				height: 60rpx;
				width: 60rpx;
				display: flex;
				align-items: center;
				justify-content: center;
				box-shadow: 1px 0px 5px rgba(50, 50, 50, 0.3);
			}
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
		margin-bottom: 45rpx;
		.item {
			padding: 0 25rpx;
			justify-content: space-between;
			height: 45px;
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
