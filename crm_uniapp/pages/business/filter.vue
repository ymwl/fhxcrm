<template>
	<view>
		<view class="slot-content">
			<u-cell-group>
				<u-cell-item  title="商机场景"  :value="formName.sceneName" @click="sceneShow = true"></u-cell-item>
				<u-cell-item  title="负责人" :value="formName.companyName" @click="companyShow = true"></u-cell-item>
				<u-cell-item  title="客户"  :value="formName.customerName" @click="customerShow = true"></u-cell-item>
				<u-cell-item  title="状态" :value="formName.isEndName" @click="isEndShow = true"></u-cell-item>
				<view class="time u-border-bottom">
					<view class="title">下次跟进时间</view>
					<view class="u-flex">
						<view class="item u-flex-1" @click="selectTimeShow('next_time_start')">
							<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
							<text class="u-font-26">{{formName.next_time_start}}</text>
						</view>
						<view class="line"></view>
						<view class="item u-flex-1" @click="selectTimeShow('next_time_end')">
							<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
							<text class="u-font-26">{{formName.next_time_end}}</text>
						</view>
					</view>
				</view>
				<view class="time u-border-bottom">
					<view class="title">预计成交时间</view>
					<view class="u-flex">
						<view class="item u-flex-1" @click="selectTimeShow('deal_time_start')">
							<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
							<text class="u-font-26">{{formName.deal_time_start}}</text>
						</view>
						<view class="line"></view>
						<view class="item u-flex-1" @click="selectTimeShow('deal_time_end')">
							<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
							<text class="u-font-26">{{formName.deal_time_end}}</text>
						</view>
					</view>
				</view>
			</u-cell-group>
			<view class="bottom-btn ">
				<u-button class="u-m-r-15" type="default" size="medium" @click="reset">重置</u-button>
				<u-button type="primary" size="medium" @click="onConfirm" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定</u-button>
			</view>
		</view>
		<!-- 选择负责人 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="companyShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择负责人</text> 
				<view class="" @click="companyShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="adminkeyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="adminSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="adminBottom">
				<view class="list">
					<block v-if="companyList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in companyList" :key="index" @click="oncompany(item,index)">
								<view class="title">{{item.realname}}</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="adminStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
		<!-- 选择客户弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="customerShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择客户</text> 
				<view class="" @click="customerShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入客户名称搜索" @change="onSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="reachBottom">
				<view class="list">
					<block v-if="customerList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in customerList" :key="index" @click="onItem(item,index)">
								<view class="title">{{item.name}}</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="listStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
			<!-- <view class="bottom_btn u-border-top">
				<u-button class="u-m-l-15" type="primary"  @click="chosen" size="medium">选好了</u-button>
			</view> -->
		</u-popup>
		<!-- 选择场景 -->
		<u-action-sheet :list="sceneList" v-model="sceneShow" @click="sceneClick"></u-action-sheet>
		<!-- 时间选择 -->
		<u-picker v-model="timeShow" :hour="true" mode="time" :params="params" @confirm="timeChange"></u-picker>
		<!-- 选择状态 -->
		<u-action-sheet :list="isEndList" v-model="isEndShow" @click="isEndClick"></u-action-sheet>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				timeShow: false,
				levelShow: false,
				customerShow: false,
				isEndShow: false,
				overdueShow: false,
				sceneShow: false,
				sceneList: [
					{
						text: '全部商机	',
						id: 7
					},
					{
						text: '我的商机',
						id: 8
					},
					{
						text: '下属商机',
						id: 9
					},
				],
				overdueList: [
					{
						text: '将要过期商机',
						id: 1
					}
				],
				params: {
					year: true,
					month: true,
					day: true,
					hour: true,
					minute: true,
					second: false
				},
				levelList: [],
				customerList: [],
				isEndList: [
					{
						text: '洽淡中',
						id: 0
					},
					{
						text: '成交',
						id: 1
					},
					{
						text: '失败',
						id: 2
					},
					{
						text: '无效',
						id: 3
					},
					
				],
				companyList: [],
				adminPage: 1,
				lastAdmin: false,
				adminStatus: 'loadmore',
				page: 0,
				lastPage: false,
				listStatus: 'loadmore',
				adminkeyword: '',
				keyword: '',
				pageSize: 20,
				companyShow: false,
				timeType: '',
				form: {
					scene_id: '',
					customer_id: '',
					owner_user_id: '',
					next_time: '',
					deal_time: '',
					is_end: '',
				},
				formName: {
					next_time_start: '选择',
					next_time_end: '选择',
					deal_time_start: '选择',
					deal_time_end: '选择',
					companyName: '选择',
					sceneName: '选择',
					levelName: '选择',
					customerName: '选择',
					isEndName: '选择',
					overdueName: '选择',
				}
			};
		},
		onLoad(e) {
			this.getBaseConfig()
			this.onSelectpage()
			this.getCustomer()
		},
		onShow(){
			if(!this.$u.test.isEmpty(this.vuex_bfilter.filter)) {
				// 已选数据合并
				this.form = Object.assign(this.form,this.vuex_bfilter.filter)
				this.formName = Object.assign(this.formName,this.vuex_bfilter.formName)
				console.log(this.form ,this.formName)
			}
		},
		methods: {
			// 时间窗口
			selectTimeShow(text) {
				console.log(text)
				this.timeType = text
				this.timeShow = !this.timeShow
			},
			// 选择时间
			timeChange(e) {
				console.log(e)
				let time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
				switch (this.timeType) {
					case 'next_time_start':
						this.formName.next_time_start = time
						break;
					case 'next_time_end':
						this.formName.next_time_end = time
						break;
					case 'deal_time_start':
						this.formName.deal_time_start = time
						break;
					case 'deal_time_end':
						this.formName.deal_time_end = time
						break;
					default:
						break;
				}
			},
			// 选择商机场景
			sceneClick(index) {
				this.formName.sceneName =  this.sceneList[index].text
				this.sceneList[index].color = '#2979ff'
				this.form.scene_id = this.sceneList[index].id
			},
			// 选择商机等级
			levelClick(index) {
				this.formName.levelName = this.levelList[index].text
				this.form.level = this.levelList[index].id
				this.levelList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
			// 选择状态
			isEndClick(index){
				this.formName.isEndName = this.isEndList[index].text
				this.form.is_end = this.isEndList[index].text
				this.isEndList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
			// 将要过期
			overdueClick(index) {
				this.formName.overdueName = this.overdueList[index].text
				this.form.expire_type = this.overdueList[index].id
				this.overdueList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
			// 获取配置字段
			getBaseConfig() {
				this.$u.api.getBaseConfig().then((res) => {
					if(res.code == 1){
						this.levelList = this.onJson(res.data.levelList)
						this.industryList = this.onJson(res.data.industryList)
					}
				})
			},
			// json 转化
			onJson(data) {
				let arr = []
				for (const key in data) {
					if (Object.hasOwnProperty.call(data, key)) {
						let obj = {}
						obj.id = key
						obj.text = data[key]
						arr.push(obj)
					}
				}
				return arr
			},
			// 获取审批人
			onSelectpage(isNextPage,pages) {
				this.$u.api.onCommonSelectpage({
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.adminkeyword,
					keyField: 'id',
					showField: 'realname',
					"q_word": this.adminkeyword,
					"searchField": "realname",
					model:'admin',
				}).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastAdmin = true
						} 
						//不够一页
						if (res.data.list.length < this.pageSize) {
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
			// 选择公司签约人
			oncompany(val,index) {
				this.companyList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.formName.companyName = val.realname
				this.companyShow = false
				this.form.owner_user_id = val.id
			},
			// 选择搜索
			adminSearch() {
				this.lastAdmin = false
				this.onSelectpage()
			},
			// 搜索
			onSearch() {
				this.page = 0
				this.getCustomer()
			},
			// 获取客户列表
			getCustomer(isNextPage,pages) {
				this.$u.api.getCustomerList({
					sort: 'id',
					order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({name: this.keyword}),
					op: JSON.stringify({name: 'LIKE'})
				}).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.rows.length == 0) {
							this.lastPage = true
						} 
						//不够一页
						if (res.data.rows.length < this.pageSize) {
							this.listStatus = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.customerList = this.customerList.concat(res.data.rows)
							return 
						}
						this.customerList = res.data.rows
					}
				})
			},
			// 滚动到底部加载更多
			reachBottom() {
				if(this.lastPage || this.listStatus == 'loading') return ;
				this.listStatus = 'loading'
				setTimeout(() => {
					if(this.lastPage) return ;
					this.getCustomer(true,++this.page)
					if(this.customerList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 选择客户
			onItem(val,i) {
				this.customerList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.form.customer_id = val.id
				this.formName.customerName = val.name
				this.customerShow = false
			},
			// 重置
			reset() {
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						if(!this.$u.test.isEmpty(this.form[key])){
							switch (this.form[key]) {
								case 'scene_id':
									this.sceneList.forEach((item,index)=>{
										if(key = item.id) {
											item.color = ""
										}
									})
									break;
								case 'level':
									this.levelList.forEach((item,index)=>{
										if(key = item.id) {
											item.color = ""
										}
									})
									break;
								case 'industry':
									this.industryList.forEach((item,index)=>{
										if(key = item.id) {
											item.color = ""
										}
									})
									break;
								case 'source':
									this.isEndList.forEach((item,index)=>{
										if(key = item.id) {
											item.color = ""
										}
									})
									break;
								case 'expire_type':
									break;
								default:
									break;
							}
						}
					}
				}
				this.form = {
					scene_id: '',
					level: '',
					industry: '',
					source: '',
					owner_user_id: '',
					next_time: '',
					deal_time: '',
					expire_type: '',
				}
				this.formName = {
					next_time_start: '选择',
					next_time_end: '选择',
					deal_time_start: '选择',
					deal_time_end: '选择',
					companyName: '选择',
					sceneName: '选择',
					levelName: '选择',
					customerName: '选择',
					isEndName: '选择',
					overdueName: '选择',
				}

			},
			// 确定
			onConfirm() {
				let filterData = {
					filter: {},
					op: {},
					formName: {}
				}
				// 时间是否选择
				if(this.formName.next_time_start != '选择' && this.formName.next_time_end != '选择'){
					this.form.next_time  = this.formName.next_time_start + '-' + this.formName.next_time_end
				}
				if(this.formName.deal_time_start != '选择' && this.formName.deal_time_end != '选择'){
					this.form.deal_time = this.formName.deal_time_start + '-' + this.formName.deal_time_end
				}
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						if(!this.$u.test.isEmpty(this.form[key])){
							if( key == 'next_time' || key == 'deal_time') {
								filterData.op[key] = 'RANGE'
							} else {
								filterData.op[key] = '='
							}
							filterData.filter[key] = this.form[key]
						}
					}
				}
				for (const key in this.formName) {
					if (this.form.hasOwnProperty.call(this.formName, key)) {
						if(this.formName[key] != '选择'){
							filterData.formName[key] = this.formName[key]
						}
					}
				}
				console.log(filterData)
				// 储存
				this.$u.vuex('vuex_bfilter', filterData)
				uni.navigateBack();
			}
		},
		
	}
</script>

<style lang="scss">
.slot-content{
	background-color: #fff;
	.time {
		padding: 26rpx 32rpx;
		.title {
			margin-bottom: 15rpx;
		}
		.line {
			width: 20rpx;
			height: 1px;
			background-color: #dcdfe6;
			margin: 0 12rpx;
		}
		.item {
			display: flex;
			align-items: center;
			min-height: 35px;
			padding: 0 25rpx;
			border-radius: 10rpx;
			border: 1px solid #dcdfe6;
		}
	}
	.bottom-btn {
		padding: 68rpx 25rpx;
		text-align: right
	}
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
		display: flex;
		justify-content: flex-end;
		padding: 28rpx 10rpx 45rpx;
	}
}
</style>
