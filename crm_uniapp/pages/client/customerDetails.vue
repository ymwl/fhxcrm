<template>
	<view class="container">
		<view class="bare" :style="{backgroundColor: vuex_theme.color}">
			<!-- 顶部导航高度 -->
			<view class="statusBar"></view>
		</view>
		<view class="notice">
			<view class="belong">
				<view class="left u-flex-1">
					<view class="store">{{customer.name}}</view>
				</view>
				<view :style="{color: customer.issuccess == 1 ? '#19be6b' :vuex_theme.color}">{{customer.issuccess | changeStatus}}<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon>
				</view>
			</view>
			<!-- 标签 -->
			<view class="tap" v-if="customer.tags !='' ">
				<u-tag :text="item"  size="min"  v-for="(item,i) in customer.tags" :key="i" />
			</view>
			<view class="relation u-flex">
				<view class="left">手机：<text class="dial" @click="call(customer.mobile)">{{customer.mobile}}</text></view>
				<view class="right u-flex">
					<u-icon name="yunhu" custom-prefix="custom-icon" size="40"></u-icon>
					<text class="dial" @click="cloudcall(customer.mobile)">云呼叫</text>
				</view>
			</view>
			<view class="bottom u-flex">
				<view class="client_time">最后跟进：{{timeFormats(customer.last_up_time)}}</view>
				<view class="right">{{customer.owner_user ? customer.owner_user.realname : ''}}</view>
			</view>
			<view class="more u-flex" @click="onLookMore">查看更多<u-icon name="arrow-down" size="35" ></u-icon></view>
		</view>
		<!-- 工具项 -->
		<view class="region u-m-t-35">
			<view class="u-border-bottom">
				<u-tabs :list="tapList" bar-width="80" :active-color="vuex_theme.color" :is-scroll="false" :current="current" @change="change"></u-tabs>
			</view>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop"  @scrolltolower="reachBottom">
				<view class="page-box">
					<!-- 跟进记录 -->
					<block v-if="current == 0" >
						<block v-if="recordList.length > 0">
							<view class="list-view" v-for="(item, index) in recordList" :key="index">
								<view class="top">
									<u-parse class="left" :html="item.content"></u-parse>
									<!-- <view class="left">{{item.content ? item.content : '--'}}</view> -->
								</view>
								<view class="item" >
									<view class="left">
										<view class="image" v-for="(i,x) in item.files" :key="x">
											<u-image width="100%" height="100%" :src="i.url" @click="lookImges(i.url)"></u-image>
										</view>
									</view>
									<view class="content">
										<!-- <view class="title u-line-2">{{ item.cproduct.product.name }}</view>
										<view class="type">产品编号: {{ item.product_no }}</view>
										<view class="type">上传人: {{ item.engineer.name }}</view> -->
									</view>
								</view>
								<view class="bottom">
									<view class="time">{{timeFormats(item.create_time)}}</view>
									<view class="way">{{item.record_type_text ? item.record_type_text : '--'}}</view>
								</view>
							</view>
							<u-loadmore :status="listStatus" ></u-loadmore>
						</block>
						<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
					</block>
					<!-- 联系列表 -->
					<block v-if="current == 1">
						<block v-if="contactList.length > 0">
							<view class="list-view" v-for="(item,index) in contactList" :key="index" @click="lookDetails(item)">
								<view class="contact">
									<view class="name">{{item.name}}（{{item.sex | sexType}}）{{item.mobile}}</view>
									<view class="dial">查看</view>
								</view>
							</view>
							<u-loadmore :status="contactStatus" ></u-loadmore>
						</block>
						<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
					</block>
					<!-- 商机 -->
					<block v-if="current == 2">
						<block v-if="businessList.length > 0">
							<view class="list-view" v-for="(item,index) in businessList" :key="index" @click="lookDetails(item)">
								<view class="top">
									<view class="left u-flex-1">{{item.name}}</view>
									<view class="right u-flex" :style="{color: vuex_theme.color}">{{item.is_end | is_endStatus}}<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon></view>
								</view>
								<view class="item" >
									<!-- <view class="left">
										<image :src="items.fitting.product.image " mode="aspectFill" />
									</view> -->
									<view class="content">
										<view class="title u-line-2" style="color: #FF0000;">
											<text>￥{{item.money}}</text>
										</view>
										<view class="type">备注: {{item.remark}}</view>
									</view>
								</view>
								<view class="bottom">
									<view class="time">预计成交：{{timeFormats(item.deal_time)}}</view>
									<view class="btn entity" :style="{backgroundColor: vuex_theme.color}" @click.stop="follow(item.id)">跟进</view>
								</view>
							</view>
							<u-loadmore :status="businessStatus" ></u-loadmore>
						</block>
						<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
					</block>
					<!-- 合同 -->
					<block v-if="current == 3">
						<block v-if="htList.length > 0">
							<view class="list-view" v-for="(item,index) in htList" :key="index" @click="lookDetails(item)">
								<view class="top">
									<view class="left u-flex-1">{{item.name}}</view>
									<view class="right u-flex" :style="{color: vuex_theme.color}">{{item.check_status | checkStatus}}<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon></view>
								</view>
								<view class="item" >
									<!-- <view class="left">
										<image :src="items.fitting.product.image " mode="aspectFill" />
									</view> -->
									<view class="content">
										<view class="title u-line-2" style="color: #FF0000;">
											<text class="u-p-r-45">金额：￥{{item.money}}</text>
											<text>回款：￥{{item.return_money}}</text>
										</view>
										<view class="u-flex">
											<view class="type u-flex-1 u-m-r-20">备注: {{item.remark ? item.remark : '--'}}</view>
											<view class="u-flex" v-if="renewShow(item)" @click.stop="onGetRenewPayurl(item.id)">
												<u-icon class="u-p-r-1" name="renew" custom-prefix="custom-icon" :size="46" :color="vuex_theme.color"></u-icon>
												续费码
											</view>
										</view>
									</view>
								</view>
								<view class="bottom">
									<view class="time">始止时间：{{ timeFormats(item.start_time) + '~' + timeFormats(item.end_time)}}</view>
									<!-- <view class="btn entity" :style="{backgroundColor: vuex_theme.color}">删除</view> -->
								</view>
							</view>
							<u-loadmore :status="listStatusHt" ></u-loadmore>
							</block>
						<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
					</block>
					<!-- 回款 -->
					<block v-if="current == 4">
						<block v-if="hkList.length > 0">
							<view class="list-view" v-for="(item,index) in hkList" :key="index" @click="lookDetails(item)">
								<view class="top">
									<view class="left u-flex-1">{{item.number}}</view>
									<view class="right u-flex" :style="{color: vuex_theme.color}">{{item.check_status | checkStatus}}<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon></view>
								</view>
								<view class="item" >
									<!-- <view class="left">
										<image :src="items.fitting.product.image " mode="aspectFill" />
									</view> -->
									<view class="content u-flex">
										<view class="type u-flex-1 u-m-r-20">备注: {{item.remark ? item.remark : '--'}}</view>
										<view class="u-flex" v-if="item.pay_type == 2 && payConfig.renew_pay == '1' && item.pay_status == '0'" @click.stop="onGetPayurl(item.id)">
											<u-icon class="u-p-r-1" name="renew" custom-prefix="custom-icon" :size="46" :color="vuex_theme.color"></u-icon>
											收款码
										</view>
									</view>
								</view>
								<view class="bottom">
									<view class="" style="color: #FF0000;">￥{{item.money}}</view>
									<view class="time">提交日期：{{timeFormats(item.create_time)}}</view>
								</view>
							</view>
							<u-loadmore :status="listStatusHk" ></u-loadmore>
						</block>
						<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
					</block>
				</view>
			</scroll-view>
		</view>
		<!-- 上拉菜单 -->
		<u-action-sheet :list="moreList" v-model="moreShow" @click="moreClick"></u-action-sheet>
		<!-- 转移客户选择目标员工组件 -->
		<staff-list ref="adminList" @onShift="onConfirm"></staff-list>
		<!-- 底部按钮 -->
		<view class="bottom-btn u-border-top" >
			<u-button class="u-m-r-15" size="medium" @click="moreShow = true">更多</u-button>
			<view class="btn sky u-m-r-15" @click="onSign">签到</view>
			<view class="btn entity" :style="{backgroundColor: vuex_theme.color}" @click="onAdd">跟进</view>
		</view>
		<!-- 支付二维码弹窗组件 -->
		<popup-qr-code v-model="payShow" :payUrlData="payUrlData"></popup-qr-code>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				payShow: false,
				moreShow: false,
				customer_id: '',
				customer: {},
				scrollTop: '',
				tapList: [
					{
						name: '跟进'
					},
					{
						name: '联系人'
					},
					{
						name: '商机',
					},
					{
						name: '合同',
					},
					{
						name: '回款',
					}
				],
				moreList: [
					{
						text: '编辑客户',
					},
					{
						text: '放入公海',
					},
					{
						text: '转移客户',
					},
					{
						text: '删除客户',
					},
					{
						text: '添加商机',
					},
					{
						text: '添加合同',
					},
					{
						text: '添加回款',
					},
					{
						text: '添加联系',
					},
					{
						text: '发邮件',
					},
					{
						text: '发信息',
					},
					{
						text: '申请发票',
					},
				],
				recordList:[],
				contactList: [],
				businessList: [],
				htList:[],
				hkList: [],
				current: 0,
				navbar: false,
				background: {
					backgroundColor: '#FE644A'
				},
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 0,
				pageSize: 20,
				lastPage: false,
				listStatus: 'loadmore',
				cPage: 0,
				lastContact: false,
				contactStatus: 'loadmore',
				Bpage: 0,
				lastBusiness: false,
				businessStatus: 'loadmore',
				pageHt: 0,
				lastHt: false,
				listStatusHt: 'loadmore',
				pageHk: 0,
				lastHk: false,
				listStatusHk: 'loadmore',
				payUrlData: {row:{number: ''}},
				payConfig: {},
			};
		},
		onLoad(e) {
			this.customer_id = e.id ? e.id: ''
			// 小程序顶部样式修改
			uni.setNavigationBarColor({
				frontColor: this.vuex_theme.bgColor,
				backgroundColor: this.vuex_theme.color,
				animation: {
					duration: 0,
					timingFunc: 'easeIn'
				}
			})
			this.getData()
			// 获取基础配置
			this.onGetInit()
		},
		onShow(){
			switch (this.current) {
					case 0:
					this.page = 0
					this.lastPage = false
					this.getCustomerRecord()
					break;
				case 1:
					this.cPage = 0
					this.lastContact = false
					this.getContactList()
					break;
				case 2:
					this.Bpage = 0
					this.lastBusiness = false
					this.getBusinessList()
					break;
				case 3:
					this.pageHt = 0
					this.lastHt = false
					this.getContractList()
					break;
				case 4:
					this.pageHk = 0
					this.lastHk = false
					this.getReceivables()
					break;
				default:
					break;
			}
		},
		onReady() {
			let that = this;
			uni.getSystemInfo({ //调用uni-app接口获取屏幕高度
				success(res) { //成功回调函数
					that.pH = res.windowHeight //windoHeight为窗口高度，主要使用的是这个
					let scrollH = uni.createSelectorQuery().select(".sv"); //想要获取高度的元素名（class/id）
					scrollH.boundingClientRect(data=>{
						let pH = that.pH; 
						that.scrollHeight = pH - data.top - 80  //计算高度：元素高度=窗口高度-元素距离顶部的距离（data.top）
					}).exec()
				}
			})
		},
		filters: {
			changeStatus(val){
				switch (val) {
					case 0:
						return '未成交'
						break;
					case 1:
						return '已成交'
						break;
					default:
						return '--'
						break;
				}
			},
			sexType(val){
				switch (val) {
					case -1:
						return '未知'
						break;
					case 0:
						return '女'
						break;
					case 1:
						return '男'
						break;
					default:
						return '--'
						break;
				}
			},
			is_endStatus(val){
				switch (val) {
					case 0:
						return '洽淡中'
						break;
					case 1:
						return '成交'
						break;
					case 2:
						return '失败'
						break;
					case 3:
						return '无效'
						break;
					default:
						return '--'
						break;
				}
			},
			checkStatus(val){
				switch (val) {
					case 0:
						return '待审核'
						break;
					case 1:
						return '审核中'
						break;
					case 2:
						return '审核通过'
						break;
					case 3:
						return '审核未通过'
						break;
					default:
						return '--'
						break;
				}
			}
		},
		methods: {
			// 判断是否显示续费二维码
			renewShow(val){
				if (val.check_status == undefined || val.check_status !=2){
					return false;
				}
				if (!this.payConfig || this.payConfig.online_pay !='1'){
					return false;
				}
				//判断过期日期
				var newtime = Math.round(new Date().getTime()/1000).toString();
				if (newtime < (val.end_time - (this.payConfig.renew_day*86400))){
					return false;
				}
				return true
			},
			// 基本设置
			onGetInit(id) {
				this.$u.api.getInit().then((res) => {
					if(res.code == 1){
						this.payConfig = res.data.payConfig
					}
				})
			},
			// 生成收款单
			onGetPayurl(id) {
				this.$u.api.getPayurl({ids: id}).then((res) => {
					if(res.code == 1){
						this.payUrlData = res.data
						this.payShow = true
					}
				})
			},
			// 生成续费单
			onGetRenewPayurl(id){
				this.$u.api.getRenewPayurl({ids: id}).then((res) => {
					if(res.code == 1){
						this.payUrlData = res.data
						this.payShow = true
					}
				})
			},
			// 确定转移客户
			onConfirm(val){
				this.$u.api.onDivert({ids: this.customer_id,admin_id: val}).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: res.msg,
							icon: 'success',
							duration: 2000
						})
						this.$refs.adminList.companyShow = false
						this.$refs.adminList.admin_id = ''
						this.getData()
					}
				})
			},
			// 查看更多
			onLookMore() {
				this.$u.route('pages/client/details/index',{
					id: this.customer_id,
				});
			},
			// 拨打电话
			call(p) {
				uni.makePhoneCall({
					phoneNumber: p 
				});
			},
			// 云呼叫
			cloudcall(p){
				this.$u.api.onCloudcall({
					type: 'customer',
					typeid: this.customer.id,
					field: p,
					prefix: '',
				}).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: '操作成功',
							icon: 'success',
							duration: 2000
						})
						setTimeout(() => {
							this.$u.route('pages/client/followUp',{
								id: this.customer_id
							});
						}, 1000);
					}
				})
			},
			// 更多操作
			moreClick(index){
				switch (index) {
					case 0:
						// 编辑客户
						this.$u.route('pages/client/clientSet/clientSet',{
							id: this.customer_id,
							type: 'edit',
						});
						break;
					case 1:
						uni.showModal({
							title: '提示',
							content: '确定将用户放入公海吗？',
							success: function (res) {
								if (res.confirm) {
									this.$u.api.onDiscard({
										ids: this.customer_id
									}).then(res => {
										if(res.code == 1 ) {
											// 提示
											uni.showToast({
												title: '操作成功',
												icon: 'success',
												duration: 2000
											})
											setTimeout(() => {
												uni.navigateBack();
											}, 1000);
										}
									})
								} else if (res.cancel) {
									console.log('用户点击取消');
								}
							}.bind(this)
						});
						break;
					case 2:
						// 转移客户
						this.$refs.adminList.companyShow = true
						break;
					case 3:
						uni.showModal({
							title: '提示',
							content: '确定删除该客户吗？',
							success: function (res) {
								if (res.confirm) {
									this.$u.api.onCustomerDel({
											ids: this.customer_id
										}).then(res => {
											if(res.code == 1 ) {
												// 提示
												uni.showToast({
													title: '操作成功',
													icon: 'success',
													duration: 2000
												})
												setTimeout(() => {
													uni.navigateBack();
												}, 1500);
											}
										})
								} else if (res.cancel) {
									console.log('用户点击取消');
								}
							}.bind(this)
						});
						break;
					case 4:
						// 添加商机
						this.$u.route('pages/business/addBusiness/index',{
							type:'add',
							customer_id:this.customer_id
						})
						break;
					case 5:
						this.$u.route('pages/contract/index',{
							type:'add',
							customer_id:this.customer_id
						});
						break;
					case 6:
						this.$u.route('pages/receivables/manage',{
							type:'add',
							customer_id:this.customer_id
						});
						break;
					case 7:
						this.$u.route('pages/contacts/addPerson',{
							type:'add',
							customer_id:this.customer_id
						}); 
						break;
					case 8:
						this.$u.route('pages/send/index',{
							type:'email',
							id: this.customer_id,
							types: 'customer',
						}); 
						break;
					case 9:
						this.$u.route('pages/send/index',{
							type:'note',
							id: this.customer_id,
							types: 'customer',
						}); 
						break;
					case 10:
						this.$u.route('pages/invoice/apply',{
							type: 'add',
							customer_id:this.customer_id
						})
						break;
					default:
						break;
				}
			},
			
			// 查看详情
			lookDetails(val){
				switch (this.current) {
					case 0:
						break;
					case 1:
						this.$u.route('pages/contacts/detail',{
							id: val.id
						});
						break;
					case 2:
						this.$u.route('pages/business/detail',{
							id: val.id
						});
						break;
					case 3:
						this.$u.route('pages/contract/index',{
							id: val.id,
							type: 'edit'
						});
						break;
					case 4:
						this.$u.route('pages/receivables/manage',{
							id: val.id,
							type: 'edit'
						});
						break;
					default:
						break;
				}
			},
			// 格式化时间
			timeFormats(val) {
				if(val) {
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM');
				} else {
					return '--'
				}
			},
			// 获取客户详情
			getData() {
				this.$u.api.getCustomer({id: this.customer_id}).then(res => {
					if(res.code == 1 ) {
						res.data.tags = res.data.tags.split(',')
						this.customer = res.data
					}
				})
			},
			// 获取客户跟进记录
			getCustomerRecord(isNextPage,pages) {
				this.$u.api.getRecord({
					sort: 'id',
					order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({scene_id:"4",types_id: this.customer_id,}),
					op: JSON.stringify({scene_id:"=",types_id:"="})
				}).then(res => {
					if(res.code == 1 ) {
						// 不够一页
						if (res.data.rows.length < 10) {
							this.listStatus = 'nomore'
						}
						// 最后一页
						if(res.data.rows.length == 0) {
							this.lastPage = true
						} 
						// 第二页开始
						if(isNextPage) {
							this.recordList = this.recordList.concat(res.data.rows)
							return 
						}
						this.recordList = res.data.rows
					}
				})
			},
			// 获取联系人
			getContactList(isNextPage,pages) {
				this.$u.api.getContactsList({
					sort: 'id',
					order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({scene_id: 4,customer_id: this.customer_id}),
					op: JSON.stringify({scene_id: '=',customer_id: '='})
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
							this.contactList = this.contactList.concat(res.data.rows)
							return 
						}
						this.contactList = res.data.rows
					}
				})
			},
			// 获取商机
			getBusinessList(isNextPage,pages) {
				this.$u.api.onBusinessList({
					order: 'desc',
					customer_id: this.customer_id,
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({}),
					op: JSON.stringify({})
				}).then(res => {
					if(res.code == 1 ) {
						// 不够一页
						if (res.data.rows.length < 10) {
							this.businessStatus = 'nomore'
						}
						// 最后一页
						if(res.data.rows.length == 0) {
							this.lastBusiness = true
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
			// 获取合同
			getContractList(isNextPage,pages) {
				this.$u.api.getContractlist({
					order: 'desc',
					customer_id: this.customer_id,
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({}),
					op: JSON.stringify({})
				}).then(res => {
					if(res.code == 1 ) {
						// 不够一页
						if (res.data.rows.length < 10) {
							this.listStatusHt = 'nomore'
						}
						// 最后一页
						if(res.data.rows.length == 0) {
							this.lastHt = true
						} 
						// 第二页开始
						if(isNextPage) {
							this.htList = this.htList.concat(res.data.rows)
							return 
						}
						this.htList = res.data.rows
					}
				})
			},
			// 获取回款
			getReceivables(isNextPage,pages){
				this.$u.api.getReceivableslist({
					order: 'desc',
					customer_id: this.customer_id,
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({}),
					op: JSON.stringify({})
				}).then(res => {
					if(res.code == 1 ) {
						// 不够一页
						if (res.data.rows.length < 10) {
							this.listStatusHk = 'nomore'
						}
						// 最后一页
						if(res.data.rows.length == 0) {
							this.lastHk = true
						} 
						// 第二页开始
						if(isNextPage) {
							this.hkList = this.hkList.concat(res.data.rows)
							return 
						}
						this.hkList = res.data.rows
					}
				})
			},
			// 滚动到底部
			reachBottom() {
				switch (this.current) {
					case 0:
						if(this.lastPage || this.listStatus == 'loading') return ;
						this.listStatus = 'loading'
						setTimeout(() => {
							if(this.lastPage) return ;
							this.getCustomerRecord(true,++this.page)
							if(this.recordList.length >= 10) this.listStatus = 'loadmore';
							else this.listStatus = 'loading';
						}, 1200)
						break;
					case 1:
						if(this.lastContact || this.contactStatus == 'loading') return ;
						this.contactStatus = 'loading'
						setTimeout(() => {
							if(this.lastContact) return ;
							this.getContactList(true,++this.cPage)
							if(this.contactList.length >= 10) this.contactStatus = 'loadmore';
							else this.contactStatus = 'loading';
						}, 1200)
						break;
					case 2:
						if(this.lastBusiness || this.businessStatus == 'loading') return ;
						this.businessStatus = 'loading'
						setTimeout(() => {
							if(this.lastBusiness) return ;
							this.getBusinessList(true,++this.Bpage)
							if(this.businessList.length >= 10) this.businessStatus = 'loadmore';
							else this.businessStatus = 'loading';
						}, 1200)
						break;
					case 3:
						if(this.lastHt || this.listStatusHt == 'loading') return ;
						this.listStatusHt = 'loading'
						setTimeout(() => {
							if(this.lastHt) return ;
							this.getContractList(true,++this.pageHt)
							if(this.htList.length >= 10) this.listStatusHt = 'loadmore';
							else this.listStatusHt = 'loading';
						}, 1200)
						break;
					case 4:
						if(this.lastHk || this.listStatusHk == 'loading') return ;
						this.listStatusHk = 'loading'
						setTimeout(() => {
							if(this.lastHk) return ;
							this.getReceivables(true,++this.pageHk)
							if(this.hkList.length >= 10) this.listStatusHk = 'loadmore';
							else this.listStatusHk = 'loading';
						}, 1200)
						break;
					default:
						return '--'
						break;
				}
			},
			// 切换导航栏
			change(index) {
				this.current = index;
				switch (index) {
					case 0:
						this.page = 0
						this.lastPage = false
						this.getCustomerRecord()
						break;
					case 1:
						this.cPage = 0
						this.lastContact = false
						this.getContactList()
						break;
					case 2:
						this.Bpage = 0
						this.lastBusiness = false
						this.getBusinessList()
						break;
					case 3:
						this.pageHt = 0
						this.lastHt = false
						this.getContractList()
						break;
					case 4:
						this.pageHk = 0
						this.lastHk = false
						this.getReceivables()
						break;
					default:
						break;
				}
			},
			// 商机跟进
			follow(id) {
				this.$u.route('pages/business/followUp',{
					id: id
				});
			},
			// 跟进客户
			onAdd(e) {
				this.$u.route('pages/client/followUp',{
					id: this.customer_id
				});
			},
			// 签到
			onSign(e) {
				this.$u.route('pages/more/sign',{
					customer_id: this.customer_id,
				}); 
			},
			// 预览图片
			lookImges(url) {
				uni.previewImage({
					urls: [url],
					longPressActions: {
						itemList: ['发送给朋友', '保存图片', '收藏'],
						success: function(data) {
						},
						fail: function(err) {
							console.log(err.errMsg);
						}
					}
        });
			}
		},
	}
</script>

<style lang="scss" >
.container {
	background-color: #F7F7F7 !important;
	// min-height: 100vh;
}
.status_bar {
	height: var(--status-bar-height);
	width: 100%;
}
.bare {
	color: #fff;
	background-color: #FE644A; 
}
.notice {
	margin: -68px 8px 10px 8px;
	left: 0;
	right: 0;
	background-color: #fff;
	border-radius: 10px;
	padding: 25rpx 20rpx 35rpx;
	.belong {
		display: flex;
		justify-content: space-between;
		.left {
			display: flex;
			align-items: center;
			padding-right: 25rpx;
			.store {
				font-size: 30rpx;
				font-weight: bold;
				word-break: break-all;
			}
		}
		.right {
			color: #FF7159;
		}
		.right1 {
			color: $u-type-success;
		}
	}
	.u-tag {
		margin-right: 4rpx;
		font-size: 24rpx;
	}
	.tap {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		margin: 20rpx 0 0;
		.tap-item {
			background-color: #FF6146;
			color: #fff;
			font-size: 25rpx;
			padding: 10rpx 25rpx;
			border-radius: 8rpx;
			margin-left: 10rpx;
			margin-bottom: 10rpx;
		}
	}
	.relation {
		justify-content: space-between;
		margin-top: 15rpx; 
		font-size: 26rpx;
		.dial {
			color: #2979ff;
			font-size: 14px;
			border-bottom: 1px solid #2979ff;
			padding-bottom: 0px;
		}
	}
	.bottom {
		margin-top: 25rpx;
		justify-content: space-between;
		.client_time {
			color: #777;
  		font-size: 26rpx;
		}
	}
	.more {
		padding-top: 25rpx; 
		justify-content: center;
		color: #2979ff;
	}
	.details {
		padding: 45rpx 0rpx 0rpx;
		.item {
			flex: 1;
			margin-bottom: 25rpx;
			.text {
				font-size: 25rpx; 
				color: #777;
			}
		}
	}
}
.region {
	// background-color: #fff;
}
.statusBar {
	position: relative;
	z-index: -1;
	height: 188rpx;
}

.page-box {
	padding: 20rpx 20rpx 45rpx;
}
.list-view {
	width: 710rpx;
	background-color: #ffffff;
	margin-bottom: 20rpx;
	border-radius: 20rpx;
	box-sizing: border-box;
	padding: 20rpx;
	font-size: 28rpx;
	.top {
		display: flex;
		justify-content: space-between;
		.left {
			display: flex;
			align-items: center;
			word-break:break-all;
			width: 100%;
			.store {
				margin: 0 10rpx;
				font-size: 28rpx;
				font-weight: bold;
			}
		}
		.right {
			color: #F76046;
		}
	}
	.item {
		display: flex;
		margin: 20rpx 0 0;
		.left {
			margin-right: 20rpx;
			.image {
				width: 120rpx;
				height: 120rpx;
				border-radius: 10rpx;
			}
			.video {
				width: 140rpx;
				height: 140rpx;
			}
		}
		.content {
			flex: 1;
			.title {
				font-size: 28rpx;
				line-height: 50rpx;
			}
			.type {
				margin: 20rpx 0;
				font-size: 26rpx;
				color: $u-tips-color;
			}
			.delivery-time {
				color: #e5d001;
				font-size: 24rpx;
			}
		}
	}
	.contact {
		padding: 25rpx 0;
		display: flex;
		justify-content: space-between;
		.name {

		}
		.dial {
			color: #2979ff;
			font-size: 14px;
			border-bottom: 1px solid #2979ff;
			padding-bottom: 0px;
		}
	}
	.bottom {
		display: flex;
		margin-top: 10rpx;
		justify-content: space-between;
		align-items: center;
		.time {
			color: #777;
  		font-size: 26rpx;
		}
		.btn {
			line-height: 60rpx;
			width: 160rpx;
			border-radius: 5px;
			font-size: 26rpx;
			text-align: center;
			color: $u-type-info-dark;
		}
		.sky {
			color: #FF6146;
			background-color: #F7F7F7;
		}
		.entity {
			color: #fff;
			background-color: #FF6146; 
		}
	}
}
.bottom-btn {
	position: fixed;
	display: flex;
	align-items: center;
	justify-content: flex-end;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 25rpx 30rpx 45rpx;
	background-color: #fff;
	z-index: 100;
	.btn {
		float: right;
		line-height: 43rpx;
		padding: 15rpx 36rpx;
		border-radius: 5px;
		font-size: 28rpx;
		text-align: center;
	}
	.sky {
		color: #fff;
		background-color: #F5A625;
	}
	.entity {
		color: #fff;
		background-color: #FF6146; 
	}
}
</style>
