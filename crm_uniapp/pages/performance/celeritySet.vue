<template>
	<view class="container">
		<view class="set-box">
			<!-- <view class="u-flex topTitle">业绩快速设置</view> -->
			<u-form :model="form" ref="uForm">
				<u-form-item :label="titleName + ':' " label-width="160">
					<u-input type="select"  :select-open="listShow" placeholder="请选择" v-model="groupName" :border="true" @click="listShow = true" />
				</u-form-item>
				<u-form-item label="业绩方式：" label-width="160">
					<u-radio-group v-model="form.config">
						<u-radio v-for="(item, index) in radioList"  shape="square" :key="index" :name="item.id" :disabled="item.disabled">
							{{ item.name }}
						</u-radio>
					</u-radio-group>
				</u-form-item>
				<u-form-item label="年份：" label-width="160"><u-input type="select"  :select-open="selectShow" placeholder="选择年份" v-model="form.year" :border="true" @click="selectShow = true" /></u-form-item>
				<u-form-item label="全年业绩：" label-width="160" :border-bottom="false">
					<view class="u-flex">
						<u-input class="u-flex-1 u-m-r-15" type="digit" v-model="form.yeartarget" @input="inputChang" :border="true"/>
						<u-checkbox v-model="halves"  @change="onHalves">平均到每个月</u-checkbox>
					</view>
				</u-form-item>
				<view class="yearly u-border-bottom">
					<view class="u-flex u-flex-wrap">
						<view class="u-flex item" v-for="(item,index) in yearlyList" :key="index">
							<text class="text">{{item.text}}：</text>
							<u-input class="u-flex-1 u-m-r-15" type="digit" v-model="item.value" @input="inputCalculate" :border="true"/>
						</view>
					</view>
				</view>
			</u-form>
			<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">提交</u-button>
		</view>
		<!-- 时间选择 -->
		<u-picker v-model="selectShow" :hour="true" mode="time" :params="params" @confirm="timeChange"></u-picker>
		<!-- 选择弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="listShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">请选择</text> 
				<view class="" @click="listShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search v-if="type == 1" margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="onSearch"></u-search>
			<view class="u-p-l-25 u-p-r-25 u-p-b-15 u-border-bottom" v-if="type == 1">
				<u-checkbox v-model="allSelect" @change="checkedAll">全选</u-checkbox>
			</view>
			<scroll-view scroll-y style="height: 660rpx;width: 100%;" @scrolltolower="reachBottom">
				<view class="list">
					<block v-if="selectList.length > 0">
						<view class="u-m-b-45 option">
							<block v-if="type == 0">
								<u-radio-group v-model="group_id" :wrap="true" @change="radioGroupChange">
									<u-radio v-for="(item, index) in selectList"  shape="square" :key="index" :name="item.id" :disabled="item.disabled">
										<view class="item u-flex u-border-bottom" >
											<u-parse style="font-size:28rpx;" :html="item.text"></u-parse>
										</view>
									</u-radio>
								</u-radio-group>
							</block>
							<block v-else>
								<u-checkbox-group @change="checkboxGroupChange" :wrap="true">	
									<u-checkbox @change="checkboxChange" v-model="item.checked" :name="item.id" v-for="(item,index) in selectList" :key="index">
										<view class="item u-flex u-border-bottom"  style="font-size:28rpx;">{{item.text}}</view>
									</u-checkbox>
								</u-checkbox-group>
							</block>
						</view>
						<u-loadmore :status="type == 0 ? 'nomore': status " ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
			<view class="bottom_btn u-border-top" v-if="type == 1">
				<u-button size="medium" @click="clear">清空</u-button> 
				<u-button class="u-m-l-15" type="primary"  @click="confirm" size="medium" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定</u-button>
			</view>
		</u-popup>
	</view>
	
</template>

<script>
export default {
	data() {
		return {
			keyword: '',
			titleName: '应用部门',
			listShow: false,
			selectShow: false,
			type: '',
			topTitle: '',
			status: 'loadmore',
			groupName: '',
			page: 1,
			pageSize: 20,
			lastPage: false,
			selectList:[],
			allSelect: false,
			halves: false,
			params: {
				year: true,
			},
			group_id: 0,
			form: {
				year: '',
				yeartarget: '',
				config: 1,
			},
			yearlyList: [
				{
					text: '1月',
					key: 'january',
					value: '',
				},
				{
					text: '2月',
					key: 'february',
					value: '',
				},
				{
					text: '3月',
					key: 'march',
					value: '',
				},
				{
					text: '4月',
					key: 'april',
					value: '',
				},
				{
					text: '5月',
					key: 'may',
					value: '',
				},
				{
					text: '6月',
					key: 'june',
					value: '',
				},
				{
					text: '7月',
					key: 'july',
					value: '',
				},
				{
					text: '8月',
					key: 'august',
					value: '',
				},
				{
					text: '9月',
					key: 'september',
					value: '',
				},
				{
					text: '10月',
					key: 'october',
					value: '',
				},
				{
					text: '11月',
					key: 'november',
					value: '',
				},
				{
					text: '12月',
					key: 'december',
					value: '',
				},
			],
			radioList: [
				{
					id: 1,
					name: '合同金额',
					disabled: false
				},
				{
					id: 2,
					name: '回款金额',
					disabled: false
				}
			],
			radio: '',
			switchVal: false
		};
	},
	onLoad(e) {
		this.type = e.type
		this.form.year = this.$u.timeFormat(new Date(), 'yyyy')
		let title = ''
		switch (this.type) {
			case '0':
				title = '快速设置团队业绩'
				this.groupName = '全部'
				break;
			case '1':
				title = '快速设置人员业绩'
				this.titleName = '选择员工'
				break;
			default:
				break;
		}
		uni.setNavigationBarTitle({
			title: title
		})
		this.getSelectList()
	},
	methods:{
		// onSearch
		onSearch() {
			this.page = 1
			this.lastPage = false
			this.getSelectList()
		},
		// 选择时间
		timeChange(e) {
			this.form.year = e.year
		},
		// 获取列表
		getSelectList(isNextPage,pages) {
			if(this.type == 0) {
				this.$u.api.getGroupdata({
					sort: 'id',
					order: 'desc',
					filter: JSON.stringify({}),
					op: JSON.stringify({})
				}).then(res => {
					if(res.code == 1 ) {
						res.data = this.onJson(res.data)
						let obj = {
							id: 0,
							text: '全部',
							checked: false
						}
						res.data.unshift(obj)
						this.selectList = res.data
					}
				})
			} else {
				this.$u.api.onSelectpage({
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name:  this.keyword,
					keyField: 'id',
					showField: 'realname',
					"q_word": this.keyword,
					"searchField": "realname"
				}).then(res => {
					if(res.code == 1 ) {
						res.data.list = res.data.list.map(item => ({id:item.id, text: item.realname,checked: false}))
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastPage = true
						} 
						//不够一页
						if (res.data.list.length <= 10) {
							this.status = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.selectList = this.selectList.concat(res.data.list)
							return 
						}
						this.selectList = res.data.list
					}
				})
			}
		},
		// 滚动到底部加载更多
		reachBottom() {
			if(this.type == 0) {
				return
			}
			if(this.lastPage || this.status == 'loading') return
			this.status = 'loading'
			setTimeout(() => {
				if(this.lastPage) return
				this.getSelectList(true,++this.page)
				if(this.selectList.length >= 10) this.status = 'loadmore'
				else this.status = 'loading'
			}, 1200)
		},
		// json 转化
		onJson(data) {
			let arr = []
			for (const key in data) {
				if (Object.hasOwnProperty.call(data, key)) {
					let obj = {}
					obj.id = key
					obj.text = data[key]
					obj.checked = false
					arr.push(obj)
				}
			}
			return arr
		},
		// 选择
		onItem(val,i) {
			this.selectList.forEach((item,index) => {
				if(val.id == item.id) {
					item.checked = item.checked ? !item.checked : true
				}
			})
		},
		// 选中某个复选框时，由checkbox时触发
		checkboxChange(e) {
			// console.log(e)
		},
		// 选中任一checkbox时，由checkbox-group触发
		checkboxGroupChange(e) {
			// console.log(e);
			// 是否勾选了全选
			if(this.allSelect){
				this.allSelect = false
			}
		},
		// 选中任一radio时，由radio-group触发
		radioGroupChange(e) {
			console.log(e)
			this.selectList.map(val =>{
				if(val.id == e) {
					this.groupName = val.text.replace(/&nbsp;/ig, "")
				}
			})
			this.listShow = false
		},
		// 全选
		checkedAll(e) {
			this.selectList.map(val => {
				val.checked = e.value;
			})
		},
		// 清空
		clear() {
			this.allSelect = false
			this.groupName = ''
			this.selectList.map(val => {
				val.checked = false;
			})
		},
		// 确定
		confirm() {
			let arr = []
			this.selectList.map(val => {
				if(val.checked) {
					val.text = val.text.replace(/&nbsp;/ig, "")
					arr.push(val.text)
				}
			})
			this.groupName = arr.join(',')
			this.listShow = false
		},
		// 总数改变
		inputChang() {
			if(this.halves){
				let number = this.form.yeartarget / 12
				this.yearlyList.map(val => {
					val.value = this.halves ? number.toFixed(2) : 0.00
				})
			}
		},
		// 月份金额改变
		inputCalculate() {
			let num = 0
			this.yearlyList.forEach((item,index)=>{
				if(item.value) {
					num +=  parseFloat(item.value)
				}
			})
			this.form.yeartarget = num
		},
		// 平分
		onHalves(e) {
			let number = this.form.yeartarget / 12
			this.yearlyList.map(val => {
				val.value = e.value ? number.toFixed(2) : 0.00 
			})
		},
		// 提交
		submit() {
			// 参数处理 
			this.yearlyList.map(val => {
				console.log()
				this.form[val.key] = val.value
			})
			console.log(this.form)
			if(this.type == 0) {
				this.form.group_id = this.group_id
				let param = {}
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						param['row[' + key + ']'] = this.form[key]
					}
				}
				console.log(param)
				this.$u.api.onBatchTeam(param).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: '操作成功',
							icon: 'success',
							duration: 2000
						})
						setTimeout(() => {
							uni.navigateBack();
						}, 1000);
					}
				})
			}else {
				let idArr = [] 
				this.selectList.map(val => {
					if(val.checked) {
						idArr.push(val.id)
					}
				})
				this.form.admin_id = idArr.join(',')
				let param = {}
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						param['row[' + key + ']'] = this.form[key]
					}
				}
				console.log(param)
				this.$u.api.onBatchAdmin(param).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: '操作成功',
							icon: 'success',
							duration: 2000
						})
						setTimeout(() => {
							uni.navigateBack();
						}, 1000);
					}
				})
			}
		}
	}
};
</script>

<style lang="scss">
.container {
	overflow: hidden;
}
.set-box {
	padding: 0rpx 22rpx;
	margin-bottom: 80rpx;
	.topTitle {
		margin: 25rpx 0;
		font-size: 30rpx;
		font-weight: 700;
		text-align: center;
	}
	.yearly {
		padding-bottom: 25rpx;
		.item {
			width: 50%;
			margin-bottom: 15rpx; 
			.text {
				width: 90rpx;
    		text-align: right;
			}
		}
	}
  .option {
    .text {
      color: #747474;
      font-size: 26rpx;
      text-align: justify;
      padding-bottom: 15rpx;
    }
    .input-box {
      width: 200rpx;
      margin-right: 15rpx;
    }
  }
	.list {
		margin-bottom: 45rpx;
		.item {
			justify-content: space-between;
			height: 55px;
			font-size: 22rpx;
			font-weight: 600;
		}
	}
 }
 	/deep/ .u-checkbox__label {
	 width: 100%;
 	}
  /deep/.u-radio__label {
		width: 100%;
	}
	/deep/ .u-checkbox-group {
		width: 100%;
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
		.option {
			padding: 0 25rpx;
		}
		.item {
			justify-content: space-between;
			height: 55px;
			width: 100%;
			font-size: 22rpx;
			font-weight: 600;
		}
	}
	.bottom_btn {
		text-align: right;
		padding: 28rpx 10rpx 45rpx;
	}
}
</style>
