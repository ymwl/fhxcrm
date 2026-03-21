<template>
	<view class="container">
		<!-- 顶部导航 -->
		<u-navbar :is-back="true" title="我的待办列表" :custom-back="onBack"></u-navbar>
		<view class="wrap">
			<view class="page-box">
				<block v-if="list.length > 0">
					<view class="backlog" v-for="(item, index) in list" :key="index" @click="onBacklog(item,index)">
						<view class="item u-flex">
							<view class="content u-flex-1">
								<view class="title u-flex">
									<u-icon :name="item.icon" custom-prefix="custom-icon" :color="item.color" size="55"></u-icon>
									<text class="u-p-l-15">{{item.name}}</text>
								</view>
							</view>
							<view class="right">
								<!-- <view class="backlog_time u-m-b-15">{{timeFormats(item.create_time)}}</view> -->
								<u-tag size="mini" :show="item.count != 0" :text="item.count" shape="circle" mode="dark" :bg-color="vuex_theme.color"/>
							</view>
						</view>
					</view>
				</block>
				<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
			</view>
		</view>
	</view>
</template>

<script>
	import {get_date} from '@/common/mUtils'
	export default {
		data() {
			return {
				type: 'oneself',
				list: [
					{
						id: 0,
						name: '待跟进客户',
						icon: 'xindekehuyong',
						count: 0,
						subscribe: 'clues_customer',
						type: 'communicate',
						color: '#1D6FFF',
						url: '/pages/client/index',
					},
					{
						id: 1,
						name: '待跟进商机',
						icon: 'shangjizhongxin',
						count: 0,
						subscribe: 'flow_business',
						type: 'business',
						color: '#6966FD',
						url: '/pages/business/index',
					},
					{
						id: 2,
						name: '待审批合同',
						icon: 'qiandinghetong',
						count: 0,
						subscribe: 'flow_contract',
						type: 'contract_flow',
						color: '#EB7411',
						url: '/pages/backlog/list?type=0',
					},
					{
						id: 3,
						name: '待审批回款',
						icon: 'shenpi',
						count: 0,
						subscribe: 'flow_receivables',
						type: 'receivables_flow',
						color: '#FBB03B',
						url: 'pages/backlog/list?type=1',
					},
					{
						id: 4,
						name: '待回款',
						icon: 'shoukuan',
						count: 0,
						subscribe: 'flow_receivables',
						type: 'contract_return',
						color: '#FBB03B',
						url: '/pages/contract/list/index?current=6',
					},
					{
						id: 5,
						name: '待审批发票',
						icon: 'shenpi',
						count: 0,
						subscribe: 'flow_invoice',
						type: 'invoice_flow',
						color: '#BB4A96',
						url: '/pages/backlog/list?type=2',
					},
					{
						id: 6,
						name: '待开具发票',
						icon: 'daikaifapiao',
						count: 0,
						subscribe: 'flow_invoice',
						type: 'invoice_opener',
						color: '#BB4A96',
						url: '/pages/invoice/index?type=2',
					},
					{
						id: 7,
						name: '将到期客户',
						icon: 'jijiangshouqing',
						count: 0,
						subscribe: 'clues_customer',
						type: 'lose_num',
						color: '#1D6FFF',
						url: '/pages/client/index',
					},
					{
						id: 8,
						name: '待跟进线索',
						icon: 'genjinjilu',
						count: 0,
						subscribe: 'clues_customer',
						type: 'clues_num',
						color: '#05D199',
						url: '/pages/clues/list',
					},
					{
						id: 9,
						name: '到期合同',
						icon: 'qiandinghetong',
						count: 0,
						subscribe: '',
						type: 'contract_expire',
						color: '#EB7411',
						url: '/pages/contract/list/index?current=5',
					}
				],
				listStatus: 'loadmore',
			};
		},
		filters: {
			
		},
		onReady() {
			
		},
		onLoad(e) {
			this.getData()
		},
		onShow(){
			
		},
		computed: {
			
		},
		methods: {
			onBack(){
				uni.switchTab({url: '/pages/index/index'});
			},
			// 点击待办栏
			onBacklog(val,index){
				// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
				//#ifdef MP-WEIXIN
					this.$reuse.subscriptionInfo(this.list[index].subscribe);
				//#endif
				
				let filterData = {
					filter: {},
					op: {},
					formName: {}
				}
				switch (val.id) {
					case 0:
						filterData.op['next_time'] = 'RANGE';
						filterData.filter['next_time'] ="1970-01-02 00:00"+" - "+get_date(0)+" 23:59";
						if(this.type =="oneself"){
							filterData.op['scene_id'] = '=';
							filterData.filter['scene_id'] = '2';
							filterData.formName['sceneName'] = "我的客户";
						}
						// 储存
						this.$u.vuex('vuex_filter', filterData)
						uni.switchTab({url: val.url});
						// 前往tabBar页面 开启返回按钮
						this.$u.vuex('vuex_back', true)
						break;
					case 1:
						filterData.op['next_time'] = 'RANGE';
						filterData.filter['next_time'] ="1970-01-02 00:00"+" - "+get_date(0)+" 23:59";
						if(this.type =="oneself"){
							filterData.op['scene_id'] = '=';
							filterData.filter['scene_id'] = '8';
							filterData.formName['sceneName'] = "我的商机";
						}
						// 储存
						this.$u.vuex('vuex_bfilter', filterData)
						uni.switchTab({url: val.url});
						// 前往tabBar页面 开启返回按钮
						this.$u.vuex('vuex_back', true)
						break;
					case 7:
						filterData.op['expire_type'] = '=';
						filterData.filter['expire_type'] ="1";
						if(this.type =="oneself"){
							filterData.op['scene_id'] = '=';
							filterData.filter['scene_id'] = '2';
							filterData.formName['sceneName'] = "我的客户";
						}
						// 储存
						this.$u.vuex('vuex_filter', filterData)
						uni.switchTab({url: val.url});
						// 前往tabBar页面 开启返回按钮
						this.$u.vuex('vuex_back', true)
						break;
					case 8:
						filterData.op['next_time'] = 'RANGE';
						filterData.filter['next_time'] ="1970-01-02 00:00"+" - "+get_date(0)+" 23:59";
						if(this.type =="oneself"){
							filterData.op['scene_id'] = '=';
							filterData.filter['scene_id'] = '11';
							filterData.formName['sceneName'] = "我的线索";
						}
						// 储存
						this.$u.vuex('vuex_cluesfilter', filterData)
						uni.switchTab({url: val.url});
						// 前往tabBar页面 开启返回按钮
						this.$u.vuex('vuex_back', true)
						break;
					default:
						console.log(val.url)
						this.$u.route(val.url)
						break;
				}
			},
			// 格式化时间
			timeFormats(val) {
				if(val) {
					return this.$u.timeFormat(val, ' hh:MM');
				} else {
					return '--'
				}
			},
			// 获取通知数量
			getData() {
				this.$u.api.onGetNotice({
					type: this.type
				}).then(res => {
					let inforData = res.data
					if(res.code == 1) {
						this.list.forEach((item,index) => {
							for (const key in res.data) {
								if (Object.hasOwnProperty.call(res.data, key)) {
									if(item.type == key) {
										item.count = res.data[key]
									}
								}
							}
						});
						const compare = (property, desc) => {
							return function (a, b) {
									let value1 = a[property], value2 = b[property]
									if (desc === "desc") {  //降序
											return value2 - value1
									} else {                 //升序
											return value1 - value2
									}
							}
						}
						this.list.sort(compare("count",'desc'))
					}
				}).catch(res =>{
					
				})
			},
			
		}
	}
</script>

<style lang="scss">
page {
	background-color: #F7F7F7;
}
.container {
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
  .backlog_time {
    color: #777;
    font-size: 26rpx;
  }
}
.backlog {
	background-color: #ffffff;
	margin-bottom: 20rpx;
	border-radius: 20rpx;
	box-sizing: border-box;
	padding: 20rpx;
	font-size: 28rpx;
	.item {
		display: flex;
		// margin: 20rpx 0 0;
		.content {
			flex: 1;
			.title {
				font-size: 32rpx;
				line-height: 50rpx;
        font-weight: 700;
			}
			.type {
				margin: 10rpx 0;
				font-size: 24rpx;
				color: $u-tips-color;
			}
		}
		.right {
			// margin-left: 10rpx;
			text-align: right;
			.number {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: auto;
				color: #fff;
				font-size: 18rpx;
        min-width: 40rpx;
        min-height: 40rpx;
        background-color: red;
        border-radius: 50%;
			}
		}
	}
}
.wrap {
	display: flex;
	flex-direction: column;
	width: 100%;
}




</style>
