<template>
	<view>
		<view class="slot-content">
			<u-cell-group>
				<u-cell-item  title="数据类型"  :value="formName.configName" @click="configShow = true"></u-cell-item>
				<view class="time u-border-bottom">
					<view class="title">时间</view>
					<view class="u-flex">
						<view class="item u-flex-1" @click="selectTimeShow('year')">
							<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
							<text class="u-font-26">年份：</text>
							<text class="u-font-26" style="color: #909399;">{{formName.yearName}}</text>
						</view>
						<view class="line"></view>
						<view class="item u-flex-1" @click="selectTimeShow('month')">
							<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
							<text class="u-font-26">月份：</text>
							<text class="u-font-26" style="color: #909399;">{{formName.monthName}}</text>
						</view>
					</view>
				</view>
			</u-cell-group>
			<view class="bottom-btn">
				<u-button class="u-m-r-15" type="default" size="medium" @click="reset">重置</u-button>
				<u-button type="primary" size="medium" @click="onConfirm" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定</u-button>
			</view>
		</view>
		<!-- 选择场景 -->
		<u-action-sheet :list="configList" v-model="configShow" @click="configClick"></u-action-sheet>
		<!-- 时间选择 -->
		<u-picker v-model="timeShow" :hour="true" mode="time" :params="params" @confirm="timeChange"></u-picker>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				timeShow: false,
				configShow: false,
				configList: [
					{
						text: '合同金额	',
						id: 1
					},
					{
						text: '回款金额',
						id: 2
					},
				],
				params: {
					year: true,
					month: true,
				},
				form: {
					config: '',
					year: '',
					month: '',
				},
				formName: {
					yearName: '选择',
					monthName: '选择',
					configName: '选择',
					timeName: '',
				},
				timeType: '',
			};
		},
		onLoad(e) {
			
		},
		onShow(){
			if(!this.$u.test.isEmpty(this.vuex_Tfilter.filter)) {
				// 已选数据合并
				this.form = Object.assign(this.form,this.vuex_Tfilter.filter)
				this.formName = Object.assign(this.formName,this.vuex_Tfilter.formName)
				console.log(this.form ,this.formName)
			}
		},
		methods: {
			// 时间窗口
			selectTimeShow(text) {
				this.timeType = text
				if(text == 'year') {
					this.params.year = true
					this.params.month = false
				} else {
					this.params.year = false
					this.params.month = true
				}
				this.timeShow = !this.timeShow
			},
			// 选择时间
			timeChange(e) {
				console.log(e)
				switch (this.timeType) {
					case 'year':
						this.form.year = e.year
						this.formName.yearName = e.year
						break;
					case 'month':
						this.form.month = e.month
						this.formName.monthName = e.month
						break;
					default:
						break;
				}
			},
			// 选择数据类型
			configClick(index) {
				this.formName.configName =  this.configList[index].text
				this.configList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
				this.form.config = this.configList[index].id
			},
			// 重置
			reset() {
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						if(!this.$u.test.isEmpty(this.form[key])){
							switch (this.form[key]) {
								case 'config':
									this.configList.forEach((item,index)=>{
										if(key = item.id) {
											item.color = ""
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
					year: '',
					month: '',
				}
				this.formName = {
					yearName: '选择',
					monthName: '选择',
					configName: '选择',
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
				this.$u.vuex('vuex_Tfilter', filterData)
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
		margin-bottom: 45rpx;
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
