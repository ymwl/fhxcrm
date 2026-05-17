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
				<view class="">归属客户：{{contactData.customer && contactData.customer.name || '--'}}</view>
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
					
					<!-- 字段详情组件（含系统字段与自定义字段） -->
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
						return '未知';
					case 1:
						return '是';
					case 2:
						return '否';
					default:
						return '--';
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
				this.$u.post('crm.setting.cloudcall/call', {
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
				this.$u.get('crm.customer_contacts/edit', {id: this.id}).then(res => {
					if(res.code == 1 ) {
						this.contactData = res.data
						// 确保 customer 对象存在，防止模板渲染时报错
						if (!this.contactData.customer) {
							this.contactData.customer = { name: '--' }
						}
						// 格式化日期字段（时间戳 → 可读格式）
						if (this.contactData.next_time) {
							this.contactData.next_time = this.timeFormat(this.contactData.next_time)
						}
						if (this.contactData.birthday) {
							this.contactData.birthday = this.timeFormat(this.contactData.birthday)
						}
						// 先获取字段定义，再根据字段定义解析关联数据
						this.getFields()
					}
				})
			},
			// 根据 customer_id 获取客户名称（使用 crm.customer/index + filter 精确查找）
			getCustomerName() {
				if (!this.contactData.customer_id) return
				const href = 'crm.customer/index?isselect=1'
				const [basePath, queryStr] = href.split('?')
				let params = {
					offset: 0,
					limit: 1
				}
				if (queryStr) {
					queryStr.split('&').forEach(pair => {
						const [k, v] = pair.split('=')
						if (k) params[k] = decodeURIComponent(v || '')
					})
				}
				params.filter = JSON.stringify({ id: this.contactData.customer_id })
				params.op = JSON.stringify({ id: '=' })
				this.$u.get(basePath, params).then(res => {
					if (res.code != 1) return
					let item = null
					if (res.data && res.data.rows && res.data.rows.length > 0) {
						item = res.data.rows[0]
					} else if (res.data && res.data.list && res.data.list.length > 0) {
						item = res.data.list[0]
					} else if (Array.isArray(res.data) && res.data.length > 0) {
						item = res.data[0]
					}
					if (item) {
						// 更新底部"归属客户"区域
						this.contactData.customer = item
						// 回填 popup_selection 字段的显示值
						const popupField = this.fields.find(f => f.field == 'customer_id' && f.formtype == 'popup_selection')
						if (popupField) {
							this.$set(popupField, 'values', item.name)
						}
					}
				})
			},
			// 获取自定义字段
			getFields() {
				this.$u.get('fields/get_fields', {table: 'crm_customer_contacts', source: 'editForm'}).then((res) => {
					if(res.code == 1){
						this.fields = res.data.fields
						this.fields.forEach(item => {
							// 复选框/单选框 数据格式化
							if(item.formtype == 'checkbox'|| item.formtype == 'radio') {
								let arr = []
								let valArr = []
								// 获取对应字段数据
								if(this.contactData[item.field]) {
									valArr = this.contactData[item.field].split(',')
								}
								const optionList = item.selectList || item.content_list;
								for (const key in optionList) {
									if (Object.hasOwnProperty.call(optionList, key)) {
										valArr.forEach((i,s) => {
											if(i == key) {
												arr.push(optionList[key]) 
											}
										});
									}
								}
								item.values = arr.join(',')
							}
							// 数据赋值
							if(this.contactData[item.field]) {
							if ((item.formtype == 'image' || item.formtype == 'images') || (item.formtype == 'file' || item.formtype == 'files')) {
									let arr = []
									 this.contactData[item.field].split(',').forEach((u,index) =>{
										arr.push(getImgUrl(u))
									})
									item.fileArr = arr 
								} 
								if(item.formtype == 'switch'){
									item.values = this.contactData[item.field] == 1 ? true : false
								}
								if((item.formtype == 'select' || item.formtype == 'selects') || (item.formtype == 'lselect' || item.formtype == 'selectpages')){
									let arrs = this.contactData[item.field].split(',')
									let narrs = []
									const optionList = item.selectList || item.content_list;
									arrs.forEach((r,x) => {
										narrs.push(optionList[r]) 
									});
									item.values = narrs.join(',')
								}
								if(item.formtype == 'array') {
									let val = this.contactData[item.field]
									if (val) {
										if (this.$u.test.object(val)) {
											let arr = [];
											for (let i in val) {
												arr.push({
													key: i,
													value: val[i]
												});
											}
											item.arrList = arr;
										} else {
											let o = JSON.parse(val);
											let arr = [];
											for (let i in o) {
												arr.push({
													key: i,
													value: o[i]
												});
											}
											item.arrList = arr;
										}
									} else {
										item.arrList = []
									}
								}
							} else {
								item.values = ''
							}
						});
						// 字段处理完后，解析 popup_selection 关联数据（如归属客户名称）
						this.getCustomerName()
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
