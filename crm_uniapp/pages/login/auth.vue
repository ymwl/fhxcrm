<template>
	<view>
		<!-- 顶部导航 -->
		<u-navbar title="授权登录"></u-navbar>
		<u-modal v-model="show" title="" :content="content" confirm-text="返回" @confirm="confirm">
			<view class="slot-content u-text-center u-m-b-30">
				<u-loading mode="flower" size="100"></u-loading>
				<view class="u-p-20">{{ content }}</view>
			</view>
		</u-modal>
	</view>
</template>

<script>
import {loginfunc} from '@/common/fa.mixin.js'
import {getQueryString} from '@/common/mUtils'

export default {
	mixins:[loginfunc],
	onLoad() {
		// this.state = getQueryString('state')
		this.code = getQueryString('code')
		console.log(this.code,this.state)
		if (this.code) {
			this.goWxAuth()
		} else {
			this.content = '授权登录失败！'
		}
	},
	data() {
		return {
			state: '',
			code: '',
			show: true,
			content: '授权登录中...'
		}
	},
	methods: {
		goWxAuth: async function() {
			let data = {
				code: this.code,
			}			
			let res = await this.$u.get('index/mplogin', data)
			if(res.code == 1) {
				this.$u.vuex('vuex_token', res.data.token)
				this.success()
			} else if(res.code == 0 && res.data) {
				this.content = res.msg
				this.$u.route('pages/login/apply',{refresh_token: res.data.refresh_token})
			} else {
				this.content = '授权登录失败！'
			}
		},
		confirm() {
			window.history.go(-2)
		}
	}
}
</script>

<style></style>
