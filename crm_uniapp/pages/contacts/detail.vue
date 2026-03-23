<template>
	<view class="container">
		<view class="bare" :style="{backgroundColor: vuex_theme.color,}">
			<!-- 顶部导航高度 -->
			<view class="statusBar"></view>
		</view>
		<view class="notice">
			<view class="belong u-flex">
				<view class="left">
					<view class="store">{{contactData.name}}</view>
				</view>
				<!-- <view class="right"><u-icon name="arrow-right" color="#FF7159" :size="26"></u-icon></view> -->
			</view>
			<view class="relation u-flex" v-if="contactData.mobile" >
				<view class="left dial" @click="call(contactData.mobile)">{{contactData.mobile}}</view>
				<view class="right u-flex"  >
					<u-icon name="yunhu" custom-prefix="custom-icon" size="40"></u-icon>
					<view class="dial"  @click="cloudcall">云呼叫</view> 
				</view>
			</view>
			<view class="bottom u-flex" @click="onCustomer(contactData.customer_id)">
				<view class="">归属客户：{{contactData.customer.name}}</view>
				<view class="client_time">查看<u-icon name="arrow-right" :color="vuex_theme.color" :size="26"></u-icon></view>
			</view>
		</view>
		<!-- 详情 -->
		<view class="region u-m-t-35">
			<view class="title">
				<text class="text" :style="{color: vuex_theme.color,borderBottomWidth: '3px', borderBottomStyle: 'solid', borderBottomColor: vuex_theme.color}">基本信息</text>
			</view>
			<view class="details">
				<u-form  ref="uForm" >
					<u-form-item label="电话:" label-width="160" prop="number">
						<u-input disabled v-model="contactData.telephone" />
					</u-form-item>
					<u-form-item label="邮箱：" label-width="160">
						<u-input disabled v-model="contactData.email"  placeholder="暂无" />
					</u-form-item>
					<u-form-item label="微信：" label-width="160" >
						<u-input disabled v-model="contactData.wechat"   placeholder="暂无"  />
					</u-form-item>
					<u-form-item label="职务：" label-width="160" prop="money">
						<u-input disabled v-model="contactData.post" placeholder="暂无" />
					</u-form-item>
					<u-form-item label="生日：" label-width="160" prop="money">
						<u-input disabled v-model="contactData.birthday" placeholder="暂无" />
					</u-form-item>
					<u-form-item label="决策人：" label-width="160" >
						<u-input disabled v-model="contactData.decision" placeholder="暂无"/>
					</u-form-item>
					<u-form-item label="地址:" label-width="160" >
						<u-input disabled v-model="contactData.detail_address" placeholder="暂无"  />
					</u-form-item>
					<u-form-item label="下次跟进:" label-width="160" >
						<u-input disabled v-model="contactData.next_time" placeholder="暂无"  />
					</u-form-item>
					<!-- 自定义字段详细组件 -->
					<f-details :fields="fields" :form="contactData"></f-details>
				</u-form>
			</view>
		</view>
	<!-- 底部按钮 -->
	<view class="bottom-btn u-border-top" >
		<view class="btn entity" :style="{backgroundColor: vuex_theme.color,}" @click="onEdit">
			<text class="u-m-l-15">修改</text>
		</view>
	</view>
	</view>
</template>

<script>
import { processingImages,getImgUrl,get_date} from '@/common/mUtils'
	export default {
		data() {
			return {
				id:'',
				contactData: {
					customer:{
						name: '',
					}
				},
				background: {
					backgroundColor: '#FE644A'
				},
				fields: [],
				form: '',
			};
		},
		onLoad(e) {
			this.id = e.id
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
			this.getData()
		},
		onReady() {
			
		},
		filters: {
			
		},
		methods: {
			changeDecision(val){
				switch (val) {
					case -1:
						return '未知'
						break;
					case 1:
						return '是'
						break;
					case 2:
						return '否'
						break;
						return '--'
						break;
				}
			},
			// 打电话
			call() {
				uni.makePhoneCall({
					phoneNumber: this.contactData.mobile 
				});
			},
			// 云呼叫
			cloudcall(){
				this.$u.api.onCloudcall({
					type: 'customer_contacts',
					typeid: this.contactData.id,
					field: this.contactData.mobile,
					prefix: '',
				}).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: '操作成功',
							icon: 'success',
							duration: 2000
						})
					}
				})
			},
			// 格式化时间
			timeFormat(val) {
				if(val) {
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM');
				} else {
					return '--'
				}
				
			},
			// 获取数据详情
			getData() {
				this.$u.api.getContactsEdit({id: this.id}).then(res => {
					if(res.code == 1 ) {
						this.contactData = res.data
						this.contactData.decision = this.changeDecision(this.contactData.decision)
						this.getFields()
					}
				})
			},
			// 获取自定义字段
			getFields() {
				let arr = []
				this.$u.api.getFields({table: 'customer_contacts',id: ''}).then((res) => {
					if(res.code == 1){
						this.fields = res.data.fields;
						res.data.fields.forEach((item,index)=>{
							
						})
						this.fields.map(item => {
							//表单赋值
							// 复选框 数据格式化
							if(item.type == 'checkbox'|| item.type == 'radio') {
								let arr = []
								let valArr = []
								// 获取对应字段数据
								if(this.contactData[item.name]) {
									valArr = this.contactData[item.name].split(',')
								}
								for (const key in item.content_list) {
									if (Object.hasOwnProperty.call(item.content_list, key)) {
										valArr.forEach((i,s) => {
											if(i == key) {
												arr.push(item.content_list[key]) 
											}
										});
									}
								}
								item.values = arr.join(',')
							}
							// 数据赋值
							if(this.contactData[item.name]) {
							 if ((item.type == 'image' || item.type == 'files') || (item.type == 'images' || item.type == 'files')) {
									let arr = []
									 this.contactData[item.name].split(',').forEach((u,index) =>{
										arr.push(getImgUrl(u))
									})
									item.fileArr = arr 
								} 
								if(item.type == 'switch'){
									item.values = this.contactData[item.name] == 1 ? true : false
								}
								if((item.type == 'select' || item.type == 'selects') || (item.type == 'selectpage' || item.type == 'selectpages')){
									let arrs = this.contactData[item.name].split(',')
									let narrs = []
									arrs.forEach((r,x) => {
										narrs.push(item.content_list[r]) 
									});
									item.values = narrs.join(',')
								}
								if(item.type == 'array') {
									let val = this.contactData[item.name]
									if (val) {
										if (this.$u.test.object(val)) {
											let arr = [];
											for (let i in val) {
												arr.push({
													key: i,
													value: val[i]
												});
											}
											if (arr.length > 0) {
												this.list = arr;
											}
										} else {
											let o = JSON.parse(val);
											let arr = [];
											for (let i in o) {
												arr.push({
													key: i,
													value: o[i]
												});
											}
											if (arr.length > 0) {
												item.arrList = arr;
											}
										}
									} else {
										item.arrList = []
									}
								}
							} else {
								item.values = ''
							}
						});
					}
				})
			},
			// 切换导航栏
			change(index) {
				this.current = index;
			},
			// 查看客户详情
			onCustomer(id) {
				this.$u.route('pages/client/customerDetails',{
					id: id
				});
			},
			onEdit(id){
				this.$u.route('pages/contacts/addPerson',{
					type:'edit',
					id: this.id,
					customer_id: this.contactData.customer_id
				});
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
	min-height: 100vh;
}
.status_bar {
	height: var(--status-bar-height);
	width: 100%;
}
.bare {
	color: #fff;
	background-color: #FE644A; 
}
.notice {
	margin: -68px 8px 10px 8px;
	left: 0;
	right: 0;
	background-color: #fff;
	border-radius: 10px;
	padding: 25rpx 20rpx 35rpx;
	.belong {
		justify-content: space-between;
		.left {
			display: flex;
			align-items: center;
			.store {
				font-size: 30rpx;
				font-weight: bold;
			}
		}
		.right {
			color: #FF7159;
		}
	}
	.relation {
		justify-content: space-between;
		margin-top: 15rpx; 
		font-size: 26rpx;
		.dial {
			color: #2979ff;
			font-size: 14px;
			border-bottom: 1px solid #2979ff;
			padding-bottom: 0px;
		}
	}
	.bottom {
		margin-top: 25rpx;
		justify-content: space-between;
		.client_time {
			color: #777;
  		font-size: 26rpx;
		}
	}
}
.region {
	// background-color: #fff;
	.title {
		text-align: center;
		font-size: 35rpx;
		font-weight: 700;
		.text {
			color: #FE644A;
			border-bottom: 6rpx solid #FE644A;
		}
	}
	.details {
		padding: 45rpx 25rpx 160rpx;
		
	}
}
.statusBar {
	position: relative;
	z-index: -1;
	height: 188rpx;
}
.bottom-btn {
	position: fixed;
	display: flex;
	align-items: center;
	justify-content: flex-end;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 25rpx 30rpx 45rpx;
	background-color: #fff;
	z-index: 100;
	.btn {
		float: right;
		line-height: 43rpx;
		padding: 15rpx 36rpx;
		border-radius: 5px;
		font-size: 28rpx;
		text-align: center;
	}
	.sky {
		color: #FF6146;
		background-color: #F7F7F7;
	}
	.entity {
		color: #fff;
		background-color: #FF6146; 
	}
}
.slot-btn {
	position: relative;
	margin: 0 15rpx 15rpx 0;  
	width: 150rpx;
	height: 150rpx;
	display: flex;
	justify-content: center;
	flex-direction: column;
	align-items: center;
	background: rgb(244, 245, 246);
	border-radius: 10rpx;
	.text {
		font-size: 26rpx;
		margin-top: 20rpx;
    line-height: 40rpx;
	}
}

</style>
