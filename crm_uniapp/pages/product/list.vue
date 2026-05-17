<template>
	<view class="container">
		<!-- 顶部导航 -->
		<view class="slot-wrap">
			<u-search style="width:100%" placeholder="搜索名称/编码/属性" v-model="keyword" :input-style="search"  :show-action="false" @change="onSearch">></u-search>
		</view>
		<view class="wrap">
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="dataList.length > 0">
						<view class="client" v-for="(item, index) in dataList" :key="index">
							<view class="top">
								<view class="left">
									<view class="store">{{item.name}}</view>
								</view>
							</view>
							<view class="item">
								<view class="content">
									<view class="title u-line-2">
										<text class="u-flex-1">规格：{{item.sku ? item.sku : '--'}}</text>
										<text class="u-p-l-15 u-flex-1">库存：{{item.inventory}}</text>
									</view>
									<view class="title u-line-2">
										<text class="u-flex-1">编号：{{item.id}}</text>
										<text class="u-p-l-15 u-flex-1">价格：{{item.price}}</text>
									</view>
								</view>
							</view>
							<view class="bottom">
								<view class="client_time">添加时间：{{timeFormats(item.create_time)}}</view>
								<view class="u-flex">
									<view class="btn u-m-l-15 entity" @click.stop="onEdit(item.id)" :style="{backgroundColor: vuex_theme.color,}">编辑</view>
								</view>
							</view>
						</view>
						<u-loadmore :status="listStatus"></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list" ></u-empty>
				</view>
			</scroll-view>
		</view>
		<!-- 底部按钮 -->
		<view class="bottom-btn u-border-top" >
			<view class="btn entity" :style="{backgroundColor: vuex_theme.color,}" @click="onAdd">
				<u-icon class="u-p-b-5" name="plus"  size="30" color="#FFF"></u-icon>
				<text class="u-m-l-15">添加</text>
			</view>
		</view>
	</view>
</template>

<script>
	import { processingImages,getImgUrl} from '@/common/mUtils'
	export default {
		data() {
			return {
				priceShow: false,
				oldScrollTop: 0,
				scrollTop: -1,
				search: {
					fontSise: '18px'
				},
				dataList: [],
				consentShow: false,
				refund_type: 1,
				current: 0,
				dx: 0,
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 0,
				pageSize: 10,
				lastPage: false,
				listStatus: 'loadmore',
				status: '',
				keyword:'',
			};
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
			if(e.status) {
				this.status = e.status;
				switch (e.status) {
					case "10":
						this.current = 1
						break;
					case "20":
						this.current = 2
						break;
					default:
						this.current = ''
						break;
				}
			}
		},
		onShow(){
			this.getProductList();
		},
		computed: {
			
		},
		methods: {
			// 返回上一页
			onBack() {
				let page = getCurrentPages()
				console.log(page.length)
				if(page.length == 1) {
					// 没有上一页返回首页
					uni.switchTab({
						url: '/pages/index/index'
					});
				} else {
					// 返回上一页
					uni.navigateBack({
						delta: 1
					});
				}
			},
			// 格式化时间
			timeFormats(val) {
				if(val){
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM')
				} else {
					return '--'
				}
			},
			// 页面数据
			getProductList(isNextPage,pages) {
				this.$u.get('crm.product.product/index', {
					sort_by: 'id',
					sort_order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					search:this.keyword,
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
					this.getProductList(true,++this.page)
					if(this.dataList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 点击搜索
			onSearch() {
				this.pages = 0
				this.lastPage = false
				this.getProductList()
			},
			// 编辑
			onEdit(id) {
				this.$u.route('pages/product/setProduct',{
					type: 'edit',
					id: id,
				});
			},
			// 添加
			onAdd(){
				this.$u.route('pages/product/setProduct',{
					type: 'add'
				})
			},
		}
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
	padding-right: 30rpx;
	flex: 1;
	padding: 25rpx;
	background: #fff;
}
.page-box {
	padding: 20rpx 20rpx 156rpx;
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
	.item {
		display: flex;
		margin: 20rpx 0 0;
		.content {
			flex: 1;
			.title {
				display: flex;
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
		justify-content: end !important;
		padding-left: 40rpx;
	}
}
.swiper-box {
	flex: 1;
}
.swiper-item {
	height: 100%;
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
		color: #FF6146;
		background-color: #F7F7F7;
	}
	.entity {
		color: #fff;
		background-color: #FF6146; 
	}
}


</style>
