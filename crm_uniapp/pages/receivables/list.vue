<template>
	<view class="container">
		<!-- 顶部导航 -->
		<u-navbar :is-back="true">
			<view class="slot-wrap">
				<u-search style="width:100%" placeholder="搜索回款编号" v-model="keyword" :input-style="search"  :show-action="false" @change="onSearch"></u-search>
			</view>
		</u-navbar>
		<view class="wrap">
			<u-tabs :list="tapList" bar-width="80" :active-color="vuex_theme.color" :is-scroll="false" :current="current" @change="change"></u-tabs>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="dataList.length > 0">
						<view class="client" v-for="(item, index) in dataList" :key="index" @click="onItem(item)">
							<view class="top">
								<view class="left">
									<view class="store">{{item.number}}</view>
									<!-- <u-icon name="arrow-right" color="rgb(203,203,203)" :size="26"></u-icon> -->
								</view>
								<view class="right" :style="{color: item.check_status == 2 ? '#19be6b' : vuex_theme.color}" >{{item.check_status | changeStatus}}<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon>
								</view>
							</view>
							<view class="item">
								<view class="content u-flex-1">
									<view class="title u-line-2">回款：￥{{item.money}}</view>
									<!-- <view class="type">{{ item.goods_attr }}4654987</view> -->
								</view>
								<view class="right u-flex-1 u-line-1">{{item.customer.name}}</view>
							</view>
							<view class="bottom">
								<view class="client_time">日期：{{timeFormats(item.return_time)}}</view>
								<view class="u-flex">
									<view @click.stop="onGetPayurl(item.id)">
										<u-icon v-if="renewShow(item)" class="u-p-r-1" name="renew" custom-prefix="custom-icon" :size="65"  :color="vuex_theme.color"></u-icon>
									</view>
									<block v-if="item.check_status == 0 || item.check_status == 1">
										<template v-if="item.checkTrue">
											<view class="btn u-m-l-15 entity" @click.stop="onCheck(item.id)" :style="{backgroundColor: vuex_theme.color,}">审核</view>
										</template>
										<view v-else class="btn u-m-l-15 primary" @click.stop="onCheck(item.id,'look')">详情</view>
									</block>
									<block v-else>
										<view class="btn u-m-l-15 primary" @click.stop="onCheck(item.id,'look')">详情</view>
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
			<u-icon class="u-p-b-5" name="plus"  size="40" :color="vuex_theme.color"></u-icon>添加回款
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
				payUrlData: {row:{number: ''}},
				payConfig: {},
				oldScrollTop: 0,
				scrollTop: -1,
				search: {
					fontSise: '18px'
				},
				keyword: '',
				dataList: [],
				tapList: [
					{
						id: '',
						name: '全部回款'
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
						name: '通过',
					},
					{
						id: '3',
						name: '不通过',
					},
				],
				current: 0,
				check_status: '',
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
			this.onGetInit()
		},
		onShow(){
			this.getList();
		},
		computed: {
			
		},
		methods: {
			// 判断是否显示收款二维码
			renewShow(val){
				if (val.pay_status == undefined || val.pay_status != 0){
					return false;
				}
				if (!this.payConfig || this.payConfig.online_pay !='1'){
					return false;
				}
				if (val.pay_type != 2){
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
			// 格式化时间
			timeFormats(val) {
				if(val) {
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM');
				} else {
					return '--'
				}
			},
			// 切换导航栏
			change(index) {
				this.current = index;
				this.check_status = this.tapList[index].id
				this.page = 0
				this.lastPage = false
				this.getList()
			},
			// 页面数据
			getList(isNextPage,pages) {
				// 筛选参数
				let filterObj = {
					number: this.keyword
				}
				let opObj = {
					number: 'LIKE',
				}
				// 筛选状态参数
				if(!this.$u.test.isEmpty(this.check_status)) {
					filterObj.check_status = this.check_status
					opObj.check_status = '='
				}
				this.$u.api.getReceivables({
					sort: 'id',
					order: 'desc',
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
			// 详情
			onItem(val) {
				this.$u.route('pages/receivables/manage',{
					id: val.id,
					type: 'edit'
				});
			},
			// 跟进
			onCheck(id,type) {
				this.$u.route('pages/backlog/receivablesCheck',{
					id: id,
					type: type,
				});
			},
			// 添加客户
			onAdd(){
				// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
				//#ifdef MP-WEIXIN
				// flow_receivables 回款审批通知
					this.$reuse.subscriptionInfo('flow_receivables');
				//#endif
				this.$u.route('pages/receivables/manage', {
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
		justify-content: space-between;
		.left {
			display: flex;
			align-items: center;
			.store {
				font-size: 28rpx;
				font-weight: bold;
			}
		}
		.right {
			color: #FF7159;
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
