<template>
	<view class="container">
		<!-- 顶部导航 -->
		<u-navbar :is-back="true">
			<view class="slot-wrap">
				<u-search style="width:100%" placeholder="搜索姓名、账号" v-model="keyword" :input-style="search"  :show-action="false" @change="onSearch"></u-search>
			</view>
		</u-navbar>
		<view class="wrap">
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px;'+ 'width:100%'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="adminList.length > 0">
						<view class="client" v-for="(item, index) in adminList" :key="index">
							<!-- 顶部：姓名和状态 -->
							<view class="top">
								<view class="left">
									<view class="store">{{item.realname || item.username}}({{item.username}})</view>
								</view>
								<view class="right">
									<text :class="item.is_open == 1 ? 'status-on' : 'status-off'">
										{{item.is_open == 1 ? '正常' : '禁用'}}
									</text>
								</view>
							</view>
							<!-- 中间：动态字段渲染 -->
							<view class="field-wrap">
								<view class="field-item" v-for="(f, fi) in displayFields" :key="fi" v-if="getNestedValue(item, f.field)">
									<text class="field-label">{{f.title}}：</text>
									<text class="field-value">{{getNestedValue(item, f.field)}}</text>
								</view>
							</view>
							<!-- 底部：操作按钮 -->
							<view class="bottom">
								<view class="client_time">最后登录：{{timeFormats(item.logintime)}}</view>
								<view class="u-flex">
									<view class="btn entity" @click.stop="onEdit(item.admin_id)" :style="{backgroundColor: vuex_theme.color}">编辑</view>
									<view class="btn entity u-m-l-15" @click.stop="onDel(item.admin_id)" v-if="item.admin_id != 1" style="background-color:#f56c6c">删除</view>
								</view>
							</view>
						</view>
						<u-loadmore :status="listStatus"></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</view>
		<!-- 底部按钮 -->
		<view class="bottom-btn u-border-top" >
			<view class="btn entity" :style="{backgroundColor: vuex_theme.color}"  @click="onAdd">
				<u-icon class="u-p-b-5" name="plus"  size="30" color="#FFF"></u-icon>
				<text class="u-m-l-15">添加</text>
			</view>
		</view>
	</view>
</template>

<script>
	import { processingImages,getImgUrl} from '@/common/mUtils'
	export default {
		data() {
			return {
				keyword: '',
				priceShow: false,
				oldScrollTop: 0,
				scrollTop: -1,
				search: {
					fontSise: '18px'
				},
				adminList: [],
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
				fields: [
					{field: 'admin_id', title: 'ID'},
					{field: 'username', title: '用户名'},
					{field: 'realname', title: '真实姓名'},
					{field: 'authGroup.title', title: '部门岗位'},
					{field: 'authRole.name', title: '角色'},
					{field: 'mubiao', title: '月目标'},
					{field: 'ticheng', title: '佣金(%)'},
					{field: 'email', title: '联系邮箱'},
					{field: 'phone', title: '联系电话'},
					{field: 'wechat', title: '联系微信'},
					{field: 'is_open', title: '状态', type: 'switch'},
					{field: 'isphone', title: '查看手机号', type: 'switch'}
				],
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
					case 'normal':
						return '正常'
						break;
					case 'hidden':
						return '隐藏'
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
			this.getAdminList()
			uni.$on('refreshMemberList', () => {
				this.page = 1
				this.lastPage = false
				this.getAdminList()
			})
		},
		onShow(){
		},
		onUnload() {
			uni.$off('refreshMemberList')
		},
		computed: {
			displayFields() {
				return this.fields.filter(f => 
					!['admin_id','username','realname','is_open','isphone'].includes(f.field)
				);
			}
		},
		methods: {
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
			getAdminList(isNextPage, pages) {
				this.$u.get('auth/adminList', {
					search: this.keyword,
					sort_by: 'admin_id',
					sort_order: 'desc',
					offset: ((pages || 1) - 1) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({}),
					op: JSON.stringify({})
				}).then(res => {
					if(res.code == 1) {
						// 不够一页
						if (res.data.length < this.pageSize) {
							this.listStatus = 'nomore'
						}
						// 判断最后一页
						if(res.data.length == 0) {
							this.lastPage = true
							return
						}
						// 总数判断
						if(res.count && (this.adminList.length + res.data.length) >= res.count) {
							this.lastPage = true
							this.listStatus = 'nomore'
						}
						// 第二页开始追加
						if(isNextPage) {
							this.adminList = this.adminList.concat(res.data)
							return
						}
						this.adminList = res.data
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
					this.getAdminList(true,++this.page)
					if(this.adminList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 点击搜索
			onSearch() {
				this.page = 1
				this.lastPage = false
				this.getAdminList()
			},
			// 查看详情
			onItem(val) {

			},
			// 支持嵌套对象取值（如 authGroup.title）
			getNestedValue(obj, path) {
				const val = path.split('.').reduce((o, k) => (o || {})[k], obj);
				if (val === null || val === undefined || val === '' || val === 0) return '';
				return val;
			},
			// 删除管理员
			onDel(id) {
				uni.showModal({
					title: '提示',
					content: '确定删除该管理员？',
					success: (res) => {
						if (res.confirm) {
							this.$u.get('auth/adminDel', {admin_id: id}).then(res => {
								if(res.code == 1) {
									uni.showToast({title: '删除成功', icon: 'success'});
									this.page = 1;
									this.lastPage = false;
									this.getAdminList();
								} else {
									uni.showToast({title: res.msg || '删除失败', icon: 'none'});
								}
							})
						}
					}
				})
			},
			// 编辑
			onEdit(id) {
				this.$u.route('pages/member/setMember',{
					type: 'edit',
					id: id
				});
			},
			// 添加
			onAdd(){
				this.$u.route('pages/member/setMember',{
					type: 'add'
				})
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
	padding: 20rpx 20rpx 150rpx;
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
.field-wrap {
	display: flex;
	flex-wrap: wrap;
	margin-top: 16rpx;
}
.field-item {
	min-width: 50%;
	box-sizing: border-box;
	padding: 8rpx 20rpx 8rpx 0;
	font-size: 26rpx;
	.field-label {
		color: #666;
	}
	.field-value {
		color: #333;
		word-break: break-all;
	}
}
.status-on {
	color: #67c23a;
	font-size: 26rpx;
}
.status-off {
	color: #f56c6c;
	font-size: 26rpx;
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
