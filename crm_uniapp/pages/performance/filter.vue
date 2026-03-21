<template>
	<view>
		<view class="slot-content">
			<u-cell-group>
				<u-cell-item  title="选择年份" :value="formName.search_time" @click="timeShow = true"></u-cell-item>
				<u-cell-item  title="业绩类型" :value="formName.configName" @click="configShow = true"></u-cell-item>
				<u-cell-item v-if="type == 0"  title="成员组"  :value="formName.groupName" @click="selectShow = true"></u-cell-item>
				<u-cell-item v-if="type == 1"  title="选择员工" :value="formName.companyName" @click="companyShow = true"></u-cell-item>
			</u-cell-group>
			<view class="bottom-btn">
				<u-button class="u-m-r-15" type="default" size="medium" @click="reset">重置</u-button>
				<u-button type="primary" size="medium" @click="onConfirm" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定</u-button>
			</view>
		</view>
		<!-- 选择员工 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="companyShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择员工</text> 
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
		<!-- 选择弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="selectShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择管理组</text> 
				<view class="" @click="selectShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="onSearch"></u-search>
			<scroll-view scroll-y style="height: 660rpx;width: 100%;">
				<view class="list">
					<block v-if="adminList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in adminList" :key="index" @click="onItem(item,index)">
								<u-parse :html="item.text"></u-parse>
								<!-- <view class="title">{{item.text}}</view> -->
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="status" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
		<!-- 时间选择 -->
		<u-picker v-model="timeShow" :hour="true" mode="time" :params="params" @confirm="timeChange"></u-picker>
		<!-- 选择业绩类型 -->
		<u-action-sheet :list="configList" v-model="configShow" @click="configClick"></u-action-sheet>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				type: '',
				selected: -1,
				selectShow: false,
				timeShow: false,
				configShow: false,
				params: {
					year: true,
				},
				configList: [
					{
						id: 1,
						text: '合同金额',
					},
					{
						id: 2,
						text: '回款金额',
					},
				],
				adminList: [],
				companyList: [],
				adminPage: 1,
				lastAdmin: false,
				adminStatus: 'loadmore',
				status: 'loadmore',
				adminkeyword: '',
				keyword: '',
				pageSize: 20,
				companyShow: false,
				timeType: '',
				form: {
					config: '',
					group_id: '',
					search_time: '',
				},
				formName: {
					month: '选择',
					search_time: '选择',
					companyName: '选择',
					groupName: '选择',
					configName: '选择',
				}
			};
		},
		onLoad(e) {
			this.type = e.type
			if(this.type == 0) {
				this.getData()
			} else {
				this.onSelectpage()
			}
		},
		onShow(){
			if(!this.$u.test.isEmpty(this.vuex_pfilter)) {
				// 已选数据合并
				this.form = Object.assign(this.form,this.vuex_pfilter.filter)
				this.formName = Object.assign(this.formName,this.vuex_pfilter.formName)
				console.log(this.form ,this.formName)
			}
		},
		methods: {
			// 选取时间
			selectTime(val) {
				this.selected = val
			},
			// 时间窗口
			selectTimeShow(text) {
				this.timeShow = !this.timeShow
			},
			// 选择时间
			timeChange(e) {
				this.form.search_time = e.year
				this.formName.search_time = e.year
			},
			// 选择
			configClick(index) {
				this.formName.configName = this.configList[index].text
				this.form.config = this.configList[index].id
				this.configList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
			// 获取管理组列表
			getData(isNextPage,pages) {
				this.$u.api.getGroupdata({
					sort: 'id',
					order: 'desc',
					filter: JSON.stringify({username: this.keyword}),
					op: JSON.stringify({username: 'LIKE'})
				}).then(res => {
					console.log(res)
					if(res.code == 1 ) {
						res.data = this.onJson(res.data)
						this.status = 'nomore'
						this.adminList = res.data
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
			// 选择管理组
			onItem(val,i) {
				this.adminList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.form.group_id = val.id
				this.formName.groupName = val.text.replace(/&nbsp;/ig, "")
				this.selectShow = false
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
					model:"admin"
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
				this.form.id = val.id
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
								case 'config':
									this.configList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'group_id':
									this.adminList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.checked = false
										}
									})
									break;
								default:
									break;
							}
						}
					}
				}
				this.form = {
					config: '',
					group_id: '',
					search_time: '',
				}
				this.formName = {
					month: '选择',
					year: '选择',
					companyName: '选择',
					groupName: '选择',
					configName: '选择',
					search_time: '选择',
				}
			},
			// 确定
			onConfirm() {
				let filterData = {
					filter: {},
					op: {},
					formName: {}
				}
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						if(!this.$u.test.isEmpty(this.form[key])){
							if( key == 'search_time') {
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
				this.$u.vuex('vuex_pfilter', filterData)
				uni.navigateBack();
			}
		},
		
	}
</script>

<style lang="scss">
.slot-content{
	background-color: #fff;
	.itemo_tion {
		display: flex;
		align-items: center;
		min-height: 35px;
		padding: 0 33%;
		border-radius: 10rpx;
		border: 1px solid #dcdfe6;
	}
	.selected {
		color: #ffffff;
    border-color: #2979ff;
    background-color: #2979ff;
	}
	
	.time {
		padding: 26rpx 32rpx;
		.title {
			margin-bottom: 15rpx;
		}
		.line {
			width: 20rpx;
			height: 1px;
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
/deep/ .u-grid-item-box {
	padding: 5px 0 !important;
}
</style>
