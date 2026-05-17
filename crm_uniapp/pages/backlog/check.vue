<template>
	<view>
		<view class="set-box">
			<u-form :model="form" ref="uForm">
				<u-form-item label="合同编号:" label-width="160" prop="number">
					<u-input disabled v-model="form.number"  :border="true" placeholder="暂无" />
				</u-form-item>
				<u-form-item label="合同名称:" label-width="160" prop="name">
					<u-input disabled v-model="form.name" placeholder="暂无" :border="true" />
				</u-form-item>
				<u-form-item label="选择客户:" label-width="160">
					<u-input disabled  :border="true" placeholder="暂无" v-model="customerName"   />
				</u-form-item>
				<u-form-item label="选择商机:" label-width="160">
					<u-input disabled  :border="true" placeholder="暂无" v-model="businessName"   />
				</u-form-item>
				<u-form-item label="合同金额:" label-width="160" prop="money">
					<u-input disabled v-model="form.money" placeholder="暂无" :border="true" />
				</u-form-item>
				<u-form-item  label="下单时间:" label-width="160" >
					<u-input disabled  :border="true" placeholder="暂无" v-model="form.order_time" />
				</u-form-item>
				<u-form-item label="开始时间:" label-width="160" >
					<u-input disabled  :border="true" placeholder="暂无" v-model="form.start_time" />
				</u-form-item>
				<u-form-item label="结束时间:" label-width="160" >
					<u-input disabled  :border="true" placeholder="暂无" v-model="form.end_time" />
				</u-form-item>
				<u-form-item label="客户签约人:" label-width="160" >
					<u-input disabled  :border="true" placeholder="暂无" v-model="contactsName"  />
				</u-form-item>
				<u-form-item label="公司签约人:" label-width="160">
					<u-input disabled  :border="true" placeholder="暂无" v-model="companyName"  />
				</u-form-item>
				<u-form-item label="审批人:" label-width="160" >
					<u-input disabled  :border="true" placeholder="暂无" v-model="adminName" />
				</u-form-item>
				<u-form-item label="备注 :"  label-width="160">
					<u-input disabled v-model="form.remark" placeholder="暂无" type="textarea"  :border="true" />
				</u-form-item>
				<!-- 自定义字段详情组件 -->
				<f-details :fields="fields" :form="form"></f-details>
			</u-form>
			<!-- 产品 -->
			<view class="u-flex cif-title u-border-bottom" >
				<view class="u-flex-1 text">意向产品</view>
				<!-- <view class="" style="color:#2979ff">
					选择产品<u-icon name="arrow-right" color="#909399" size="30"></u-icon>
				</view> -->
			</view>
			<view class="list">
				<block v-if="selectProduct.length > 0">
					<view class="item" v-for="(item,index) in selectProduct" :key="index">
						<view class="number u-flex">
							<view class="u-flex-1">序号: {{index+1}}</view>
							<u-number-box disabled :min="1" v-model="item.nums" @change="valChange"></u-number-box>
						</view>
						<view class="content u-flex">
							<view class="u-flex-1 name">{{item.name ? item.name : (item.info ? item.info.name : '')}}</view>
							<view class="price u-flex">
								<view class="u-p-r-15" style="font-weight: 600;">￥</view>
								<u-input class="u-flex-1" placeholder="暂无" disabled :clearable="false" v-model="item.price" :border="true" />
							</view>
						</view>
						<view class="brief u-flex">
							<view class="">备注：</view>
							<u-input class="u-flex-1" placeholder="暂无" disabled v-model="item.remarks" :border="true" />
						</view>
						<!-- 删除按钮 -->
						<!-- <view class="close" @click="removeProduct(index)">
							<u-icon name="close"  color="#909399" size="30"></u-icon>
						</view> -->
					</view>
				</block>
				<u-empty text="暂未选择产品" v-else  margin-top="100" mode="list"></u-empty>
			</view>
			<view class="u-flex u-m-b-15 u-m-t-15">
				<view class="u-m-r-15" style="width:150rpx">优惠率：</view>
				<u-input class="u-flex-1" disabled placeholder="暂无" v-model="form.discount_rate" :border="true" @input="inputChang"/>
			</view>
			<view class="u-flex u-m-b-15 u-m-t-15">
				<view class="u-m-r-15" style="width: 150rpx">总额：</view>
				<u-input class="u-flex-1" placeholder="暂无" disabled v-model="form.total_price" :border="true" />
			</view>
			<!-- 底部按钮 -->
			<view class="bottom-btn u-border-top" >
				<view class="btn primary" @click="goCheckLog">审核日志</view>
				<block v-if="checkTrue">
					<view class="btn sky u-m-l-15" @click="onCheck('')">审核</view>
					<view class="btn entity u-m-l-15" @click="onCheck('reject')" :style="{backgroundColor: vuex_theme.color}">驳回</view>
				</block>
			</view>
		</view>
		<!-- 审核弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="checkShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">{{typeCheck == '' ? '审核提示' : '输入驳回理由'}}</text> 
				<view class="" @click="checkShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<view style="height: 760rpx;width: 100%;">
				<block v-if="typeCheck == '' ">
					<view class="u-flex u-m-t-50 u-p-l-20 u-p-r-20">
						<view class="">下个审批人：</view>
						<u-input class="u-flex-1" type="select" :border="true" :select-open="adminShow" v-model="nextAdminName" placeholder="如果要结束审批请不要选择审批人" @click="adminShow = true" />
					</view>
				</block>
				<view v-else class="u-flex u-m-t-50 u-p-l-20 u-p-r-20">
					<u-input class="u-flex-1" v-model="remark" height="250" placeholder="请输入驳回理由" type="textarea"  :border="true" />
				</view>
				<view  style="text-align: center;position: absolute;bottom: 30px;left: 20px;right: 20px">
					<u-button class="u-m-l-15" type="success"  @click="submit" >{{typeCheck == '' ? '审核通过' : '确定'}}</u-button>
				</view>
			</view>
		</u-popup>
		<!-- 选择审批人弹窗 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="adminShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择审批人</text> 
				<view class="" @click="adminShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<u-search margin="30rpx 20rpx" shape="square" v-model="adminkeyword" :show-action="false" :clearabled="true"  placeholder="输入审批人名称搜索" @change="adminSearch"></u-search>
			<scroll-view scroll-y style="height: 760rpx;width: 100%;" @scrolltolower="adminBottom">
				<view class="list">
					<block v-if="adminList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in adminList" :key="index" @click="onAdmin(item,index)">
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
			<view class="bottom_btn u-border-top">
				<u-button size="medium" @click="selected = true">查看已选</u-button> 
				<u-button class="u-m-l-15" type="primary"  @click="chosen" size="medium">选好了</u-button>
			</view>
		</u-popup>
		<!-- 已选审批人 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38" v-model="selected" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">已选审批人</text> 
				<view class="" @click="selected = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<scroll-view scroll-y style="height: 960rpx;width: 100%;" @scrolltolower="reachBottom">
				<view class="list">
					<block v-if="selectList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in selectList" :key="index">
								<view class="title u-p-r-15">{{item.realname}}</view>
								<view class="check-icon">
									<u-button size="mini" type="error" @click="remove(item,index)">删除</u-button>
								</view>
							</view>
						</view>
						<u-loadmore status="nomore" ></u-loadmore>
					</block>
					<u-empty text="暂无数据,请先选择审批人" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
	</view>
</template>

<script>
	import {getImgUrl} from '@/common/mUtils'


	export default {
		data() {
			return {
				searchTimer: null,
				contractData: {
					row: {
						check_status: 0
					}
				},
				next_admin_id: '',
				typeCheck: '',
				remark: '',
				checkShow:false,
				selected: false,
				adminShow: false,
				customerName: '',
				businessName: '',
				contactsName: '',
				adminName: '',
				nextAdminName: '',
				companyName: '',
				adminkeyword: '',
				pageSize: 20, // 每一页多少条数据
				adminPage: 1,
				lastAdmin: false,
				adminStatus: 'loadmore',
				selectList: [],
				selectProduct: [],
				adminList:[],
				type: '',
				id: '',
				form: {},
				fields: [],
				checkTrue: false,
				params: '',
			};
		},
		onLoad(e) {
			this.params = e
			if(this.params.type == "look") {
				uni.setNavigationBarTitle({
					title: '合同详情'
				});
			}
			if(e.id) {
				this.id = e.id
				this.getContractEdit()
				this.onSelectpage()
			}
		},
		onShow() {},
		methods: {
			// 搜索
			adminSearch(){
				clearTimeout(this.searchTimer)
				this.searchTimer = setTimeout(() => {
					this.adminPage = 1
					this.lastAdmin = false
					this.onSelectpage()
				}, 500)
			},
			// 获取自定义字段
			getFields() {
				let arr = []
				this.$u.get('fields/get_fields', {table: 'contract',id: ''}).then((res) => {
					if(res.code == 1){
						res.data.fields.forEach((item,index)=>{
							// 复选框 数据格式化
							if(item.type == 'checkbox'|| item.type == 'radio') {
								let arr = []
								let valArr = []
								// 获取对应字段数据
								if(this.form[item.name]) {
									valArr = this.form[item.name].split(',')
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
							if(this.form[item.name]) {
							 if ((item.type == 'image' || item.type == 'files') || (item.type == 'images' || item.type == 'files')) {
									let arr = []
									 this.form[item.name].split(',').forEach((u,index) =>{
										arr.push(getImgUrl(u))
									})
									item.fileArr = arr 
								} 
								if(item.type == 'switch'){
									item.values = this.form[item.name] == 1 ? true : false
								}
								if((item.type == 'select' || item.type == 'selects') || (item.type == 'selectpage' || item.type == 'selectpages')){
									let arrs = this.form[item.name].split(',')
									let narrs = []
									arrs.forEach((r,x) => {
										narrs.push(item.content_list[r]) 
									});
									item.values = narrs.join(',')
								}
								if(item.type == 'array') {
									let val = this.form[item.name]
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
						})
						this.fields = res.data.fields
					}
				})
			},
			// 获取合同详情
			getContractEdit() {
				if(this.params.type == 'look'){
					// 查看合同详情
					this.$u.get('crm.contract.index/edit', {
						id: this.id,
						types:'contract',
					}).then(res => {
						if(res.code == 1 ) {
							this.carryOut(res)
						}
					})
				} else {
					// 查看合同详情（审批的时候）
					this.$u.get('crm.backlog/verify', {
						id: this.id,
						types:'contract',
					}).then(res => {
						if(res.code == 1 ) {
							this.carryOut(res)
						}
					})
				}
			},
			carryOut(res){
				this.contractData = res.data
				// 数据赋值
				let data = res.data.row
				this.form = res.data.row
				this.form.id = data.id
				this.form.order_time = this.$u.timeFormat( data.order_time, 'yyyy-mm-dd hh:MM')
				this.form.start_time = this.$u.timeFormat( data.start_time, 'yyyy-mm-dd hh:MM') 
				this.form.end_time = this.$u.timeFormat( data.end_time, 'yyyy-mm-dd hh:MM')
				this.selectProduct = data.product
				// 是否可以审核
				if(this.form.check_status == '0' || this.form.check_status == '1') {
					// flow_admin_id为空不显示
					if(this.form.flow_admin_id != '') {
						let userid = String(uni.getStorageSync('admin_info').id)
						let idArr = this.form.flow_admin_id.split(',')
						// 账号有资格审核显示
						if(idArr.indexOf(userid) > -1){
							this.checkTrue = true
						}	
					}
				}
				// 获取商机
				if(this.contractData.row.business_id){
					this.$u.get('crm.business.index/index', {
						sort_by: 'id',
						sort_order: 'desc',
						filter: JSON.stringify({id: this.contractData.row.business_id}),
						op: JSON.stringify({id: '='})
					}).then(res => {
						if(res.code == 1 ) {
							if(res.data.rows.length > 0) {
								this.businessName = res.data.rows[0].name
							}
						}
					})
				}
				// 获取审批人数据
				if(this.contractData.row.flow_admin_id){
					this.$u.get('crm.common/selectpage/model/admin/type/all', {
						keyField: 'id',
						showField: 'realname',
						keyValue: this.contractData.row.flow_admin_id,
					}).then(res => {
						if(res.code == 1 ) {
							let name = []
							res.data.list.forEach((item,index) => {
								name.push(item.realname)
							});
							this.adminName = name.join(',')
						}
					})
				}
				// 获取签约人数据
				if(this.contractData.row.order_admin_id){
					this.$u.get('crm.common/selectpage/model/admin/type/all', {
						keyField: 'id',
						showField: 'realname',
						keyValue: this.contractData.row.order_admin_id,
					}).then(res => {
						console.log(res,'89546')
						if(res.code == 1 ) {
							if(res.data.list.length > 0) {
								this.companyName = res.data.list[0].realname
							}
						}
					})
				}
				if(this.contractData.row.customer_id){
					// 获取已选的客户
					this.$u.post('crm.customer.index/selectpage', {
						keyField: 'id',
						showField: 'name',
						"q_word": this.contractData.row.customer_id,
						"searchField": "id",
						type: 'all',
					}).then(res => {
						if(res.code == 1 ) {
							if(res.data.list.length > 0) {
								this.customerName = res.data.list[0].name
							}
						}
					})
				}
				// 获取客户签约人
				if(this.contractData.row.contacts_id) {
					this.$u.get('crm.customer_contacts/index', {
						sort_by: 'id',
						sort_order: 'desc',
						filter: JSON.stringify({id: this.contractData.row.contacts_id }),
						op: JSON.stringify({id: '='})
					}).then(res => {
						if(res.code == 1 ) {
							this.contactsName = res.data.rows[0].name
						}
					})
				}
				// 获取自定义字段
				this.getFields()
			},
			// 查看审核日志
			goCheckLog(){
				this.$u.route('pages/backlog/checkLog',{
					id: this.id,
					flow_id: this.contractData.row.flow_id
				});
			},
			// 审批
			onCheck(val) {
				this.typeCheck = val
				this.next_admin_id = ''
				this.remark = ''
				this.selectList = []
				this.nextAdminName = ''
				if(this.$u.test.isEmpty(this.contractData.flow)){
					// 审批流程被删除，默认直接提交
					this.submit()
					return
				}
				if(this.contractData.flow.config == 1 && val != 'reject') {
					// 审核配置为固定审批直接提交
					this.submit()
				} else {
					this.checkShow = true
				}
			},
			// 获取审批人
			onSelectpage(isNextPage,pages) {
				this.$u.get('crm.common/selectpage/model/admin/type/all', {
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.adminkeyword,
					keyField: 'id',
					showField: 'realname',
					"q_word": this.adminkeyword,
					"searchField": "realname"
				}).then(res => {
					if(res.code == 1 ) {
						// 已选审批人标记
						res.data.list.forEach((item,index)=>{
							this.selectList.forEach((i,index) => {
								if(i.id == item.id) {
									item.checked = true
								}
							})
						})
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
							this.adminList = this.adminList.concat(res.data.list)
							return 
						}
						this.adminList = res.data.list
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
					if(this.adminList.length >= 10) this.adminStatus = 'loadmore';
					else this.adminStatus = 'loading';
				}, 1200)
			},
			// 选择审批人
			onAdmin(val,i) {
				this.adminList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = item.checked ? !item.checked : true
					}
				})
				// 添加已选
				if(this.adminList[i].checked == true) {
					// 添加已选中
					this.selectList.push(this.adminList[i])
				} else {
					// 删除取消选中
					this.selectList.forEach((item,i) => {
						if(item.id == val.id) {
							this.selectList.splice(i, 1)
						}
					})
				}
			},
			//移除审批人
			remove(val,index) {
				this.adminList.forEach((i,index) => {
					if(val.id == i.id) {
						i.checked = false
					}
				})
				this.selectList.splice(index, 1)
				// 删除完，关闭已选弹窗
				if(this.selectList.length == 0) {
					this.selected = false
				}
			},
			// 审批人选好了
			chosen() {
				let id = []
				let name = []
				this.selectList.forEach((item,index) => {
					id.push(item.id)
					name.push(item.realname)
				});
				this.next_admin_id = id.join(',')
				this.nextAdminName = name.join(',')
				this.adminShow = false
			},
			// 获取商机数据详情
			getBusinessEdit(id) {
				this.$u.get('crm.business.index/edit', {id: id}).then(res => {
					if(res.code == 1 ) {
						this.selectProduct = res.data.product
					}
				})
			},
			// 提交
			submit() {
				if(this.remark == '' && this.typeCheck == 'reject') {
					// 提示
					uni.showToast({
						title: '请选择输入驳回理由',
						icon: 'none',
						duration: 2000
					})
					return
				}
				// 参数处理
				let param = {
					types: 'contract',
					id: this.id
				}
				if(this.typeCheck == 'reject'){
					// 驳回参数
					param.type = this.typeCheck
					param.remark = this.remark
				} else {
					param.next_admin_id = this.next_admin_id
				}
				this.$u.post('crm.backlog/verify', param).then((res) => {
					if(res.code == 1) {
						// 提示
						uni.showToast({
							title: '审核成功',
							icon: 'success',
							duration: 2000
						})
						this.checkShow = false
						setTimeout(() => {
							uni.navigateBack();
						}, 1000);
					}
				})
			},
		},
	}
</script>

<style lang="scss">
.set-box {
  padding: 0rpx 22rpx;
  padding-bottom: 178rpx;
  .cif-title {
		padding: 22rpx 0;
		.text {
			font-size: 30rpx;
			font-weight: 700;
		}
	}
  .list {
		margin: 40rpx 15rpx 60rpx;
    .item {
			position: relative;
			border-radius: 15rpx;
			box-shadow: 1px 0px 5px rgba(50, 50, 50, 0.3);
			padding: 25rpx 35rpx;
      margin-bottom: 45rpx;
			.number {
				margin-bottom: 20rpx;
			}
			.content {
				margin-bottom: 20rpx;
				.price {
					width: 245rpx;
					font-size: 28rpx;
					color: #fa3534;
				}
			}
			.brief {
				margin: 10rpx 0;
				font-size: 28rpx;
				color: $u-tips-color;
			}
			.close {
				position: absolute;
				right: -8px;
    		top: -30rpx;
				/* border: 1px solid; */
				background-color: #fff;
				/* padding: 25rpx; */
				border-radius: 50%;
				height: 60rpx;
				width: 60rpx;
				display: flex;
				align-items: center;
				justify-content: center;
				box-shadow: 1px 0px 5px rgba(50, 50, 50, 0.3);
			}
    }
    .input-box {
      width: 200rpx;
      margin-right: 15rpx;
    }
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
		color: #FFF;
		background-color: #19be6b;
	}
	.primary {
		color: #FFF;
		background-color: $u-type-primary;
	}
	.entity {
		color: #fff;
		background-color: #FF6146; 
	}
}
</style>
