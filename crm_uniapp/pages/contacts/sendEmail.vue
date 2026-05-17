<template>
	<view>
		<view class="set-box" v-if="showForm">
			<u-form :model="form" ref="uForm" :error-type="errorType">
				<!-- 收件邮箱 -->
				<u-form-item label="收件邮箱" required :label-position="labelPosition" label-width="180" prop="to_email">
					<u-input v-model="form.to_email" :border="border" placeholder="请输入收件邮箱" :clearable="true" />
				</u-form-item>
				<!-- 邮件模板 -->
				<u-form-item label="邮件模板" :label-position="labelPosition" label-width="180">
					<u-input v-model="form.tpl_name" type="select" :border="border" :select-open="tplShow" placeholder="快捷选择邮件模板" @click="tplShow = true" />
					<u-select v-model="tplShow" :list="tplList" @confirm="tplConfirm"></u-select>
				</u-form-item>
				<!-- 邮件标题 -->
				<u-form-item label="邮件标题" required :label-position="labelPosition" label-width="180" prop="subject">
					<u-input v-model="form.subject" :border="border" placeholder="请输入邮件标题" :clearable="true" />
				</u-form-item>
				<!-- 邮件内容 -->
				<u-form-item label="邮件内容" required :label-position="labelPosition" label-width="180" prop="email_content">
					<fa-editor v-model="form.email_content" placeholder="请输入邮件内容" :html="html"></fa-editor>
				</u-form-item>
			</u-form>
			<view class="u-m-t-80" style="text-align: center;">
				<u-button type="success" @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">发送</u-button>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				showForm: false,
				labelPosition: 'top',
				border: true,
				errorType: ['message','toast'],
				id: '',
				customer_id: '',
				tplShow: false,
				tplList: [],
				html: '',
				form: {
					customer_contacts_id: '',
					customer_id: '',
					type: 'customer_contacts',
					to_email: '',
					emailtpl_id: '',
					tpl_name: '',
					subject: '',
					email_content: ''
				},
				rules: {
					to_email: [
						{required: true, message: '收件邮箱不能为空', trigger: ['blur']},
						{type: 'email', message: '收件邮箱格式不正确', trigger: ['blur']}
					],
					subject: [
						{required: true, message: '邮件标题不能为空', trigger: ['blur']},
						{max: 200, message: '邮件标题不能超过200字符', trigger: ['blur']}
					],
					email_content: [
						{required: true, message: '邮件内容不能为空', trigger: ['blur']}
					]
				}
			};
		},
		onLoad(e) {
			this.id = e.id || '';
			this.customer_id = e.customer_id || '';
			this.form.customer_contacts_id = this.id;
			this.form.customer_id = this.customer_id;
			this.form.to_email = e.email || '';
			uni.setNavigationBarTitle({
				title: '发送邮件'
			});
			this.getEmailTpl();
		},
		onReady() {
			this.showForm = true;
			this.$nextTick(() => {
				this.$refs.uForm.setRules(this.rules);
			});
		},
		methods: {
			// 获取邮件模板列表
			getEmailTpl() {
				this.$u.get('emailtpl/index', {
					filter: JSON.stringify({type: ['customer', 'customer_contacts']}),
					op: JSON.stringify({type: 'in'})
				}).then(res => {
					if (res.code == 1) {
						const list = res.data && res.data.rows ? res.data.rows : [];
						this.tplList = list.map(item => ({
							value: item.id,
							label: item.name
						}));
					}
				});
			},
			// 邮件模板选择确认
			tplConfirm(e) {
				const item = e[0];
				if (item) {
					this.form.emailtpl_id = item.value;
					this.form.tpl_name = item.label;
					// 获取模板内容
					this.$u.post('emailtpl/getTemplate', {
						id: item.value,
						customer_id: this.customer_id,
						customer_contacts_id: this.id
					}).then(res => {
						if (res.code == 1) {
							this.form.subject = res.data.tpl_title || '';
							this.form.email_content = res.data.tpl_content || '';
							this.html = res.data.tpl_content || '';
						}
					});
				}
			},
			// 提交发送
			submit() {
				this.$refs.uForm.validate(valid => {
					if (valid) {
						uni.showLoading({title: '发送中...'});
						this.$u.post('crm.customer_contacts/sendEmail', this.form).then(res => {
							uni.hideLoading();
							if (res.code == 1) {
								uni.showToast({
									title: '邮件发送成功',
									icon: 'success',
									duration: 2000
								});
								setTimeout(() => {
									uni.navigateBack();
								}, 1500);
							}
						}).catch(() => {
							uni.hideLoading();
						});
					} else {
						console.log('验证失败');
					}
				});
			}
		}
	}
</script>

<style lang="scss">
.set-box {
	padding: 0rpx 22rpx;
	padding-bottom: 80rpx;
}
</style>

