<template>
	<view class="container">
		<!-- 顶部导航 -->
		<u-navbar :is-back="true">
			<view class="slot-wrap">
				<u-search style="width:100%" placeholder="搜索名称/编码/属性" v-model="keyword" :input-style="search"  :show-action="false" @change="onSearch"></u-search>
			</view>
		</u-navbar>
		<!-- tab选项卡 -->
		<view class="wrap">
			<!-- 排序 -->
			<view class="sort u-border-bottom">
				<u-checkbox-group>
					<u-checkbox v-model="checked" 	@change="checkedAll" >全选</u-checkbox>
				</u-checkbox-group>
				<!-- <view class="right-text">领取</view> -->
			</view>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="list.length > 0">
						<u-checkbox-group style="width:100%" @change="checkboxGroupChange">
							<view class="client" v-for="(item, index) in list" :key="index">
								<view class="top">
									<view class="left">
										<view class="store">
											<u-checkbox style="line-height: 1.5;"
												@change="checkboxChange" 
												v-model="item.checked" 
												:name="item.id"
											>{{item.name}}</u-checkbox>
											</view>
									</view>
									<!-- <view class="right">未成交<u-icon name="arrow-right" color="#FF7159" :size="26"></u-icon></view> -->
								</view>
								<view class="item">
									<image class="product-thumb" :src="getImgUrl(item.thumb)" mode="aspectFill" v-if="item.thumb"></image>
									<view class="client-info u-flex-1">
										<view class="info-row"><text class="label">分类：</text><text class="value">{{item.type ? item.type.title : '--'}}</text></view>
										<view class="info-row"><text class="label">型号：</text><text class="value">{{item.model || '--'}}</text></view>
										<view class="info-row"><text class="label">规格：</text><text class="value">{{item.specification || '--'}}</text></view>
										<view class="info-row"><text class="label">售价：</text><text class="value price-sale">¥{{item.sale_price}}</text></view>
										<view class="info-row"><text class="label">成本：</text><text class="value price-cost">¥{{item.cost_price}}</text></view>
										<view class="info-row"><text class="label">库存：</text><text :class="item.inventory <= item.min_warning ? 'value stock-warn' : 'value stock-ok'">{{item.inventory}}</text></view>
									</view>
								</view>
								<view class="bottom">
									<view class="client_time">添加时间: {{item.create_time_text}}</view>
									<view class="u-flex">
										<view class="btn u-m-l-15 entity" :style="{backgroundColor: vuex_theme.color,}" @click.stop="onSelect(item,index)">{{item.checked ? '取消':'选择'}}</view>
									</view>
								</view>
							</view>
						</u-checkbox-group>
						<u-loadmore :status="listStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
			<view class="bottom_btn u-border-top">
<!--				<u-button size="medium" @click="selected = true">查看已选</u-button> -->
				<u-button class="u-m-l-15" type="primary"  @click="chosen" size="medium" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">选好了</u-button>
			</view>
		</view>
		<!-- 已选产品弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38" v-model="selected" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">已选产品</text> 
				<view class="" @click="selected = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<scroll-view scroll-y style="height: 960rpx;width: 100%;" @scrolltolower="reachBottom">
				<view class="list">
					<block v-if="selectList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in selectList" :key="index">
								<view class="title u-p-r-15">{{item.name}}</view>
								<view class="check-icon">
									<u-button size="mini" type="error" @click="remove(item,index)">删除</u-button>
									<!-- <u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon> -->
								</view>
							</view>
						</view>
						<u-loadmore status="nomore" ></u-loadmore>
					</block>
					<u-empty text="暂无数据,请先选择产品" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
	</view>
</template>

<script>
	import { processingImages,getImgUrl} from '@/common/mUtils'
	export default {
		data() {
			return {
				searchTimer: null,
				keyword: '',
				selected: false,
				checked: false,
				priceShow: false,
				oldScrollTop: 0,
				scrollTop: -1,
				search: {
					fontSise: '18px'
				},
				list: [],
				selectList: [],
				consentShow: false,
				refund_type: 1,
				current: 0,
				dx: 0,
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 1,
				pageSize: 10,
				lastPage: false,
				listStatus: 'loadmore',
				status: '',
				order_id: '',
				source: '',
				preSelected: [],
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
			if (e.source) {
				this.source = e.source;
			}
			// 支持通过 URL 参数 pre_selected 传入已选产品 ID 列表（JSON 数组字符串）
			if (e.pre_selected) {
				try {
					this.preSelected = JSON.parse(decodeURIComponent(e.pre_selected));
				} catch (err) {
					this.preSelected = [];
				}
			}
			// 恢复 Vuex 中已选的产品（支持多次进出选择页追加模式）
			if (!this.$u.test.isEmpty(this.vuex_selectProduct)) {
				const existing = Array.isArray(this.vuex_selectProduct) ? this.vuex_selectProduct : [];
				this.selectList = [...existing];
			}
			if(e.status) {
				this.status = e.status;
				switch (e.status) {
					case "10":
						this.current = 1
						break;
					case "20":
						this.current = 2
						break;
					default:
						this.current = ''
						break;
				}
			}
		},
		onShow(){
			this.getProductList();
		},
		computed: {
			
		},
		methods: {
			getImgUrl,
			// 选中某个复选框时，由checkbox时触发
			checkboxChange(e) {
				this.checked = false 
				let i = ''
				this.list.forEach((item,index) => {
					if(e.name == item.id) {
						item.checked = item.checked ? !item.checked : true
						i = index
					}
				})
				// 添加已选
				if(this.list[i].checked == true) {
					// 添加已选中
					this.selectList.push(this.list[i])
				} else {
					// 删除取消选中
					this.selectList.forEach((item,i) => {
						if(item.id == e.name) {
							this.selectList.splice(i, 1)
						}
					})
				}
			},
			// 选中任一checkbox时，由checkbox-group触发
			checkboxGroupChange(e) {
				// console.log(e);
			},
			// 选中一个
			onSelect(val,i){
				this.checked = false 
				this.list.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = item.checked ? !item.checked : true
					}
				})
				// 添加已选
				if(this.list[i].checked == true) {
					// 添加已选中
					this.selectList.push(this.list[i])
				} else {
					// 删除取消选中
					this.selectList.forEach((item,i) => {
						if(item.id == val.id) {
							this.selectList.splice(i, 1)
						}
					})
				}
			},
			// 全选
			checkedAll(e) {
				this.list.map(val => {
					val.checked = e.value;
				})
				if(e.value) {
					this.list.forEach((item,index)=>{
						let filter = this.selectList.find(i => {
							return item.id == i.id
						})
						//  没有的，添加到 list 列表
						if(filter == undefined) {
							this.selectList.push(item)
						}
					})
				} else {
					this.selectList = []
				}
			},
			//移除已选产品
			remove(val,index) {
				this.list.forEach((i,index) => {
					if(val.id == i.id) {
						i.checked = false
					}
				})
				this.selectList.splice(index, 1)
				// 删除完，关闭已选药品弹窗
				if(this.selectList.length == 0) {
					this.selected = false
				}
			},
			// 选好了
			chosen() {
				// 标准化输出数据格式，确保各调用页面能统一消费
				const normalizedList = this.selectList.map(item => ({
					id: item.id,
					name: item.name || '',
					specification: item.specification || '',
					model: item.model || '',
					cost_price: item.cost_price || 0,
					sale_price: item.sale_price || 0,
					nums: item.nums || 1,
					discount: item.discount || 0,
					remarks: item.remarks || '',
					thumb: item.thumb || '',
					inventory: item.inventory || 0,
					type: item.type || null,
				}))
				this.$u.vuex('vuex_selectProduct', normalizedList)
				const pages = getCurrentPages();
				if (pages.length <= 1) {
					uni.switchTab({ url: '/pages/index/index' });
				} else {
					uni.navigateBack({
						fail: () => {
							uni.switchTab({ url: '/pages/index/index' });
						}
					});
				}
			},
			// 格式化时间
			timeFormats(val) {
				return this.$u.timeFormat(val, 'yyyy/mm/dd');
			},
			// 页面数据
			getProductList(isNextPage,pages) {
				this.$u.get('product/index', {
					sort_by: 'id',
					sort_order: 'desc',
					page: pages,
					limit: this.pageSize,
					search:this.keyword,
				}).then(res => {
					console.log(res.data);
					
					if(res.code == 1 ) {
						res.data.forEach((item,index)=>{
							// 保留原始时间戳，仅添加格式化显示字段
							item.create_time_text = this.timeFormats(item.create_time)
							item.checked = false
							// 设置默认数据
							item.nums = 1
							item.remarks = ''
							this.selectList.forEach((i,index) => {
								if(i.id == item.id) {
									item.checked = true
								}
							})
						})
						// 不够一页
						if (res.data.length < 10) {
							this.listStatus = 'nomore'
						}
						// 最后一页
						if(res.data.length == 0) {
							this.lastPage = true
						} 
						// 第二页开始
						if(isNextPage) {
							this.list = this.list.concat(res.data)
              console.log(this.list);
							return 
						}
						console.log(this.list);
						this.list = res.data
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
					this.getProductList(true,++this.page)
					if(this.list.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 点击搜索
			onSearch() {
				clearTimeout(this.searchTimer)
				this.searchTimer = setTimeout(() => {
					this.page = 0
					this.getProductList()
				}, 500)
			},
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
	padding: 20rpx 20rpx 160rpx;
}
.client {
	background-color: #ffffff;
	margin-bottom: 20rpx;
	border-radius: 20rpx;
	box-sizing: border-box;
	padding: 20rpx;
	font-size: 28rpx;
  width: 100%;
	.top {
		display: flex;
		justify-content: space-between;
		overflow: hidden;
		.left {
			display: flex;
			align-items: center;
			flex: 1;
			min-width: 0;
			overflow: hidden;
			.store {
				font-size: 28rpx;
				font-weight: bold;
				overflow: hidden;
				text-overflow: ellipsis;
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
		align-items: center;
		margin: 20rpx 0 0;
		overflow: hidden;
		.product-thumb {
			width: 120rpx;
			height: 120rpx;
			border-radius: 10rpx;
			margin-right: 15rpx;
			flex-shrink: 0;
			background-color: #f5f5f5;
		}
		.client-info {
			min-width: 0;
			overflow: hidden;
			.info-row {
				float: left;
				min-width: 50%;
				align-items: flex-start;
				line-height: 36rpx;
				font-size: 26rpx;
				box-sizing: border-box;
				padding-right: 20rpx;
				.label {
					flex-shrink: 0;
					color: #999;
					min-width: 140rpx;
				}
				.value {
					flex: 1;
					color: #333;
					word-break: break-all;
					&.price-sale {
						font-size: 28rpx;
						font-weight: 700;
						color: #fa3534;
					}
					&.price-cost {
						font-size: 22rpx;
						color: $u-tips-color;
					}
					&.stock-ok {
						font-size: 24rpx;
						color: #18a058;
					}
					&.stock-warn {
						font-size: 24rpx;
						color: #fa3534;
						font-weight: 600;
					}
				}
			}
		}
		.right {
			margin-left: 10rpx;
			text-align: right;
			min-width: 140rpx;
			flex-shrink: 0;
			.price-sale {
				font-size: 28rpx;
				font-weight: 700;
				color: #fa3534;
				line-height: 40rpx;
			}
			.price-cost {
				font-size: 22rpx;
				color: $u-tips-color;
				margin: 2rpx 0 6rpx;
			}
			.stock-ok {
				font-size: 24rpx;
				color: #18a058;
			}
			.stock-warn {
				font-size: 24rpx;
				color: #fa3534;
				font-weight: 600;
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
			flex-shrink: 0;
			margin-right: 20rpx;
		}
		.btn {
			line-height: 60rpx;
			width: 160rpx;
			border-radius: 5px;
			font-size: 26rpx;
			text-align: center;
			color: $u-type-info-dark;
			flex-shrink: 0;
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
.bottom_btn {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	text-align: right;
	padding: 28rpx 10rpx 45rpx;
	background-color: #fff;
	z-index: 100;
}
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
		margin-bottom: 45rpx;
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
}

</style>
