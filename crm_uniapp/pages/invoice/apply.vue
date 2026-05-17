<template>
	<view class="container">
		<view class="set-box" v-if="showForm">
			<u-alert-tips v-if="invoiceStatus == 2" type="warning" title="已经审核通过的只能修改备注" :show-icon="true"></u-alert-tips>
			<u-form :model="form" ref="uForm" :rules="rules" :error-type="errorType" >
        <u-form-item required label="选择合同:" label-width="160" label-position="top">
					<u-input type="select" :border="border" :select-open="contractShow" v-model="contractName" disabledColor="#f5f7fa" :disabled="type == 'edit'"  placeholder="请选择合同" @click="type == 'edit' ? '' : contractShow = true" />
				</u-form-item>
				<u-form-item required label="开票主体:" label-width="160" label-position="top" prop="invoice_body">
					<u-input v-model="form.invoice_body" :border="border" />
				</u-form-item>
        <u-form-item required label="发票抬头:" label-width="160" label-position="top" prop="invoice_name">
					<u-input v-model="form.invoice_name" :border="border" />
				</u-form-item>
        <u-form-item required label="抬头类型:" label-width="160" label-position="top">
					<u-radio-group v-model="form.invoice_issue">
						<u-radio v-for="(item, index) in typeList" :key="index" :active-color="vuex_theme.color" :disabled="item.disabled" :name="item.id">
							{{ item.name }}
						</u-radio>
					</u-radio-group>
				</u-form-item>
        <u-form-item required label="发票类型:" label-width="160" label-position="top">
					<u-radio-group v-model="form.invoice_type">
						<u-radio v-for="(item, index) in typeList2" :key="index" :active-color="vuex_theme.color" :disabled="item.disabled" :name="item.id">
							{{ item.name }}
						</u-radio>
					</u-radio-group>
				</u-form-item>
				<!-- 抬头不是个人的需要 -->
				<block v-if="form.invoice_issue != 2">
					<u-form-item label="统一社会信用代码:" label-width="160" label-position="top">
						<u-input v-model="form.register_no" :border="border" />
					</u-form-item>
					<u-form-item label="开户行名称:" label-width="160" label-position="top">
						<u-input v-model="form.bank_name" :border="border" />
					</u-form-item>
					<u-form-item label="开户账号:" label-width="160" label-position="top">
						<u-input v-model="form.bank_no" :border="border" />
					</u-form-item>
				</block>
        <u-form-item required label="开票金额:" label-width="160" label-position="top" prop="money">
					<view style="width:100%">
						<u-input type='number' v-model="form.money" :border="border" />
						<view v-if="contractData" style="color: #909399;">合同金额：{{contractData.money}},已收款：{{contractData.return_money}},已开票：{{contractData.invoice_money}}</view>
					</view>
				</u-form-item>
        <u-form-item label="开票税率(%):" label-width="160" label-position="top">
					<u-input type='number' v-model="form.tax_rate" :border="border" />
				</u-form-item>
        <u-form-item label="开票内容:" label-width="160" label-position="top">
					<u-input v-model="form.content" :border="border" />
				</u-form-item>
				<u-form-item v-if="invoiceStatus != 0" label="发票附件:" label-width="160" label-position="top">
					<fa-upload-image v-model="form.files" imgType="many" :file-list="filesList"></fa-upload-image>
				</u-form-item>
        <u-form-item label="接收邮箱:" label-width="160" label-position="top" prop="email">
					<u-input v-model="form.email" :border="border" placeholder="可空，输入邮箱完成开票时，会把附件自动发送" />
				</u-form-item>
        <u-form-item label="收件人:" label-width="160" label-position="top">
					<u-input v-model="form.user_name" :border="border" placeholder="如需要邮寄请填写" />
				</u-form-item>
        <u-form-item label="手机:" label-width="160" label-position="top" prop="user_phone">
					<u-input v-model="form.user_phone" :border="border" placeholder="如需要邮寄请填写" />
				</u-form-item>
        <u-form-item label="邮寄地址:" label-width="160" label-position="top">
					<u-input v-model="form.user_address" :border="border" placeholder="如需要邮寄请填写" />
				</u-form-item>
				<u-form-item label="备注 :"  label-width="160" prop="sort" label-position="top">
					<u-input @blur="textareaBlur" type="textarea" :value="form.remarks"  :border="border" />
				</u-form-item>
				<u-form-item required label="审批人:"  label-width="160" prop="sort" label-position="top">
					<u-input :disabled="true" type="select" :border="border" disabledColor="#f5f7fa" :select-open="adminShow" v-model="adminName" placeholder="请选择审批人" />
				</u-form-item>
			</u-form>
			<view class="u-m-t-40" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">{{type == 'add'? '确认申请' : '确认修改'}}</u-button>
			</view>
		</view>
		<u-modal v-model="show" :content="content"></u-modal>
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
			<u-search margin="30rpx 20rpx" shape="square" v-model="contractkeyword" :show-action="false" :clearabled="true"  placeholder="输入合同名称搜索" @change="onContractSearch"></u-search>
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
	import { formRule } from '@/common/fa.mixin.js'
	export default {
		mixins: [formRule],
		data() {
			return {
				searchTimer: null,
				setting: {},
				contractData: '',
				id: '',
				customer_id: '',
				border: true,
				contractkeyword: '',
				adminkeyword: '',
				selectShow:false,
				selected: false,
				selectList: [],
				customerName: '',
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
				type: '',
				coupon_id: '',
				contractShow: false,
				adminShow: false,
				show: false,
				content: '',
				showForm: true,
				time_field: '',
				params: {},
				form: {
					contract_id: '',
					invoice_body: '',
					invoice_name: '',
					invoice_issue: 1,
					invoice_type: 1,
					register_no: '',
					bank_name: '',
					bank_no: '',
					money: '',
					tax_rate: '',
					content: '',
					files: '',
					email: '',
					user_name: '',
					user_phone: '',
					user_address: '',
					remarks: '',
					flow_admin_id: '',
				},
				filesList: [],
				invoiceStatus: 0,
				errorType: ['message','toast'],
				rules: {
					invoice_body: [
						{ 
							required: true, 
							message: '请输入开票主体', 
							trigger: ['change','blur'],
						}
					],
					invoice_name: [
						{ 
							required: true, 
							message: '请输入发票抬头', 
							trigger: ['change','blur'],
						}
					],
					money: [
						{ 
							required: true, 
							message: '请输入开票金额', 
							trigger: ['change','blur'],
						}
					],
					user_phone: [
						{
							validator: (rule, value, callback) => {
								if(value == '') {
									return true
								} else {
									return this.$u.test.mobile(value);
								}
							},
							message: '请填写正确手机号码',
							trigger: ['change', 'blur']
						}
					],
					email: [
						{
							validator: (rule, value, callback) => {
								if(value == '') {
									return true
								} else {
									return this.$u.test.email(value);
								}
							},
							message: '请填写正确邮箱',
							trigger: ['change', 'blur']
						}
					]
				},
				flowConfig: {
					config: 0
				},
				typeList: [
					{
						id: 1,
						name: '企业',
						disabled: false,
					},
					{
						id: 2,
						name: '个人',
						disabled: false,
					},
          {
						id: 3,
						name: '事业单位',
						disabled: false,
					},
				],
        typeList2: [
					{
						id: 1,
						name: '增值税普票',
						disabled: false,
					},
					{
						id: 2,
						name: '增值税专票',
						disabled: false,
					},
				],
			};
		},
		onLoad(e) {
			if(e.id) {
				this.id = e.id
			}
			if(e.customer_id) {
				this.customer_id = e.customer_id
			}
			// 是否是编辑
			this.type = e.type
			if(this.type == "edit") {
				uni.setNavigationBarTitle({
					title: '编辑发票'
				});
				// 获取发票详情
				this.getInvoiceEdit()
			} else {
				// 获取配置
				this.getInvoiceAdd()
				// 加载合同
				this.getContract()
				// // 获取审批人
				// this.onSelectpage()
			}
		},
		watch:{
			// 此处监听form.invoice_issue变量，当期有变化时执行
			'form.invoice_issue'(item1,item2){
				// item1为新值，item2为旧值
				if(item1 == 2) {
					this.typeList2[1].disabled = true
					this.form.invoice_type = 1
				} else {
					this.typeList2[1].disabled = false
				}
			},
			'form.invoice_type'(item1,item2) {
				let arr = [this.setting.tax_rate1,this.setting.tax_rate2]
				console.log(arr.indexOf(this.form.tax_rate))
				if(arr.indexOf(this.form.tax_rate) == -1) return // 如果 tax_rate 手动修改过就不默认系统配置的
				if(item1 == 2) {
					let arr = []
					arr.push(this.setting.tax_rate2)
					arr.push(this.setting.tax_rate1)
					this.form.tax_rate = this.setting.tax_rate2
				} else {
					this.form.tax_rate = this.setting.tax_rate1
				}
			}
		},
		// 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
		onReady() {
			this.$refs.uForm.setRules(this.rules);
		},
		methods: {
			// 优化微信小程序input、textarea快速删除时光标会跳到最后 处理：改用 textarea 失去焦点触发修改
			textareaBlur(val) {
				this.form.remarks = val
			},
			// 获取发票配置
			getInvoiceAdd() {
				this.$u.get('crm.invoice/add').then((res) => {
					if(res.code == 1){
						this.setting = res.data.setting
						this.form.tax_rate = res.data.setting.tax_rate1
						this.form.invoice_body = res.data.setting.invoice_body
						// 获取审批人数据
						if(res.data.flow.flow_admin_id) {
							this.$u.get('crm.common/selectpage/model/admin/type/all', {
								keyField: 'id',
								keyValue: res.data.flow.flow_admin_id,
								showField: 'realname',
							}).then(res => {
								if(res.code == 1 ) {
									this.selectList = res.data.list
									this.chosen()
									this.onSelectpage()
								}
							})
						}
					}
				})
			},
			// 合同搜索
			onContractSearch() {
				clearTimeout(this.searchTimer)
				this.searchTimer = setTimeout(() => {
					this.contractPage = 0
					this.lastContract = false
					this.getContract()
				}, 500)
			},
			// 审批人搜索
			adminSearch() {
				clearTimeout(this.searchTimer)
				this.searchTimer = setTimeout(() => {
					this.adminPage = 0
					this.onSelectpage()
				}, 500)
			},
			// 获取发票详情
			getInvoiceEdit() {
				this.$u.get('crm.invoice/edit', {
					ids: this.id
				}).then(res => {
					if(res.code == 1 ) {
						this.invoiceStatus = res.data.row.check_status
						let infoData = res.data.row
						for (const key in this.form) {
							if (Object.hasOwnProperty.call(this.form, key)) {
								if(infoData[key]){
									if(key == 'files') {
										this.form[key] = infoData[key].join(',')
										infoData[key].forEach(it => {
											this.filesList.push({
												url: it
											});
										});
									} else {
										this.form[key] = infoData[key]
									}
								}
							}
						}
						console.log(this.form)
						// 获取审批人数据
						if(infoData.flow_admin_id) {
							this.$u.get('crm.common/selectpage/model/admin/type/all', {
								keyField: 'id',
								keyValue: infoData.flow_admin_id,
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
							keyValue: infoData.contract_id,
						}).then(res => {
							if(res.code == 1 ) {
								this.contractName = res.data.list[0].name
							}
						})
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
			// 获取合同列表
			getContract(isNextPage,pages) {
				let params = {
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					id: this.contractkeyword,
					keyField: 'id',
					showField: 'name',
					"q_word": this.contractkeyword,
					"searchField": "name",
				}
				// 客户详情跳转过来只筛选对应客户的合同
				if(this.customer_id != ''){
					params['custom[customer_id]'] = this.customer_id
				}
				this.$u.get('crm.contract.index/selectpage', params).then(res => {
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
				// 获取填写输入记录
				this.getInvoiceHistory(val)
			},
			// 获取发票历史记录，自动填写
			getInvoiceHistory(val) {
					this.$u.get('crm.invoice/history', {customer_id: val.customer_id,contract_id: val.id}).then(res => {
					if(res.code == 1 ) {
						this.contractData = res.data.contract
						// 赋值
						let arr = ['bank_name','bank_no','content','email','invoice_body','invoice_name','register_no','remarks','user_address','user_name','user_phone','invoice_issue','invoice_type',]
						let infoData = res.data.invoice
						for (const key in this.form) {
							if (Object.hasOwnProperty.call(this.form, key)) {
								if(	arr.indexOf(key) != -1){
									this.form[key] = infoData[key]
								}
							}
						}
					}
				})
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
			// 修确认提交
			submit() {
				// 抬头类型是个人部分参数数据清空
				if(this.form.invoice_issue == 2) {
					this.form.register_no = ''
					this.form.bank_name = ''
					this.form.bank_no = ''
				}
				// 参数转化
				let params = {}
				for (const key in this.form) {
					if (Object.hasOwnProperty.call(this.form, key)) {
						params['row[' + key + ']'] = this.form[key]
					}
				}
				console.log(params)
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
							this.$u.post('crm.invoice/add', params).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: '申请成功',
										icon: 'success',
										duration: 2000
									})
									// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
									//#ifdef MP-WEIXIN
									// flow_invoice 发票审批
										this.$reuse.subscriptionInfo('flow_invoice');
									//#endif
									setTimeout(() => {
										uni.navigateBack();
									}, 1000);
								}
							})
						} else {
							params.ids = this.id
							this.$u.post('crm.invoice/edit', params).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: '修改成功',
										icon: 'success',
										duration: 2000
									})
									// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
									//#ifdef MP-WEIXIN
									// flow_invoice 发票审批
										this.$reuse.subscriptionInfo('flow_invoice');
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
