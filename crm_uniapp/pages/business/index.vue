<template>
	<view class="container">
		<!-- 顶部导航 -->
		<u-navbar :is-back="vuex_back" :custom-back="onBack">
			<view class="slot-wrap">
				<u-search style="width:100%" placeholder="搜索商机名称" v-model="keyword" :input-style="search"  :show-action="false" @change="onSearch"></u-search>
			</view>
		</u-navbar>
		<view class="wrap">
			<!-- 排序 -->
			<view class="sort">
				<u-dropdown :border-bottom="true">
					<u-dropdown-item class="45" v-model="value1" :title="sortName" :options="options1" @change="optionsChange"></u-dropdown-item>
				</u-dropdown>
				<view class="right-text">
					<navigator url="/pages/business/filter" hover-class="none">
						<view class="fils">筛选/{{scopeName}}<u-icon name="arrow-right" color="#303133" size="30"></u-icon></view>
					</navigator>
				</view>
			</view>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box" @touchmove="handletouchstart" @touchend="handletouchend">
					<block v-if="dataList.length > 0">
						<view class="client" v-for="(items, index) in dataList" :key="index" @click="onItem(items)">
							<view class="top">
								<view class="left">
									<view class="store u-line-1">{{items.name}}</view>
									<!-- <u-icon name="arrow-right" color="rgb(203,203,203)" :size="26"></u-icon> -->
								</view>
								<view :class="colorList[items.is_end]" :style="{color: items.is_end == 0 ? vuex_theme.color: ''}">{{items.is_end | changeStatus}}<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon>
								</view>
							</view>
							<view class="item">
								<view class="content">
									<view class="title">￥{{items.money}}</view>
									<!-- <view class="type">{{ item.goods_attr }}4654987</view> -->
								</view>
								<view class="right u-line-1" >{{items.customer ? items.customer.name : ''}}</view>
							</view>
							<view class="bottom">
								<view class="client_time">预计成交：{{timeFormats(items.deal_time)}}</view>
								<view class="u-flex">
									<view class="btn u-m-l-15 entity" :style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" @click.stop="follow(items.id)">跟进</view>
								</view>
							</view>
						</view>
						<u-loadmore :status="listStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</view>
		<view class="floatBtn" :class="specClass" @click="onAdd">
			<u-icon class="u-p-b-5" name="plus"  size="40" :color="vuex_theme.color"></u-icon>添加商机
		</view>
		<!-- 底部导航 -->
		<fa-tabbar></fa-tabbar>
	</view>
</template>

<script>
	import { processingImages,getImgUrl} from '@/common/mUtils'
	export default {
		data() {
			return {
				sortName: '默认排序',
				sort: 'id',
				specClass: 'hide',
				scopeName: '全部商机',
				keyword: '',
				oldScrollTop: 0,
				scrollTop: -1,
				search: {
					fontSise: '18px'
				},
				dataList: [],
				value1: 0,
				options1: [
					{
						label: '默认排序',
						value: 0,
						sort: 'id',
					},
					{
						label: '下次跟进',
						value: 1,
						sort: 'next_time',
					},
					{
						label: '状态',
						value: 2,
						sort: 'is_end',
					},
					{
						label: '创建时间',
						value: 3,
						sort: 'create_time',
					},
					{
						label: '更新时间',
						value: 4,
						sort: 'update_time',
					}
				],
				dx: 0,
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 0,
				pageSize: 10,
				lastPage: false,
				listStatus: 'loadmore',
				colorList: ['succeed','fail','underway'],
			};
		},
		onPageScroll(e) {
			this.scrollToph = e.scrollTop;
		},
		filters: {
			//图片地址url 拼接
			changImg(val) {
				if (val) {
					return getImgUrl(val)
				} else {
					return '' 
				}
			},
			changeStatus(val){
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
		onUnload() {
			// 页面销毁清除已选商品数据
			this.$u.vuex('vuex_bfilter', '')
		},
		onLoad(e) {},
		onShow(){
			// 是否有筛选数据
			if(!this.$u.test.isEmpty(this.vuex_bfilter.filter)) {
				this.page = 0
				this.lastPage = false
				this.getList();
			} else {
				this.getList();
			}
		},
		computed: {
			
		},
		methods: {
			// 返回上一页
			onBack() {
				// 返回上一页
				this.$u.route('pages/backlog/index');
			},
			// 排序
			optionsChange(){
				this.sort = this.options1[this.value1].sort
				this.sortName = this.options1[this.value1].label
				console.log(this.sort)
				this.page = 0,
				this.lastPage = false
				this.getList()
			},
			handletouchstart() {
				this.specClass = 'show';
			},
			handletouchend() {
				this.specClass = 'hide';
			},
			// 格式化时间
			timeFormats(val) {
				if(val) {
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM')
				} else {
					return '--'
				}
			},
			// 页面数据
			getList(isNextPage,pages) {
				// 筛选参数
				let filterObj = {}
				let opObj = {}
				if(!this.$u.test.isEmpty(this.vuex_bfilter.filter)) {
					filterObj = this.vuex_bfilter.filter
					opObj = this.vuex_bfilter.op
					if(this.vuex_bfilter.formName.scopeName) {
						this.scopeName = this.vuex_bfilter.formName.scopeName
					}
				} else {
					this.scopeName = '全部商机'
				}
				// 下次跟进排序筛选 next_time > 0
				if(this.sort == 'next_time') {
					filterObj.next_time = '0'
					opObj.next_time ='>'
				}
				this.$u.api.onBusinessList({
					sort: this.sort,
					search:this.keyword,
					order:  this.sort == 'next_time' ? 'asc' : 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify(filterObj),
					op: JSON.stringify(opObj)
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
			// 搜索
			onSearch() {
				this.page = 0
				this.getList()
			},
			// 查看详情
			onItem(val) {
				this.$u.route('pages/business/detail',{
					id: val.id
				});
			},
			// 跟进
			follow(id) {
				this.$u.route('pages/business/followUp',{
					id: id
				});
			},
			// 添加商机
			onAdd(){
					// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
				//#ifdef MP-WEIXIN
				// flow_business 商机跟进通知
					this.$reuse.subscriptionInfo('flow_business');
				//#endif
				this.$u.route('pages/business/addBusiness/index',{type:'add'})
			},
		},
	}
</script>

<style lang="scss">
.container {
	background-color: #F7F7F7;
	// min-height: 100vh;
}
.slot-wrap {
	display: flex;
	align-items: center;
	padding: 0 30rpx;
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
				max-width: 400rpx;
				font-size: 28rpx;
				font-weight: bold;
			}
		}
		.succeed {
			color: $u-type-success
		}
		.underway {
			color: $u-type-info
		}
		.fail {
			color: $u-type-error
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
			max-width: 300rpx;
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
		justify-content: left !important;
		padding-left: 40rpx;
	}
}

.floatBtn {
	font-size: 23rpx;
	bottom: 100px;
	right: 10px;
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
	&.show {
		animation: showLayer 0.2s linear both;
	}
	&.hide {
		animation: hideLayer 0.5s linear both;
	}
	@keyframes showLayer {
		0% {
			transform: translateX(0%);
		}
		100% {
			transform: translateX(120rpx); //这里可以通过变大变小调整偏移量
		}
	}
	@keyframes hideLayer {
		0% {
			transform: translateX(120rpx);
		}
		100% {
			transform: translateX(0);
		}
	}
}

</style>
