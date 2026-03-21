<template>
	<view class="container">
		<view class="bare" :style="{backgroundColor: vuex_theme.color}">
			<!-- 顶部导航高度 -->
			<view class="statusBar"></view>
		</view>
		<view class="notice">
			<view class="belong">
				<view class="left u-flex-1">
					<view class="store">{{businessData.name}}</view>
				</view>
				<view :class='colorList[businessData.is_end]' :style="{color: businessData.is_end == 0 ? vuex_theme.color: ''}">{{businessData.is_end | changeStatus}}<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon>
				</view>
			</view>
			<view class="relation">
				<view class="left">{{businessData.remark}}</view>
			</view>
			<view class="bottom u-flex">
				<view class="client_time">预计成交：{{timeFormats(businessData.deal_time)}}</view>
				<view class="right">{{businessData.owner_user ? businessData.owner_user.realname : ''}}</view>
			</view>
			<view class="more u-flex" @click="moreClick(0)">查看更多<u-icon name="arrow-down" size="35" ></u-icon></view>
		</view>
		<!-- 工具项 -->
		<view class="region">
			<view class="u-border-bottom">
				<u-tabs :list="tapList" bar-width="80" :active-color="vuex_theme.color" :is-scroll="false" :current="current" @change="change"></u-tabs>
			</view>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop"  @scrolltolower="reachBottom">
				<!-- 跟进记录 -->
				<block v-if="current == 0" >
					<view class="page-box">
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
									<view class="content"></view>
								</view>
								<view class="bottom">
									<view class="time">{{timeFormats(item.create_time)}}</view>
									<view class="way">{{item.record_type_text ? item.record_type_text : '--'}}</view>
								</view>
							</view>
							<u-loadmore :status="listStatus" ></u-loadmore>
						</block>
						<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
					</view>
				</block>
				<!-- 联系列表 -->
				<block v-if="current == 1">
					<view class="page-box">
						<block v-if="contactList.length > 0">
							<view class="list-view" v-for="(item, index) in contactList" :key="index" @click="lookDetails(item)">
								<view class="contact">
									<view class="name">{{item.contact.name}}（{{item.contact.sex | changeSex}}）{{item.contact.mobile}}</view>
									<view class="dial" @click.stop="callUp(item)">点击拨打</view>
								</view>
							</view>
							<u-loadmore status="nomore" ></u-loadmore>
						</block>
						<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
					</view>
				</block>
				<!-- 意向产品 -->
				<block v-if="current == 2">
					<view class="product">
						<view class="item u-border-bottom"  v-for="(item,index) in businessData.product" :key="index">
							<view class="left">
								<view class="image">
									<u-image width="100%" height="100%" :src="item.src"></u-image>
								</view>
							</view>
							<view class="content">
								<view class="title u-line-2">{{item.info ? item.info.name : ''}}</view>
								<view class="price">￥{{item.price}}</view>
							</view>
							<view class="">x{{item.nums}}</view>
						</view>
						<view class="rental">
							<text class="u-m-r-25">优惠率：{{businessData.discount_rate}}%</text>
							<text class="">总额：￥{{businessData.total_price}}</text>
						</view>
						<view class="btn">
							<u-button type="primary" size="medium " @click="moreClick(0)">修改</u-button>
						</view>
					</view>
				</block>
			</scroll-view>
		</view>
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
				business_id: "",
				shopQrcode: '',
				scrollTop: 0,
				businessData: {},
				moreShow: false,
				moreList: [
					{
						text: '编辑商机',
					},
					{
						text: '关联联系人',
					},
					{
						text: '发邮件',
					},
					{
						text: '发信息',
					},
				],
				tapList: [
					{
						name: '跟进记录'
					},
					{
						name: '联系人'
					},
					{
						name: '意向产品',
					},
					{
						name: '客户信息',
					},
				],
				recordList: [],
				contactList: [],
				current: 0,
				navbar: false,
				background: {
					backgroundColor: '#FE644A'
				},
				performanceList:[],
				regionList: [],
				pH:0, //窗口高度
				scrollHeight:0, //元素的所需高度
				page: 0,
				pageSize: 10,
				lastPage: false,
				listStatus: 'loadmore',
				levelList: [],
				industryList: [],
				sourceList: [],
				colorList: ['succeed','fail','underway'],
			};
		},
		onLoad(e) {
			this.business_id = e.id ? e.id: ''
			// 小程序顶部样式修改
			uni.setNavigationBarColor({
				frontColor: this.vuex_theme.bgColor,
				backgroundColor: this.vuex_theme.color,
				animation: {
					duration: 0,
					timingFunc: 'easeIn'
				}
			})
		},
		onShow(){
			this.getData()
			this.getBusinessRecord()
			this.getContactList()
		},
		onReady() {
			let that = this;
			uni.getSystemInfo({ //调用uni-app接口获取屏幕高度
				success(res) { //成功回调函数
					that.pH = res.windowHeight //windoHeight为窗口高度，主要使用的是这个
					let scrollH = uni.createSelectorQuery().select(".sv"); //想要获取高度的元素名（class/id）
					scrollH.boundingClientRect(data=>{
						let pH = that.pH; 
						that.scrollHeight = pH - data.top - 35//计算高度：元素高度=窗口高度-元素距离顶部的距离（data.top）
					}).exec()
				}
			})
		},
		filters: {
			changeSex(val){
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
						return '--'
						break;
				}
			},
			changeStatus(val){
				switch (val) {
					case 0:
						return '洽淡中'
						break;
					case 1:
						return '成交'
						break;
					case 2:
						return '失败'
						break;
					case 3:
						return '无效'
						break;
					default:
						return '--'
						break;
				}
			},
		},
		methods: {
			// 查看联系人
			lookDetails(val) {
				this.$u.route('pages/contacts/detail',{
					id: val.contacts_id
				});
			},
			// 拨打电话
			callUp(p) {
				uni.makePhoneCall({
					phoneNumber: p.contact.mobile
				});
			},
			// 格式化时间
			timeFormats(val) {
				if(val) {
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM');
				} else {
					return '--'
				}
			},
			// 获取商机数据详情
			getData() {
				this.$u.api.getBusinessEdit({id: this.business_id}).then(res => {
					if(res.code == 1 ) {
						this.businessData = res.data
						// this.homeData.time = res.time
					}
				})
			},
			// 获取商机跟进记录
			getBusinessRecord(isNextPage,pages) {
				this.$u.api.onBusinessRecord({
					business_id: this.business_id,
					sort: 'id',
					order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({name: this.keyword}),
					op: JSON.stringify({name: 'LIKE'})
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
							this.recordList = this.recordList.concat(res.data.rows)
							return 
						}
						this.recordList = res.data.rows
					}
				})
			},
			// 滚动到底部
			reachBottom() {
				if(this.lastPage || this.listStatus == 'loading') return ;
				this.listStatus = 'loading'
				setTimeout(() => {
					if(this.lastPage) return ;
					this.getBusinessRecord(true,++this.page)
					if(this.recordList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 获取联系人
			getContactList(isNextPage,pages) {
				this.$u.api.getBusinessContacts({
					business_id: this.business_id
				}).then(res => {
					if(res.code == 1 ) {
						// 不够一页
						if (res.data.rows.length < 10) {
							this.contactStatus = 'nomore'
						}
						// 最后一页
						if(res.data.rows.length == 0) {
							this.lastContact = true
						} 
						// 第二页开始
						if(isNextPage) {
							this.contactList = this.contactList.concat(res.data.rows)
							return 
						}
						this.contactList = res.data.rows
					}
				})
			},
			// 切换导航栏
			change(index) {
				if(index==3){
					//如果是点击客户详情栏直接跳转到客户详情，这样更详细
					this.$u.route('pages/client/customerDetails',{
						id: this.businessData.customer_id
					});
					return;
				}
				this.current = index;
			},
			// 更多操作
			moreClick(index){
				switch (index) {
					case 0:
						// 编辑商机
						this.$u.route('pages/business/addBusiness/index',{
							id: this.business_id,
							type: 'edit',
						});
						break;
					case 1:
						// 关联联系人
						this.$u.route('pages/contacts/relevance',{
							id: this.business_id,
							cid: this.businessData.customer.id
						});
						break;
					case 2:
						this.$u.route('pages/send/index',{
							type:'email',
							id: this.business_id,
							types: 'business',
						}); 
						break;
					case 3:
						this.$u.route('pages/send/index',{
							type:'note',
							id: this.business_id,
							types: 'business',
						}); 
						break;
					default:
						break;
				}
			},
			// 跟进商机
			onAdd(e) {
				this.$u.route('pages/business/followUp',{
					id: this.business_id
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

<style lang="scss" scoped>
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
		.default {
			color: #FF7159;
		}
		.succeed {
			color: $u-type-success
		}
		.underway {
			color: $u-type-info
		}
		.fail {
			color: $u-type-error
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
}
.region {
	// background-color: #fff;
}
.statusBar {
	position: relative;
	height: 188rpx;
}

.page-box {
	padding: 20rpx 20rpx 140rpx;
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
.product {
	background-color: #fff;
	height: 100%;
	width: 100%;
	.item {
		display: flex;
		padding: 20rpx 20rpx 45rpx;
		.left {
			margin-right: 20rpx;
			.image {
				width: 150rpx;
				height: 150rpx;
				border-radius: 10rpx;
			}
		}
		.content {
			flex: 1;
			.title {
				font-size: 28rpx;
				line-height: 50rpx;
			}
			.price {
				margin: 10rpx 0;
				font-size: 26rpx;
				color: #FF0000;
			}
		}
	}
	.rental {
		font-weight: 600;
		text-align: right;
		font-size: 28rpx;
		padding: 28rpx 20rpx;
	}
	.btn {
		text-align: right;
		margin-right: 45rpx;
	}
}
.customer {
	padding: 0rpx 22rpx;
	background-color: #fff;
	height: 100%;
	width: 100%;
	.option {
    padding: 20rpx 0;
    font-size: 28rpx;
    color: #303133;
    box-sizing: border-box;
    line-height: 70rpx;
		.name {
			padding-right: 20rpx;
		}
		.val {
			flex: 1;
			color: #777;
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
