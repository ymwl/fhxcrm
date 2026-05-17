<template>
	<view class="container">
		<view class="top" :style="{backgroundColor: vuex_theme.color}">
			<!-- 顶部导航高度 -->
			<view class="statusBar"></view>
		</view>
		<view class="notice">
			<view class="option-box u-flex">
				<view class="option u-p-r-25 u-flex-1">
					<view class="title">业绩数据</view>
					<view class="content tips">已成交（{{totaldata.contract_count ? totaldata.contract_count : '0'}}单）</view>
					<view class="content total">￥{{totaldata.contract_moeny | moneyFormat}}</view>
					<view class="content ">
						<text class="tips">完成率：</text>
						<text class="">{{contract_data.complete_percent ? contract_data.complete_percent : '0'}}%</text>
					</view>
					<view class="content">
						<text class="tips">月目标：</text> 
						<text class="">￥{{contract_data.yeartarget | moneyFormat}}元</text>
					</view>
				</view>
				<view class="line"></view> 
				<view class="option u-p-l-25 u-flex-1 ">
					<view class="title u-flex u-row-right" @click="filterShow('1')">
						筛选/{{AtimeName}}<u-icon class="u-p-l-10" name="shaixuan" custom-prefix="custom-icon" size="35" color="#24D396"></u-icon>
					</view>
					<view class="content tips">已回款（{{totaldata.receivables_count ? totaldata.receivables_count : '0'}}笔）</view>
					<view class="content total">￥{{totaldata.receivables_money | moneyFormat}}</view>
					<view class="content ">
						<text class="tips">完成率：</text>
						<text class="">{{receivables_data.complete_percent ? receivables_data.complete_percent : '0'}}%</text>
					</view>
					<view class="content">
						<text class="tips">月目标：</text> 
						<text class="">￥{{receivables_data.yeartarget | moneyFormat}}元</text>
					</view>
				</view>
			</view>
			<!-- 图表 -->
			<view class="charts">
				<qiun-data-charts
					type="demotype"
					:chartData="demotypeChartData"
					canvasId="zidng"
					:canvas2d="true"
					:ontouch="true"
					:opts="{enableScroll:true,xAxis:{scrollShow:true,itemCount:3,disableGrid:true}}"
					background="none"
				/>
			</view>
		</view>
		<!-- 工具项 -->
		<view class="region u-m-t-35">
			<view class="title u-row-between u-flex ">
				<view class="">工作数据</view>
				<view class="u-flex"  @click="filterShow('2')">
					筛选/{{CtimeName}}<u-icon class="u-p-l-10" name="shaixuan" custom-prefix="custom-icon" size="35" color="#24D396"></u-icon>
				</view>
			</view>
			<u-grid :col="4" :border="false" @click="onGrid" >
				<u-grid-item v-for="(item, index) in regionList" :index="index" :key="index">
					<text class="u-p-b-15">{{ item.count }}</text>
					<!-- <u-icon class="u-p-b-15" :name="item.icon" custom-prefix="custom-icon" :size="46" :color="item.color"></u-icon> -->
					<text class="grid-text">{{ item.name }}</text>
				</u-grid-item>
			</u-grid>
			<view class="charts">
				<qiun-data-charts
					type="demotype"
					:chartData="countChartData"
					canvasId="count"
					:canvas2d="true"
					:ontouch="true"
					:opts="{yAxis:{data:[{tofix:0}]},enableScroll:true,xAxis:{scrollShow:true,itemCount:3,disableGrid:true}}"
					background="none"
				/>
			</view>
		</view>
		<!-- 工具项 -->
		<view class="region u-m-t-35">
			<view class="title u-row-between u-flex ">
				<view class="">业绩排行（{{typeName}}）</view>
				<view class="u-flex" @click="filterShow('3')">
					筛选/{{timeName}}<u-icon class="u-p-l-10" name="shaixuan" custom-prefix="custom-icon" size="35" color="#24D396"></u-icon>
				</view>
			</view>
			<view class="performance">
				<block v-if="performanceList.length > 0">
					<view class="list u-flex u-border-bottom" v-for="(item,index) in performanceList" :key="index">
						<view class="left-avatar">
							<u-avatar :src="item.orderAdmin ? item.orderAdmin.avatar  : '' " size="100" sex-icon="/static/first.png"></u-avatar>
							<view class="icon">
								<image class="img" :src="item.icon" mode="aspectFill" />
							</view>
						</view>
						<view class="content">
							<view class="name u-line-1">{{item.orderAdmin ? item.orderAdmin.realname : '--'}}</view>
							<view class="type u-line-1">
								<text v-for="(i,x) in item.orderAdmin ? item.orderAdmin.groups : []" :key="x">{{i.name}}</text>
							</view> 
						</view>
						<view class="money">￥{{item.money | moneyFormat}}元
							<view class="count">{{item.count}}/{{unit}}</view>
						</view>
					</view>
					<view class="more">更多数据可上电脑版查看</view>
				</block>
				<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
			</view>
		</view>
		<!-- 底部导航 -->
		<fa-tabbar></fa-tabbar>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				timeName: '默认',
				AtimeName: '本月',
				CtimeName: '本月',
				typeName: '合同金额',
				unit:'单',
				shopQrcode: '',
				navbar: false,
				performanceList:[],
				totaldata: {},
				receivables_data: {},
				contract_data: {},
				regionList: [
					{
						name: '新增客户',
						count: 0,
						color: '#1D6FFF',
					},
					{
						name: '跟进次数',
						count: 0,
						color: '#FF5252',
					},
					{
						name: '新增商机',
						count: 0,
						color: '#EB7411',
					},
					{
						name: '新联系人',
						count: 0,
						color: '#FBB03B',
					},
				],
				demotypeChartData: {
					categories: [],
					series: []
				},
				countChartData: {
					categories: [],
					series: []
				},
			};
		},
		onLoad(e) {
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
			this.getContractRanking()
			this.onAchievementAnalysis()
			this.onAnalysis()
		},
		onHide() {
			console.log('隐藏')
		},
		onUnload() {
			console.log('卸载')
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
		filters: {
			moneyFormat:function(value){
				if(value) {
					let num;
					if (value > 9999) { //大于9999显示x.xx万
						num = (Math.floor(value / 100) / 100) + '万';
					} else if (value <= 9999 && value > -9999) {
						num = value
					}
					return num;
				} else {
					return 0;
				}
			}
		},
		methods: {
			// 业绩分析数据
			onAchievementAnalysis() {
				let params = {
					ajax: 1,
					type: 'achievement',
				}
				if(!this.$u.test.isEmpty(this.vuex_Afilter.filter)) {
					params = Object.assign(params,this.vuex_Afilter.filter);
				}
				this.$u.get('crm.analysis.admin/achievement', params).then(res => {
					if(res.code == 1) {
						let series = res.data.achievement.data
						// 清除已有的 series 数据
						this.demotypeChartData.series = []
						// 数据格式化
						for (const key in series) {
							if (Object.hasOwnProperty.call(series, key)) {
								let obj = {
									legendShape: 'circle',// 图例形状 圆形
								}
								obj.name = key
								obj.data = series[key]
								this.demotypeChartData.series.push(obj)
							}
						}
						// 添加日期数据
						this.demotypeChartData.categories = res.data.achievement.date
						this.contract_data = res.data.contract_data
						this.receivables_data = res.data.receivables_data
						this.totaldata = res.data.totaldata
						if(!this.$u.test.isEmpty(this.vuex_Afilter.filter) && this.vuex_Afilter.formName.timeName == '自定义') {
							this.AtimeName = this.vuex_Afilter.formName.timeName
						} else {
							this.AtimeName = this.receivables_data.year + '-' + this.receivables_data.s_month
						}
					}
				})
			},
			// 客量分析
			onAnalysis() {
				let params = {
					ajax: 1,
					type: 'orde',
				}
				if(!this.$u.test.isEmpty(this.vuex_Cfilter.filter)) {
					params = Object.assign(params,this.vuex_Cfilter.filter)
					if(this.vuex_Cfilter.formName.timeName) {
						this.CtimeName = this.vuex_Cfilter.formName.timeName
					}
				} else {
					this.CtimeName = '本月'
				}
				this.$u.get('crm.analysis.admin/index', params).then(res => {
					if(res.code == 1) {
						// 清除已有的 series 数据
						this.countChartData.series = []
						this.countChartData.categories = res.data.customer_data.date
						// 数据格式化
						for (const key in res.data) {
							if (Object.hasOwnProperty.call(res.data, key)) {
								if(key != 'totaldata') {
									for (const keys in res.data[key].data) {
										if (Object.hasOwnProperty.call(res.data[key].data, keys)) {
											let obj = {
												legendShape: 'circle',// 图例形状 圆形
											}
											obj.name = keys
											obj.data = res.data[key].data[keys]
											this.countChartData.series.push(obj)
										}
									}
								}
							}
						}
						// 赋值数量
						this.regionList[0].count = res.data.totaldata.m_add_count
						this.regionList[1].count = res.data.totaldata.r_add_count
						this.regionList[2].count = res.data.totaldata.b_add_count
						this.regionList[3].count = res.data.totaldata.r_m_count
					}
				})
			},
			// 排行数据
			getContractRanking(){
				// 筛选参数
				let type_id=0;
				let filterObj = {}
				let opObj = {}
				if(!this.$u.test.isEmpty(this.vuex_rfilter.filter)) {
					filterObj = this.vuex_rfilter.filter
					opObj = this.vuex_rfilter.op
					if(this.vuex_rfilter.formName.timeName) {
						this.timeName = this.vuex_rfilter.formName.timeName
					}
					if(this.vuex_rfilter.formName.typeName) {
						this.typeName = this.vuex_rfilter.formName.typeName
					}
					//区分是合同还是回款
						if(filterObj.type_id){
							type_id=filterObj.type_id;
							delete filterObj.type_id,opObj.type_id;
						}
						
				} else {
					this.timeName = '默认'
				}
				console.log(this.vuex_rfilter)
				if(type_id==2){
					//回款
					this.unit='笔';
					this.$u.get('crm.analysis.ranking/receivables', {
						sort_by: 'order_admin_id',
						sort_order: 'desc',
						offset: 0,
						limit: 20,
						filter: JSON.stringify(filterObj),
						op: JSON.stringify(opObj)
					}).then(res => {
						if(res.code == 1) {
							this.performanceList = res.data.rows
							// 添加前三名头像标签
							this.performanceList.forEach((item,index) => {
								switch (index) {
									case 0:
										this.performanceList[0].icon = '/static/first.png'
										break;
									case 1:
										this.performanceList[1].icon = '/static/second.png'
										break;
									case 2:
										this.performanceList[2].icon = '/static/third.png'
										break;
									default:
										break;
								}
							});
						}
					})
				}else{
					this.unit='单';
					this.$u.get('crm.analysis.ranking/contract', {
						sort_by: 'order_admin_id',
						sort_order: 'desc',
						offset: 0,
						limit: 20,
						filter: JSON.stringify(filterObj),
						op: JSON.stringify(opObj)
					}).then(res => {
						if(res.code == 1) {
							this.performanceList = res.data.rows
							// 添加前三名头像标签
							this.performanceList.forEach((item,index) => {
								switch (index) {
									case 0:
										this.performanceList[0].icon = '/static/first.png'
										break;
									case 1:
										this.performanceList[1].icon = '/static/second.png'
										break;
									case 2:
										this.performanceList[2].icon = '/static/third.png'
										break;
									default:
										break;
								}
							});
							
						}
					})
				}
				
			},
			// 点击筛选
			filterShow(type) {
				this.$u.route('pages/presentation/filter',{type: type})
			},
			// 格式化时间
			timeFormats(val) {
				return this.$u.timeFormat(val, 'mm/dd hh:MM');
			},
			// 点击工具栏
			onGrid() {

			},
			// 我的切换
			onMy() {

			},
			// 点击功能
			onOptions(e) {

			},
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
	color: #fff;
	background-color: #FE644A; 
}
.notice {
	margin: -68px 8px 10px 8px;
	left: 0;
	right: 0;
	background-color: #fff;
	border-radius: 10px;
	padding: 6px 0px;
	.option-box {
		padding: 20rpx;
		font-size: 30rpx;
		.line {
			width: 1px;
			min-height: 100px;
			background-color: #dcdfe6;
		}
		.option {
			.title {
				margin-bottom: 25rpx;
			}
			.content {
				font-size: 28rpx;
				margin-bottom: 13rpx;
			}
			.total { 
				font-size: 34rpx;
				font-weight: 600;
				color: #fa3534;
			}
			.tips {
				color: $u-tips-color;
			}
		}
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
	}
	.performance {
		padding-bottom: 25rpx;
		.list {
			padding: 28rpx 20rpx;
			.left-avatar {
				position: relative;
				margin-right: 20rpx;
				.icon {
					position: absolute;
					top: -18px;
    			right: -6px;
					width: 35px;
					height: 35px;
					.img {
						width: 100%;
						height: 100%;
					}
				}
			}
			.content {
				flex: 1;
				.name {
					font-size: 28rpx;
					line-height: 50rpx;
					max-width: 180rpx;
				}
				.type {
					max-width: 180rpx;
					margin: 10rpx 0;
					font-size: 24rpx;
					color: $u-tips-color;
				}
			}
			.money {
				font-weight: 600;
				font-size: 28rpx;
				color: #fa3534;
				.count {
					font-size: 24rpx;
					color: $u-tips-color;
					text-align: right;
					font-weight: 300;
					padding: 10rpx 0rpx
				}
				
			}
		}
		.more {
			text-align: center;
			padding: 44rpx 0 15rpx;
			font-size: 28rpx;
			color: $u-tips-color;
		}
	}
	
}
.statusBar {
	position: relative;
	z-index: -1;
	height: 188rpx;
}
.charts {
	width: 100%;
	height: 250px;
}


</style>
