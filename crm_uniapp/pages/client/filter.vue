<template>
	<view>
		<view class="slot-content">
			<u-cell-group>
				<u-cell-item  title="客户场景"  :value="formName.sceneName" @click="sceneShow = true"></u-cell-item>
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
						<view class="item u-flex-1" @click="selectTimeShow('last_up_time_start')">
							<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
							<text class="u-font-26">{{formName.last_up_time_start}}</text>
						</view>
						<view class="line"></view>
						<view class="item u-flex-1" @click="selectTimeShow('last_up_time_end')">
							<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
							<text class="u-font-26">{{formName.last_up_time_end}}</text>
						</view>
					</view>
				</view>
				<u-cell-item  title="客户来源" :value="formName.sourceName" @click="sourceShow = true"></u-cell-item>
				<u-cell-item  title="客户行业"  :value="formName.hangyeName" @click="hangyeShow = true"></u-cell-item>
				<u-cell-item  title="客户级别" :value="formName.rankName" @click="rankShow = true"></u-cell-item>
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
				</view>
				<text class="">选择负责人</text> 
				<view class="" @click="companyShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="adminkeyword" :show-action="false" :clearabled="true"  placeholder="输入员工搜索" @change="adminSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="adminBottom">
				<view class="list">
					<block v-if="companyList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in companyList" :key="index" @click="oncompany(item,index)">
								<view class="title">{{item.username}} <text v-if="item.realname">（{{item.realname}}）</text></view>
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
		<u-picker v-model="timeShow" :hour="true" mode="time" :params="params" @confirm="timeChange" @cancel="canceltimeChange"></u-picker>
		<!-- 选择客户场景 -->
		<u-action-sheet :list="sceneList" v-model="sceneShow" @click="sceneClick" @close="sceneClose"></u-action-sheet>
		<!-- 选择客户等级 -->
		<u-action-sheet :list="rankList" v-model="rankShow" @click="rankClick" @close="rankClose"></u-action-sheet>
		<!-- 选择客户行业 -->
		<u-action-sheet :list="hangyeList" v-model="hangyeShow" @click="hangyeClick" @close="hangyeClose"></u-action-sheet>
		<!-- 选择客户来源 -->
		<u-action-sheet :list="sourceList" v-model="sourceShow" @click="sourceClick" @close="sourceClose"></u-action-sheet>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				timeShow: false,
				rankShow: false,
				hangyeShow: false,
				sourceShow: false,
				overdueShow: false,
				sceneShow: false,
				sceneList: [
					{
						text: '我的客户',
						id: 1
					},
					{
						text: '下属客户',
						id: 2
					},
					{
						text: '全部客户',
						id: 3
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
				rankList: [],
				hangyeList: [],
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
					scene_id: '',
          kh_rank: '',
          kh_hangye: '',
					source: '',
					owner_user_id: '',
					next_time: '',
					last_up_time: '',
					expire_type: '',
				},
				formName: {
					next_time_start: '选择',
					next_time_end: '选择',
					last_up_time_start: '选择',
					last_up_time_end: '选择',
					companyName: '选择',
					sceneName: '选择',
					rankName: '选择',
          hangyeName: '选择',
					sourceName: '选择',
					overdueName: '选择',
				}
			};
		},
		onLoad(e) {
			this.getBaseConfig()
			this.onSelectpage()
		},
		onShow(){
			if(!this.$u.test.isEmpty(this.vuex_filter.filter)) {
				// 已选数据合并
				this.form = Object.assign(this.form,this.vuex_filter.filter)
				this.formName = Object.assign(this.formName,this.vuex_filter.formName)
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
					if(this.form.last_up_time){
						this.formName.last_up_time_start=this.form.last_up_time.slice(0, 16);
						this.formName.last_up_time_end=this.form.last_up_time.slice(19, 35);
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
					case 'last_up_time_start':
						this.formName.last_up_time_start = time
						break;
					case 'last_up_time_end':
						this.formName.last_up_time_end = time
						break;
					default:
						break;
				}
			},
      canceltimeChange(e) {
			  //取消时间选择
				let time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
				switch (this.timeType) {
					case 'next_time_start':
						this.formName.next_time_start = '选择';
						break;
					case 'next_time_end':
						this.formName.next_time_end = '选择';
						break;
					case 'last_up_time_start':
						this.formName.last_up_time_start = '选择';
						break;
					case 'last_up_time_end':
						this.formName.last_up_time_end = '选择';
						break;
					default:
						break;
				}
			},
			// 选择客户场景
			sceneClick(index) {
				this.formName.sceneName =  this.sceneList[index].text
				this.form.scene_id = this.sceneList[index].id
				this.sceneList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
      sceneClose() {
        this.formName.sceneName =  '我的客户'
        this.form.scene_id = 1
        this.sceneList.forEach((item,i)=>{
            item.color = ''
        })
      },
			// 选择客户等级
			rankClick(index) {
				this.formName.rankName = this.rankList[index].text
				this.form.kh_rank = this.rankList[index].id
				this.rankList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
      rankClose() {
        this.formName.rankName = '选择'
        this.form.kh_rank = '';
        this.rankList.forEach((item,i)=>{
            item.color = ''
        })
      },
			// 选择客户行业
			hangyeClick(index){
				this.formName.hangyeName = this.hangyeList[index].text
				this.form.kh_hangye = this.hangyeList[index].id
				this.hangyeList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
      hangyeClose(){
        this.formName.hangyeName = '选择'
        this.form.kh_hangye = '';
        this.hangyeList.forEach((item,i)=>{
            item.color = '';
        })
      },
			// 选择客户来源
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
			},	sourceClose(){
				this.formName.sourceName = '选择';
				this.form.source = '';
				this.sourceList.forEach((item,i)=>{
						item.color = ''
				})
			},
			// 获取配置字段
			getBaseConfig() {
        this.$u.post('crm.customer/type', {}).then(res => {
          if(res.code == 1){
            //转换成需要的格式
            this.rankList = this.onJson(res.data.rankList)
            this.hangyeList = this.onJson(res.data.hangyeList)
            this.sourceList = this.onJson(res.data.sourceList)
          }
        });
			},
			// json 转化
			onJson(data) {
				let arr = []
				for (const key in data) {
					if (Object.hasOwnProperty.call(data, key)) {
						let obj = {}
						obj.id = data[key]['name']
						obj.text = data[key]['name']
						arr.push(obj)
					}
				}
				return arr
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
			// 选择客户负责人
			oncompany(val,index) {
				this.companyList.forEach((item,index) => {
					if(val.admin_id == item.admin_id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.formName.companyName = val.username
				this.companyShow = false
				this.form.pr_user = val.username
			},
			// 选择搜索
			adminSearch() {
				this.lastAdmin = false
				this.onSelectpage()
			},
			// 重置
			reset() {
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						if(!this.$u.test.isEmpty(this.form[key])){
							switch (key) {
								case 'scene_id':
									this.sceneList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'rank':
									this.rankList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'hangye':
									this.hangyeList.forEach((item,index)=>{
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
					scene_id: '',
					rank: '',
					hangye: '',
					source: '',
					owner_user_id: '',
					next_time: '',
					last_up_time: '',
					expire_type: '',
				}
				this.formName = {
					next_time_start: '选择',
					next_time_end: '选择',
					last_up_time_start: '选择',
					last_up_time_end: '选择',
					companyName: '选择',
					sceneName: '选择',
					rankName: '选择',
					hangyeName: '选择',
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
				if(this.formName.last_up_time_start != '选择' && this.formName.last_up_time_end != '选择'){
					this.form.last_up_time = this.formName.last_up_time_start + ' - ' + this.formName.last_up_time_end
				}
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						if(!this.$u.test.isEmpty(this.form[key])){
							if( key == 'next_time' || key == 'last_up_time') {
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
				this.$u.vuex('vuex_filter', filterData)
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
