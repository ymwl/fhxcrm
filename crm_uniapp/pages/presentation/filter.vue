<template>
	<view>
		<view class="slot-content">
			<u-cell-group>
				<view class="time u-border-bottom">
					<view class="title">时间</view>
					<u-grid :col="3" :border="false">
						<u-grid-item>
							<view class="itemo_tion" :class="selected == 0 ? 'selected' : ''" @click="selectTime(0)">
								<text class="u-font-26">本月</text>
							</view>
						</u-grid-item>
						<u-grid-item>
							<view class="itemo_tion" :class="selected == 1 ? 'selected' : ''" @click="selectTime(1)">
								<text class="u-font-26">上月</text>
							</view>
						</u-grid-item>
						<u-grid-item v-if="filterType == 3" >
							<view class="itemo_tion" :class="selected == 2 ? 'selected' : ''" @click="selectTime(2)">
								<text class="u-font-26">本年</text>
							</view>
						</u-grid-item>
						<u-grid-item v-if="filterType == 3">
							<view class="itemo_tion" :class="selected == 3 ? 'selected' : ''" @click="selectTime(3)">
								<text class="u-font-26">去年</text>
							</view>
						</u-grid-item>
					</u-grid>
					<view class="u-flex u-m-t-15">
						<view class="item u-flex-1" @click="selectTimeShow()">
							<text class="u-font-26">自定义：</text>
							<text class="u-font-26" style="color: #909399">{{formName.search_time}}</text>
						</view>
					</view>
				</view>
				<u-cell-item v-if="filterType == 1 || filterType == 2 "  title="选择部门"  :value="formName.groupName" @click="selectShow = true"></u-cell-item>
				<u-cell-item v-if="filterType == 1 || filterType == 2" title="选择用户" :value="formName.companyName" @click="companyShow = true"></u-cell-item>
				<u-cell-item v-if="filterType == 3"  title="排名方式" :value="formName.typeName" @click="typeShow = true"></u-cell-item>
			</u-cell-group>
			<view class="bottom-btn">
				<u-button class="u-m-r-15" type="default" size="medium" @click="reset">重置</u-button>
				<u-button type="primary" size="medium" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true" @click="onConfirm">确定</u-button>
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
			<!-- <u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="onSearch"></u-search> -->
			<scroll-view scroll-y style="height: 660rpx;width: 100%;" >
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
						<u-loadmore status="nomore" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
		<!-- 时间选择 -->
		<u-calendar v-model="timeShow" mode="range"  @change="timeChange"></u-calendar>
		<!-- 选择排名类型 -->
		<u-action-sheet :list="typeList" v-model="typeShow" @click="typeClick"></u-action-sheet>
	</view>
</template>

<script>
import {getMonth,getYear} from '@/common/mUtils'
	export default {
		data() {
			return {
				filterType: '',
				selected: -1,
				selectShow: false,
				timeShow: false,
				typeShow: false,
				typeList: [
					{
						type_id: '1',
						text: '合同金额',
					},
					{
						type_id: '2',
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
				page: 1,
				pageSize: 20,
				companyShow: false,
				timeType: '',
				form: {
					type_id: '',
					group_id: '',
					owner_user_id: '',
					start_date: '',
					end_date: '',
					search_time: '',
				},
				formName: {
					search_time: '选择',
					groupName: '选择',
					typeName: '选择',
					companyName: '选择'
				}
			};
		},
		onLoad(e) {
			// 筛选数据类型
			this.filterType = e.type // 1 (this.vuex_Afilter) 2(this.vuex_Cfilter) 3(this.vuex_rfilter)
			this.getData()
			this.onSelectpage()
		},
		onShow(){
			switch (this.filterType) {
				case '1':
					if(!this.$u.test.isEmpty(this.vuex_Afilter)) {
						// 已选数据合并
						this.form = Object.assign(this.form,this.vuex_Afilter.filter)
						this.formName = Object.assign(this.formName,this.vuex_Afilter.formName)
						this.selected = this.vuex_Afilter.selected > -1 ? this.vuex_Afilter.selected : -1
					}
					break;
				case '2':
					if(!this.$u.test.isEmpty(this.vuex_Cfilter)) {
						// 已选数据合并
						this.form = Object.assign(this.form,this.vuex_Cfilter.filter)
						this.formName = Object.assign(this.formName,this.vuex_Cfilter.formName)
						this.selected = this.vuex_Cfilter.selected > -1 ? this.vuex_Cfilter.selected : -1
					}
					break;
				case '3':
					console.log(this.vuex_rfilter)
					if(!this.$u.test.isEmpty(this.vuex_rfilter)) {
						// 已选数据合并
						this.form = Object.assign(this.form,this.vuex_rfilter.filter)
						this.formName = Object.assign(this.formName,this.vuex_rfilter.formName)
						this.selected = this.vuex_rfilter.selected > -1 ? this.vuex_rfilter.selected : -1
					}
					break;
			
				default:
					break;
			}
			
		},
		methods: {
			onSearch() {
				this.lastPage = false
				this.getData()
			},
			// 选取时间
			selectTime(val) {
				switch (val) {
					case 0:
						if(this.filterType == 1 || this.filterType == 2) {
							this.form.start_date = getMonth("s",0) + ' 00:00:00' 
							this.form.end_date = getMonth("e",0) + ' 23:59:59'
						}
						if(this.filterType == 3){
							this.form.search_time = getMonth("s",0) + ' 00:00:00 - ' + getMonth("e",0) + ' 23:59:59'
						}
						break;
					case 1:
						if(this.filterType == 1 || this.filterType == 2) {
							this.form.start_date = getMonth("s",-1) + ' 00:00:00' 
							this.form.end_date = getMonth("e",-1) + ' 23:59:59'
						}
						if(this.filterType == 3){
							this.form.search_time = getMonth("s",-1) + ' 00:00:00 - ' + getMonth("e",-1) + ' 23:59:59'
						}
						break;
					case 2:
						if(this.filterType == 1 || this.filterType == 2) {
							this.form.start_date = getYear("s",0) + ' 00:00:00' 
							this.form.end_date = getYear("e",0) + ' 23:59:59'
						}
						if(this.filterType == 3){
							this.form.search_time = getYear("s",0) + ' 00:00:00 - ' + getYear("e",0) + ' 23:59:59'
						}
						break;
					case 3:
						if(this.filterType == 1 || this.filterType == 2) {
							this.form.start_date = getYear("s",-1) + ' 00:00:00' 
							this.form.end_date = getYear("e",-1) + ' 23:59:59'
						}
						if(this.filterType == 3){
							this.form.search_time = getYear("s",-1) + ' 00:00:00 - ' + getYear("e",-1) + ' 23:59:59'
						}
						break;
					default:
						break;
				}
				this.formName.search_time = '选择'
				this.selected = val
			},
			// 时间窗口
			selectTimeShow(text) {
				this.timeShow = !this.timeShow
			},
			// 选择时间
			timeChange(e) {
				this.selected = -1
				// 类型切换 数据赋值
				if(this.filterType == 3) {
					this.form.search_time = e.startDate + ' 00:00:00 - ' + e.endDate + ' 23:59:59'
				} else {
					this.form.start_date = e.startDate + ' 00:00:00'
					this.form.end_date = e.endDate + ' 23:59:59'
				}
				this.formName.search_time = e.startDate + ' 00:00:00 - ' + e.endDate + ' 23:59:59'
			},
			// 选择类型
			typeClick(index) {
				this.formName.typeName = this.typeList[index].text
				this.form.type_id = this.typeList[index].type_id
				this.typeList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
			// 获取管理组列表
			getData(isNextPage,pages) {
				console.log(isNextPage,this.status)
				this.$u.get('auth/getGroupdata', {
					sort_by: 'id',
					sort_order: 'desc',
					offset: (pages || 0 ) * this.pageSize,
					limit: this.pageSize,
					filter: JSON.stringify({username: this.keyword}),
					op: JSON.stringify({username: 'LIKE'})
				}).then(res => {
					console.log(res)
					if(res.code == 1 ) {
						res.data = this.onJson(res.data)
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

			// 获取下级员工
			onSelectpage(isNextPage,pages) {
				this.$u.get('ajax/selectpage', {
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.adminkeyword,
					keyField: 'id',
					showField: 'realname',
					"q_word": this.adminkeyword,
					"searchField": "realname",
					'model':'admin'
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

			// 重置
			reset() {
				this.selected = -1
				this.adminList.forEach((item,index) => {
					item.checked = false
				})
				this.form = {
					type_id: '',
					group_id: '',
					search_time: '',
				}
				this.formName = {
					search_time: '选择',
					groupName: '选择',
					typeName: '选择',
					companyName: '选择'
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
				if(this.selected > -1) {
					filterData.selected = this.selected
				}
				if(this.selected > -1) {
					let name = ''
					switch (this.selected) {
						case -1:
							name = '默认'
							break;
						case 0:
							name = '本月'
							break;
						case 1:
							name = '上月'
							break;
						case 2:
							name = '本年'
							break;
						case 3:
							name = '去年'
							break;
						default:
							break;
					}
					filterData.formName.timeName = name
				} else if (this.formName.search_time) {
					filterData.formName.timeName = '自定义'
				}
				console.log(filterData)
				// 储存 1 (this.vuex_Afilter) 2(this.vuex_Cfilter) 3(this.vuex_rfilter)
				switch (this.filterType) {
					case '1':
						this.$u.vuex('vuex_Afilter', filterData)
						break;
					case '2':
						this.$u.vuex('vuex_Cfilter', filterData)
						break;
					case '3':
						this.$u.vuex('vuex_rfilter', filterData)
						break;
					default:
						break;
				}
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
