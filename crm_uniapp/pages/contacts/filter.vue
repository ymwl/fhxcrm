<template>
	<view>
		<view class="slot-content">
			<u-cell-group>
				<u-cell-item  title="客户场景"  :value="formName.scopeName" @click="sceneShow = true"></u-cell-item>
				<u-cell-item  title="负责人" :value="formName.companyName" @click="companyShow = true"></u-cell-item>

				<u-cell-item  title="性别"  :value="formName.sexName" @click="sexShow = true"></u-cell-item>
				<u-cell-item  title="决策人" :value="formName.decisionName" @click="decisionShow = true"></u-cell-item>
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
		
		<!-- 选择客户场景 -->
		<u-action-sheet :list="sceneList" v-model="sceneShow" @click="sceneClick"></u-action-sheet>
		
		<!-- 选择性别-->
		<u-action-sheet :list="sexList" v-model="sexShow" @click="sexClick"></u-action-sheet>
		<!-- 选择决策 -->
		<u-action-sheet :list="decisionList" v-model="decisionShow" @click="decisionClick"></u-action-sheet>
	</view>
</template>

<script>
	export default {
		data() {
			return {

				sexShow: false,
				decisionShow: false,
				sceneShow: false,
				sceneList: [
				],
				companyList: [],
				sexList: [
					{
						text: '男',
						id: 1
					},
					{
						text: '女',
						id: '0'
					},
					{
						text: '未知',
						id:'-1'
					}
				],
				decisionList: [
					{
						text: '是',
						id: 1
					},
					{
						text: '否',
						id: '0'
					},
					{
						text: '未知',
						id:'-1'
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
			
				adminPage: 1,
				lastAdmin: false,
				adminStatus: 'loadmore',
				adminkeyword: '',
				pageSize: 20,
				companyShow: false,
				timeType: '',
				form: {
					scope: '',
					sex: '',
					decision: '',

				},
				formName: {
					scopeName: '选择',
					sexName: '选择',
					decisionName: '选择',
					companyName: '选择',
			
				}
			};
		},
		onLoad(e) {
			const tempscene = uni.getStorageSync('contacts_scene');
			if(tempscene){
				for (var i in tempscene) {
				    this.sceneList.push({id:i,text:tempscene[i]}); 
				}
			}
			this.getBaseConfig()
			this.onSelectpage()
		},
		onShow(){
		
		},
		
		methods: {
			// 选择场景	
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
			// 选择性别
			sexClick(index) {
				this.formName.sexName = this.sexList[index].text
				this.form.sex = this.sexList[index].id
				this.sexList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
			// 决策
			decisionClick(index){
				this.formName.decisionName = this.decisionList[index].text
				this.form.decision = this.decisionList[index].id
				this.decisionList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},
			// 获取配置字段
			getBaseConfig() {
			
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
			// 获取
			onSelectpage(isNextPage,pages) {
				this.$u.api.onCommonSelectpage({
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.adminkeyword,
					keyField: 'id',
					showField: 'realname',
					"q_word": this.adminkeyword,
					"searchField": "realname",
					model:'admin'
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
			// 选择负责人
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
								case 'sex':
									this.sexList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'decision':
									this.decisionList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
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
					scope: '',
					sex: '',
					decision: '',
					
				}
				this.formName = {
					companyName: '选择',
					scopeName: '选择',
					sexName: '选择',
					decisionName: '选择',
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
							filterData.op[key] = '='
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
				uni.setStorageSync('contacts_filter',filterData);
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
