<template>
	<view>
		<view class="tap">
			<u-button type="default" size="medium" @click="setShow = true">快速设置</u-button>
		</view>
		<view class="sort u-border-bottom">
			<u-dropdown :border-bottom="true">
				<u-dropdown-item class="45" v-model="current" :title="sortName" :options="tapList" @change="optionsChange"></u-dropdown-item>
			</u-dropdown>
			<view class="right-text">
				<view class="u-flex" @click="onFilter">
					{{configName}}/{{editParame.year}}<u-icon class="u-p-l-10" name="shaixuan" custom-prefix="custom-icon" size="35" color="#24D396"></u-icon>
				</view>
			</view>
		</view>
		<scroll-view scroll-x class="sv" show-scrollbar="true">
			<view class="table">
				<u-table align="left" padding="10rpx">
					<u-tr>
						<u-th v-for="(item,index) in thList" :key="index">{{item}}</u-th>
					</u-tr>
					<u-tr v-for="(item,index) in tdList" :key="index">
						<u-td class="table_one">
							<view class="td-on">
								<u-parse :html="item.name ? item.name : item.realname"></u-parse>
							</view>
						</u-td>
						<u-td>
							<view class="td-on">
								{{item.achievement.yeartarget ? item.achievement.yeartarget : 0.00}}
							</view>
						</u-td>
						<u-td>
							<view class="td-item" @click="inputclisk(item,1,'january')">
								{{item.achievement.january ? item.achievement.january : 0.00}}
							</view>
						</u-td >
						<u-td> 
							<view class="td-item" @click="inputclisk(item,2,'february')">
								{{item.achievement.february ? item.achievement.february : 0.00}}
							</view>
						</u-td>
						<u-td> 
							<view class="td-item" @click="inputclisk(item,3,'march')">
								{{item.achievement.march ? item.achievement.march : 0.00}} 
							</view>
						</u-td>
						
						<u-td> 
							<view class="td-item" @click="inputclisk(item,4,'april')">
								{{item.achievement.april ? item.achievement.april : 0.00}} 
							</view>
						</u-td>
						<u-td> 
							<view class="td-item" @click="inputclisk(item,5,'may')">
								{{item.achievement.may ? item.achievement.may : 0.00}} 
							</view>
						</u-td>
						<u-td> 
							<view class="td-item" @click="inputclisk(item,6,'june')">
								{{item.achievement.june ? item.achievement.june : 0.00}} 
							</view>
						</u-td>
						<u-td> 
							<view class="td-item" @click="inputclisk(item,7,'july')">
								{{item.achievement.july ? item.achievement.july : 0.00}} 
							</view>
						</u-td>
						<u-td> 
							<view class="td-item" @click="inputclisk(item,8,'august')">
								{{item.achievement.august ? item.achievement.august : 0.00}} 
							</view>
						</u-td>
						<u-td> 
							<view class="td-item" @click="inputclisk(item,9,'september')">
								{{item.achievement.september ? item.achievement.september : 0.00}} 
							</view>
						</u-td>
						<u-td> 
							<view class="td-item" @click="inputclisk(item,10,'october')">
								{{item.achievement.october ? item.achievement.october : 0.00}} 
							</view>
						</u-td>
						<u-td> 
							<view class="td-item" @click="inputclisk(item,11,'november')">
								{{item.achievement.november ? item.achievement.january : 0.00}} 
							</view>
						</u-td>
						<u-td> 
							<view class="td-item" @click="inputclisk(item,12,'december')">
								{{item.achievement.december ? item.achievement.december : 0.00}} 
							</view>
						</u-td>
					</u-tr>
				</u-table>
			</view>
		</scroll-view>
		<!-- 快速设置 -->
		<u-action-sheet :list="setList" v-model="setShow" @click="setClick"></u-action-sheet>
		<!-- 修改弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="selectShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">{{current == 0 ? '团队业绩编辑' : '成员业绩编辑'}}</text> 
				<view class="" @click="selectShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<view scroll-y style="height: 350rpx;width: 100%;" >
				<view class="list">
					<view class="title"><u-parse :html="titleName"></u-parse></view>
					<u-input v-model="inputVal"  :border="true" placeholder="输入业绩金额" />
				</view>
			</view>
			<view class="bottom_btn u-border-top">
				<u-button size="medium" @click="selectShow = false">取消</u-button> 
				<u-button class="u-m-l-15" type="primary"  @click="onAchievementEdit" size="medium">确定</u-button>
			</view>
		</u-popup>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				selectShow: false,
				sortName: '团队业绩',
				configName: '合同金额',
				current: 0,
				tapList: [
					{
						label: '团队业绩',
						value: 0,
						sort: 'id',
					},
					{
						label: '成员业绩',
						value: 1,
						sort: 'follow_time',
					},
				],
				thList: ['名称','全年','1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'],
				tdList: [],
				setShow: false,
				setList: [
					{
						text: '快速设置业绩（团队）',
						type: 'team',
					},
					{
						text: '快速设置业绩（成员）',
						type: 'staff',
					},
				],
				parameter: {
					sort: 'id',
					order: 'desc',
				},
				titleName: '',
				inputVal: '',
				typeVal: '',// 已选那个月
				editParame: {}//编辑参数
			};
		},
		onLoad(e) {
			
		},
		onUnload() {
			// 页面销毁，清除筛选数据
			this.$u.vuex('vuex_pfilter', {})
		},
		onShow(){
			// 筛选参数
			let filterObj = {}
			let opObj = {}
			if(!this.$u.test.isEmpty(this.vuex_pfilter.filter)) {
				filterObj = this.vuex_pfilter.filter
				opObj = this.vuex_pfilter.op
				if(this.vuex_pfilter.formName.configName){
					this.configName = this.vuex_pfilter.formName.configName
				}
			} else {
				this.configName = '合同金额'
			}
			this.parameter.filter = JSON.stringify(filterObj),
			this.parameter.op = JSON.stringify(opObj)
			if(this.current == 0){
				this.getGroupdata()
			} else {
				this.getAdminGroupdata()
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
			}
		},
		methods: {
			// 排序
			optionsChange(){
				this.sortName = this.tapList[this.current].label
				if(this.current == 0) {
					this.getGroupdata()
				} else {
					this.getAdminGroupdata()
				}
			},
			// 切换导航栏
			change(index) {
				this.current = index;
				if(index == 0) {
					this.getGroupdata()
				} else {
					this.getAdminGroupdata()
				}
			},
			// 获取数据
			getGroupdata(){
				this.$u.api.getAchievementIndex(this.parameter).then(res => {
					if(res.code == 1 ) {
						this.tdList = res.data.rows
						this.editParame = res.data.extend
					}
				})
			},
			// 获取管理员数据
			getAdminGroupdata(){
				this.$u.api.getAchievementAdmin(this.parameter).then(res => {
					if(res.code == 1 ) {
						console.log(res.data)
						this.tdList = res.data.rows
						this.editParame = res.data.extend
					}
				})
			},
			// 快速设置
			setClick(index) {
				this.$u.route('pages/performance/celeritySet',{
					type: index
				});
			},
			// 筛选
			onFilter() {
				this.$u.route('pages/performance/filter',{
					type: this.current
				});
			},
			// 选中业绩
			inputclisk(val,index,type) {
				// 赋值
				this.titleName = (val.name ? val.name : val.realname) + '（' + this.thList[index + 1] + ')'
				this.inputVal = val.achievement[type]
				this.typeVal = type
				this.editParame.field = type
				this.editParame[type] = this.inputVal
				this.current == 0 ? this.editParame.group_id = val.id: this.editParame.admin_id = val.id 
				this.selectShow = true
			},
			// 业绩设置
			onAchievementEdit() {
				console.log(this.editParame)
				let obj = {}
				for (const key in this.editParame) {
					if (this.editParame.hasOwnProperty.call(this.editParame, key)) {
						if(this.typeVal == key) {
							obj['row[' + key + ']'] = this.inputVal
						} else {
							obj['row[' + key + ']'] = this.editParame[key]
						}
					}
				}
				console.log(obj)
				if(this.current == 0) {
					// 团队业绩编辑
					this.$u.api.onAchievementEdit(obj).then(res => {
						if(res.code == 1 ) {
							console.log(res.data)
								// 提示
								uni.showToast({
									title: '操作成功',
									icon: 'success',
									duration: 2000
								})
								this.selectShow = false
								this.getGroupdata()
						}
					})
				} else {
					// 成员业绩编辑
					this.$u.api.onAchievementAedit(obj).then(res => {
						if(res.code == 1 ) {
								// 提示
								uni.showToast({
									title: '操作成功',
									icon: 'success',
									duration: 2000
								})
								this.selectShow = false
								this.getAdminGroupdata()
						}
					})
				}
				
			}
		}
	}
</script>

<style lang="scss">
.tap {
	justify-content: space-between;
  padding: 10rpx 20rpx;
}
.table {
	min-width: 1800px;
	padding: 30rpx 15rpx;
}
.table_one {
	border-top: 0;
	border-left: 0;
	position: relative;
}
.td-on {
	padding: 25rpx 0;
}
.td-item {
	padding: 25rpx 0;
	color: #2979ff;
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
	}
	/deep/ .u-dropdown__menu__item {
		justify-content: left !important;
		padding-left: 40rpx;
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
		padding: 0 20rpx;
		.title{
			padding: 25rpx 0;
			font-size: 30rpx;
			font-weight: 600;
		}
	}
	.bottom_btn {
		text-align: right;
		padding: 28rpx 10rpx 45rpx;
	}
}

</style>
