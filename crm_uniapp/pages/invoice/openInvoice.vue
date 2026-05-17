<template>
	<view class="container">
		<view class="set-box">
			<u-form :model="form" ref="uForm" :rules="rules" :error-type="errorType" >
			 	<u-form-item  label="发票信息" label-width="160" label-position="left">
					<u-tag text="查看" mode="dark" @click="onItem" />
				</u-form-item>
        <u-form-item required label="开票金额:" label-width="160" label-position="top">
					<u-input type='number' v-model="form.money" :border="border" />
				</u-form-item>
        <u-form-item label="开票税率（%）:" label-width="160" label-position="top">
					<u-input type='number' v-model="form.tax_rate" :border="border" />
				</u-form-item>
        <u-form-item label="开票内容:" label-width="160" label-position="top">
					<u-input v-model="form.content" :border="border" />
				</u-form-item>
        <u-form-item label="接收邮箱:" label-width="160" label-position="top" prop="email">
					<u-input v-model="form.email" :border="border" placeholder="可空，输入邮箱完成开票时，会把附件自动发送" />
				</u-form-item>
        <u-form-item label="发票附件:"  label-width="180" label-position="top">
					<fa-upload-file v-model="form.files" fileType="many" :isDom="true" :showValue="showFile"></fa-upload-file>
				</u-form-item>
				<u-form-item label="备注:"  label-width="160" prop="sort" label-position="top">
					<u-input @blur="textareaBlur" type="textarea" :value="form.remarks"  :border="border" />
				</u-form-item>
			</u-form>
			<view class="u-m-t-40" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确认</u-button>
			</view>
		</view>
	</view>
</template>

<script>
	import { formRule } from '@/common/fa.mixin.js'
	export default {
		mixins: [formRule],
		data() {
			return {
        showFile: [],
				id: '',
				border: true,
				contractkeyword: '',
				adminkeyword: '',
				selectList: [],
				type: '',
				form: {
					money: '',
					tax_rate: '',
          content: '',
					email: '',
					files: '',
					remarks: '',
        },
				errorType: ['message','toast'],
				rules: {
					email: [
						{
							validator: (rule, value, callback) => {
								if(value == '') {
									return true
								} else {
									return this.$u.test.email(value);
								}
							},
							message: '请填写正确邮箱',
							trigger: ['change', 'blur']
						}
					]
				},
			};
		},
		onLoad(e) {
			if(e.id) {
				this.id = e.id
			}
			// 获取发票详情
			this.getInvoiceEdit()
		},
		// 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
		onReady() {
			this.$refs.uForm.setRules(this.rules);
		},
		methods: {
			// 查看详情
			onItem() {
				this.$u.route('pages/invoice/details',{
					id: this.id,
				});
			},
			// 获取发票详情
			getInvoiceEdit() {
				this.$u.get('crm.invoice/edit', {
					ids: this.id,
				}).then(res => {
					if(res.code == 1 ) {
						let infoData = res.data.row
						for (const key in this.form) {
							if (Object.hasOwnProperty.call(this.form, key)) {
								this.form[key] = infoData[key]
							}
						}
					}
				})
			},
			// 基本设置
			onGetInit(id) {
				var payConfig = this.vuex_payConfig
				if (payConfig && Object.keys(payConfig).length > 0) {
					if(payConfig.online_pay == '0') {
						this.payList[1].disabled = true
					}
				} else {
					this.$u.get('login/config').then((res) => {
						if(res.code == 1){
							if(res.data.payConfig.online_pay == '0') {
								this.payList[1].disabled = true
							}
						}
					})
				}
			},
			// 优化微信小程序input、textarea快速删除时光标会跳到最后 处理：改用 textarea 失去焦点触发修改
			textareaBlur(val) {
				this.form.remarks = val
			},
			// 获取发票配置
			getReceivablesAdd() {
				this.$u.get('crm.contract.receivables/receivablesadd').then(res => {
					if(res.code == 1 ) {
						this.flowConfig = res.data
						if(res.data.flow_admin_id && res.data.config == 1){
							// 获取审批人数据
							this.$u.get('crm.common/selectpage/model/admin/type/all', {
								keyField: 'id',
								keyValue: res.data.flow_admin_id,
								showField: 'realname',
							}).then(res => {
								if(res.code == 1 ) {
									this.selectList = res.data.list
									this.chosen()
								}
							})
						}
					}
				})
			},
			// 修确认提交
			submit() {
				// 参数转化
				let params = {}
				for (const key in this.form) {
					if (Object.hasOwnProperty.call(this.form, key)) {
						params['row[' + key + ']'] = this.form[key]
					}
				}
				this.$refs.uForm.validate(valid => {
					if (valid) {
						params.ids = this.id
						this.$u.post('crm.invoice/opener', params).then((res) => {
							if(res.code == 1) {
								// 提示
								uni.showToast({
									title: '开具成功',
									icon: 'success',
									duration: 2000
								})
								setTimeout(() => {
									uni.navigateBack();
								}, 1000);
							}
						})
					} else {
						console.log('验证失败');
					}
				});
				
			},
		},
	}
</script>

<style lang="scss">
.set-box {
	padding: 0rpx 22rpx;
	padding-bottom: 80rpx;
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
 .slot-btn {
	position: relative;
	width: 200rpx;
	height: 200rpx;
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
