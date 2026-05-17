<template>
	<view>
		<view class="set-box" v-if="showForm">
      <u-form :model="form" v-model="form" :rules="rules" ref="uForm" :error-type="errorType">
        <!-- 自定义字段组件 -->
        <fa-fields :fields="fields" :form="form" :model="form"  v-model="form" :rules="rules" :labelPosition="labelPosition" :border="border" ></fa-fields>
      </u-form>
			<!-- 底部浮动按钮 -->
			<view class="bottom-btn u-border-top" >
				<u-button type="success"  @click="submit"  :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor,fontSize: '30rpx',fontWeight: '600'}" :ripple="true" >提交</u-button>
			</view>
		</view>

	</view>
</template>

<script>
	import { processingImages,getImgUrl,get_date} from '@/common/mUtils'
	import {baseUrl,api_v1} from '@/common/config'
	import { formRule } from '@/common/fa.mixin.js'

	export default {
		mixins: [formRule],
		data() {
			return {
				searchTimer: null,
				showForm: false,
				labelPosition: 'left',
				border: true,
				selectShow:false,
				nextTimeShow: false,
				city_field: '',
				listStatus: 'loadmore',
				page: 1,
				pageSize: 10,
				keyword: '',
				lastPage: false,
				customerList: [],
				customerName: '',
				type: '',
				contacts_id: '',
				customer_id: '',
				minDate: '',
				maxDate: '',
				dateShow: false,
				show: false,
				content: '',
				time_field: '',
				fields: [],
				contactsData: {},
				form: {},
				params: {
					year: true,
					month: true,
					day: true,
					hour: true,
					minute: true,
					second: false
				},
				errorType: ['message','toast'],


				rules: {}
			};
		},
		onLoad(e) {
			this.contacts_id = e.id ? e.id : '';
			this.customer_id = e.customer_id ? e.customer_id : '' // 默认选中的客户id
			this.type = e.type
			if(this.type == "edit") {
				uni.setNavigationBarTitle({
					title: '编辑联系人'
				});
				this.getInfo()
			} else {
				this.getFields()
			}
			this.getData()
		},
		methods: {
			// 优化微信小程序input、textarea快速删除时光标会跳到最后 处理：改用 textarea 失去焦点触发修改
			textareaBlur(val) {
				this.form.remark = val
			},
			// 搜索
			onSearch() {
				clearTimeout(this.searchTimer)
				this.searchTimer = setTimeout(() => {
					this.page = 1
					this.lastPage = false
					this.getData()
				}, 500)
			},
			// 获取联系人详情
			getInfo() {
				if(this.contacts_id){
					this.$u.get('crm.customer_contacts/edit', {id: this.contacts_id}).then(res => {
						if(res.code == 1 ) {
							this.contactsData = res.data
							this.getFields()
						}
					})
				}
			},
			// 获取自定义字段
			getFields() {
        let source='addForm';this.type == 'edit' ? source='editForm' : source='addForm';
				this.$u.get('fields/get_fields', {table: 'crm_customer_contacts',source: source}).then((res) => {
					if(res.code == 1){
						this.fields = res.data.fields;
						//渲染自定义字段,默认字段
						let custom_form = {
						};
            let rules = {};
            this.fields.forEach(item => {
              const isEdit = this.type == 'edit';
              // 统一数据源：编辑取已有值，新增取默认值
              const srcVal = isEdit ? this.contactsData[item.field] : (item.value || item.default);

              // 表单赋值
              if (isEdit) {
                custom_form[item.field] = srcVal;
                if (item.type != 'number') {
                  item.value = srcVal;
                }
              } else if (item.type == 'number') {
                custom_form[item.field] = srcVal || 0;
              } else {
                custom_form[item.field] = srcVal || '';
              }

              // 图片/文件类型：统一转换为子组件需要的格式
              if (item.type == 'image') {
                item.value = srcVal ? [{ url: getImgUrl(srcVal) }] : [];
              }
              if (item.type == 'images') {
                item.value = srcVal ? String(srcVal).split(',').map(it => ({ url: getImgUrl(it) })) : [];
              }
              if (item.type == 'file') {
                item.value = srcVal ? [srcVal] : [];
              }
              if (item.type == 'files') {
                item.value = srcVal ? String(srcVal).split(',') : [];
              }

              //追加自定义表单验证
              rules[item.field] = this.getRules(item)
            });
						this.form = custom_form // 表单字段数据合并
						// 添加默认客户
						if(this.customer_id){
							this.form.customer_id =this.customer_id
						}
						this.rules = rules;
						this.showForm = true;
						//设置表单验证规则 — u-form未声明rules prop，需通过setRules方法注入
						this.$nextTick(() => {
							this.$refs.uForm.setRules(this.rules);
						});
						console.log(this.form, this.rules, this.fields);
					}
				})
			},
			// 获取客户列表
			getData(isNextPage,pages) {
				// 筛选参数
				let obj = {
					pageNumber: (pages || 1 ),
					pageSize: this.pageSize,
					name: this.keyword,
					keyField: 'id',
					showField: 'name',
					"q_word": this.keyword,
					"searchField": "name"
				}
				if(this.customer_id) {
					obj = {
						keyField: 'id',
						showField: 'name',
						"q_word": this.customer_id,
						"searchField": "id"
					}
				}
				this.$u.post('crm.customer.index/selectpage', obj).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastPage = true
						} 
						//不够一页
						if (res.data.list.length < this.pageSize) {
							this.listStatus = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.customerList = this.customerList.concat(res.data.list)
							return 
						}
						this.customerList = res.data.list
						if(this.customer_id) {
							this.customerList.forEach((item,index) => {
								if(this.customer_id == item.id) {
									item.checked = true
									this.customerName = item.name
								} else {
									item.checked = false
								}
							})
						}
					}
				})
			},
			// 滚动到底部加载更多
			reachBottom() {
				if(this.lastPage || this.listStatus == 'loading') return ;
				this.listStatus = 'loading'
				setTimeout(() => {
					if(this.lastPage) return ;
					this.getData(true,++this.page)
					if(this.customerList.length >= 10) this.listStatus = 'loadmore';
					else this.listStatus = 'loading';
				}, 1200)
			},
			// 选择客户
			onItem(val,i) {
				this.customerList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
						val.name=val.name?val.name:item.name;
					} else {
						item.checked = false
					}
				})
				this.form.customer_id = val.id
				this.customerName = val.name ? val.name : ''
				this.selectShow = false
			},
			// 选择时间
			nextTimeChange(e){
				this.form.next_time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
				console.log(	this.form.next_time)
			},
			// 修确认提交
			submit() {
				this.$refs.uForm.validate(valid => {
					if (valid) {
						if(this.type == 'add') {
							// 添加
							this.$u.post('crm.customer_contacts/add', this.form).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: "添加成功",
										icon: 'success',
										duration: 2000
									})
									setTimeout(() => {
										uni.navigateBack();
									}, 1000);
								}
							})
						} else {
							//修改
							this.form.id = this.contacts_id
							this.$u.post('crm.customer_contacts/edit', this.form).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: "修改成功",
										icon: 'success',
										duration: 2000
									})
									setTimeout(() => {
										uni.navigateBack();
									}, 1000);
								}
							})
						}
					} else {
						console.log('验证失败');
					}
				});
			},
			// 选择生日时间变化
			change(e) {
				this.form.birthday = e.result
			},
		},
	}
</script>

<style lang="scss">
.set-box {
	padding: 0rpx 22rpx 250rpx;
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
.bottom-btn {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 25rpx 30rpx 45rpx;
	background-color: #fff;
	z-index: 100;
}
</style>
