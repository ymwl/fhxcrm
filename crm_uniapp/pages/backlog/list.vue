<template>
	<view class="container">
		<!-- 顶部导航 -->
		<view class="wrap">
			<u-tabs :list="tapList" bar-width="80" :active-color="vuex_theme.color" :is-scroll="false" :current="current" @change="change"></u-tabs>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="dataList.length > 0">
						<view class="client" v-for="(item, index) in dataList" :key="index" @click="onItem(item)">
							<view class="top">
								<view class="left">
									<view class="store">{{current == 2 ? item.invoice_name : item.number}}</view>
									<!-- <u-icon name="arrow-right" color="rgb(203,203,203)" :size="26"></u-icon> -->
								</view>
								<view class="right" :style="{color: item.check_status == 2 ? '#19be6b' : vuex_theme.color}">{{item.check_status | changeStatus}}<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon>
								</view>
							</view>
							<view class="item">
								<view class="content u-flex-1">
									<view class="title u-line-2">{{typeText}}：￥{{item.money}}</view>
									<!-- <view class="type">{{ item.goods_attr }}4654987</view> -->
								</view>
								<view class="right u-flex-1  u-line-1">{{item.customer.name}}</view>
							</view>
							<view class="bottom">
								<view class="client_time">日期：{{timeFormats(item.create_time)}}</view>
								<view class="u-flex">
									<block v-if="item.check_status == 0 ||item.check_status == 1">
										<view class="btn u-m-l-15 entity" :style="{backgroundColor: vuex_theme.color}" @click.stop="onCheck(item.id)">审核</view>
									</block>
								</view>
							</view>
						</view>
						<u-loadmore :status="listStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</view>
	</view>
</template>

<script>
	import { getImgUrl} from '@/common/mUtils'
	export default {
		data() {
			return {
				oldScrollTop: 0,
				scrollTop: -1,
				search: {
					fontSise: '18px'
				},
				keyword: '',
				dataList: [],
				tapList: [
					{
						id: 'contract',
						name: '合同审批 '
					},
					{
						id: 'receivables',
						name: '回款审批'
					},
					{
						id: 'invoice',
						name: '发票审批'
					}
				],
				current: 0,
				types: 'contract',
				dx: 0,
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 1,
				pageSize: 20,
				lastPage: false,
				listStatus: 'loadmore',
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
			changeStatus(val){
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
				this.current = parseInt(e.type)
				switch (this.current){
					case 0:
						this.types = 'contract'
						break;
					case 1:
						this.types = 'receivables' 
						break
					case 2:
						this.types = 'invoice' 
						break
					default:
						break;
				}
			}
		},
		onShow(){
			this.getList();
		},
		computed: {
			typeText(){
				if(this.current == 0) {
					return '合同金额'
				}
				if(this.current == 1) {
					return '回款金额'
				}
				if(this.current == 2) {
					return '发票金额'
				}
			}
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
				this.types = this.tapList[index].id
				this.page = 1
				this.lastPage = false
				this.getList()
			},
			// 页面数据
			getList(isNextPage,pages) {
				this.$u.get('crm.backlog/index', {
					types: this.types,
					sort_by: 'id',
					sort_order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
				}).then(res => {
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
				switch (this.current){
					case 0:
						this.$u.route('pages/contract/index',{
							id: val.id,
							type: 'edit'
						});
						break;
					case 1:
						this.$u.route('pages/receivables/manage',{
							id: val.id,
							type: 'edit'
						});
						break
					case 2:
						this.$u.route('pages/invoice/apply',{
							type: 'edit',
							id: val.id
						});
						break
					default:
						break;
				}
			},
			// 审核
			onCheck(id) {
				let url = ''
				switch (this.current){
					case 0:
						url = 'pages/backlog/check'
						break;
					case 1:
						url = 'pages/backlog/receivablesCheck' 
						break
					case 2:
						url = 'pages/invoice/details' 
						break
					default:
						break;
				}
				this.$u.route(url,{
					id: id,
				});
			},
			// 添加客户
			onAdd(){
				this.$u.route('pages/receivables/manage', {
					type: 'add'
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



</style>
