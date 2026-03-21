<template>
	<view class="container">
		<view class="wrap">
			<view class="page-box">
				<block v-if="extentypeList.length > 0">
					<view class="client" v-for="(item, index) in extentypeList" :key="index">
						<view class="bottom">
							<view class="type_name">
								<u-tag  text="默认" type="error" class="u-m-r-15" v-if="value == item.id" size="min"/>
								{{item.name}}
							</view>
							<view v-if="value != item.id" class="btn u-m-l-15 entity" :style="{backgroundColor: vuex_theme.color}" @click.stop="onSet(item.id)">设置</view>
						</view>
					</view>
				</block>
				<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				extentypeList: [],
				value: '',
			};
		},
		filters: {
		
		},
		onReady() {
			
		},
		onLoad(e) {
			this.getData()
		},
		onShow(){
		},
		computed: {
			
		},
		methods: {
			// 获取数据
			getData(isNextPage,pages) {
				this.$u.api.getCloudcallMode().then(res => {
					if(res.code == 1 ) {
						console.log(res)
						this.value = res.data.callrow.exten_type
						this.extentypeList = this.onJson(res.data.extentype)
					}
				})
			},
			// json 转化
			onJson(data) {
				let arr = []
				for (const key in data) {
					if (Object.hasOwnProperty.call(data, key)) {
						let obj = {}
						obj.id = key
						obj.name = data[key]
						arr.push(obj)
					}
				}
				return arr
			},
			// 设置
			onSet(type) {
				this.$u.api.postCloudcallMode({exten_type: type}).then(res => {
					console.log(res)
					if(res.code == 1) {
						uni.showToast({
							title: '设置成功',
							duration: 2000
						});
						this.getData()
					}
				})
			}
		}
	}
</script>

<style lang="scss">
page {
	background-color: #F7F7F7;
}
.page-box {
	padding: 20rpx 20rpx 150rpx;
}
.client {
	background-color: #ffffff;
	margin-bottom: 20rpx;
	border-radius: 20rpx;
	box-sizing: border-box;
	padding: 32rpx;
	font-size: 28rpx;
	.type {
		padding: 25rpx 0;
		font-size: 35rpx;
		font-weight: bold;
	}
	.bottom {
		display: flex;
		justify-content: space-between;
		align-items: center;
		.type_name {
			font-weight: bold;
  		font-size: 30rpx;
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
</style>
