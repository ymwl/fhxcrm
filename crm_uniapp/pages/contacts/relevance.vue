<template>
	<view class="container">
		<!-- tab选项卡 -->
		<view class="wrap">
			<!-- 排序 -->
			<view class="sort u-border-bottom">
				<u-checkbox-group>
					<u-checkbox v-model="checked" 	@change="checkedAll" >全选</u-checkbox>
				</u-checkbox-group>
				<view class="right-text" @click="allGuanLian">关联</view>
			</view>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px;'+ 'width:100%'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="list.length > 0">
						<u-checkbox-group >
							<view class="client" v-for="(item, index) in list" :key="index" @click="onItem(item)">
								<view class="top">
									<view class="left">
										<view class="store">
											<u-checkbox 
												@change="checkboxChange" 
												v-model="item.checked" 
												:name="item.id"
											>{{item.name}}</u-checkbox> 
											</view>
									</view>
									<!-- <view class="right">{{item.issuccess}}<u-icon name="arrow-right" color="#FF7159" :size="26"></u-icon></view> -->
								</view>
								<view class="item">
									<view class="content">
										<view class="title u-line-2">手机号：{{item.mobile}}</view>
										<!-- <view class="type">{{ item.goods_attr }}4654987</view> -->
									</view>
									<view class="right">性别：{{item.sexText }}</view>
								</view>
								<view class="bottom">
									<view class="client_time">{{item.create_time}}</view>
									<view class="u-flex">
										<view class="btn u-m-l-15 entity" :style="{backgroundColor: vuex_theme.color,}" @click.stop="deliver(item.id,index)">关联</view>
									</view>
								</view>
							</view>
						</u-checkbox-group>
						<u-loadmore :status="listStatus" ></u-loadmore>
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
				business_id: '',
				customer_id: '',
				checked: false,
				priceShow: false,
				oldScrollTop: 0,
				scrollTop: -1,
				search: {
					fontSise: '18px'
				},
				list: [],
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
				order_id: '',
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
			if(e.id) {
				this.business_id = e.id
			}
			if(e.cid) {
				this.customer_id = e.cid
			}
		},
		onShow(){
			this.getCommon();
		},
		computed: {
			
		},
		methods: {
			// 选中某个复选框时，由checkbox时触发
			checkboxChange(e) {
				this.checked = false
			},
			// 全选
			checkedAll(e) {
				this.list.map(val => {
					val.checked = e.value;
				})
			},
			// 多个关联客户
			allReceive() {
				let idArr = []
				this.list.forEach((item,index)=>{
					if(item.checked) {
						idArr.push(item.id)
					}
				})
				if(idArr.length == 0){
					uni.showToast({
						title: '请选择联系人',
						icon: 'none',
						duration: 2000
					})
					return
				}
				this.$u.api.onContactsCorrelation({contacts_ids: idArr.join(','),business_id: this.business_id}).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: res.msg,
							icon: 'success',
							duration: 2000
						})
						setTimeout(() => {
							uni.navigateBack();
						}, 1000);
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
			// 获取联系人列表
			getCommon(isNextPage,pages) {
				this.$u.api.getContactsList({
					sort: 'id',
					order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({customer_id: this.customer_id}),
					op: JSON.stringify({scene_id: '=',customer_id: '='})
				}).then(res => {
					if(res.code == 1 ) {
						res.data.rows.forEach((item,index) => {
							item.checked = false
							item.create_time = this.timeFormats(item.create_time)
							switch (item.sex) {
								case -1:
									item.sexText = '未知'
									break;
								case 0:
									item.sexText = '女'
									break;
								case 1:
									item.sexText = '男'
									break;
									return '--'
									break;
							}
						});
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
							this.list = this.list.concat(res.data.rows)
							return 
						}
						this.list = res.data.rows
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
					this.getCommon(true,++this.page)
					if(this.list.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 点击搜索
			onSearch() {
				this.page = 0
				this.getCommon()
			},
			// 查看详情
			onItem(val) {
				// this.$u.route('pages/store/order/orderDetail',{
				// 	order_id: val.order_id
				// });
			},
			// 关联
			deliver(id,index) {
				this.$u.api.onContactsCorrelation({contacts_ids: id,business_id: this.business_id}).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: res.msg,
							icon: 'success',
							duration: 2000
						})
						setTimeout(() => {
							uni.navigateBack();
						}, 1000);
					}
				})
			},
			// 多个关联
			allGuanLian(){
				let idArr = []
				this.list.forEach((item,index)=>{
					if(item.checked) {
						idArr.push(item.id)
					}
				})
				if(idArr.length == 0){
					this.$u.toast('请先选择联系人')
					return
				}
				this.$u.api.onContactsCorrelation({contacts_ids: idArr.join(','),business_id: this.business_id}).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: res.msg,
							icon: 'success',
							duration: 2000
						})
						setTimeout(() => {
							uni.navigateBack();
						}, 1000);
					}
				})
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
	min-height: 40px;
	padding: 0 25rpx;
	justify-content: space-between;
	height: 100%;
	.right-text {
		line-height: 60rpx;
    width: 160rpx;
    border-radius: 5px;
    font-size: 26rpx;
    text-align: center;
    color: #fff;
    background-color: #2979ff;
	}
	/deep/ .u-dropdown__menu__item {
		justify-content: end !important;
		padding-left: 40rpx;
	}
}

</style>
