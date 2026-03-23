<template>
	<view style="overflow:hidden">
		<view class="set-box" v-if="showForm">
			<block v-if="check_status == '2'">
				<u-alert-tips type="warning" title="已经审核通过的合同只能修改备注" description=" " ></u-alert-tips>
			</block>
			<u-form :model="form" ref="uForm" :error-type="errorType">
				<u-form-item required label="合同编号:" label-width="160" prop="number">
					<u-input v-model="form.number" :border="border" placeholder="请填写合同编号" />
				</u-form-item>
				<u-form-item required label="合同名称:" label-width="160" prop="name">
					<u-input v-model="form.name" :border="border" />
				</u-form-item>
				<u-form-item required label="选择客户:" label-width="160">
					<u-input type="select" :border="border" :select-open="selectShow" v-model="customerName" disabledColor="#f5f7fa" :disabled="type == 'edit'" placeholder="选择客户" @click="type == 'add' ? selectShow = true : ''" />
				</u-form-item>
				<u-form-item required label="选择商机:" label-width="160">
					<u-input type="select" :border="border" :select-open="businessShow" v-model="businessName" disabledColor="#f5f7fa" :disabled="type == 'edit'" placeholder="请选商机" @click="type == 'add' ? businessShow = true : ''" />
				</u-form-item>
				<u-form-item required label="合同金额:" label-width="160" prop="money">
					<u-input v-model="form.money" type="digit" :border="border" />
				</u-form-item>
				<u-form-item  label="下单时间:" label-width="160" >
					<u-input type="select" :border="border" :select-open="timeShow && timeText == 'order_time' " v-model="form.order_time" placeholder="选择下单时间" @click="selectTimeShow('order_time')" />
				</u-form-item>
				<u-form-item  label="开始时间:" label-width="160" >
					<u-input type="select" :border="border" :select-open="timeShow && timeText == 'start_time'" v-model="form.start_time" placeholder="选择合同开始时间" @click="selectTimeShow('start_time')" />
				</u-form-item>
				<u-form-item  label="结束时间:" label-width="160" >
					<u-input type="select" :border="border" :select-open="timeShow && timeText == 'end_time'" v-model="form.end_time" placeholder="选择合同结束时间" @click="selectTimeShow('end_time')" />
				</u-form-item>
				<u-form-item required label="客户签约人:" label-width="160" >
					<u-input type="select" :border="border" :select-open="contactsShow" v-model="contactsName" placeholder="选择联系人" @click="contactsShow = true" />
				</u-form-item>
				<u-form-item required label="公司签约人:" label-width="160">
					<u-input type="select" :border="border" :select-open="companyShow" v-model="companyName" placeholder="请选择" @click="companyShow = true" />
				</u-form-item>
				<u-form-item required label="审批人:" label-width="160" >
					<u-input type="select" :border="border" :select-open="adminShow" v-model="adminName" placeholder="请选择审批人" @click="type == 'add' ? (flowConfig.config == 0 ? adminShow = true : '' ) : adminShow = true " />
				</u-form-item>
				<u-form-item label="备注 :"  label-width="160">
					<u-input  type="textarea" @blur="textareaBlur" :value="form.remark"  :border="border" />
				</u-form-item>
				<!-- 自定义字段组件 -->
				<fa-fields :fields="fields" :form="form" :rules="rules" v-model="form" :labelPosition="labelPosition" :border="border"></fa-fields>
			</u-form>
			<!-- 产品 -->
			<view class="u-flex cif-title u-border-bottom" @click="onSelectProduct">
				<view class="u-flex-1 text">销售产品</view>
				<view class="" style="color:#2979ff">
					选择产品<u-icon name="arrow-right" color="#909399" size="30"></u-icon>
				</view>
			</view>
			<view class="list">
				<block v-if="selectProduct.length > 0">
					<view class="item" v-for="(item,index) in selectProduct" :key="index">
						<view class="number u-flex">
							<view class="u-flex-1">序号: {{index+1}}</view>
							<u-number-box :min="1" v-model="item.nums" :long-press="false" @change="valChange"></u-number-box>
						</view>
						<view class="content u-flex">
							<view class="u-flex-1 name">{{item.name ? item.name : (item.info ? item.info.name : '')}}</view>
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
						<view class="close" @click="removeProduct(index)">
							<u-icon name="close"  color="#909399" size="30"></u-icon>
							<!-- <van-icon name="close"  size="20px"/> -->
						</view>
					</view>
				</block>
				<u-empty text="暂未选择产品" v-else  margin-top="100" mode="list"></u-empty>
			</view>
			<view class="u-flex u-m-b-15 u-m-t-15">
				<view class="u-m-r-15" style="width:150rpx">优惠率：</view>
				<u-input class="u-flex-1" type="digit" v-model="form.discount_rate" :border="border" @input="inputChang"/>
			</view>
			<view class="u-flex u-m-b-15 u-m-t-15">
				<view class="u-m-r-15" style="width: 150rpx">产品总额：</view>
				<u-input class="u-flex-1" type="digit" v-model="form.total_price" :border="border" />
			</view>
			<view class="u-m-t-40" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定提交</u-button>
			</view>
		</view>
		<!-- 跟进时间选择 -->
		<u-picker v-model="timeShow" :hour="true" mode="time" :params="params" @confirm="timeChange"></u-picker>
		<!-- 日期选择 -->
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
			<u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入客户名称搜索" @change="onSearch(0)"></u-search>
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
		<!-- 选择商机弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="businessShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择商机</text> 
				<view class="" @click="businessShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="businesskeyword" :show-action="false" :clearabled="true"  placeholder="输入商机名称搜索" @change="onSearch(1)"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="businessBottom">
				<view class="list">
					<block v-if="businessList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in businessList" :key="index" @click="businessItem(item,index)">
								<view class="title">{{item.name}}</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="businessStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据，请先选择客户" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
		<!-- 选择联系人弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="contactsShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择联系人</text> 
				<view class="" @click="contactsShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<!-- <u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="onSearch"></u-search> -->
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="contactsBottom">
				<view class="list">
					<block v-if="contactsList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in contactsList" :key="index" @click="onContactsItem(item,index)">
								<view class="title">{{item.name}}</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="contactStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据,请先选择客户" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
		<!-- 选择公司签约人 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="companyShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择公司签约人</text> 
				<view class="" @click="companyShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="companykeyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="signSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="signBottom">
				<view class="list">
					<block v-if="companyList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in companyList" :key="index" @click="oncompany(item,index)">
								<view class="title">{{item.realname}}</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="signStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
		<!-- 选择审批人弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="adminShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择审批人</text> 
				<view class="" @click="adminShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="adminkeyword" :show-action="false" :clearabled="true"  placeholder="输入审批人名称搜索" @change="adminSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="adminBottom">
				<view class="list">
					<block v-if="adminList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in adminList" :key="index" @click="onAdmin(item,index)">
								<view class="title">{{item.realname}}</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="adminStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
			<view class="bottom_btn u-border-top">
				<u-button size="medium" @click="selected = true">查看已选</u-button> 
				<u-button class="u-m-l-15" type="primary"  @click="chosen" size="medium">选好了</u-button>
			</view>
		</u-popup>
		<!-- 已选审批人 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38" v-model="selected" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">已选审批人</text> 
				<view class="" @click="selected = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<scroll-view scroll-y style="height: 960rpx;width: 100%;" @scrolltolower="reachBottom">
				<view class="list">
					<block v-if="selectList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in selectList" :key="index">
								<view class="title u-p-r-15">{{item.realname}}</view>
								<view class="check-icon">
									<u-button size="mini" type="error" @click="remove(item,index)">删除</u-button>
								</view>
							</view>
						</view>
						<u-loadmore status="nomore" ></u-loadmore>
					</block>
					<u-empty text="暂无数据,请先选择审批人" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
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
				labelPosition: 'left',
				border: true,
				selectShow:false,
				businessShow: false,
				selected: false,
				timeShow: false,
				adminShow: false,
				contactsShow: false,
				companyShow: false,
				showPicker: false,
				calendarShow: false,
				cityShow: false,
				city_field: '',
				customerName: '',
				businessName: '',
				contactsName: '',
				adminName: '',
				companyName: '',
				keyword: '',
				businesskeyword: '',
				adminkeyword: '',
				companykeyword: '',
				page: 1,
				pageSize: 20, // 每一页多少条数据
				lastPage: false,
				listStatus: 'loadmore',
				businessPage: 1,
				lastBusiness: false,
				businessStatus: 'loadmore',
				adminPage: 1,
				lastAdmin: false,
				adminStatus: 'loadmore',
				signPage: 1,
				lastSign: false,
				signStatus: 'loadmore',
				cPage: 1,
				lastContact: false,
				contactStatus: 'loadmore',
				customerList: [],
				selectList: [],
				businessList: [],
				contactsList: [],
				selectProduct: [],
				adminList:[],
				companyList: [],
				type: '',
				contract_id: '',
				customer_id: '',
				minDate: '',
				maxDate: '',
				dateShow: false,
				show: false,
				content: '',
				check_status:'',
				fields: [],
				contractData: {},
				form: {},
				params: {
					year: true,
					month: true,
					day: true,
					hour: true,
					minute: true,
					second: false
				},
				timeText: '',
				errorType: ['message','toast'],
				rules: {},
				flowConfig: {
					config: 0
				}
			};
		},
		onLoad(e) {
			// 默认选中客户
			if(e.customer_id){
				this.customer_id = e.customer_id
			}
			if(e.id) {
				this.contract_id = e.id
				this.getContractEdit()
				this.getData()
				// this.getBusiness()
			} else {
				this.getData()
				// this.getBusiness()
				this.onSelectpage()
				this.getAllAdmin()
				// 获取自定义字段
				this.getFields()
			}
			this.type = e.type
			if(this.type == "edit") {
				uni.setNavigationBarTitle({
					title: '编辑合同'
				});
			}
			// 设定时间选择器最大时间和当前时间
			this.minDate = this.$u.timeFormat(new Date(), 'yyyy-mm-dd')
			this.maxDate = (parseInt(this.$u.timeFormat(new Date(), 'yyyy')) + 1) + this.$u.timeFormat(new Date(), '-mm-dd')
			
		},
		onShow() {
			if(!this.$u.test.isEmpty(this.vuex_selectProduct)) {
				// 已选产品数据处理
				this.vuex_selectProduct.forEach((item,index)=>{
					let filter = this.selectProduct.find(i => {
						return item.id == i.id
					})
					//  没有的，添加到 list 列表
					if(filter == undefined) {
						this.selectProduct.push(item)
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
		onUnload() {
			// 页面销毁清除已选商品数据
			this.$u.vuex('vuex_selectProduct', '')
		},
		methods: {
			// 优化微信小程序input、textarea快速删除时光标会跳到最后 处理：改用 textarea 失去焦点触发修改
			textareaBlur(val) {
				this.form.remark = val
			},
			// 获取配置字段
			getBaseConfig() {
				this.$u.api.getBaseConfig().then((res) => {
					if(res.code == 1){
						this.form.number = res.data.cprefix ? this.prefiex(res.data.cprefix) :  this.$u.timeFormat('','yyyymmddhhMMss')
					}
				})
			},
			// 获取合同审批配置
			getContractAdd(){
				this.$u.api.getContractAdd().then(res => {
					if(res.code == 1) {
						this.flowConfig = res.data
						if(res.data.flow_admin_id && res.data.config == 1){
							// 获取审批人数据
							this.$u.api.getAllAdmin({
								keyField: 'id',
								keyValue: res.data.flow_admin_id,
								showField: 'realname',
							}).then(res => {
								if(res.code == 1 ) {
									this.selectList = res.data.list
									this.chosen()
								}
							})
						}
					}
				})
			},
			// 获取合同详情
			getContractEdit() {
				this.$u.api.getContractEdit({
					id: this.contract_id
				}).then(res => {
					if(res.code == 1 ) {
						this.contractData = res.data.row
						// 数据赋值
						let data = res.data.row
						this.selectProduct = data.product
						this.check_status=data.check_status;
						// 获取自定义字段
						this.getFields()
						// 获取商机
						this.$u.api.onBusinessList({
							sort: 'id',
							order: 'desc',
							filter: JSON.stringify({id: this.contractData.business_id}),
							op: JSON.stringify({id: '='})
						}).then(res => {
							if(res.code == 1 ) {
								if(res.data.rows.length > 0) {
									this.businessName = res.data.rows[0].name
								}
							}
						})
						// 获取审批人数据
						this.$u.api.getAllAdmin({
							keyField: 'id',
							keyValue: this.contractData.flow_admin_id,
							showField: 'realname',
						}).then(res => {
							if(res.code == 1 ) {
								this.selectList = res.data.list
								this.chosen()
								this.onSelectpage()
							}
						})
						// 获取公司签约人数据
						this.$u.api.getAllAdmin({
							keyField: 'id',
							showField: 'realname',
							"searchField": "realname",
							keyValue: this.contractData.order_admin_id,
						}).then(res => {
							if(res.code == 1 ) {
								if(res.data.list.length > 0) {
									this.companyName = res.data.list[0].realname
								}
								this.getAllAdmin()
							}
						})
						
						if(this.contractData.customer_id){
							this.defaultCustomer(this.contractData.customer_id)
						}
						// 获取客户签约人
						if(this.contractData.contacts_id) {
							this.$u.api.getContactsList({
								sort: 'id',
								order: 'desc',
								filter: JSON.stringify({id: this.contractData.contacts_id }),
								op: JSON.stringify({id: '='})
							}).then(res => {
								if(res.code == 1 ) {
									this.contactsName = res.data.rows[0].name
								}
							})
						}
					}
				})
			},
			// 编辑情况下选中已选客户
			defaultCustomer(id) {
				console.log(id)
				// 获取已选的客户
				this.$u.api.getCustomerSelectpage({
					keyField: 'id',
					q_word: id,
					searchField: 'id',
					type: 'all',
				}).then(res => {
					if(res.code == 1 ) {
						if(res.data.list.length > 0) {
							this.customerName = res.data.list[0].name
							this.form.customer_id = res.data.list[0].id
						}
						// 查询联系人
						this.getContactsList()
					}
				})
			},
			// 获取自定义字段
			getFields() {
				let arr = []
				this.$u.api.getFields({table: 'contract',id: ''}).then((res) => {
					if(res.code == 1){
						this.fields = res.data.fields;
						//渲染自定义字段,默认字段
						let custom_form = {
							customer_id: this.contract_id ? this.contractData.customer_id : '',
							number: this.contract_id ? this.contractData.number : '',
							name: this.contract_id ? this.contractData.name : '',
							business_id: this.contract_id ? this.contractData.business_id : '',
							money: this.contract_id ? this.contractData.money : '',
							order_time: this.contract_id ? this.$u.timeFormat( this.contractData.order_time, 'yyyy-mm-dd hh:MM'): '',
							start_time: this.contract_id ? this.$u.timeFormat( this.contractData.start_time, 'yyyy-mm-dd hh:MM'): '',
							end_time: this.contract_id ? this.$u.timeFormat( this.contractData.end_time, 'yyyy-mm-dd hh:MM'): '',
							contacts_id: this.contract_id ? this.contractData.contacts_id : '',
							order_admin_id: this.contract_id ? this.contractData.order_admin_id : '',
							flow_admin_id: this.contract_id ? this.contractData.flow_admin_id : '',
							discount_rate: this.contract_id ? this.contractData.discount_rate : '',
							total_price: this.contract_id ? this.contractData.total_price : '',
							remark: this.contract_id ? this.contractData.remark : '',
						};
						let rules = {
							number: [
								// 对first_consume字段进行必填验证
								{
									required: true,
									message: '请输入合同编号',
									trigger: ['change','blur']
								},
							],
							name: [
								// 对first_consume字段进行必填验证
								{
									required: true,
									message: '请输入合同名字',
									trigger: ['change','blur']
								},
							],
							money: [
								{
									required: true,
									message: '请输入合同金额',
									trigger: ['change','blur']
								},
							],
						};
						this.fields.map(item => {
							// 编辑场景
							if(this.type == 'edit') {
								//表单赋值
								if (item.type == 'number') {
									custom_form[item.name] = this.contractData[item.name]
								} else {
									custom_form[item.name] = this.contractData[item.name]
									item.value = this.contractData[item.name] // 默认值为已有的数据
								}
								//单图赋值
								if (item.type == 'image') {
									if (this.contractData[item.name]) {
										item.value = [
											{
												url: getImgUrl(this.contractData[item.name])
											}
										];
									} else {
										item.value = [];
									}
								}
								//多图赋值
								if (item.type == 'images') {
									if (this.contractData[item.name]) {
										let images = this.contractData[item.name].split(',');
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
									item.value = this.contractData[item.name] ? [this.contractData[item.name]] : [];
								}
								//多文件
								if (item.type == 'files') {
									if (this.contractData[item.name]) {
										item.value = this.contractData[item.name].split(',');
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
									item.value = this.contractData[item.name]
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
						if(this.type == "add") {
							// 设置默认数据
							this.form.start_time = this.$u.timeFormat(new Date(), 'yyyy-mm-dd hh:MM')
							this.form.order_time = this.$u.timeFormat(new Date(), 'yyyy-mm-dd hh:MM')
							this.form.end_time = get_date(3) + ' ' +  this.$u.timeFormat(new Date(), 'hh:MM')
							// 获取审批流程配置
							this.getContractAdd()
							this.getBaseConfig()
						}
						// 选中默认客户
						if(this.customer_id) {
							this.form.customer_id = this.customer_id
							// 查询该客户的商机
							this.getBusiness()
							// 查询该客户的联系人
							this.getContactsList()
						}
						//设置表单验证规则
						this.$nextTick(() => {
							this.$refs.uForm.setRules(this.rules);
						});
					}
				})
			},

			//时间显示
			selectPicker(mode, field) {
				this.mode = mode;
				this.time_field = field;
				switch (mode) {
					case 'date':
						this.params = {
							year: true,
							month: true,
							day: true,
							hour: false,
							minute: false,
							second: false
						};
						break;
					case 'time':
						this.params = {
							year: false,
							month: false,
							day: false,
							hour: true,
							minute: true,
							second: true
						};
						break;
					case 'datetime':
						this.params = {
							year: true,
							month: true,
							day: true,
							hour: true,
							minute: true,
							second: true
						};
						break;
				}
				this.showPicker = true;
			},
			// 搜索
			onSearch(index) {
				switch (index) {
					case 0:
						this.pages = 0
						this.lastPage = false
						this.getData()
						break;
					case 1:
						this.businessPage = 0
						this.lastBusiness = false
						this.getBusiness()
						break;
					default:
						break;
				}

			},
			// 选择搜索
			adminSearch() {
				this.adminPage = 1
				this.lastAdmin = false
				this.onSelectpage()
			},
			// 公司签约人搜索
			signSearch() {
				this.signPage = 1
				this.lastSign = false
				this.getAllAdmin()
			},
			// 选择产品
			onSelectProduct() {
				this.$u.route('pages/business/addBusiness/selectProduct');
			},
			// 移除已选产品
			removeProduct(i) {
				this.selectProduct.splice(i, 1)
				// 计算价格
				this.count_price()
			},
			// 时间窗口
			selectTimeShow(text) {
				this.timeText = text
				this.timeShow = !this.timeShow
			},
			// 选择时间
			timeChange(e) {
				let time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
				switch (this.timeText) {
					case 'order_time':
						this.form.order_time = time
						break;
					case 'start_time':
						this.form.start_time = time
						break;
					case 'end_time':
						this.form.end_time = time
						break;
					default:
						break;
				}
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
				
				const admin_info = uni.getStorageSync('admin_info');
				if (admin_info&&admin_info['contract']) {
					obj.type='all';
				}
				if(this.customer_id) {
					obj.q_word= this.customer_id;
					obj.searchField= 'id';
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
						// 添加默认客户
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
					} else {
						item.checked = false
					}
				})
				this.form.customer_id = val.id
				this.customerName = val.name
				this.selectShow = false
				// 选择客户清除已选的联系人信息
				this.form.contacts_id =''
				this.contactsName = ''
				// 查询联系人
				this.getContactsList()
				// 选择客户清除已选的商机信息
				this.form.business_id = ''
				this.businessName = ''
				// 查询商机
				this.getBusiness()
			},

			// 获取客户联系人
			getContactsList(isNextPage,pages) {
				this.$u.api.getContactsList({
					sort: 'id',
					order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({scope: 4,customer_id: this.form.customer_id }),
					op: JSON.stringify({scope: '=',customer_id: '='})
				}).then(res => {
					if(res.code == 1 ) {
						// 不够一页
						if (res.data.rows.length < 10) {
							this.contactStatus = 'nomore'
						}
						// 最后一页
						if(res.data.rows.length == 0) {
							this.lastContact = true
						} 
						// 第二页开始
						if(isNextPage) {
							this.contactsList = this.contactsList.concat(res.data.rows)
							return 
						}
						this.contactsList = res.data.rows
					}
				})
			},
			// 联系人加载更多
			contactsBottom(){

			},
			// 选择联系人
			onContactsItem(val,i) {
				this.contactsList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.form.contacts_id = val.id
				this.contactsName = val.name
				this.contactsShow = false
			},
			// 获取商机列表
			getBusiness(isNextPage,pages) {
				this.$u.api.onBusinessList({
					sort: 'id',
					order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({name: this.businesskeyword,customer_id: this.form.customer_id }),
					op: JSON.stringify({name: 'LIKE',customer_id: '='})
				}).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.rows.length == 0) {
							this.lastBusiness = true
						} 
						//不够一页
						if (res.data.rows.length < this.pageSize) {
							this.businessStatus = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.businessList = this.businessList.concat(res.data.rows)
							return 
						}
						this.businessList = res.data.rows
					}
				})
			},
			// 滚动到底部加载更多
			businessBottom() {
				if(this.lastBusiness || this.businessStatus == 'loading') return ;
				this.businessStatus = 'loading'
				setTimeout(() => {
					if(this.lastBusiness) return ;
					this.getBusiness(true,++this.businessPage)
					if(this.businessList.length >= 10) this.businessStatus = 'loadmore';
					else this.businessStatus = 'loading';
				}, 1200)
			},
			// 选择商机
			businessItem(val,i) {
				this.businessList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.form.business_id = val.id
				this.businessName = val.name
				this.businessShow = false
				this.getBusinessEdit(val.id)
				
			},

			// 获取审批人
			onSelectpage(isNextPage,pages) {
				this.$u.api.getAllAdmin({
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.adminkeyword,
					keyField: 'id',
					showField: 'realname',
					"q_word": this.adminkeyword,
					"searchField": "realname"
				}).then(res => {
					if(res.code == 1 ) {
						// 已选审批人标记
						res.data.list.forEach((item,index)=>{
							this.selectList.forEach((i,index) => {
								if(i.id == item.id) {
									item.checked = true
								}
							})
						})
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
			// 获取签约人
			getAllAdmin(isNextPage,pages) {
				this.$u.api.getAllAdmin({
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.companykeyword,
					keyField: 'id',
					showField: 'realname',
					"q_word": this.companykeyword,
					"searchField": "realname"
				}).then(res => {
					if(res.code == 1 ) {
						if(this.form.order_admin_id){
							res.data.list.forEach((item,index) => {
								if(this.form.order_admin_id == item.id) {
									item.checked = true
								} else {
									item.checked = false
								}
							})
						}
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastSign = true
						} 
						//不够一页
						if (res.data.list.length < this.pageSize) {
							this.signStatus = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.companyList = this.companyList.concat(res.data.list)
							return 
						}
						this.companyList = res.data.list
						
					}
				})
			},
			// 滚动到底部加载更多
			signBottom() {
				if(this.lastSign || this.signStatus == 'loading') return ;
				this.signStatus = 'loading'
				setTimeout(() => {
					if(this.lastSign) return ;
					this.getAllAdmin(true,++this.signPage)
					if(this.companyList.length >= 10) this.signStatus = 'loadmore';
					else this.signStatus = 'loading';
				}, 1200)
			},
			// 选着公司签约人
			oncompany(val,index) {
				this.companyList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.form.order_admin_id = val.id
				this.companyName = val.realname
				this.companyShow = false
			},
			// 选择审批人
			onAdmin(val,i) {
				this.adminList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = item.checked ? !item.checked : true
					}
				})
				// 添加已选
				if(this.adminList[i].checked == true) {
					// 添加已选中
					this.selectList.push(this.adminList[i])
				} else {
					// 删除取消选中
					this.selectList.forEach((item,i) => {
						if(item.id == val.id) {
							this.selectList.splice(i, 1)
						}
					})
				}
			},
			//移除审批人
			remove(val,index) {
				this.adminList.forEach((i,index) => {
					if(val.id == i.id) {
						i.checked = false
					}
				})
				this.selectList.splice(index, 1)
				// 删除完，关闭已选弹窗
				if(this.selectList.length == 0) {
					this.selected = false
				}
			},
			// 审批人选好了
			chosen() {
				let id = []
				let name = []
				this.selectList.forEach((item,index) => {
					id.push(item.id)
					name.push(item.realname)
				});
				this.form.flow_admin_id = id.join(',')
				this.adminName = name.join(',')
				this.adminShow = false
			},
			// 获取商机数据详情
			getBusinessEdit(id) {
				this.$u.api.getBusinessEdit({id: id}).then(res => {
					if(res.code == 1 ) {
						this.selectProduct = res.data.product
						// 计算价格
						this.count_price()
					}
				})
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
			// 计算价格
			count_price() {
				// 获取商品列表数据
				let list = this.selectProduct;
				// 声明一个变量接收数组列表price
				let total = 0;
				// 循环列表得到每个数据
				for (let i = 0; i < list.length; i++) {
					// 所有价格加起来 count_money
					total += list[i].nums * list[i].price;
				}
				// 优惠率计算
				if(!this.$u.test.isEmpty(this.form.discount_rate)) {
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
				let param = {}
				this.selectProduct.forEach((item,index)=>{
					var product_id= item.id;
					if(item.product_id != undefined) {
						product_id =item.product_id;
					}
					param['product['+ index + '][product_id]'] = product_id
					
					param['product['+ index + '][name]'] =  !this.$u.test.isEmpty(item.name) ? item.name : (!this.$u.test.isEmpty(item.info) ? item.info.name : '')
					param['product['+ index + '][nums]']= item.nums
					param['product['+ index + '][price]'] = item.price
					param['product['+ index + '][sku]']= !this.$u.test.isEmpty(item.sku) ? item.sku : (!this.$u.test.isEmpty(item.info) ? item.info.sku : '')
					param['product['+ index + '][specification]'] = !this.$u.test.isEmpty(item.specification) ? item.specification : (!this.$u.test.isEmpty(item.info) ? item.info.specification : '')
					param['product['+ index + '][subtotal]'] = item.nums * item.price
					param['product['+ index + '][remarks]'] = item.remarks
				})
				// 合并对象
				Object.assign(param, this.form)
				console.log(param)
				this.$refs.uForm.validate(valid => {
					if (valid) {
						if(this.type == 'add') {
							this.$u.api.onContractAdd(param).then((res) => {
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
							param.id = this.contractData.id
							this.$u.api.onContractEdit(param).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: '编辑成功',
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
			// 选择时间
			onSelect(){
				console.log('ss')
				this.dateShow = !this.dateShow
			},
			// 选择时间变化
			change(e) {
				console.log(e,'46');
				this.form.start_time = e.startDate + ' ' +  this.$u.timeFormat(new Date(), 'hh:MM')
				this.form.end_time = e.endDate + ' ' +  this.$u.timeFormat(new Date(), 'hh:MM')
				this.timeText = 	this.form.start_time + '~' + this.form.end_time
			},
			//自动生成编号
			prefiex(cprefix){
				 var vNow = new Date(),sNow='';
				sNow=cprefix.replace('{:Y}', vNow.getFullYear());
				sNow=sNow.replace('{:m}', this.PrefixZero(vNow.getMonth() + 1, 2));
				sNow=sNow.replace('{:d}', this.PrefixZero(vNow.getDate(),2));
				sNow=sNow.replace('{:h}', this.PrefixZero(vNow.getHours(),2));
				sNow=sNow.replace('{:i}', vNow.getMinutes());
				sNow=sNow.replace('{:s}', vNow.getSeconds());
				sNow=sNow.replace('{:rand}', this.PrefixZero(vNow.getMilliseconds(),6));
				return sNow;
			},
			PrefixZero(num, n) {
				return (Array(n).join(0) + num).slice(-n);
			}
		
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
</style>
