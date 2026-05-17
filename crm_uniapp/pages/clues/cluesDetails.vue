<template>
	<view class="container">
		<view class="bare" :style="{backgroundColor: vuex_theme.color}">
			<!-- 顶部导航高度 -->
			<view class="statusBar"></view>
		</view>
		<view class="notice">
			<view class="belong">
				<view class="left u-flex-1">
					<view class="store">{{clues.name}}</view>
				</view>
				<view :style="{color: clues.status == 1 ? '#19be6b' : vuex_theme.color}">{{clues.status | changeStatus}}<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon>
				</view>
			</view>
			<!-- 标签 -->
			<view class="tap" v-if="clues.tags !='' ">
				<u-tag :text="item"  size="min"  v-for="(item,i) in clues.tags"  :key="i" />
			</view>
			<view class="relation u-flex">
				<view class="left" @click="call(clues.mobile)">手机：<text class="dial">{{clues.mobile}}</text></view>
				<view class="right u-flex">
					<u-icon name="yunhu" custom-prefix="custom-icon" size="40"></u-icon>
					<text class="dial" @click="cloudcall(clues.mobile)">云呼叫</text>
				</view>
			</view>
			<view class="bottom u-flex">
				<view class="client_time">最后跟进：{{timeFormats(clues.follow_time)}}</view>
				<view class="right">{{clues.owner_user ? clues.owner_user.realname : ''}}</view>
			</view>
			<view class="more u-flex" @click="onLookMore">查看更多<u-icon name="arrow-down" size="35" ></u-icon></view>
		</view>
		<!-- 工具项 -->
		<view class="region u-m-t-35">
			<view class="u-border-bottom">
				<u-tabs :list="tapList" bar-width="80" :active-color="vuex_theme.color" :is-scroll="false" :current="current" @change="change"></u-tabs>
			</view>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop"  @scrolltolower="reachBottom">
				<view class="page-box">
					<!-- 跟进记录 -->
					<block v-if="current == 0" >
						<block v-if="recordList.length > 0">
							<view class="list-view" v-for="(item, index) in recordList" :key="index">
								<view class="top">
									<view class="left">{{item.content ? item.content : '--'}}</view>
								</view>
								<view class="item" >
									<view class="left">
										<view class="image" v-for="(i,x) in item.files" :key="x">
											<u-image width="100%" height="100%" :src="i.url" @click="lookImges(i.url)"></u-image>
										</view>
									</view>
									<view class="content">
										<!-- <view class="title u-line-2">{{ item.cproduct.product.name }}</view>
										<view class="type">产品编号: {{ item.product_no }}</view>
										<view class="type">上传人: {{ item.engineer.name }}</view> -->
									</view>
								</view>
								<view class="bottom">
									<view class="time">{{timeFormats(item.create_time)}}</view>
									<view class="way">{{item.record_type_text ? item.record_type_text : '--'}}</view>
								</view>
							</view>
							<u-loadmore :status="listStatus" ></u-loadmore>
						</block>
						<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
					</block>
				</view>
			</scroll-view>
		</view>
		<!-- 转移客户选择目标员工组件 -->
		<staff-list ref="adminList" @onShift="onConfirm"></staff-list>
		<!-- 上拉菜单 -->
		<u-action-sheet :list="moreList" v-model="moreShow" @click="moreClick"></u-action-sheet>
		<!-- 底部按钮 -->
		<view class="bottom-btn u-border-top" >
			<u-button class="u-m-r-15" size="medium" @click="moreShow = true">更多</u-button>
			<view class="btn entity" @click="onAdd" :style="{backgroundColor: vuex_theme.color}">跟进</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				lookMore: false,
				moreShow: false,
				clues_id: '',
				clues: {},
				scrollTop: '',
				tapList: [
					{
						name: '跟进记录'
					},
				],
				moreList: [
					{
						text: '编辑',
					},
					{
						text: '转客户',
					},
					{
						text: '放入线索池',
					},
					{
						text: '转移线索',
					},
					{
						text: '删除',
					},
					{
						text: '发邮件',
					},
					{
						text: '发信息',
					},
				],
				recordList:[],
				current: 0,
				navbar: false,
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 0,
				pageSize: 20,
				lastPage: false,
				listStatus: 'loadmore',
			};
		},
		onLoad(e) {
			this.clues_id = e.id ? e.id: ''
			// 小程序顶部样式修改
			uni.setNavigationBarColor({
				frontColor: this.vuex_theme.bgColor,
				backgroundColor: this.vuex_theme.color,
				animation: {
					duration: 0,
					timingFunc: 'easeIn'
				}
			})
			this.getData()
		},
		onShow(){
			switch (this.current) {
					case 0:
					this.page = 0
					this.lastPage = false
					this.getCustomerRecord()
					break;
				default:
					break;
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
						that.scrollHeight = pH - data.top - 80  //计算高度：元素高度=窗口高度-元素距离顶部的距离（data.top）
					}).exec()
				}
			})
		},
		filters: {
			changeStatus(val){
				switch (val) {
					case -1:
						return '无效线索'
						break;
					case 0:
						return '未转客户'
						break;
					case 1:
						return '已转客户'
						break;
					default:
						return '--'
						break;
				}
			},
			sexType(val){
				switch (val) {
					case -1:
						return '未知'
						break;
					case 0:
						return '女'
						break;
					case 1:
						return '男'
						break;
					default:
						return '--'
						break;
				}
			},
		},
		methods: {
			// 确定转移客户
			onConfirm(val){
				this.$u.post('crm.clues.index/divert',{ids: this.clues_id,admin_id: val}).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: res.msg,
							icon: 'success',
							duration: 2000
						})
						this.$refs.adminList.companyShow = false
						this.$refs.adminList.admin_id = ''
						this.getData()
					}
				})
			},
			// 查看更多
			onLookMore() {
				this.$u.route('pages/clues/details',{
					id: this.clues_id,
				});
			},
			// 拨打电话
			call(p) {
				uni.makePhoneCall({
					phoneNumber: p 
				});
			},
			// 云呼叫
			cloudcall(p){
				this.$u.post('crm.setting.cloudcall/call',{
					type: 'clues',
					typeid: this.clues_id,
					field: p,
					prefix: '',
				}).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: '操作成功',
							icon: 'success',
							duration: 2000
						})
						setTimeout(() => {
							this.$u.route('pages/clues/followUp',{
								id: this.clues_id
							});
						}, 1000);
					}
				})
			},
			// 更多操作
			moreClick(index){
				switch (index) {
					case 0:
						// 编辑客户
						this.$u.route('pages/clues/index',{
							id: this.clues_id,
							type: 'edit',
						});
						break;
					case 1:
						// 转化为客户
						this.$u.route('pages/clues/index',{
							type: 'transform',
							id: this.clues_id,
						})
						break;
					case 2:
						uni.showModal({
							title: '提示',
							content: '确定放入线索池吗？',
							success: function (res) {
								if (res.confirm) {
									this.$u.post('crm.clues.index/discard',{
											ids: this.clues_id
										}).then(res => {
											if(res.code == 1 ) {
												// 提示
												uni.showToast({
													title: '操作成功',
													icon: 'success',
													duration: 2000
												})
												setTimeout(() => {
													uni.navigateBack();
												}, 1500);
											}
										})
								} else if (res.cancel) {
									console.log('用户点击取消');
								}
							}.bind(this)
						});
						break;
					case 3:
						// 转移客户
						this.$refs.adminList.companyShow = true
						break;
					case 4:
						uni.showModal({
							title: '提示',
							content: '确定删除该线索吗？',
							success: function (res) {
								if (res.confirm) {
									this.$u.post('crm.clues.index/delete',{
											ids: this.clues_id
										}).then(res => {
											if(res.code == 1 ) {
												// 提示
												uni.showToast({
													title: '操作成功',
													icon: 'success',
													duration: 2000
												})
												setTimeout(() => {
													uni.navigateBack();
												}, 1500);
											}
										})
								} else if (res.cancel) {
									console.log('用户点击取消');
								}
							}.bind(this)
						});
						break;
					case 5:
						this.$u.route('pages/send/index',{
							type:'email',
							id: this.clues_id,
							types: 'clues',
						}); 
						break;
					case 6:
						this.$u.route('pages/send/index',{
							type:'note',
							id: this.clues_id,
							types: 'clues',
						}); 
						break;
					default:
						break;
				}
			},
			// 查看详情
			lookDetails(val){
				switch (this.current) {
					case 0:
						break;
					case 1:
						this.$u.route('pages/contacts/detail',{
							id: val.id
						});
						break;
					case 2:
						this.$u.route('pages/business/detail',{
							id: val.id
						});
						break;
					default:
						break;
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
			// 获取详情
			getData() {
				this.$u.get('crm.clues.index/edit',{ids: this.clues_id}).then(res => {
					if(res.code == 1 ) {
						res.data.tags = res.data.tags.split(',')
						this.clues = res.data
						if(this.clues.customer_id) {
							let obj = {
								name: '客户详情'
							}
							this.tapList.push(obj)
						}
					}
				})
			},
			// 获取跟进记录
			getCustomerRecord(isNextPage,pages) {
				this.$u.get('crm.clues.record/index',{
					sort_by: 'id',
					sort_order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({types_id: this.clues_id,}),
					op: JSON.stringify({types_id:"="})
				}).then(res => {
					if(res.code == 1 ) {
						// 不够一页
						if (res.data.rows.length < 10) {
							this.listStatus = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.recordList = this.recordList.concat(res.data.rows)
							return 
						}
						this.recordList = res.data.rows
							// 最后一页
						if(res.data.total == this.recordList.length ) {
							this.lastPage = true
						} 
					}
				})
			},
			// 滚动到底部
			reachBottom() {
				switch (this.current) {
					case 0:
						if(this.lastPage || this.listStatus == 'loading') return ;
						this.listStatus = 'loading'
						setTimeout(() => {
							if(this.lastPage) return ;
							this.getCustomerRecord(true,++this.page)
							if(this.recordList.length >= 10) this.listStatus = 'loadmore';
							else this.listStatus = 'loading';
						}, 1200)
						break;
					default:
						return '--'
						break;
				}
			},
			// 切换导航栏
			change(index) {
				switch (index) {
					case 0:
						this.current = index;
						this.page = 0
						this.lastPage = false
						this.getCustomerRecord()
						break;
					case 1:
						this.$u.route('pages/client/customerDetails',{
							id: this.clues.customer_id
						});
						break;
					default:
						break;
				}
			},
			// 商机跟进
			follow(id) {
				this.$u.route('pages/business/followUp',{
					id: id
				});
			},
			// 跟进客户
			onAdd(e) {
				this.$u.route('pages/clues/followUp',{
					id: this.clues_id
				});
			},
			// 预览图片
			lookImges(url) {
				uni.previewImage({
					urls: [url],
					longPressActions: {
						itemList: ['发送给朋友', '保存图片', '收藏'],
						success: function(data) {
						},
						fail: function(err) {
							console.log(err.errMsg);
						}
					}
        });
			}
		},
	}
</script>

<style lang="scss" >
.container {
	background-color: #F7F7F7 !important;
	// min-height: 100vh;
}
.status_bar {
	height: var(--status-bar-height);
	width: 100%;
}
.bare {
	color: #fff;
	background-color: #FE644A; 
}
.notice {
	margin: -68px 8px 10px 8px;
	left: 0;
	right: 0;
	background-color: #fff;
	border-radius: 10px;
	padding: 25rpx 20rpx 35rpx;
	.belong {
		display: flex;
		justify-content: space-between;
		.left {
			display: flex;
			align-items: center;
			padding-right: 25rpx;
			.store {
				font-size: 30rpx;
				font-weight: bold;
				word-break: break-all;
			}
		}
		.right {
			color: #FF7159;
		}
		.right1 {
			color: $u-type-success;
		}
	}
	.u-tag {
		margin-right: 4rpx;
		font-size: 24rpx;
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
	.relation {
		justify-content: space-between;
		margin-top: 15rpx; 
		font-size: 26rpx;
		.dial {
			color: #2979ff;
			font-size: 14px;
			border-bottom: 1px solid #2979ff;
			padding-bottom: 0px;
		}
	}
	.bottom {
		margin-top: 25rpx;
		justify-content: space-between;
		.client_time {
			color: #777;
  		font-size: 26rpx;
		}
	}
	.more {
		padding-top: 25rpx; 
		justify-content: center;
		color: #2979ff;
	}
	.details {
		padding: 45rpx 0rpx 0rpx;
		.item {
			flex: 1;
			margin-bottom: 25rpx;
			.text {
				font-size: 25rpx; 
				color: #777;
			}
		}
	}
}
.region {
	// background-color: #fff;
}
.statusBar {
	position: relative;
	z-index: -1;
	height: 188rpx;
}

.page-box {
	padding: 20rpx 20rpx 45rpx;
}
.list-view {
	width: 710rpx;
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
			word-break:break-all;
			width: 100%;
			.store {
				margin: 0 10rpx;
				font-size: 28rpx;
				font-weight: bold;
			}
		}
		.right {
			color: #F76046;
		}
	}
	.item {
		display: flex;
		margin: 20rpx 0 0;
		.left {
			margin-right: 20rpx;
			.image {
				width: 120rpx;
				height: 120rpx;
				border-radius: 10rpx;
			}
			.video {
				width: 140rpx;
				height: 140rpx;
			}
		}
		.content {
			flex: 1;
			.title {
				font-size: 28rpx;
				line-height: 50rpx;
			}
			.type {
				margin: 10rpx 0;
				font-size: 26rpx;
				color: $u-tips-color;
			}
			.delivery-time {
				color: #e5d001;
				font-size: 24rpx;
			}
		}
	}
	.contact {
		padding: 25rpx 0;
		display: flex;
		justify-content: space-between;
		.name {

		}
		.dial {
			color: #2979ff;
			font-size: 14px;
			border-bottom: 1px solid #2979ff;
			padding-bottom: 0px;
		}
	}
	.bottom {
		display: flex;
		margin-top: 10rpx;
		justify-content: space-between;
		align-items: center;
		.time {
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
.bottom-btn {
	position: fixed;
	display: flex;
	align-items: center;
	justify-content: flex-end;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 25rpx 30rpx 45rpx;
	background-color: #fff;
	z-index: 100;
	.btn {
		float: right;
		line-height: 43rpx;
		padding: 15rpx 36rpx;
		border-radius: 5px;
		font-size: 28rpx;
		text-align: center;
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


</style>
