<template>
	<view>
		<view class="slot-content">
			<u-cell-group>
				<u-cell-item  title="线索场景"  :value="formName.scopeName" @click="sceneShow = true"></u-cell-item>
				<u-cell-item  title="负责人" :value="formName.companyName" @click="companyShow = true"></u-cell-item>
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
					<view class="title">最近跟进时间</view>
					<view class="u-flex">
						<view class="item u-flex-1" @click="selectTimeShow('follow_time_start')">
							<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
							<text class="u-font-26">{{formName.follow_time_start}}</text>
						</view>
						<view class="line"></view>
						<view class="item u-flex-1" @click="selectTimeShow('follow_time_end')">
							<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
							<text class="u-font-26">{{formName.follow_time_end}}</text>
						</view>
					</view>
				</view>
				<u-cell-item  title="线索来源" :value="formName.sourceName" @click="sourceShow = true"></u-cell-item>
				<u-cell-item  title="线索行业"  :value="formName.industryName" @click="industryShow = true"></u-cell-item>
				<u-cell-item  title="线索级别" :value="formName.levelName" @click="levelShow = true"></u-cell-item>
			</u-cell-group>
			<view class="bottom-btn">
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
		<!-- 时间选择 -->
		<u-picker v-model="timeShow" :hour="true" mode="time" :params="params" @confirm="timeChange"></u-picker>
		<!-- 选择线索场景 -->
		<u-action-sheet :list="sceneList" v-model="sceneShow" @click="sceneClick"></u-action-sheet>
		<!-- 选择线索等级 -->
		<u-action-sheet :list="levelList" v-model="levelShow" @click="levelClick"></u-action-sheet>
		<!-- 选择线索行业 -->
		<u-action-sheet :list="industryList" v-model="industryShow" @click="industryClick"></u-action-sheet>
		<!-- 选择线索来源 -->
		<u-action-sheet :list="sourceList" v-model="sourceShow" @click="sourceClick"></u-action-sheet>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				searchTimer: null,
				timeShow: false,
				levelShow: false,
				industryShow: false,
				sourceShow: false,
				sceneShow: false,
				sceneList: [
					{
						text: '全部线索	',
						id: 10
					},
					{
						text: '我的线索',
						id: 11
					},
					{
						text: '下属线索',
						id: 12
					},
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
				industryList: [],
				sourceList: [],
				companyList: [],
				adminPage: 1,
				lastAdmin: false,
				adminStatus: 'loadmore',
				adminkeyword: '',
				pageSize: 20,
				companyShow: false,
				timeType: '',
				form: {
					scope: '',
					level: '',
					industry: '',
					source: '',
					owner_user_id: '',
					next_time: '',
					follow_time: '',
					expire_type: '',
				},
				formName: {
					next_time_start: '选择',
					next_time_end: '选择',
					follow_time_start: '选择',
					follow_time_end: '选择',
					companyName: '选择',
					scopeName: '选择',
					levelName: '选择',
					industryName: '选择',
					sourceName: '选择',
				}
			};
		},
		onLoad(e) {
			this.getBaseConfig()
			this.onSelectpage()
		},
		onShow(){
			if(!this.$u.test.isEmpty(this.vuex_cluesfilter.filter)) {
				// 已选数据合并
				this.form = Object.assign(this.form,this.vuex_cluesfilter.filter)
				this.formName = Object.assign(this.formName,this.vuex_cluesfilter.formName)
				this.bselectdata();
			}
		},
		
		methods: {
			//绑定输入框的值
			bselectdata(){
					if(this.form.next_time){
						this.formName.next_time_start=this.form.next_time.slice(0, 16);
						this.formName.next_time_end=this.form.next_time.slice(19, 35);
					}
					if(this.form.follow_time){
						this.formName.follow_time_start=this.form.follow_time.slice(0, 16);
						this.formName.follow_time_end=this.form.follow_time.slice(19, 35);
					}
	
			},
			// 时间窗口
			selectTimeShow(text) {
				this.timeType = text
				this.timeShow = !this.timeShow
			},
			// 选择时间
			timeChange(e) {
				let time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
				switch (this.timeType) {
					case 'next_time_start':
						this.formName.next_time_start = time
						break;
					case 'next_time_end':
						this.formName.next_time_end = time
						break;
					case 'follow_time_start':
						this.formName.follow_time_start = time
						break;
					case 'follow_time_end':
						this.formName.follow_time_end = time
						break;
					default:
						break;
				}
			},
			// 选择线索场景
			sceneClick(index) {
				this.formName.scopeName =  this.sceneList[index].text
				this.form.scope = this.sceneList[index].id
				this.sceneList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
			// 选择线索等级
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
			// 选择线索行业
			industryClick(index){
				this.formName.industryName = this.industryList[index].text
				this.form.industry = this.industryList[index].id
				this.industryList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
			// 选择线索来源
			sourceClick(index){
				this.formName.sourceName = this.sourceList[index].text
				this.form.source = this.sourceList[index].id
				this.sourceList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
			// 获取配置字段
			getBaseConfig() {
				this.$u.get('crm.common/baseConfig').then((res) => {
					if(res.code == 1){
						this.levelList = this.onJson(res.data.levelList)
						this.industryList = this.onJson(res.data.industryList)
						this.sourceList = this.onJson(res.data.sourceList)
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
			// 获取负责人
			onSelectpage(isNextPage,pages) {
				this.$u.get('ajax/selectpage',{
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
				clearTimeout(this.searchTimer)
				this.searchTimer = setTimeout(() => {
					this.lastAdmin = false
					this.onSelectpage()
				}, 500)
			},
			// 重置
			reset() {
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						if(!this.$u.test.isEmpty(this.form[key])){
							switch (key) {
								case 'scope':
									this.sceneList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'level':
									this.levelList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'industry':
									this.industryList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'source':
									this.sourceList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
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
					scope: '',
					level: '',
					industry: '',
					source: '',
					owner_user_id: '',
					next_time: '',
					follow_time: '',
					expire_type: '',
				}
				this.formName = {
					next_time_start: '选择',
					next_time_end: '选择',
					follow_time_start: '选择',
					follow_time_end: '选择',
					companyName: '选择',
					scopeName: '选择',
					levelName: '选择',
					industryName: '选择',
					sourceName: '选择',
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
					this.form.next_time  = this.formName.next_time_start + ' - ' + this.formName.next_time_end
				}
				if(this.formName.follow_time_start != '选择' && this.formName.follow_time_end != '选择'){
					this.form.follow_time = this.formName.follow_time_start + ' - ' + this.formName.follow_time_end
				}
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						if(!this.$u.test.isEmpty(this.form[key])){
							if( key == 'next_time' || key == 'follow_time') {
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
				// 储存
				this.$u.vuex('vuex_cluesfilter', filterData)
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
		text-align: right;
		padding: 68rpx 25rpx;
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
