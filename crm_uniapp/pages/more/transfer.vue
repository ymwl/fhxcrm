<template>
	<view class="container">
		<!-- 顶部导航 -->
		<u-navbar :is-back="true">
			<view class="slot-wrap">
				<u-search style="width:100%" placeholder="搜索客户名称、联系电话" v-model="keyword" :input-style="search"  :show-action="false" @change="onSearch"></u-search>
			</view>
		</u-navbar>
		<!-- tab选项卡 -->
		<view class="wrap">
      <!-- 排序 -->
			<view class="sort u-border-bottom" >
        <u-dropdown ref="uDropdown" :close-on-click-mask="false">
          <u-dropdown-item :title="'筛选'+ (pr_user_id ?'(' + pr_user_name + ')' : '')">
            <view class="slot-content"  style="background-color: #ffffff;">
							<view class="search u-border-bottom">
								<u-search shape="square" v-model="adminkeyword" :show-action="false" :clearabled="true"  placeholder="输入员工账号、姓名搜索" @change="adminSearch"></u-search>
							</view>
							<scroll-view scroll-y style="height: 650rpx;width: 100%;" @scrolltolower="adminBottom">
								<view class="list">
									<block v-if="companyList.length > 0">
										<view class="u-m-b-45">
											<view class="item u-flex u-border-bottom" v-for="(item,index) in companyList" :key="index" @click="oncompany(item,'筛选')">
                        <view class="title">{{item.username}} <text v-if="item.realname">（{{item.realname}}）</text></view>
												<view class="check-icon">
													<u-icon v-if="pr_user_id == item.admin_id" name="checkmark" color="#2979ff" size="38"></u-icon>
												</view>
											</view>
										</view>
										<u-loadmore :status="adminStatus" ></u-loadmore>
									</block>
									<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
								</view>
							</scroll-view>
							<view class="bottom_btn u-border-top">
								<u-button size="medium" @click="closeDropdown(true)">取消</u-button> 
								<u-button class="u-m-l-15" type="primary"  @click="closeDropdown(false)" size="medium">确定</u-button>
							</view>
            </view>
          </u-dropdown-item>
        </u-dropdown>
				<view class="right-text u-flex">
					 <u-checkbox-group>
              <u-checkbox v-model="checked" 	@change="checkedAll" >全选</u-checkbox>
            </u-checkbox-group>
          <view class="right-btn" @click="moreSelect('more')">转移</view>
				</view>
			</view>
			<scroll-view scroll-y class="sv" :style="{height:scrollHeight+'px'}" :scroll-top="scrollTop" @scroll="scroll"  @scrolltolower="reachBottom">
				<view class="page-box">
					<block v-if="list.length > 0">
						<u-checkbox-group @change="checkboxGroupChange">
							<view class="client" v-for="(item, index) in list" :key="index" >
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
								<view class="item" @click.stop="onItem(item)">
									<view class="content">
										<view class="title u-line-2">{{item.phone ? item.phone :item.email}}</view>
									</view>
                  <view class="right">负责人：{{item.pr_user}}</view>
								</view>
								<view class="bottom">
                  <view class="client_time" v-if="item.next_up_time > 0">下次跟进：{{timeFormats(item.next_up_time)}}</view>
								  <view class="client_time" v-else>最后跟进：{{timeFormats(item.last_up_time)}}</view>
									<view class="u-flex">
										<view class="btn u-m-l-15 entity" :style="{backgroundColor: vuex_theme.color}" @click.stop="deliver(item.id,index)">转移</view>
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
		<!-- 选择转移人 -->
		<u-popup class="slot-content" mode="bottom" border-radius="38"  v-model="companyShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择目标员工</text> 
				<view class="" @click="companyShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="adminkeyword" :show-action="false" :clearabled="true"  placeholder="输入员工账号、姓名搜索" @change="adminSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="adminBottom">
				<view class="list">
					<block v-if="companyList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in companyList" :key="index" @click="oncompany(item,'选择')">

                <view class="title">{{item.username}} <text v-if="item.realname">（{{item.realname}}）</text></view>
								<view class="check-icon">
									<u-icon v-if="admin_id == item.admin_id" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="adminStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
			<view class="bottom_btn u-border-top">
				<u-button size="medium" @click="onCliskDivert(false)">取消</u-button> 
				<u-button class="u-m-l-15" type="primary"  @click="onCliskDivert(true)" size="medium">转移</u-button>
			</view>
		</u-popup>
		
	</view>
</template>

<script>
	import { processingImages,getImgUrl} from '@/common/mUtils'
	export default {
		data() {
			return {
				companyShow: false,
				adminkeyword: '',
				adminPage: 1,
				companyList: [],
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
				status: '',
				pr_user_id: '',
				pr_user_name: '',
        admin_id: '',
        username: '',
				idArr: '',
			};
		},
		filters: {
			
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
			this.onSelectpage()
			this.getCommon();
		},
		onShow(){

		},
		watch : {
			companyShow:function(val) {
				if(!val) {
					this.adminkeyword = ''
				}
			},
        
    },
		computed: {
			
		},
		methods: {
			changeStatus(val){
				switch (val) {
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
        let filter={},op={};
			  if(!this.$u.test.empty(this.keyword)){
          filter = {
            name: this.keyword,
          }
          op = {
            name: 'LIKE',
          }
        }

				if(this.pr_user_id) {
					filter.pr_user = this.pr_user_name
					op.pr_user = '='
				}
        filter.scope=3
				this.$u.api.getCustomerList({
					sort: 'id',
					order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify(filter),
					op: JSON.stringify(op)
				}).then(res => {
					if(res.code == 1 ) {
						res.data.rows.forEach((item,index) => {
							item.checked = false
							item.tags = item.tags ? item.tags.split(',') : ''
							item.follow_time = this.timeFormats(item.follow_time)
							item.next_time = this.timeFormats(item.next_time)
							item.issuccess = this.changeStatus(item.issuccess)
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
				this.lastPage = true
				this.getCommon()
			},
			// 查看详情
			onItem(val) {
				this.$u.route('pages/client/customerDetails',{
					id: val.id
				});
			},
			// 获取负责人
			onSelectpage(isNextPage,pages) {
				this.$u.api.onCommonSelectpage({
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.adminkeyword,
          keyField: 'admin_id',
          showField: 'username,realname',
          "q_word": this.adminkeyword,
          "searchField": "username,realname",
					model:'admin',
				}).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastAdmin = true
						} 
						//不够一页
						if (res.data.list.length <= this.pageSize) {
							this.adminStatus = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.companyList = this.companyList.concat(res.data.list)
							return 
						}
						this.companyList = res.data.list
					}
				})
			},
			// 滚动到底部加载更多
			adminBottom() {
				if(this.lastAdmin || this.adminStatus == 'loading') return ;
				this.adminStatus = 'loading'
				setTimeout(() => {
					if(this.lastAdmin) return ;
					this.onSelectpage(true,++this.adminPage)
					if(this.companyList.length >= 10) this.adminStatus = 'loadmore';
					else this.adminStatus = 'loading';
				}, 1200)
			},
			// 选择员工
			oncompany(val,type) {
        console.log('选择员工：',val,type)
				if(type == '筛选'){
					this.pr_user_id = val.admin_id
          this.pr_user_name = val.username
				} else {
					this.admin_id = val.admin_id
					this.username = val.username
        }
			},
			// 清空
			closeDropdown(val) {
				/*if(this.pr_user_id){
					this.page = 0
					this.lastPage = false
					this.checked = false
					this.getCommon()
				}*/
        if(val) {
          this.pr_user_id = ''
          this.adminkeyword = ''
        }
        this.page = 0
        this.lastPage = false
        this.checked = false
        this.getCommon()


				this.$refs.uDropdown.close();
			},
			// 选择搜索
			adminSearch() {
				this.lastAdmin = false
				this.onSelectpage()
			},
			// 单个转移
			deliver(id,index) {
				this.idArr = id
				this.companyShow = true
			},
			// 多个转移
			moreSelect(){
				let idArr = []
				this.list.forEach((item,index)=>{
					if(item.checked) {
						idArr.push(item.id)
					}
				})
				if(idArr.length == 0){
					uni.showToast({
						title: '请先勾选客户',
						icon: 'none',
						duration: 2000
					})
					return
				}
				this.idArr = idArr.join(',')
				this.companyShow = true
			},
			// 确认转移 、取消
			onCliskDivert(val){
				if(val){
					if(this.admin_id == ''){
						uni.showToast({
							title: '请先选择目标员工',
							icon: 'none',
							duration: 2000
						})
						return
					}
					this.$u.api.onDivert({ids: this.idArr,username: this.username}).then(res => {
						if(res.code == 1 ) {
							// 提示
							uni.showToast({
								title: res.msg,
								icon: 'success',
								duration: 2000
							})
							this.companyShow = false
							this.page = 0
							this.lastPage = false
							this.getCommon()
						}else{
              uni.showToast({
                title: res.msg,
                icon: 'none',
                duration: 2000
              })
            }
					})
				} else {
					this.admin_id = ''
					this.companyShow = false
				}
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
  .right-btn {
		line-height: 60rpx;
    width: 160rpx;
    border-radius: 5px;
    font-size: 26rpx;
    text-align: center;
    color: #fff;
    background-color: #2979ff;
	}
	/deep/ .u-dropdown__menu__item {
		justify-content: left !important;
		padding-left: 25rpx;
	}
}
.slot-content {
	.search {
		padding: 30rpx 25rpx;
	}
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
		padding-bottom: 45rpx;
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
	.bottom_btn {
		text-align: right;
		padding: 28rpx 10rpx 28rpx;
	}
}
.u-checkbox-group{
  display: block;
}

</style>
