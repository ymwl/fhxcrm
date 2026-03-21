<template>
	<view class="container">
		<!-- 顶部导航 -->
		<view class="wrap">
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px;'+ 'width:100%'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="thirdlist.length > 0">
						<view class="client" v-for="(item, index) in thirdlist" :key="index" @click="onItem(item)">
							<view class="top">
								<view class="left">
									<view class="store">{{item.openname}}</view>
								</view>
							</view>
							<view class="item">
								<view class="content">
									<view class="title u-line-2">{{item.platform=='admin_mp'?"公众号":'小程序'}}</view>
								</view>
								<view class="right"></view>
							</view>
							<view class="bottom">
								<view class="client_time">最后登录：{{timeFormats(item.logintime)}}</view>
								<view class="u-flex">
									<view class="btn u-m-l-15 entity" :style="{backgroundColor: vuex_theme.color}" @click.stop="onUnbind(item.platform)">解绑</view>
								</view>
							</view>
						</view>
						<u-loadmore :status="listStatus"></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</view>
	</view>
</template>

<script>
	import { processingImages,getImgUrl} from '@/common/mUtils'
	export default {
		data() {
			return {
				keyword: '',
				priceShow: false,
				oldScrollTop: 0,
				scrollTop: -1,
				search: {
					fontSise: '18px'
				},
				thirdlist: [],
				consentShow: false,
				refund_type: 1,
				current: 0,
				dx: 0,
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 1,
				pageSize: 1000,
				lastPage: false,
				listStatus: 'nomore',
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
			this.getThirdlist()
		},
		onShow(){
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
			getThirdlist(isNextPage,pages) {
				this.$u.api.getThirdlist().then(res => {
					if(res.code == 1 ) {
						this.thirdlist = res.data
					}
				})
			},
			// scroll 滚动记录
			scroll(e) {
				this.oldScrollTop = e.detail.scrollTop; // 必要
			},
			// 解绑微信
			onUnbind(platform) {
				let _this = this
				uni.showModal({
					title: '提示',
					content: '确定解绑吗？',
					success: function (res) {
						if (res.confirm) {
							_this.$u.api.onUnbind({platform: platform}).then(res => {
								console.log(res)
								if(res.code == 1) {
									uni.showToast({
										title: '解绑成功！',
										duration: 2000
									});
									_this.getThirdlist()
								}
							})
						} else if (res.cancel) {
							console.log('用户点击取消');
						}
					}
				});
			}
		
			
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
	padding-right: 30rpx;
	flex: 1;
}
.page-box {
	padding: 20rpx 20rpx 150rpx;
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


</style>
