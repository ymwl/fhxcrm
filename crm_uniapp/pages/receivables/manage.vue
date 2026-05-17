<template>
	<view class="container">
		<view class="set-box" v-if="showForm">
			<u-form :model="form" ref="uForm" :rules="rules" :error-type="errorType">
				<u-form-item required label="回款编号:" label-width="160" prop="number">
					<u-input v-model="form.number" :border="border" />
				</u-form-item>
				<u-form-item required label="选择客户:" label-width="160" >
					<u-input type="select" :border="border" :select-open="selectShow" v-model="customerName" disabledColor="#f5f7fa" :disabled="type == 'edit'" placeholder="请选择客户" @click="type == 'add' ? selectShow = true : '' " />
				</u-form-item>
				<u-form-item required label="选择合同:" label-width="160" >
					<u-input type="select" :border="border" :select-open="contractShow" v-model="contractName" disabledColor="#f5f7fa" :disabled="type == 'edit'"  placeholder="请选择合同" @click="type == 'add' ? contractShow = true : '' " />
				</u-form-item>
				<u-form-item required label="收款方式:" label-width="160" >
					<u-radio-group v-model="form.pay_type" @change="payTypeChang">
						<u-radio v-for="(item, index) in payList" :key="index" :active-color="vuex_theme.color" :disabled="item.disabled" :name="item.id">
							{{ item.name }}
						</u-radio>
					</u-radio-group>
				</u-form-item>
				<u-form-item required label="回款金额:" label-width="160" prop="money">
					<u-input type='number' v-model="form.money" :border="border" />
				</u-form-item>
				<u-form-item required label="回款日期:" label-width="160" v-if="form.pay_type == 1">
					<u-input type="select" :border="border" :select-open="dateShow" v-model="form.return_time" placeholder="请选择回款日期" @click="dateShow = true" />
				</u-form-item>
				<u-form-item required label="回款方式:" label-width="160" v-if="form.pay_type == 1">
					<u-input type="select" :border="border" :select-open="accountShow" v-model="accountName" placeholder="请选择回款方式" @click="accountShow = true" />
				</u-form-item>
				<u-form-item label="备注 :"  label-width="160" prop="sort">
					<u-input @blur="textareaBlur" type="textarea" :value="form.remark"  :border="border" />
				</u-form-item>
				<u-form-item required label="审批人:"  label-width="160" prop="sort" v-if="form.pay_type == 1">
					<u-input type="select" :border="border" :select-open="adminShow" v-model="adminName" placeholder="请选择审批人" @click="type == 'add' ? (flowConfig.config == 0 ? adminShow = true : '' ) : adminShow = true " />
				</u-form-item>
				<!-- 自定义字段组件 -->
				<fa-fields :fields="fields" :form="form" v-model="form" :rules="rules" :labelPosition="labelPosition" :border="border"></fa-fields>
			</u-form>
			<view class="u-m-t-40" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确认</u-button>
			</view>
		</view>
		<u-modal v-model="show" :content="content"></u-modal>
		<!-- 选择日期 -->
		<u-calendar v-model="dateShow" :min-date="minDate" :max-date="maxDate" mode="date" @change="change"></u-calendar>
		<!-- 选择回款方式 -->
		<u-action-sheet :list="accountList" v-model="accountShow" @click="accountClick"></u-action-sheet>
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
		<!-- 选择合同弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="contractShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择合同</text> 
				<view class="" @click="contractShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search v-if="form.customer_id" margin="30rpx 20rpx" shape="square" v-model="contractkeyword" :show-action="false" :clearabled="true"  placeholder="输入合同名称搜索" @change="onContractSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="contractBottom">
				<view class="list">
					<block v-if="contractList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in contractList" :key="index" @click="onContract(item,index)">
								<view class="title">{{item.name}}</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="contractStatus" ></u-loadmore>
					</block>
					<u-empty :text="form.customer_id ? '暂无数据' : '请先选择客户' " v-else  margin-top="100" mode="list"></u-empty>
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
		<!-- 支付二维码弹窗组件 -->
		<popup-qr-code v-model="payShow" :payUrlData="payUrlData"></popup-qr-code>
	</view>
</template>

<script>
	import {getImgUrl} from '@/common/mUtils'
	import { formRule } from '@/common/fa.mixin.js'

	export default {
		mixins: [formRule],
		data() {
			return {
				payShow: false,
				id: '',
				labelPosition: 'left',
				border: true,
				contractkeyword: '',
				keyword: '',
				adminkeyword: '',
				selectShow:false,
				selected: false,
				selectList: [],
				customerName: '',
				accountName: '',
				contractName: '',
				adminName: '',
				page: 1,
				pageSize: 20, // 每一页多少条数据
				lastPage: false,
				listStatus: 'loadmore',
				contractPage: 1,
				lastContract: false,
				contractStatus: 'loadmore',
				adminPage: 1,
				lastAdmin: false,
				adminStatus: 'loadmore',
				customerList: [],
				contractList: [],
				adminList:[],
				accountList: [],
				type: '',
				coupon_id: '',
				customer_id: '',
				minDate: '',
				maxDate: '',
				dateShow: false,
				accountShow: false,
				contractShow: false,
				adminShow: false,
				show: false,
				content: '',
				showForm: false,
				showPicker: false,
				fields: [],
				receivablesData: {},
				time_field: '',
				params: {},
				form: {},
				timeText: '',
				errorType: ['message','toast'],
				rules: {},
				flowConfig: {
					config: 0
				},
				payList: [
					{
						id: 1,
						name: '默认方式',
						disabled: false,
					},
					{
						id: 2,
						name: '在线收款',
						disabled: false,
					},
				],
				payUrlData: {row:{number: ''}},
			};
		},
		onLoad(e) {
			this.onGetInit()
			// 获取配置
			this.getBaseConfig()
			// 默认回款时间
			this.form.return_time = this.$u.timeFormat(new Date(), 'yyyy-mm-dd hh:MM')
			if(e.id) {
				this.id = e.id
			}
			// 默认选中客户
			if(e.customer_id) {
				this.customer_id = e.customer_id
			}
			// 是否是编辑
			this.type = e.type
			if(this.type == "edit") {
				uni.setNavigationBarTitle({
					title: '编辑回款'
				});
				// 获取回款详情
				this.getReceivablesEdit()
			} else {
				// 加载客户
				this.getData()
				// 加载合同
				this.getContract()
				// 获取审批人
				this.onSelectpage()
			}
		},
		// 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
		onReady() {
			// this.$refs.uForm.setRules(this.rules);
		},
		methods: {
			// 基本设置
			onGetInit(id) {
				var payConfig = this.vuex_payConfig
				if (payConfig && Object.keys(payConfig).length > 0) {
					if(payConfig.online_pay == '0') {
						this.payList[1].disabled = true
					}
				} else {
					this.$u.get('login/config').then((res) => {
						if(res.code == 1){
							if(res.data.payConfig.online_pay == '0') {
								this.payList[1].disabled = true
							}
						}
					})
				}
			},
			// 收款方式改变
			payTypeChang(val){
				if(val == 2) {
					this.form.return_time = this.$u.timeFormat('', 'yyyy-mm-dd hh:MM')
					this.form.account_id = this.accountList[0].id
				} else {
					this.form.return_time = ''
					this.accountName = this.accountList[0].text
					this.form.account_id = this.accountList[0].id
				}
			},
			// 生成收款单
			onGetPayurl(id) {
				this.$u.get('crm.contract.receivables/payurl', {ids: id}).then((res) => {
					if(res.code == 1){
						this.payUrlData = res.data
						this.payShow = true
					}
				})
			},
			// 优化微信小程序input、textarea快速删除时光标会跳到最后 处理：改用 textarea 失去焦点触发修改
			textareaBlur(val) {
				this.form.remark = val
			},
			// 获取基本配置
			getBaseConfig() {
				this.$u.get('crm.common/baseConfig').then((res) => {
					if(res.code == 1){
						this.accountList = this.onJson(res.data.accountList)
						this.accountName = this.accountList[0].text
						this.form.account_id = this.accountList[0].id
						// 新增回款配置默认编号
						this.form.number = res.data.rprefix ? this.prefiex(res.data.rprefix) :  this.$u.timeFormat('','yyyymmddhhMMss')
						// 加载自定义字段
						if(this.type != "edit") {
							// 不是编辑情况下加载自定义字段
							this.getFields()
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
			// 获取回款配置
			getReceivablesAdd() {
				this.$u.get('crm.contract.receivables/receivablesadd').then(res => {
					if(res.code == 1 ) {
						this.flowConfig = res.data
						if(res.data.flow_admin_id && res.data.config == 1){
							// 获取审批人数据
							this.$u.get('crm.common/selectpage/model/admin/type/all', {
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
			// 搜索
			onSearch() {
				this.page = 0
				this.getData()
			},
			// 合同搜索
			onContractSearch() {
				this.contractPage = 0
				this.lastContract = false
				this.getContract()
			},
			// 审批人搜索
			adminSearch() {
				this.adminPage = 0
				this.onSelectpage()
			},
			// 获取回款详情
			getReceivablesEdit() {
				this.$u.get('crm.contract.receivables/edit', {
					id: this.id
				}).then(res => {
					if(res.code == 1 ) {
						this.receivablesData = res.data.row
						this.accountList.forEach((item,index)=>{
							if(this.receivablesData.account_id == item.id) {
								this.accountName = item.text
							}
						})
						// 加载自定义字段
						this.getFields()
						// 获取审批人数据
						if(this.receivablesData.flow_admin_id) {
							this.$u.get('crm.common/selectpage/model/admin/type/all', {
								keyField: 'id',
								keyValue: this.receivablesData.flow_admin_id,
								showField: 'realname',
							}).then(res => {
								if(res.code == 1 ) {
									this.selectList = res.data.list
									this.chosen()
									this.onSelectpage()
								}
							})
						}
						// 获取已选合同
						this.$u.get('crm.contract.index/selectpage', {
							keyField: 'id',
							showField: 'name',
							"q_word": this.receivablesData.contract_id,
							"searchField": "id"
						}).then(res => {
							if(res.code == 1 ) {
								this.contractName = res.data.list[0].name
							}
						})
						// 获取已选的客户
						this.defaultCustomer(this.receivablesData.customer_id)
					}
				})
			},
			// 默认选中已选客户
			defaultCustomer(id) {
				// 获取已选的客户
				this.$u.post('crm.customer.index/selectpage', {
					keyField: 'id',
					showField: 'name',
					"q_word": id,
					"searchField": "id",
					type:'all'
				}).then(res => {
					if(res.code == 1 ) {
						if(res.data.list.length > 0) {
							this.customerName = res.data.list[0].name
							this.form.customer_id = res.data.list[0].id
						}
					}
				})
			},

			// 获取自定义字段
			getFields() {
				let arr = []
				this.$u.get('fields/get_fields', {table: 'contract_receivables',id: ''}).then((res) => {
					if(res.code == 1){
						this.fields = res.data.fields;
						//渲染自定义字段,默认字段
						let custom_form = {
							number: this.id ? this.receivablesData.number : this.form.number,
							contract_id: this.id ? this.receivablesData.contract_id : '',
							account_id: this.id ? this.receivablesData.account_id : this.form.account_id,
							customer_id: this.id ? this.receivablesData.customer_id : '',
							flow_admin_id: this.id ? this.receivablesData.flow_admin_id : '',
							money: this.id ? this.receivablesData.money : '',
							remark: this.id ? this.receivablesData.remark : '',
							return_time: this.id ? this.$u.timeFormat(this.receivablesData.return_time, 'yyyy-mm-dd hh:MM') : '',
							pay_type: this.id ? (this.receivablesData.pay_type == 3 ? 1 : this.receivablesData.pay_type) : 1,
						};
						let rules = {
							number: [
								// 对first_consume字段进行必填验证
								{
									required: true,
									message: '请输入回款编号',
									trigger: ['change','blur']
								},
							],
							money: [
								{
									required: true,
									message: '请输入回款金额',
									trigger: ['change','blur']
								},
							]
						};
						this.fields.map(item => {
							// 编辑场景
							if(this.type == 'edit') {
								//表单赋值
								if (item.type == 'number') {
									custom_form[item.name] = this.receivablesData[item.name]
								} else {
									custom_form[item.name] = this.receivablesData[item.name]
									item.value = this.receivablesData[item.name] // 默认值为已有的数据
								}
								//单图赋值
								if (item.type == 'image') {
									if (this.receivablesData[item.name]) {
										item.value = [
											{
												url: getImgUrl(this.receivablesData[item.name])
											}
										];
									} else {
										item.value = [];
									}
								}
								//多图赋值
								if (item.type == 'images') {
									if (this.receivablesData[item.name]) {
										let images = this.receivablesData[item.name].split(',');
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
									item.value = this.receivablesData[item.name] ? [this.receivablesData[item.name]] : [];
								}
								//多文件
								if (item.type == 'files') {
									if (this.receivablesData[item.name]) {
										item.value = this.receivablesData[item.name].split(',');
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
									item.value = this.receivablesData[item.name]
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
						if(this.type == "add") {
							// 获取汇款审批流程
							this.getReceivablesAdd();
						}
						// 选中默认选择的客户
						if(this.customer_id){
							this.form.customer_id = this.customer_id
							// 查询当前客户的合同
							this.getContract()
						}
						this.rules = rules;
						this.showForm = true;
						//设置表单验证规则
						this.$nextTick(() => {
							this.$refs.uForm.setRules(this.rules);
						});
					}
				})
			},
			// 获取审批人
			onSelectpage(isNextPage,pages) {
				this.$u.get('crm.common/selectpage/model/admin/type/all', {
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.adminkeyword,
					keyField: 'id',
					showField: 'realname',
					"q_word": this.adminkeyword,
				}).then(res => {
					if(res.code == 1 ) {
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
				}
				
				const admin_info = uni.getStorageSync('admin_info');
				if (admin_info&&admin_info['receivables']) {
					obj.type='all';
				}
				if(this.customer_id) {
					obj.q_word= this.customer_id;
					obj.searchField= 'id';
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
			// 获取合同列表
			getContract(isNextPage,pages) {
				this.$u.get('crm.contract.index/selectpage', {
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					id: this.contractkeyword,
					keyField: 'id',
					showField: 'name',
					"q_word": this.contractkeyword,
					"searchField": "name",
					'custom[customer_id]': this.form.customer_id
				}).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastContract = true
						} 
						//不够一页
						if (res.data.list.length < this.pageSize) {
							this.contractStatus = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.contractList = this.contractList.concat(res.data.list)
							return 
						}
						this.contractList = res.data.list
					}
				})
			},
			// 合同列表加载更多
			contractBottom() {
				if(this.lastContract || this.contractStatus == 'loading') return ;
				this.contractStatus = 'loading'
				setTimeout(() => {
					if(this.lastContract) return ;
					this.getContract(true,++this.contractPage)
					if(this.contractList.length >= 10) this.contractStatus = 'loadmore';
					else this.contractStatus = 'loading';
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
				// 选择客户清除已选的合同信息
				this.form.contract_id = ''
				this.contractName = ''
				// 选完客户后 加载合同
				this.contractPage = 0
				this.lastContract = false
				this.getContract()
			},
			// 选择合同
			onContract(val,i) {
				this.contractList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.form.contract_id = val.id
				this.contractName = val.name
				this.contractShow = false
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
			// 选择回款时间变化
			change(e) {
				this.form.return_time = e.result
			},
			// 选择回款方式
			accountClick(index){
				this.accountName = this.accountList[index].text
				this.form.account_id = this.accountList[index].id
			},
			// 修确认提交
			submit() {
				this.$refs.uForm.validate(valid => {
					if (valid) {
						// 验证
						if(this.form.contract_id == '') {
							uni.showToast({
								title: '请选择合同',
								icon: 'none',
								duration: 2000
							})
							return
						}
						if(this.form.customer_id == '') {
							uni.showToast({
								title: '请选择客户',
								icon: 'none',
								duration: 2000
							})
							return
						}
						if(this.form.flow_admin_id == '' && this.form.pay_type == 1) {
							uni.showToast({
								title: '请选择审批人',
								icon: 'none',
								duration: 2000
							})
							return
						}
						// 添加和编辑操作
						if(this.type == 'add') {
							this.$u.post('crm.contract.receivables/receivablesadd', this.form).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: '添加成功',
										icon: 'success',
										duration: 2000
									})
									// 收款方式为线上收款
									if(this.form.pay_type == 2){
										this.onGetPayurl(res.data.receivables_id)
										return
									}
									// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
									//#ifdef MP-WEIXIN
									// flow_receivables 回款审批
										this.$reuse.subscriptionInfo('flow_receivables');
									//#endif
									setTimeout(() => {
										uni.navigateBack();
									}, 1000);
								}
							})
						} else {
							this.form.id = this.id
							this.$u.post('crm.contract.receivables/edit', this.form).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: '修改成功',
										icon: 'success',
										duration: 2000
									})
									// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
									//#ifdef MP-WEIXIN
									// flow_receivables 回款审批
										this.$reuse.subscriptionInfo('flow_receivables');
									//#endif
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
.container {
	height: 100%;
	width: 100%;
}
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
		text-align: right;
		padding: 28rpx 10rpx 45rpx;
	}
}
</style>
