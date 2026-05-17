<template>
	<view class="container">
		<!-- 顶部导航 -->
		<u-navbar :is-back="true">
			<view class="slot-wrap">
				<u-search style="width:100%" :placeholder=" '搜索' + (type == 'client' ? '客户' : '线索') + '名称、联系电话'" v-model="keyword" :input-style="search"  :show-action="false" @change="onSearch"></u-search>
			</view>
		</u-navbar>
		<!-- tab选项卡 -->
		<view class="wrap">
			<!-- 排序 -->
<!--			<view class="sort u-border-bottom">
				<u-checkbox-group>
					<u-checkbox v-model="checked" 	@change="checkedAll" >全选</u-checkbox>
				</u-checkbox-group>
				<view class="right-text" @click="allReceive">领取{{type == 'client' ? '客户' : '线索'}}</view>
			</view>-->
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="list.length > 0">
						<u-checkbox-group @change="checkboxGroupChange">
							<view class="client" v-for="(item, index) in list" :key="index" @click="onItem(item)">
								<view class="top">
									<view class="left u-line-2">
										<view class="store u-line-2">
											<u-checkbox 
												@change="checkboxChange" 
												v-model="item.checked" 
												:name="item.id"
											>{{item.name}}</u-checkbox> 
											</view>
									</view>
									<view :style="{color: item.issuccess == 1 ? '#19be6b' : vuex_theme.color}">
									{{item.issuccess}}
									<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon></view>
								</view>
								<!-- 标签 -->
								<view class="tap" v-if="item.tags">
									<u-tag :text="s"  size="min"  v-for="(s,i) in item.tags" :key="i" />
								</view>
								<view class="item">
									<view class="content">
                    <view class="title u-line-2">前负责人： {{item.pr_user}}</view>
<!--										 <view class="title u-line-2">手机号： {{item.phone}}</view>-->
										 <view class="type">转公海时间：{{ item.to_gh_time}}</view>
									</view>
									<!-- <view class="right">admin拥有</view> -->
								</view>
								<view class="bottom">
									<view class="client_time">下次跟进: {{item.next_time}}</view>
									<view class="u-flex">
										<view class="btn u-m-l-15 entity" :style="{backgroundColor: vuex_theme.color}" @click.stop="deliver(item.id,index)">领取</view>
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
				keyword: '',
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
				type: '',
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
			if(e.type) {
				this.type = e.type;
				console.log(this.type);
			}
			console.log(e);
		},
		onShow(){
			this.getCommon();
		},
		computed: {
			
		},
		methods: {
			changeStatus(val){
				switch (val) {
					case -1:
						return '无效线索'
						break;
					case 0:
						return '未成交'
						break;
					case 1:
						return '已成交'
						break;
					default:
						return '--'
						break;
				}
			},
			// 选中某个复选框时，由checkbox时触发
			checkboxChange(e) {
				// console.log(e);
			},
			// 选中任一checkbox时，由checkbox-group触发
			checkboxGroupChange(e) {
				if(this.checked){
					this.checked = false
				}
				// console.log(e);
			},
			// 全选
			checkedAll(e) {
				this.list.map(val => {
					val.checked = e.value;
				})
			},
			// 多个领取客户
			allReceive() {
				let idArr = []
				this.list.forEach((item,index)=>{
					if(item.checked) {
						idArr.push(item.id)
					}
				})
				if(idArr.length == 0){
					uni.showToast({
						title: '请选择' + (this.type == 'client' ? '客户' : '线索'),
						icon: 'none',
						duration: 2000
					})
					return
				}
				if(this.type == 'client') {
					this.$u.post('crm.liberum/robClient', {ids: idArr.join(',')}).then(res => {
						if(res.code == 1 ) {
							// 提示
							uni.showToast({
								title: res.msg,
								icon: 'success',
								duration: 2000
							})
							let arrs = []
							this.list.forEach((item,index)=>{
								if(!item.checked) {
									arrs.push(item);
								}
							})
							this.list = arrs
						}
					})
				} else {
					this.$u.post('crm.clues.index/receive', {ids: idArr.join(',')}).then(res => {
						if(res.code == 1 ) {
							// 提示
							uni.showToast({
								title: res.msg,
								icon: 'success',
								duration: 2000
							})
							let arrs = []
							this.list.forEach((item,index)=>{
								if(!item.checked) {
									arrs.push(item);
								}
							})
							this.list = arrs
						}
					})
				}
				
			},
			// 格式化时间
			timeFormats(val) {
				if(val) {
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM');
				} else {
					return '--'
				}
			},
			// 页面数据
			getCommon(isNextPage,pages) {
			  let that=this;
				if(this.type == 'client') {
					// 公海客户
					this.$u.get('crm.customer/seas', {
						sort_by: 'id',
						sort_order: 'desc',
						offset: (pages || 0 ) * this.pageSize,
						limit: this.pageSize,
						search: this.keyword,
					}).then(res => {
						if(res.code == 1 ) {
							res.data.rows.forEach((item,index) => {
								item.checked = false
								item.tags = item.tags ? item.tags.split(',') : ''
								item.next_time = this.timeFormats(item.next_time)
								item.to_gh_time = this.timeFormats(item.to_gh_time)
								item.issuccess = this.changeStatus(item.issuccess)
								item.pr_user = that.$u.test.empty(item.pr_user)?'暂无':item.pr_user;
							});
							// 不够一页
							if (res.data.rows.length <= 10) {
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
              console.log('res.data.rows=', res.data.rows)
							this.list = res.data.rows
						}
					})
				} else {
					// 公共线索
					this.$u.get('crm.clues.index/common', {
						sort_by: 'id',
						sort_order: 'desc',
						offset: (pages || 0 ) * this.pageSize,
						limit: this.pageSize,
						search: this.keyword,
					}).then(res => {
						if(res.code == 1 ) {
							res.data.rows.forEach((item,index) => {
								item.checked = false
								item.tags = item.tags ? item.tags.split(',') : ''
								item.next_time = this.timeFormats(item.next_time)
								item.issuccess = this.changeStatus(item.status)
							});
							// 不够一页
							if (res.data.rows.length <= 10) {
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
				}
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
				this.lastPage = false
				this.getCommon()
			},
			// 查看详情
			onItem(val) {
				// this.$u.route('pages/store/order/orderDetail',{
				// 	order_id: val.order_id
				// });
			},
			// 领取
			deliver(id,index) {
				if(this.type == 'client'){
					// 客户领取
					this.$u.post('crm.liberum/robClient', {ids: id}).then(res => {
						if(res.code == 1 ) {
							// 提示
							uni.showToast({
								title: res.msg,
								icon: 'success',
								duration: 2000
							})
							this.list.splice(index, 1);
						}else{
              uni.showToast({
                title: res.msg,
                icon: 'none',
                duration: 2000
              })
            }
					})
				} else {
					// 线索领取
					this.$u.post('crm.clues.index/receive', {ids: id}).then(res => {
						if(res.code == 1 ) {
							// 提示
							uni.showToast({
								title: res.msg,
								icon: 'success',
								duration: 2000
							})
							this.list.splice(index, 1);
						}
					})
				}
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
			flex: 1;
			padding-right: 15rpx;
			.store {
				font-size: 28rpx;
				font-weight: bold;
			}
		}
	}
	.tap {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		margin: 20rpx 0 0;
		.u-tag{
			margin-right: 4rpx;
			font-size: 24rpx;
		}
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
      display: flex;justify-content: space-between;
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
