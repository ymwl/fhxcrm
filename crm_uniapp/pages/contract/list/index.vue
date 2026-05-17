<template>
	<view class="container">
		<!-- 顶部导航 -->
		<u-navbar :is-back="true">
			<view class="slot-wrap">
				<u-search style="width:100%" placeholder="搜索合同名称、编号" v-model="keyword" :input-style="search"  :show-action="false" @change="onSearch"></u-search>
			</view>
		</u-navbar>
		<view class="wrap">
			<!-- 排序 -->
			<u-tabs :list="tapList" bar-width="80" :active-color="vuex_theme.color" :is-scroll="true" :current="current" @change="change"></u-tabs>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="dataList.length > 0">
						<view class="client" v-for="(items, index) in dataList" :key="index" @click="onItem(items)">
							<view class="top">
								<view class="left u-line-1">
									<view class="store u-line-1">{{items.name}}</view>
									<!-- <u-icon name="arrow-right" color="rgb(203,203,203)" :size="26"></u-icon> -->
								</view>
								<view :style="{color: items.check_status == 1 ? '#19be6b' : vuex_theme.color}">{{items.check_status | changeStatus}}<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon>
								</view>
							</view>
							<view class="item">
								<view class="content">
									<view class="title u-line-2">￥{{items.money}} <text class="u-p-l-15">回款:￥{{items.return_money}}</text> </view>
									<!-- <view class="type">{{ item.goods_attr }}4654987</view> -->
								</view>
								<view class="right">{{items.order_admin ? items.order_admin.realname : '--'}}</view>
							</view>
							<view class="bottom">
								<view class="client_time">到期：{{timeFormats(items.end_time)}}</view>
								<view class="u-flex">
									<view @click.stop="onGetRenewPayurl(items.id)">
										<u-icon v-if="renewShow(items)" class="u-p-r-1" name="renew" custom-prefix="custom-icon" :size="65"  :color="vuex_theme.color"></u-icon>
									</view>
									<block v-if="items.check_status == 0 || items.check_status == 1">
										<template v-if="items.checkTrue">
											<view class="btn u-m-l-15 entity" @click.stop="onCheck(items.id)" :style="{backgroundColor: vuex_theme.color,}">审核</view>
										</template>
										<view v-else class="btn u-m-l-15 primary" @click.stop="onCheck(items.id,'look')">详情</view>
									</block>
									<block v-else>
										<view class="btn u-m-l-15 primary" @click.stop="onCheck(items.id,'look')">详情</view>
									</block>
								</view>
							</view>
						</view>
						<u-loadmore :status="listStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</view>
		<view class="floatBtn" @click="onAdd">
			<u-icon class="u-p-b-5" name="plus"  size="40" :color="vuex_theme.color"></u-icon>添加合同
		</view>
		<!-- 支付二维码弹窗组件 -->
		<popup-qr-code v-model="payShow" :payUrlData="payUrlData"></popup-qr-code>
	</view>
</template>

<script>
	import {get_date} from '@/common/mUtils'
	export default {
		data() {
			return {
				payShow: false,
				payUrlData: {row:{number: ''}},
				payConfig: {},
				keyword: '',
				oldScrollTop: 0,
				scrollTop: -1,
				search: {
					fontSise: '18px'
				},
				dataList: [],
				tapList: [
					{
						id: '',
						name: '全部'
					},
					{
						id: '0',
						name: '待审核'
					},
					{
						id: '1',
						name: '审核中',
					},
					{
						id: '2',
						name: '已通过',
					},
					{
						id: '3',
						name: '不通过',
					},
					{
						id: '4',
						name: '已过期',
					},
					{
						id: '5',
						name: '待回款',
					},
				],
				current: 0,
				value1: 1,
				options1: [{
						label: '下次跟进',
						value: 1,
					},
					{
						label: '跟进状态',
						value: 2,
					},
					{
						label: '创建时间',
						value: 3,
					},
					{
						label: '更新时间',
						value: 4,
					}
				],
				dx: 0,
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 1,
				pageSize: 10,
				lastPage: false,
				listStatus: 'loadmore',
			};
		},
		onPageScroll(e) {
			this.scrollToph = e.scrollTop;
		},
		filters: {
			changeStatus(val){
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
		onReady() {
			let that = this;
			uni.getSystemInfo({ //调用uni-app接口获取屏幕高度
				success(res) { //成功回调函数
					that.pH = res.windowHeight //windoHeight为窗口高度，主要使用的是这个
					let scrollH = uni.createSelectorQuery().select(".sv"); //想要获取高度的元素名（class/id）
					scrollH.boundingClientRect(data=>{
						let pH = that.pH; 
						that.scrollHeight = pH - data.top  //计算高度：元素高度=窗口高度-元素距离顶部的距离（data.top）
					}).exec()
				}
			})
		},
		onLoad(e) {
			if(!this.$u.test.isEmpty(e.current)) {
				this.current=e.current;
				this.check_status = this.tapList[this.current].id;
			}
			this.onGetInit()
		},
		onShow(){
			this.getList();
		},
		computed: {
			
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
				this.payConfig = this.vuex_payConfig
				if (!this.payConfig || Object.keys(this.payConfig).length === 0) {
					this.$u.get('login/config').then((res) => {
						if(res.code == 1){
							this.payConfig = res.data.payConfig
						}
					})
				}
			},
			// 生成续费单
			onGetRenewPayurl(id){
				this.$u.get('crm.contract.index/payurl', {ids: id}).then((res) => {
					if(res.code == 1){
						this.payUrlData = res.data
						this.payShow = true
					}
				})
			},
			// 格式化时间
			timeFormats(val) {
				return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM');
			},
			// 切换导航栏
			change(index) {
				this.current = index;
				this.check_status = this.tapList[index].id
				this.page = 0
				this.lastPage = false
				this.getList()
			},
			// 审核
			onCheck(id,type) {
				this.$u.route('pages/backlog/check',{
					id: id,
					type: type ? type : ''
				});
			},
			// 页面数据
			getList(isNextPage,pages) {
				// 筛选参数
				let filterObj = {}
				let opObj = {}
				// 筛选状态参数
				if(!this.$u.test.isEmpty(this.check_status)) {
					switch(this.check_status){
						case '4':
							//要处理合同
							opObj.end_time = 'RANGE';
							filterObj.end_time ="1970-01-02 00:00"+" - "+get_date(30)+" 23:59";
							opObj.expire_handle = '=';
							filterObj.expire_handle ="0";
							filterObj.check_status = 2;
							break;
						case '5':
							opObj.expire_handle = '=';
							filterObj.expire_handle ="100";
							filterObj.check_status = 2;
							break;
						default:
							filterObj.check_status = this.check_status
							break;
					}
					opObj.check_status = '='
					
				}
				this.$u.get('crm.contract.index/lists', {
					sort_by: 'id',
					search:this.keyword,
					sort_order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify(filterObj),
					op: JSON.stringify(opObj)
				}).then(res => {
					if(res.code == 1 ) {
						// 当前登录账号是否可以审核
						res.data.rows.map(item => {
							// flow_admin_id为空不显示
							if(item.flow_admin_id != '') {
								let userid = String(uni.getStorageSync('admin_info').id)
								let idArr = item.flow_admin_id.split(',')
								// 账号有资格审核显示
								if(idArr.indexOf(userid) > -1){
									item.checkTrue = true
								}	
							} else {
								item.checkTrue = false
							}
						})
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
							this.dataList = this.dataList.concat(res.data.rows)
							return 
						}
						this.dataList = res.data.rows
					}
				})
			},
			// scroll 滚动记录
			scroll(e) {
				this.oldScrollTop = e.detail.scrollTop; // 必要
			},
			// 滚动到底部
			reachBottom() {
				if(this.lastPage || this.listStatus == 'loading') return ;
				this.listStatus = 'loading'
				setTimeout(() => {
					if(this.lastPage) return ;
					this.getList(true,++this.page)
					if(this.dataList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 点击搜索
			onSearch() {
				this.page = 0
				this.lastPage = false
				this.getList()
			},
			// 编辑合同
			onItem(val) {
				this.$u.route('pages/contract/index',{
					id: val.id,
					type: 'edit'
				});
			},
			// 删除
			onDel(id,index) {
				this.$u.post('crm.contract.index/delete', {
					ids: id,
				}).then(res => {
					if(res.code == 1 ) {
							// 提示
						uni.showToast({
							title: '删除成功',
							icon: 'none',
							duration: 2000
						})
						this.dataList.splice(index, 1)
					}
				})
			},
			// 添加客户
			onAdd(){
				// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
				//#ifdef MP-WEIXIN
				// flow_contract 审批合同通知
					this.$reuse.subscriptionInfo('flow_contract');
				//#endif
				this.$u.route('pages/contract/index',{
					type: 'add'
				})
			},
		}
	}
</script>

<style lang="scss">
.container {
	background-color: #F7F7F7;
	min-height: 100vh;
}
.slot-wrap {
	display: flex;
	align-items: center;
	padding-right: 30rpx ;
	flex: 1;
}
.page-box {
	padding: 20rpx 20rpx 45rpx;
}
.client {
	background-color: #ffffff;
	margin-bottom: 20rpx;
	border-radius: 20rpx;
	box-sizing: border-box;
	padding: 20rpx;
	font-size: 28rpx;
	.top {
		display: flex;
		// justify-content: space-between;
		.left {
			display: flex;
			align-items: center;
			flex: 1;
			.store {
				font-size: 28rpx;
				font-weight: bold;
				padding-right: 50rpx
			}
		}
	}
	.item {
		display: flex;
		margin: 20rpx 0 0;
		.content {
			flex: 1;
			.title {
				font-size: 28rpx;
				line-height: 50rpx;
			}
			.type {
				margin: 10rpx 0;
				font-size: 24rpx;
				color: $u-tips-color;
			}
		}
		.right {
			margin-left: 10rpx;
			text-align: right;
			.decimal {
				font-size: 24rpx;
				margin-top: 4rpx;
			}
			.number {
				color: $u-tips-color;
				font-size: 24rpx;
			}
		}
	}
	.total {
		margin-top: 20rpx;
		text-align: right;
		font-size: 24rpx;
		.total-price {
			font-size: 32rpx;
		}
	}
	.bottom {
		display: flex;
		margin-top: 20rpx;
		justify-content: space-between;
		align-items: center;
		.client_time {
			color: #777;
  		font-size: 26rpx;
		}
		.btn {
			line-height: 56rpx;
			padding: 0rpx 25rpx;
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
		.primary {
			color: #FFF;
			background-color: $u-type-primary;
		}
	}
}
.wrap {
	display: flex;
	flex-direction: column;
	width: 100%;
}
.sort {
	position: relative;
	display: flex;
	align-items: center;
	background: #fff;
	height: 100%;
	.right-text {
		position: absolute;
		display: flex;
		right: 10px;
		height: 100%;
		z-index: 100;
		.fils {
			display: flex;
			align-items: center;
			height: 100%;
		}
	}
	/deep/ .u-dropdown__menu__item {
		justify-content: end !important;
		padding-left: 40rpx;
	}
}

.floatBtn {
	font-size: 23rpx;
	bottom: 100px;
	right: 20px;
	border-radius: 5000px;
	z-index: 9;
	opacity: 1;
	width: 130rpx;
	height: 130rpx;
	position: fixed;
	display: flex;
	flex-direction: row;
	flex-direction: column;
	justify-content: center;
	background-color: #fff;
	color: #606266;
	align-items: center;
	transition: opacity 0.4s;
	border: 1px solid #dcdfe6;
}


</style>
