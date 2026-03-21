<template>
	<view class="container" >
		<view class="wrap">
			<!-- 排序 -->
			<view class="sort u-border-bottom">
				<u-dropdown :border-bottom="false">
					<u-dropdown-item class="45" v-model="value" :title="options[value].label" :options="options" @change="optionsChange"></u-dropdown-item>
				</u-dropdown>
				<view class="right-text">
					<u-search style="width:100%" placeholder="输入关键字搜索" shape="square" v-model="keyword" :input-style="search"  :show-action="false" @change="onSearch"></u-search>
				</view>
			</view>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll" @scrolltolower="reachBottom">
				<view class="page-box" @touchmove="handletouchstart" @touchend="handletouchend">
					<block v-if="dataList.length > 0">
						<view class="client" v-for="(items, index) in dataList" :key="index">
							<view class="top">
								<view class="left">
									<view class="store u-line-1">{{items.name}}</view>
									<!-- <u-icon name="arrow-right" color="rgb(203,203,203)" :size="26"></u-icon> -->
								</view>
								<view :style="{color:items.issuccess == 1 ? '#19be6b' : vuex_theme.color}">
								{{items.issuccess | changeStatus}}
								<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon></view>
							</view>
							<!-- 标签 -->
							<view class="tap" v-if="items.tags !='' ">
								<u-tag :text="item"  size="min"  v-for="(item,i) in items.tagsArr" :key="i" />
							</view>
							<view class="item">
								<view class="content">
									<view class="title u-line-2">{{items.mobile ? items.mobile : '--'}}</view>
								</view>
								<view class="right"> {{items.owner_user ? '归：' + items.owner_user.realname : ''}}</view>
							</view>
							<view class="bottom">
								<view class="client_time" >{{timeFormats(items.create_time)}}</view>
								<view class="u-flex"> {{ items.create_user ? '创：' + items.create_user.realname : ''}}</view>
							</view>
						</view>
						<u-loadmore :status="listStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</view>
		<view class="floatBtn" :class="specClass"   @click="onAdd"  >
			<u-icon class="u-p-b-5" name="plus"  size="40" :color="vuex_theme.color"></u-icon>添加
		</view>
	</view>
</template>

<script>
	import { processingImages,getImgUrl} from '@/common/mUtils'
	export default {
		data() {
			return {
				sort: 'id',
				keyword:'',
				sceneName: '全部客户',
				specClass: 'hide',
				priceShow: false,
				oldScrollTop: 0,
				scrollTop: -1,
				search: {
					fontSise: '18px'
				},
				dataList: [],
				value: 0,
				options: [
					{
						label: '客户名称',
						value: 0,
						sort: 'name',
					},
					{
						label: '手机号',
						value: 1,
						sort: 'mobile',
					},
				],
				consentShow: false,
				refund_type: 1,
				current: 0,
				dx: 0,
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 0,
				pageSize: 20,
				lastPage: false,
				listStatus: 'loadmore',
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
			changeStatus(val){
				switch (val) {
					case 0:
						return '未成交'
						break;
					case 1:
						return '已成交'
						break;
					case 2:
						return '跟进中'
						break;
					default:
						return '--'
						break;
				}
			}
		},
		onHide() {
			console.log('隐藏')
			// 页面销毁清除已选筛选数据
			// this.$u.vuex('vuex_filter', {})
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
			
		},
		onShow(){
			// 是否有筛选数据
			if(!this.$u.test.isEmpty(this.vuex_filter.filter)) {
				this.page = 0
				this.lastPage = false
				this.getReduplicate();
			} else {
				this.getReduplicate();
			}
			
		},
		computed: {
			
		},
		methods: {
			// 排序
			optionsChange(){
				this.page = 0
				this.lastPage = false
				this.getReduplicate()
			},
			handletouchstart() {
				this.specClass = 'show';
			},
			handletouchend() {
				this.specClass = 'hide';
			},
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
			getReduplicate(isNextPage,pages) {
				// 筛选参数
				let filterObj = {}
				let opObj = {}
        filterObj[this.options[this.value].sort] = this.keyword
        opObj[this.options[this.value].sort] = '='
				this.$u.api.getReduplicate({
					sort: this.sort,
					order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify( this.keyword ? filterObj : {}),
					op: JSON.stringify( this.keyword ? opObj : {})
				}).then(res => {
					res.data.rows.forEach((item,index) => {
						if(item.tags) {
							item.tagsArr = item.tags.split(',')
						}
					});
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
					this.getReduplicate(true,++this.page)
					if(this.dataList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// tab栏切换
			change(index) {
				this.scrollTop = this.oldScrollTop
				this.$nextTick(function() {
					this.scrollTop = 0
				})
				this.page = 1
				this.lastPage = false
				this.current = index
				switch (index) {
					case 0:
						this.status = ''
						break;
					case 1:
						this.status = 10
						break;
					case 2:
						this.status = 20
						break;
					case 3:
						this.status = 30
						break;
					case 4:
						this.status = 40
						break;
					case 5:
						this.status = 50
						break;
					case 6:
						this.status = 60
						break;
					default:
						this.status = ''
						break;
				}
				this.getReduplicate();
			},
			// 点击搜索
			onSearch() {
				this.page = 0
				this.lastPage = false
				this.getReduplicate()
			},
			// 查看客户详情
			onItem(val) {
				this.$u.route('pages/client/customerDetails',{
					id: val.id
				});
			},
			// 跟进
			follow(id) {
				this.$u.route('pages/client/followUp',{
					id: id
				});
			},
			// 添加客户
			onAdd(){
				this.$u.route('pages/client/clientSet/clientSet',{type:'add'})
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
	padding: 25rpx 30rpx;
	flex: 1;
  background-color: #fff;
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
				font-size: 30rpx;
				font-weight: bold;
			}
		}
		.right {
			color: #FF7159;
		}
		.right1 {
			color: $u-type-success;
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
  padding-bottom: 15rpx;
	background: #fff;
	height: 100%;
	.right-text {
    flex: 1;
		position: absolute;
		display: flex;
		align-items: center;
		right: 10px;
		height: 100%;
    width: 72%;
		z-index: 100;
		.fils {
			display: flex;
			align-items: center;
			height: 100%;
		}
	}
	/deep/ .u-dropdown__menu__item {
		justify-content: left !important;
		padding-left: 20rpx;
	}
}
.swiper-box {
	flex: 1;
}
.swiper-item {
	height: 100%;
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
