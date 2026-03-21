<template>
	<view class="container">
		<view class="item" v-for="(item,index) in list" :key="index">
			<view class="text">
				<view class="title u-flex">{{item.name}} <u-icon name="question-circle" color="#BFBFBF" size="40" @click="look"></u-icon></view>
				<view class="gray">{{item.text}}</view>
			</view>
			<view class="switch">
				<u-switch v-model="item.checked" @change="change" :active-value="index" :inactive-value="index" active-color="#29AC66"></u-switch>
			</view>
		</view>
		<!-- 提示弹窗 -->
		<view class="popup-content">
			<u-popup mode="bottom"  border-radius="38"  v-model="hintShow" >
				<view class="popup-title u-border-bottom">
					<view class="" @click="look()" style="width: 45px;">
						<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
					</view>
					<text class="">订阅消息说明</text> 
					<view class="" @click="look()" style="width: 45px;">
						<u-icon name="close"  color="#909399" size="30"></u-icon>
					</view>
				</view>
				<view class="explain">
					<view class="option">
						<view class="title">1、什么是微信小程序订阅消息</view>
						<text>小程序通过服务通知向你推送小程序消息
							订阅消息推送位置：服务通知
							订阅消息下发条件：用户自主订阅
							订阅消息卡片跳转能力：点击查看详情可跳转至该小程序的页面
						</text>
					</view>
					<view class="option">
						<view class="title">2、怎样同意订阅，不再询问（弹窗）</view>
						<text>弹窗后用户勾选 “总是保持以上选择，不再询问” 之后，下次订阅不会弹窗，保持之前的选择，修改选择需要打开小程序设置进行修改。</text>
						<text style="color: #29AC66;" @click="previewImage">查看指引</text>
					</view>
					<view class="option">
						<view class="title">3、注意</view>
						<text>开启订阅后，在使用小程序过程会有弹窗提示，这是正常现象，勾选“总是保持以上选择，不再询问” 之后就不会有弹窗了或者关闭订阅。</text>
					</view>
					<view class="option">
						<view class="title">4、实践</view>
						<text style="color: #29AC66;" @click="msg">点击订阅商家订单收益消息</text>
					</view>
			</view>
			</u-popup>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				checked: false,
				hintShow: false,
				list: [
					{
						id: 0,
						name: '接收“审批”订阅消息',
						text: '为您推送【审批】订单收益通知',
						checked: false,
					},
					{
						id: 1,
						name: '接收“待跟进客户”订阅消息',
						text: '为您推送【待跟进客户】提现成功通知',
						checked: false,
					},
						{
						id: 1,
						name: '接收“待跟进合同”订阅消息',
						text: '为您推送【待跟进合同】新订单提醒通知',
						checked: false,
					},
					{
						id: 1,
						name: '接收“合同审批结果”订阅消息',
						text: '为您推送【合同审批结果】申请售后提醒通知',
						checked: false,
					}
				]
			};
		},
		onShow(){
			this.getNoticeTpl()
			let data = this.vuex_subscribe
			if(data) {
				this.list[0].checked = data.subscribe_income == 1 ? true : false
				this.list[1].checked = data.subscribe_withdraw == 1 ? true : false
				this.list[2].checked = data.subscribe_order == 1 ? true : false
				this.list[3].checked = data.subscribe_service == 1 ? true : false
			}
		},
		methods: {
			getNoticeTpl() {
				this.$u.api.getNoticeTpl().then(res => {
					console.log(res)
					if(res.code == 1) {
						console.log(res)
					}
				})
			},
			change(index) {
				let param = {
					subscribe_income: this.list[0].checked ? 1 : 0,
					subscribe_withdraw: this.list[1].checked ? 1 : 0,
					subscribe_order: this.list[2].checked ? 1 : 0,
					subscribe_service: this.list[3].checked ? 1 : 0
				}
				this.$u.api.getNoticeTpl(param).then(res => {
					console.log(res)
					if(res.code == 1) {
						this.$u.vuex('vuex_subscribe', param)
					}
				})
			},
			look() {
				this.hintShow = !this.hintShow
			},
			// 实践
			msg() {
				// 微信小程序订阅消息 订阅商家消息
				uni.requestSubscribeMessage({
					tmplIds: ['Kc1w6ha1QMje4NzqRteb6qS2WaDcTWRVT7oFfHiW1O8'],
					success (res) { 
						console.log(res.errMsg)
					},
					fail (res) {
						console.log(res)
					},
					complete (res) {
					}
				})
			},
			// 查看指引
			previewImage() {
				uni.previewImage({
					urls: ['https://dividend.ngxcloud123.com/uploads/20210423/61cec416fd6552584ffac1695767f81b.jpg'],
					longPressActions: {
						itemList: ['发送给朋友', '保存图片', '收藏'],
						success: function(data) {
							console.log(data);
						},
						fail: function(err) {
							console.log(err.errMsg);
						}
					}
				});
			},
		}
	}
</script>

<style lang="scss">
.container {
	background-color: #EDEDED;
	min-height: 100vh;
	padding: 30rpx 15rpx 20rpx;
}
.item {
	display: flex;
	align-items: center;
	padding: 35rpx 30rpx;
	background-color: #fff;
	border-radius: 15rpx;
	margin-bottom: 25rpx;
	.text {
		line-height: 45rpx;
		flex: 1;
		.title {
			font-size: 30rpx;
			font-weight: 600; 
		}
		.gray {
			color: #BFBFBF;
		}
	}
}
// 弹窗样式
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
	.explain {
		padding: 40rpx 25rpx 180rpx;
		font-size: 26rpx;
		line-height: 45rpx;
		.option {
			margin-bottom: 25rpx;
		}
		.title {
			font-size: 28rpx;
    	font-weight: 600;
			margin-bottom: 15rpx;
		}
	}
}
</style>
