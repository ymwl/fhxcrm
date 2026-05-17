<template>
	<view class="container">
		<view class="set-box">
			<u-form :model="form" ref="uForm" >
				<u-form-item label="状态:" label-width="160" >
					<u-tag :text="form.check_status | changeStatus" mode="dark" :type="form.check_status | changeType" />
				</u-form-item>
				<u-form-item label="合同:" label-width="160" >
					<u-input disabled v-model="contractName"   placeholder="暂无"  />
				</u-form-item>
				<u-form-item label="开票主体:" label-width="160" prop="number">
					<u-input disabled v-model="form.invoice_body" />
				</u-form-item>
				<u-form-item label="发票抬头:" label-width="160">
					<u-input disabled v-model="form.invoice_name"  placeholder="暂无" />
				</u-form-item>
				<u-form-item label="抬头类型:" label-width="160" prop="money">
					<u-input disabled v-model="invoice_issue" />
				</u-form-item>
				<u-form-item label="发票类型:" label-width="160" >
					<u-input disabled v-model="invoice_type" placeholder="暂无"/>
				</u-form-item>
				<block v-if="moreShow">
					<block v-if="form.invoice_issue != 2">
						<u-form-item label="统一社会信用代码:" label-width="160" >
							<u-input disabled v-model="form.register_no" placeholder="暂无"  />
						</u-form-item>
						<u-form-item label="开户行名称:" label-width="160" >
							<u-input disabled v-model="form.bank_name" placeholder="暂无"/>
						</u-form-item>
						<u-form-item label="开户账号:" label-width="160" >
							<u-input disabled v-model="form.bank_no" placeholder="暂无"/>
						</u-form-item>
					</block>
					<u-form-item label="开票金额:" label-width="160" >
						<u-input disabled v-model="form.money" placeholder="暂无"/>
					</u-form-item>
					<u-form-item label="开票税率(%):" label-width="160" >
						<u-input disabled v-model="form.tax_rate" placeholder="暂无"/>
					</u-form-item>
					<u-form-item label="开票内容:" label-width="160" >
						<u-input disabled v-model="form.content" placeholder="暂无"/>
					</u-form-item>
					<u-form-item v-if="this.form.check_status > 0" label="发票附件:" label-width="160" label-position="top">
						<view class="u-flex" >
							<view class="u-m-r-15"  v-for="(item,index ) in this.form.files" :key="index">
								<u-image width="150rpx" height="150rpx" :src="item" @click="lookImges(index)"></u-image>
							</view>
						</view>
					</u-form-item>
					<u-form-item label="接收邮箱:" label-width="160" >
						<u-input disabled v-model="form.email" placeholder="暂无"/>
					</u-form-item>
					<u-form-item label="收件人:" label-width="160" >
						<u-input disabled v-model="form.user_name" placeholder="暂无"/>
					</u-form-item>
					<u-form-item label="手机:" label-width="160" >
						<u-input disabled v-model="form.user_phone" placeholder="暂无"/>
					</u-form-item>
					<u-form-item label="邮寄地址:" label-width="160" >
						<u-input disabled v-model="form.user_address" placeholder="暂无"/>
					</u-form-item>
					<u-form-item label="备注 :"  label-width="160" prop="sort">
						<u-input disabled v-model="form.remarks" type="textarea" placeholder="暂无"  />
					</u-form-item>
					<u-form-item label="审批人:"  label-width="160" prop="sort">
						<u-input disabled v-model="adminName" placeholder="暂无"  />
					</u-form-item>
				</block>
				<view class="look-more" @click="moreShow = !moreShow">{{ moreShow ? '收起' : '展示所有'}}发票信息<u-icon :name="moreShow ? 'arrow-up': 'arrow-down'" color="#409eff" size="32"></u-icon> </view>
			</u-form>
		</view>
		<!-- 审核日志 -->
		<view class="title u-border-bottom">审批日志</view>
		<block v-if="tdList.length > 0">
			<scroll-view  scroll-x class="sv" show-scrollbar="true">
				<view class="table">
					<u-table align="left" padding="10rpx">
						<u-tr>
							<u-th v-for="(item,index) in thList" :key="index">{{item}}</u-th>
						</u-tr>
						<u-tr v-for="(item,index) in tdList" :key="index">
							<u-td class="table_one">
								<view class="td-on">
									{{item.id}}
								</view>
							</u-td>
							<u-td>
								<view class="td-on">
									{{item.realname}}
								</view>
							</u-td>
							<u-td>
								<view class="td-item">
									{{item.admin_id}}
								</view>
							</u-td >
							<u-td> 
								<view class="td-item">
									{{item.remark ? item.remark : '结束'}}
								</view>
							</u-td>
							<u-td> 
								<view class="td-item">
									{{timeFormats(item.create_time)}} 
								</view>
							</u-td>
							<u-td> 
								<view class="td-item">
									<text :class="item.status == 1 ? 'su' : 'err' ">{{item.status | changeStatuss}} </text>
								</view>
							</u-td>
						</u-tr>
					</u-table>
				</view>
			</scroll-view>
		</block>
		<u-empty v-else text="暂无数据"  margin-top="100" mode="list"></u-empty>
		<!-- 底部按钮 -->
		<view class="bottom-btn u-border-top" >
			<view class="btn primary" @click="onEdit">修改</view>
			<block v-if="checkTrue">
				<view class="btn sky u-m-l-15" @click="onCheck('')">审核</view>
				<view class="btn entity u-m-l-15" @click="onCheck('reject')" :style="{backgroundColor: vuex_theme.color,}">驳回</view>
			</block>
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
						<u-input class="u-flex-1" :border="true"  type="select" :select-open="adminShow" v-model="nextAdminName" placeholder="如果要结束审批请不要选择审批人" @click="adminShow = true" />
					</view>
				</block>
				<view v-else class="u-flex u-m-t-50 u-p-l-20 u-p-r-20">
					<u-input class="u-flex-1" :border="true" v-model="remark" height="250" placeholder="请输入驳回理由" type="textarea"  />
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
	export default {
		data() {
			return {
				searchTimer: null,
				moreShow: false,
				labelPosition: 'left',
				border: false,
				fields: [],
				id: '',
				adminkeyword: '',
				checkShow:false,
				selected: false,
				selectList: [],
				customerName: '',
				contractName: '',
				adminName: '',
				form: {},
				params: '',
				thList: ['ID','审批人','审批人ID','意见','审批时间','状态'],
				tdList: [],
				logId: '',
				flow_id: '',
				checkTrue: false,
				next_admin_id: '',
				typeCheck: '',
				remark: '',
				nextAdminName: '',
				adminShow: false,
				adminList: [],
				pageSize: 20, // 每一页多少条数据
				adminPage: 1,
				lastAdmin: false,
				adminStatus: 'loadmore',
				detailAll: {
					row: {
						check_status: 0
					}
				},
			};
		},
		filters: {
			changeStatuss(val){
				switch (val) {
					case 0:
						return '驳回'
						break;
					case 1:
						return '通过'
						break;
					default:
						return '--'
						break;
				}
			},
			changeStatus(val){
				switch (val) {
					case 0:
						return '待审核'
						break;
					case 1:
						return '审核中'
						break;
					case 2:
						return '审核通过'
						break;
					case 3:
						return '审核未通过'
						break;
					default:
						return '--'
						break;
				}
			},
			changeType(val){
				switch (val) {
					case 0:
						return 'info'
						break;
					case 1:
						return 'warning'
						break;
					case 2:
						return 'success'
						break;
					case 3:
						return 'error'
						break;
					default:
						return '--'
						break;
				}
			}
		},
		computed: {
			// 计算属性的 getter
			invoice_type: function () {
				switch (this.form.invoice_type) {
					case 1:
						return '增值税普票'
						break;
					case 2:
						return '增值税专票'
						break;
					default:
						return '--'
						break;
				}
			},
			invoice_issue: function () {
				switch (this.form.invoice_issue) {
					case 1:
						return '企业'
						break;
					case 2:
						return '个人'
						break;
					case 3:
						return '事业单位'
						break;
					default:
						return '--'
						break;
				}
			},
		},
		onLoad(e) {
			this.params = e
			if(this.params.type == "look") {
				uni.setNavigationBarTitle({
					title: '发票详情'
				});
			}
			if(e.id) {
				this.id = e.id
				// 获取发票详情
				this.getInvoiceEdit()
				this.onSelectpage()
			}
			// 是否是编辑
			this.type = e.type
			
			
		},
		methods: {
			// 审批
			onCheck(val) {
				console.log(val)
				this.typeCheck = val
				this.next_admin_id = ''
				this.remark = ''
				this.selectList = []
				this.nextAdminName = ''
				if(this.$u.test.isEmpty(this.detailAll.flow)){
					// 审批流程被删除，默认直接提交
					let _this = this
					uni.showModal({
						title: '提示',
						content: '确定审核通过吗',
						success: function (res) {
							if (res.confirm) {
								_this.submit()
							} else if (res.cancel) {
								console.log('用户点击取消');
							}
						}
					});
					return
				}
				if(this.detailAll.flow.config == 1 && val != 'reject') {
					// 审核配置为固定审批直接提交
					let _this = this
					uni.showModal({
						title: '提示',
						content: '确定审核通过吗',
						success: function (res) {
							if (res.confirm) {
								_this.submit()
							} else if (res.cancel) {
								console.log('用户点击取消');
							}
						}
					});
				} else {
					this.checkShow = true
				}
			},
			
			// 修确认提交
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
					types: 'invoice',
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
			// 查看图片
			lookImges(index){
				uni.previewImage({
					current: index,
					urls: this.form.files,
					longPressActions: {
						itemList: ['发送给朋友', '保存图片', '收藏'],
						success: function(data) {

						},
						fail: function(err) {
							console.log(err.errMsg);
						}
					}
				});
			},
			// 格式化时间
			timeFormats(val) {
				if(val) {
					return this.$u.timeFormat(val, 'yyyy/mm/dd hh:MM');
				} else {
					return '--'
				}
			},
			// 获取审批日志数据
			getGroupdata(id){
				this.$u.get('crm.flow.log/index', {
					flow_id: this.form.flow_id,
					types_id: this.id,
				}).then(res => {
					if(res.code == 1 ) {
						this.tdList = res.data.rows
						// console.log(this.tdList)
					}
				})
			},
			// 修改
			onEdit(){
				this.$u.route('pages/invoice/apply',{
					type: 'edit',
					id: this.id
				});
			},
			// 获取发票详情
			getInvoiceEdit() {
				this.$u.get('crm.invoice/edit', {
					ids: this.id,
				}).then(res => {
					if(res.code == 1 ) {
						this.carryOut(res)
						// 获取审批日志
						this.getGroupdata()
					}
				})
			},
			carryOut(res){
				this.detailAll = res.data
				this.form = res.data.row
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
				// 获取审批人数据
				this.$u.get('crm.common/selectpage/model/admin/type/all', {
					keyField: 'id',
					showField: 'realname',
					keyValue: this.form.flow_admin_id,
				}).then(res => {
					if(res.code == 1 ) {
						let name = []
						res.data.list.forEach((item,index) => {
							name.push(item.realname)
						});
						this.adminName = name.join(',')
					}
				})
				// 获取已选合同
				this.$u.get('crm.contract.index/selectpage', {
					keyField: 'id',
					keyValue: this.form.contract_id,
				}).then(res => {
					if(res.code == 1 ) {
						this.contractName = res.data.list[0].name
					}
				})
			},
			// 审批人搜索
			adminSearch() {
				clearTimeout(this.searchTimer)
				this.searchTimer = setTimeout(() => {
					this.adminPage = 0
					this.lastAdmin = false
					this.onSelectpage()
				}, 500)
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
		},
	}
</script>

<style lang="scss">
.container {
	padding-bottom: 150rpx;
}
.set-box {
	padding: 0rpx 22rpx 50rpx;
	.cif-title {
		font-size: 30rpx;
		font-weight: 700;
		padding: 22rpx 0;
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
 }
.tap {
	justify-content: space-between;
  padding: 10rpx 20rpx;
}
.table {
	min-width: 1000px;
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
	// color: #2979ff;
	.su {
		color: #19be6b
	}
	.err {
		color: #fa3534
	}
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
.title {
	padding: 25rpx 20rpx;
	font-weight: 700;
	font-size: 32rpx;
}
.look-more {
	text-align: center;
	font-weight: 600;
	font-size: 35rpx;
	color: #409eff;
	margin: 25rpx 0;
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
