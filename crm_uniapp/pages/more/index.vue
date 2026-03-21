<template>
	<view class="container">
		<!-- 浮动顶部导航 -->
		<view class="top" :style="{backgroundColor: vuex_theme.color,}">
			<!-- 顶部导航高度 -->
			<view class="statusBar"></view>
		</view>
		<view class="notice">
			<view class="title">功能模块</view>
			<u-grid :col="4" :border="false" @click="onGrid" >
				<u-grid-item v-for="(item, index) in regionList" :index="index" :key="index">
					<u-icon class="u-p-b-15" :name="item.icon" custom-prefix="custom-icon" :size="46" :color="item.color"></u-icon>
					<text class="grid-text">{{ item.name }}</text>
				</u-grid-item>
			</u-grid>
		</view>
		<!-- 工具项 -->
		<view class="region u-m-t-35">
			<view class="title">其他功能</view>
			<u-cell-group>
				<navigator url="/pages/more/personalDetails" hover-class="none">
					<u-cell-item title="个人信息修改"></u-cell-item>
				</navigator>

			</u-cell-group>
			<view class="btnsu">
				<u-button type="success"  @click="moreShow = true" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor,fontSize: '30rpx',fontWeight: '600'}" :ripple="true" >退出登录</u-button>
			</view>
		</view>
		<u-action-sheet :list="moreList" v-model="moreShow" @click="moreClick"></u-action-sheet>
	</view>
</template>

<script>
let systemInfo = uni.getSystemInfoSync();
	export default {
		data() {
			return {
				moreShow: false,
				shopQrcode: '',
				moreList: [
					{
						text: '换账号登录',
					},
					{
						text: '退出登录',
					}
				],
				homeData: {
					merchant: {
						id: 0,
						logo: "",
						name: "--",
						thumb: "",
					},
					sell_count: 0,
					today_order: 0,
					today_pay: 0,
					undelivery_count: 0,
					unpay_count: 0,
					unsell_count: 0,
					yesterday_order: 0,
					yesterday_pay: 0,
				},
				navbar: false,
				statusBarHeight: systemInfo.statusBarHeight + 48,
				regionList: [
				{
						name: '违章记录',
						url: 'pages/violation/index',
						icon: 'weizhang',
						color: '#BB4A96',
					}
				],
			};
		},
		onLoad(e) {
			// 小程序顶部样式修改
			uni.setNavigationBarColor({
				frontColor: this.vuex_theme.bgColor,
				backgroundColor: this.vuex_theme.color,
				animation: {
					duration: 0,
					timingFunc: 'easeIn'
				}
			})
		},
		onShow(){

		},
		filters: {
			moneyFormat:function(value){
				let num;
        if (value > 9999) { //大于9999显示x.xx万
          num = (Math.floor(value / 100) / 100) + '万';
        } else if (value <= 9999 && value > -9999) {
          num = value
        }
        return num;
			}
		},
		methods: {
			// 退出登录
			moreClick(index) {
				if(index == 0) {
					this.$u.route('pages/login/index',{
						type: 'change'
					});
				} else {
          // vuex储存 token
          this.$u.vuex('vuex_token', '')
          this.$u.route('pages/login/index');
					/*this.$u.api.onLogout().then(res => {
						if(res.code == 1 ) {

						}
					})*/
				}
			},
			// 点击工具栏
			onGrid(e) {
        this.$u.route(this.regionList[e].url);
				// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
				//#ifdef MP-WEIXIN
				switch(e) {
					case 0:
						// 线索和客户
						this.$reuse.subscriptionInfo('clues_customer');
						break;
					case 1:
						// 线索和客户
						this.$reuse.subscriptionInfo('clues_customer');
						break;
					case 2:
						// 线索和客户
						this.$reuse.subscriptionInfo('clues_customer');
						break;
					case 3:
						// 商机
						this.$reuse.subscriptionInfo('flow_business');
						break;
					case 5:
						// 审批合同
						this.$reuse.subscriptionInfo('flow_contract');
						break;
					case 6:
						// 审批回款
						this.$reuse.subscriptionInfo('flow_receivables');
						break;
					case 10:
						// 线索和客户
						this.$reuse.subscriptionInfo('clues_customer');
						break;
					case 11:
						// 线索和客户
						this.$reuse.subscriptionInfo('clues_customer');
						break;
					default:
						break;
				}
				//#endif

				// 跳转方式
				switch (e) {
					case 0:
						this.$u.route({
							type: 'switchTab',
							url: this.regionList[e].url
						});
						break;
					case 3:
						this.$u.route({
							type: 'switchTab',
							url: this.regionList[e].url
						});
						break;
					case 10:
						this.$u.route({
							type: 'switchTab',
							url: this.regionList[e].url
						});
						break;

					default:
						this.$u.route(this.regionList[e].url);
						break;
				}
			},
			// 点击功能
			onOptions(e) {

			},

		},
	}
</script>

<style lang="scss" scoped>
page{
	background-color: #F7F7F7 !important;
}
.container {
}
.status_bar {
	height: var(--status-bar-height);
	width: 100%;
}
.top {
	color: #fff;
	background-color: #FE644A;
}
.notice {
	margin: -68px 8px 10px 8px;
	left: 0;
	right: 0;
	background-color: #fff;
	border-radius: 10px;
	padding: 6px 0px;
	.title {
		padding: 28rpx 20rpx 15rpx;
		font-size: 30rpx;
		font-weight: 600;
	}
}
.region {
	background-color: #fff;
	border-radius: 12px;
	margin: 0 10px;
	padding: 8px 5px 25px;
	.title {
		padding: 28rpx 20rpx;
		font-size: 30rpx;
		font-weight: 600;
	}
	.performance {
		.list {
			padding: 28rpx 20rpx;
			.left-avatar {
				position: relative;
				margin-right: 20rpx;
				.icon {
					position: absolute;
					top: -18px;
    			right: -6px;
					width: 35px;
					height: 35px;
					.img {
						width: 100%;
						height: 100%;
					}
				}
			}
			.content {
				flex: 1;
				.name {
					font-size: 28rpx;
					line-height: 50rpx;
				}
				.type {
					margin: 10rpx 0;
					font-size: 24rpx;
					color: $u-tips-color;
				}
			}
			.money {
				font-weight: 600;
				font-size: 28rpx;
				color: #fa3534;
			}
		}
		.more {
			text-align: center;
			padding: 44rpx 0 15rpx;
			font-size: 28rpx;
			color: $u-tips-color;
		}
	}

}
.statusBar {
	position: relative;
	height: 188rpx;
}
.charts {
	width: 100%;
	height: 300px;
}
.btnsu {
	padding: 35rpx 20rpx;
}


</style>
