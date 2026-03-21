<template>
	<view class="container" >
		<!-- 顶部导航 -->
		<u-navbar :is-back="vuex_back" :custom-back="onBack">
			<view class="slot-wrap">
				<u-search style="width:100%" placeholder="搜索违章事件、小区、证件" v-model="keyword" :input-style="search"  :show-action="false" @change="onSearch"></u-search>
			</view>
		</u-navbar>
		<!-- tab选项卡 -->
		<view class="wrap">
			<!-- 排序 -->
			<view class="sort">
				<u-dropdown :border-bottom="true">
					<u-dropdown-item class="45" v-model="value1" :title="sortName" :options="options1" @change="optionsChange"></u-dropdown-item>
				</u-dropdown>
			</view>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll" @scrolltolower="reachBottom">
				<view class="page-box" @touchmove="handletouchstart" @touchend="handletouchend">
					<block v-if="dataList.length > 0">
						<view class="client" v-for="(item, index) in dataList" :key="index" @click="onItem(items)">

              <view class="top">
                <view class="left">
                  <view class="store">事件：{{item.event}}</view>
                  <view>{{timeFormats('违章时间：',item.time)}}</view>
                </view>
                <image :src="item.image" mode="aspectFill" class="event-img" @click="imgPreview(item.image)" />
              </view>
              <view class="item">
                <view class="content">
                  <view class="title u-line-2">
                    <text class="u-flex-1">小区：{{item.community}}</text>
                    <text class="u-p-l-15 u-flex-1">证件：{{item.zhengjianbianhao}}</text>
                  </view>
                  <view class="title u-line-2">
                    <text class="u-flex-1">记录者：{{item.create_username}}</text>
                    <text class="u-p-l-15 u-flex-1">{{timeFormats('记录时间：',item.create_time)}}</text>
                  </view>
                </view>
              </view>
              <view class="bottom">
                <view class="client_time">{{timeFormats('更新时间：',item.update_time)}}</view>
                <view class="u-flex">

                </view>
              </view>
						</view>
						<u-loadmore :status="listStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
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
				keyword:'',
        unicode: '',
				sceneName: '违章记录',
				specClass: 'hide',
				priceShow: false,
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
						label: '记录时间',
						value: 1,
						sort: 'create_time',
					},
					{
						label: '更新时间',
						value: 2,
						sort: 'update_time',
					}
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
				listStatus: 'loadmore',//
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
					case 1:
						return '已成交'
					default:
						return '--'
				}
			}
		},
		onHide() {
			// 页面销毁清除已选筛选数据
			// this.$u.vuex('vuex_filter', {})
		},
		onUnload() {
			// 页面销毁清除已选商品数据
			this.$u.vuex('vuex_back', false)
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
this.unicode = e.unicode
		},
		onShow(){
      console.log('onShow');
			// 是否有筛选数据
			if(!this.$u.test.isEmpty(this.vuex_filter.filter)) {
				this.page = 0
				this.lastPage = false
				this.getCustomerList();
			} else {
				this.getCustomerList();
			}

		},
		computed: {

		},
		methods: {
      imgPreview(photoImg){
        let imgsArray = [];
        imgsArray[0] = photoImg;
        uni.previewImage({
          indicator:'none',
          current: 0,
          urls: imgsArray
        });
      },
			// 排序
			optionsChange(){
				this.sort = this.options1[this.value1].sort
				this.sortName = this.options1[this.value1].label
				this.page = 0,
				this.lastPage = false
				this.getCustomerList()
			},
			handletouchstart() {
				this.specClass = 'show';
			},
			handletouchend() {
				this.specClass = 'hide';
			},
			// 返回上一页
			onBack() {
				// 返回上一页
				this.$u.route('pages/backlog/index');
			},
			// 格式化时间
			timeFormats(lable,val) {
				if(val){
					return lable+this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM')
				} else {
					return ''
				}
			},
			// 页面数据
			getCustomerList(isNextPage,pages) {
				// 筛选参数
				let filterObj = {}
				let opObj = {}
				console.log(this.vuex_filter)
				if(!this.$u.test.isEmpty(this.vuex_filter.filter)) {
					filterObj = this.vuex_filter.filter
					opObj = this.vuex_filter.op

					if(this.vuex_filter.formName.sceneName) {
						this.sceneName = this.vuex_filter.formName.sceneName
					}
				} else {
					this.sceneName = '违章记录'
				}
				// 下次跟进排序筛选 next_time > 0
				if(this.sort == 'next_time') {
					filterObj.next_time = '0'
					opObj.next_time ='>'
				}
        //violationList
				this.$u.api.getViolationMore({
					sort: this.sort,
					order: this.sort == 'next_time' ? 'asc' : 'desc',
					search:this.keyword,
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify(filterObj),
					op: JSON.stringify(opObj),
          unicode: this.unicode
				}).then(res => {
					if(res.code == 1 ) {
            this.keyword=res.zhengshu.zhengjianbianhao;
						// 不够一页
						if (res.data.length <= res.count) {
              console.log(240)
							this.listStatus = 'nomore'
						}
						// 最后一页
						if(res.data.length == 0) {
							this.lastPage = true;
              this.listStatus = 'nomore';
						}
            console.log(res.data)
						// 第二页开始
						if(isNextPage) {
							this.dataList = this.dataList.concat(res.data)
							return
						}
						this.dataList = res.data;
					}else{
            this.$reuse.showError(res.msg);
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
					this.getCustomerList(true,++this.page)
					if(this.dataList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 点击搜索
			onSearch() {
				this.page = 0
				this.lastPage = false
				this.getCustomerList()
			}

		},
	}
</script>

<style lang="scss">
.event-img {
  max-width: 100rpx;
  max-height: 100rpx;
}
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
			align-items: center;
			.store {
				font-size: 30rpx;
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
    margin-top: 0;
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
		justify-content: left !important;
		padding-left: 40rpx;
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
