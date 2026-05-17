<template>
	<view class="container">
		<!-- 浮动顶部导航 -->
		<u-navbar v-if="navbar" :is-back="false" back-icon-color="#fff"  title-color="#fff" :background="{backgroundColor: vuex_theme.color}">
			<view class="slot-wrap " @click="onMore">
				<view class="u-m-r-10">
					<u-avatar :src="vuex_admin.avatar" size="60"></u-avatar>
				</view>
				<view class="u-flex-1 u-line-1">
					<view class="u-font-16 u-line-1" style="color:#fff;width:400rpx">{{vuex_admin.username}}({{vuex_admin.role_name}})</view>
				</view>
			</view>
		</u-navbar>
		<view class="top" :style="{backgroundColor: vuex_theme.color}">
			<!-- 顶部导航高度 -->
			<view class="statusBar" :is-back="false" :style="{ height: statusBarHeight + 'px' }" ></view>
			<view class="u-flex u-p-l-30 u-p-r-20 u-p-b-30">
				<view class="u-m-r-10"  @click="onMore">
					<u-avatar :src="vuex_admin.avatar" size="80"></u-avatar>
				</view>
				<view class="u-flex-1">
					<view class="u-font-16 u-flex" @click="onMore" >
						<view class="u-line-1" style="color:#fff;max-width:180rpx">{{vuex_admin.username}}</view>
            <view class="u-line-1" style="color:#fff;max-width:180rpx">({{vuex_admin.role_name}})</view>
					</view>
					<view class="u-font-14" @click="onGroups" v-if="vuex_admin.group_name">{{vuex_admin.group_name}}<u-icon name="arrow-right" color="#fff" size="28"></u-icon></view>
				</view>
				<view class="share u-m-l-10" :style="{backgroundColor: vuex_theme.color}" @click="onMy">
					<text class="u-m-l-10">{{datatype.text}}/切换</text> 
				</view>
			</view>
		</view>
		<!-- 待办 -->
		<view class="notice" >
			<view class="title u-flex">
				<view class="u-flex">
					<u-icon name="daiban"  custom-prefix="custom-icon"  size="42" color="#29AC66"></u-icon>
					<view class="u-p-l-10">待办（{{datatype.text}}）</view>
				</view>
				<view class="left-icon u-flex" @click="lookMore(1)">
					<u-tag :show="count > 0" size="mini" :text="count" shape="circle" mode="dark" :bg-color="vuex_theme.color"/>
					<u-icon name="arrow-right" ></u-icon>
				</view>
			</view>
			<u-grid :col="4" :border="false">
				<u-grid-item v-for="(item, index) in list" :index="index"  :key="index" @click="onBacklog(item,index)">
					<!-- <u-icon :name="item.icon" :size="46" :color="item.color"></u-icon> -->
					<text class="u-p-b-10">{{item.amount}}</text>
					<text class="grid-text">{{ item.name }}</text>
				</u-grid-item>
			</u-grid>
		</view>
		<!-- 工具项 -->
		<view class="region u-m-t-35">
			<view class="title u-flex">
				<text>常用功能</text>
				<view class="" @click="lookMore" >更多<u-icon name="arrow-right" ></u-icon></view>
			</view>
			<u-grid :col="4" :border="false" @click="onGrid" >
				<u-grid-item v-for="(item, index) in regionList" :index="index" :key="index">
					<u-icon class="u-p-b-15" :name="item.icon" custom-prefix="custom-icon" :size="46" :color="item.color"></u-icon>
					<text class="grid-text">{{ item.name }}</text>
				</u-grid-item>
			</u-grid>
		</view>
		<!-- 图表 -->
		<view class="charts-box">
			<view class="u-flex introduce">
				<view class="text">$业绩目标（{{homeData.achievement.name}}）</view>
				<view class="screen u-flex" @click="onFilter">筛选/{{homeData.achievement.year}}{{homeData.achievement.month!=0?"-"+homeData.achievement.month:''}}<u-icon class="u-p-l-10" name="shaixuan" custom-prefix="custom-icon" size="40" color="#24D396"></u-icon> </view>
			</view>
			<view class="charts">
				<qiun-data-charts
					type="gauge"
					:chartData="chartData"
					:canvas2d="true"
					canvasId="finishing"
					background="none"
					:opts="{title:{name: '完成率',color: colorText,fontSize: 15,offsetY:50},subtitle: {name: homeData.achievement.complete_percent + '%',fontSize: 25,}}"
				/>
			</view>
			<view class="u-flex target">
				<view class="text">目标：{{homeData.achievement.yeartarget | moneyFormat}}</view>
				<view class="text">回款：{{homeData.achievement.complete_money | moneyFormat}}</view>
				<view class="text">合同：{{homeData.achievement.contract_money | moneyFormat}}</view>
			</view>
		</view>
		<!-- 底部导航 -->
		<fa-tabbar></fa-tabbar>
	</view>
</template>

<script>
	import {get_date} from '@/common/mUtils'
let systemInfo = uni.getSystemInfoSync();
	export default {
		data() {
			return {
				count: '',
				datatype:{
					text:'我的',
					value : "oneself",
				},
				homeData: {
					achievement: {
						complete_percent: 0,
						complete_money:0,
						yeartarget: 0,
						contract_money: 0,
						name: '合同金额',
						year: '2021',
						month: ''
					},
				},
				navbar: false,
				statusBarHeight: systemInfo.statusBarHeight + 48,
				list: [
					{
						amount: 0,
						name: '待跟客户',
						url: '/pages/client/index',
					},
					{
						amount: 0,
						name: '待跟商机',
						url: '/pages/business/index',
					},
					{
						amount: 0,
						name: '待审合同',
						url: '/pages/backlog/list?type=0',
					},
					{
						amount: 0,
						name: '待审回款',
						url: 'pages/backlog/list?type=1',
					},
					{
						amount: 0,
						name: '到期客户',
						url: '/pages/client/index',
					},
					{
						amount: 0,
						name: '到期合同',
						url: '/pages/contract/list/index?current=5',
					},
					{
						amount: 0,
						name: '待回款',
						url: '/pages/contract/list/index?current=6',
					},
					{
						amount: 0,
						name: '待跟线索',
						url: '/pages/clues/list',
					},
				],
				regionList: [
					{
						name: '建客户',
						url: 'pages/client/clientSet/clientSet?type=add',
						icon: 'xinjiankehu',
						color: '#1D6FFF',
					},
					{
						name: '建商机',
						url: 'pages/business/addBusiness/index?type=add',
						icon: 'xinjianshangji',
						color: '#FF5252',
					},
					{
						name: '建合同',
						url: 'pages/contract/index?type=add',
						icon: 'xinjianhetong',
						color: '#EB7411',
					},
					{
						name: '建回款',
						url: 'pages/receivables/manage?type=add',
						icon: 'shoukuan',
						color: '#FBB03B',
					},
					{
						name: '查重',
						url: 'pages/check/index',
						icon: 'huiyuanchaxun',
						color: '#1D6FFF',
					},
					{
						name: '公海',
						url: 'pages/highSeas/index?type=client',
						icon: 'gonghai',
						color: '#1B8CFE',
					},
					{
						name: '联系人',
						url: 'pages/contacts/list',
						icon: 'lianxiren',
						color: '#01AB3E',
					},
					{
						name: '外访签到',
						url: 'pages/more/sign',
						icon: 'kaoqinqiandao',
						color: '#F5A625',
					},
				],
				chartData:{
					categories:[
						{
							"value": 0.2,
							"color": "#1890ff"
						},
						{
							"value": 0.8,
							"color": "#2fc25b"
						},
						{
							"value": 1,
							"color": "#f04864"
						}
					],
					series:[
						{
							"name": "完成率%",
							"data": 0
						}
					],
				},
			};
		},
		onLoad(e) {
		},
		onShow(){
      console.log(this.vuex_admin.avatar);
      if (!this.vuex_token) {
        this.$u.route('pages/login/index');
      }
			this.getData()
			this.getUser()
		},
		onPageScroll(e) {
			// 页面滑动 顶部导航显示与隐藏
			if(e.scrollTop > 25) {
				this.navbar = true
			} else{
				this.navbar = false
			}
			if(e.scrollTop < 148) {
				this.navbar = false
			}
		},
		computed: {
			colorText(){
				if(this.homeData.achievement.complete_percent > 20) {
					return '#2fc25b';
				} else if(this.homeData.achievement.complete_percent > 80) {
					return '#f04864';
				} else {
					return '#1890ff';
				}
			}
		},
		filters: {
			moneyFormat:function(value){
				let num;
        if (value > 9999) { //大于9999显示x.xx万
          num = (Math.floor(value / 100) / 100) + '万';
        } else if (value <= 9999 && value > -9999) {
          num = value
        }
        return num;
			},
		},
		methods: {
			// 前往人员管理
			onGroups() {
				this.$u.route('pages/member/list',{});
			},
			// 跳转到更多
			onMore() {
				this.$u.route('pages/more/index',{});
			},
			// 格式化时间
			timeFormats(val) {
				return this.$u.timeFormat(val, 'mm/dd hh:MM');
			},
			// 获取首页数据
			getData() {
				let params={type:this.datatype.value};
				if(!this.$u.test.isEmpty(this.vuex_Tfilter.filter)) {
					if(this.vuex_Tfilter.filter && this.$u.test.isEmpty(this.vuex_Tfilter.filter.month)){
						this.vuex_Tfilter.filter.month=0;
					}
					params = Object.assign(params,this.vuex_Tfilter.filter)
				}
				this.$u.get('crm.dashboard/index', params).then(res => {
					if(res.code == 1 ) {
						this.homeData = res.data
						this.homeData.achievement.complete_money=this.homeData.achievement.config==1?this.homeData.achievement.contract_money:this.homeData.achievement.receivables_money
						this.chartData.series[0].data = this.homeData.achievement.complete_percent > 100 ? 1 : this.homeData.achievement.complete_percent / 100
						this.list.forEach((item,index) => {
							switch (item.name) {
								case '待跟客户':
									item.amount = this.homeData.communicate
									break;
								case '待跟商机':
									item.amount = this.homeData.business
									break;
								case '到期合同':
									item.amount = this.homeData.contract_expire
									break;
								case '待审合同':
									item.amount = this.homeData.contract_flow?this.homeData.contract_flow:0
									break;
								case '待审回款':
									item.amount = this.homeData.receivables_flow?this.homeData.receivables_flow:0
									break;
								case '待回款':
									item.amount = this.homeData.contract_return
									break;
								case '到期客户':
									item.amount = this.homeData.lose_num
									break;
								case '线索':
									item.amount = this.homeData.clues_num
									break;
								default:
									break;
							}
						});
					}
				}).catch( res =>{
					// 未登录还原默认数据
					if(res.data.code == 401){
						this.list.forEach((item,index) =>{
							item.amount = 0
						})
						this.homeData = {
							achievement: {
								complete_percent: 0,
								complete_money:0,
								yeartarget: 0,
								contract_money: 0,
								name: '合同金额',
								year: '2022',
								month: ''
							},
						}
					}
				})
				this.$u.get('crm.dashboard/getNotice', params).then(res => {
					if(res.code == 1) {
						let count = 0
						for (const key in res.data) {
							if (Object.hasOwnProperty.call(res.data, key)) {
								count = res.data[key] + count
							}
						}
						this.count = count
					}
				}).catch( res =>{
					
				})
				
			},
			// 获取用户信息
			getUser() {
				this.$u.get('general.profile/getInfo').then(res => {
					if(res.code == 1 ) {
						// this.vuex_admin = res.data;
						uni.setStorageSync('admin_info', res.data);
					}
				}).catch( res =>{
					// 未登录还原默认数据
					if(res.data.code == 401){
					/*	this.vuex_admin = {
							avatar: "",
							groups: [{id: 1, name: ""}],
							loginfailure: 0,
							realname: "未登录",
							username: "",
						}*/
					}
				})
			},
			// 点击待办栏
			onBacklog(val,index){
				// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
				let type = ""
				switch(index) {
					case 0:
						// 线索和客户
						type = "clues_customer"
						break;
					case 1:
						// 商机
						type = "flow_business"
						break;
					case 2:
						// 审批合同
						type = "flow_contract"
						break;
					case 3:
						// 审批回款
						type = "flow_receivables"
						break;
					case 4:
						// 线索和客户
						type = "clues_customer"
					case 5:
						// 审批合同
						type = "flow_contract"
					case 6:
						// 审批回款
						type = "flow_receivables"
					case 7:
						// 线索和客户
						type = "clues_customer"
						break;
					default:
						break;
				}
				//#ifdef MP-WEIXIN
					this.$reuse.subscriptionInfo(type);
				//#endif
				
				let filterData = {
					filter: {},
					op: {},
					formName: {}
				}
				switch (index) {
					case 0:
						filterData.op['next_time'] = 'RANGE';
						filterData.filter['next_time'] ="1970-01-02 00:00"+" - "+get_date(0)+" 23:59";
						if(this.datatype.value=="oneself"){
							filterData.op['scope'] = '=';
							filterData.filter['scope'] = '2';
							filterData.formName['scopeName'] = "我的客户";
						}
						// 储存
						this.$u.vuex('vuex_filter', filterData)
						uni.switchTab({url: val.url});
						break;
					case 1:
						filterData.op['next_time'] = 'RANGE';
						filterData.filter['next_time'] ="1970-01-02 00:00"+" - "+get_date(0)+" 23:59";
						if(this.datatype.value=="oneself"){
							filterData.op['scope'] = '=';
							filterData.filter['scope'] = '8';
							filterData.formName['scopeName'] = "我的商机";
						}
						// 储存
						this.$u.vuex('vuex_bfilter', filterData)
						uni.switchTab({url: val.url});
						break;
					case 4:
						filterData.op['expire_type'] = '=';
						filterData.filter['expire_type'] ="1";
						if(this.datatype.value=="oneself"){
							filterData.op['scope'] = '=';
							filterData.filter['scope'] = '2';
							filterData.formName['scopeName'] = "我的客户";
						}
						// 储存
						this.$u.vuex('vuex_filter', filterData)
						uni.switchTab({url: val.url});
						break;
					case 7:
						filterData.op['next_time'] = 'RANGE';
						filterData.filter['next_time'] ="1970-01-02 00:00"+" - "+get_date(0)+" 23:59";
						if(this.datatype.value=="oneself"){
							filterData.op['scope'] = '=';
							filterData.filter['scope'] = '11';
							filterData.formName['scopeName'] = "我的线索";
						}
						// 储存
						this.$u.vuex('vuex_cluesfilter', filterData)
						uni.switchTab({url: val.url});
						break;
					default:
						this.$u.route(val.url)
						break;
				}
			},
			// 点击工具栏
			onGrid(e) {
				// 微信小程序订阅消息 因为是一次性订阅所以暂时每个点击事件埋下订阅事件
				//#ifdef MP-WEIXIN
				switch(e) {
					case 0:
						// 线索和客户
						this.$reuse.subscriptionInfo('clues_customer');
						break;
					case 1:
						// 商机
						this.$reuse.subscriptionInfo('flow_business');
						break;
					case 2:
						// 审批合同
						this.$reuse.subscriptionInfo('flow_contract');
						break;
					case 3:
						// 审批回款
						this.$reuse.subscriptionInfo('flow_receivables');
						break;
					default:
						break;
				}
				//#endif
				this.$u.route(this.regionList[e].url);
			},
			lookMore(index){
				if(index == 1){
					this.$u.route('pages/backlog/index');
				} else {
					this.$u.route('pages/more/index');
				}
			},
			// 我的切换
			onMy() {
				if(this.datatype.value=='oneself'){
					this.datatype={text:'团队',value : "team",}
				}else{
					this.datatype={text:'我的',value : "oneself",}
				}
				//加载数据
				this.getData();
			},
			// 筛选
			onFilter() {
				this.$u.route('pages/index/filter',{});
			}
		},
	}
</script>

<style lang="scss" scoped>
.container {
	background-color: #F7F7F7 !important;
	padding-bottom: 50px;
	min-height: 100vh;
}
.status_bar {
	height: var(--status-bar-height);
	width: 100%;
}
.top {
	height: 450rpx;
	/* #ifdef H5 */
	height: 360rpx;
	/* #endif */
	color: #fff;
	// background-image: linear-gradient(#FF7E69, #FE644A);
}

.share {
	border: 1px solid #fff;
	border-radius: 5px;
	padding: 15rpx 30rpx;
}
.notice {
	margin: -68px 8px 10px 8px;
	left: 0;
	right: 0;
	background-color: #fff;
	border-radius: 10px;
	padding: 6px 0px;
	.title {
		padding: 20rpx;
		font-size: 30rpx;
		font-weight: 500;
		justify-content: space-between;
	}
}
.region {
	background-color: #fff;
	border-radius: 12px;
	margin: 0 10px;
	padding: 8px 5px;
	.title {
		padding: 28rpx 20rpx;
		font-size: 30rpx;
		font-weight: 500;
		justify-content: space-between;
	}
	.plus {
		background-color: #FFF0ED;
    border-radius: 10rpx;
    padding: 15rpx;
	}
	.nape {
		width: 48%;
		border-radius: 8px;
		background-color: #FBFBFB;
		padding: 30rpx 20rpx 30rpx 35rpx;
		margin-bottom: 15rpx; 
		&:nth-child(2n+1){
			margin-right: 4%;
		}
	}
}
.item-padding {
	padding: 45rpx 0;
}
.gray {
	color: #BFBFBF;
}
.slot-wrap {
	display: flex;
	align-items: center;
	padding: 0 30rpx;
}
.statusBar {
	position: relative;
}
.arrow-icon {
	position: absolute;
	bottom: 16px;
	left: 10px;
}
/deep/ .u-avatar {
	display: block !important;
}
// 图表样式
/* 请根据需求修改图表容器尺寸，如果父容器没有高度图表则会显示异常 */
.charts-box{
	.charts {
		width: 100%;
		height:300px;
	}
	.introduce {
		justify-content: space-between;
		padding: 45rpx 12px;
		.text {
			font-size: 30rpx;
			font-weight: 500;
		}
		.screen {
			font-size: 30rpx;
			font-weight: 500;
			background-color: #fff;
			padding: 10rpx 32rpx;
    	border-radius: 60rpx;
		}
	}
	.target {
		margin-top: -60rpx;
		justify-content: space-around;
		.text {
			background-color: #D7D7D7;
			font-size: 25rpx; 
			padding: 10rpx 20rpx;
    	border-radius: 20rpx;
		}
	}
}
/deep/.left-icon .u-size-mini {
	display: block;
}

</style>