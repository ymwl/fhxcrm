<template>
	<view class="container">
		<!-- 顶部导航 -->
		<view class="wrap">
			<u-tabs :list="tapList" bar-width="80" :active-color="vuex_theme.color" :is-scroll="false" :current="current" @change="change"></u-tabs>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="dataList.length > 0">
            <view class="include-list">
              <view class="include u-border-bottom" v-for="(item, index) in dataList" :key="index" @click="onItem(item)">
                <view class="top">
                  <view class="left">
                    <view class="store">{{item.invoice_name}}</view>
                  </view>
                  <view class="right" :style="{color: item.check_status == 2 ? '#19be6b' : vuex_theme.color}">{{item.check_status | changeCheckStatus}}{{item.status | changeStatus}}</view>
                </view>
                <view class="item u-flex">
                  <view class="content u-flex-1">
                    <view class="title u-line-2">开票主体：{{item.invoice_body}}</view>
                  </view>
									<view v-if="current == 2" class="btn u-m-l-15 entity" :style="{backgroundColor: vuex_theme.color}" @click.stop="onOpen(item.id)">开具</view>
                </view>
                <view class="bottom">
                  <view class="u-flex" :style="{color: vuex_theme.color}">￥{{item.money}}</view>
                  <view class="include_time">{{timeFormats(item.create_time)}}</view>
                </view>
              </view>
            </view>
						<u-loadmore :status="listStatus" margin-top="50"></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
      <view class="bottom-btn u-border-top">
				<u-button type="primary"  @click="onApply" size="medium" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">申请发票</u-button>
			</view>
		</view>
	</view>
</template>

<script>
	import {getImgUrl} from '@/common/mUtils'
	export default {
		data() {
			return {
				oldScrollTop: 0,
				scrollTop: -1,
				keyword: '',
				dataList: [],
				tapList: [
					{
						id: 'owner',
						name: '我申发票 '
					},
				],
				current: 0,
				types: 'owner',
				dx: 0,
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 1,
				pageSize: 20,
				lastPage: false,
				listStatus: 'loadmore',
				currents: 0,
			};
		},
		onPageScroll(e) {
			this.scrollToph = e.scrollTop;
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
			changeCheckStatus(val){
				switch (val) {
					case 0:
						return '待审核'
						break;
					case 1:
						return '审核中'
						break;
					case 2:
						return '审核通过'
						break;
					case 3:
						return '审核未通过'
						break;
					default:
						return '--'
						break;
				}
			},
			changeStatus(val){
				switch (val) {
					case 1:
						return '(开票中)'
						break;
					case 2:
						return '(已开票)'
						break;
					case 3:
						return '(开票失败)'
						break;
					default:
						return ''
						break;
				}
			}
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
				console.log(e.type)
				// this.current = e.type
				this.currents = e.type
				this.types = 'all'
			}
			this.getList();
		},
		onShow(){
			
		},
		computed: {
			
		},
		methods: {
			// 格式化时间
			timeFormats(val) {
				if(val) {
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM');
				} else {
					return '--'
				}
			},
			// 切换导航栏
			change(index) {
				this.current = index;
				this.currents = index;
				this.types = this.tapList[index].id
				this.page = 1
				this.lastPage = false
				this.getList()
			},
			// 页面数据
			getList(isNextPage,pages) {
				// 筛选参数
				let filterObj = {
					scope: this.types
				}
				let opObj = {
					scope: '='
				}
				// 代开发票筛选
				if(this.currents == 2) {
					filterObj.check_status = 2
					filterObj.status = 0
					opObj.check_status = '='
					opObj.status = '='
				}
				let apiUrl = this.types == 'all' ? 'getAllInvoice' : 'getInvoiceList'
				// 查询全部发票
				this.$u.api[apiUrl]({
					sort_by: 'id',
					sort_order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify(filterObj),
					op: JSON.stringify(opObj)
				}).then(res => {
					if(res.code == 1 ) {
						// 加载顶部tap导航数据
						if(res.data.scene_list && this.tapList.length == 1) {
							for (const key in res.data.scene_list) {
								if(key != 'owner'){
									let obj = {}
									if (Object.hasOwnProperty.call(res.data.scene_list, key)) {
										if(key == 'opener') {
											obj.id = 'all'
										} else {
											obj.id = key
										}
										obj.name = res.data.scene_list[key]
									}
									this.tapList.push(obj)
								}
							}
							this.current = this.currents
						}
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
					this.getList(true,++this.page)
					if(this.dataList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 点击搜索
			onSearch() {
				this.page = 0
				this.lastPage = false
				this.getList()
			},
			// 查看详情
			onItem(val) {
				this.$u.route('pages/invoice/details',{
					id: val.id,
				});
			},
			// 审核
			onCheck(id) {
				let url = ''
				this.current == 0 ? url = 'pages/backlog/check' : url = 'pages/backlog/receivablesCheck' 
				this.$u.route(url,{
					id: id,
				});
			},
			// 申请发票
			onApply(){
				this.$u.route('pages/invoice/apply',{
					type: 'add',
				})
			},
			// 开具发票
			onOpen(id){
				this.$u.route('pages/invoice/openInvoice',{
					id: id
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
	padding-right: 30rpx ;
	flex: 1;
}
.page-box {
	padding: 20rpx 20rpx 160rpx;
}
.include-list {
	background-color: #ffffff;
  border-radius: 18rpx;
  padding: 0rpx 20rpx;
}
.include {
	box-sizing: border-box;
	padding: 30rpx 0rpx;
	font-size: 28rpx;
	.top {
		display: flex;
		justify-content: space-between;
		.left {
			display: flex;
			align-items: center;
			.store {
				font-size: 30rpx;
				font-weight: bold;
			}
		}
		.right {
			color: #FF7159;
		}
	}
	.item {
		display: flex;
		.content {
			margin-top: 15rpx;
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
	.btn {
		line-height: 53rpx;
		width: 120rpx;
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
	.bottom {
		display: flex;
		margin-top: 20rpx;
		justify-content: space-between;
		align-items: center;
		.include_time {
			color: #777;
  		font-size: 26rpx;
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
.bottom-btn {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 25rpx 30rpx 45rpx;
	background-color: #fff;
	z-index: 100;
}
.bottom-btn /deep/.u-size-medium {
	width: 100% !important;
}


</style>
