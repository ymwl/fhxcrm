<template>
	<view>
		<!-- 支付二维码弹窗 -->
		<u-popup class="popup-content" mode="center" border-radius="38" :mask-close-able="false"  v-model="show" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">扫码付款</text> 
				<view class="" @click="show = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<view class="u-m-l-25 u-m-b-25 u-m-r-25 u-m-t-25">
				<u-image width="600rpx" height="600rpx" :src="payUrlData.qrcode_url"></u-image>
			</view>
			<view v-if="payUrlData.row.number" class="u-m-t-25 u-m-b-25" style="text-align: center;">扫码付款:{{payUrlData.row.number}}</view>
			<view style="text-align: center;" class="u-p-l-25 u-p-r-25 u-p-b-25">
				<u-button type="success"  @click="copyUrl" >复制支付链接</u-button>
			</view>
		</u-popup>
	</view>
</template>

<script>

export default {
	name: 'popup-qr-code',
	props: {
		// 通过双向绑定控制组件的弹出与收起
		value: {
			type: Boolean,
			default: false
		},
		payUrlData: {
			type: Object,
			default: {}
		},
	},
	data() {
		return {
			show: false,
		};
	},
	watch: {
		// 通过双向绑定控制组件的弹出与收起
		value(newValue, oldValue) {
			this.show = newValue
		},
		show(newValue, oldValue) {
			if(newValue == false ) {
				this.close()
			}
		}
	},
	methods: {
		//复制url
		copyUrl() {
			let content = this.payUrlData.url
			let _this = this
			/**
			 * 小程序端 和 H5端的复制逻辑
			 */
			//#ifndef H5
			uni.setClipboardData({
				data: content,
				success: function() {
					_this.$u.toast('链接已复制');
				},
				fail: function() {
					_this.$u.toast('复制失败');
				}
			});
			//#endif

			/**
			 * H5端的复制逻辑
			 */
			// #ifdef H5
			if (!document.queryCommandSupported('copy')) { //为了兼容有些浏览器 queryCommandSupported 的判断
				// 不支持
				error('浏览器不支持')
			}
			let textarea = document.createElement("textarea")
			textarea.value = content
			textarea.readOnly = "readOnly"
			document.body.appendChild(textarea)
			textarea.select() // 选择对象
			textarea.setSelectionRange(0, content.length) //核心
			let result = document.execCommand("copy") // 执行浏览器复制命令
			if (result) {
				_this.$u.toast('链接已复制');
			} else {
				_this.$u.toast('复制失败，请检查h5中调用该方法的方式，是不是用户点击的方式调用的，如果不是请改为用户点击的方式触发该方法，因为h5中安全性，不能js直接调用！');
			}
			textarea.remove()
			// #endif
		},
		close() {
			this.$emit('input', false);
		},
	}
};
</script>

<style lang="scss">
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
