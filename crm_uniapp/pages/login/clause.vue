<template>
	<view class="u-content">
		<u-parse :html="content"></u-parse>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				content: '',
			};
		},
		onLoad() {
			this.getArticleDetail()
		},
		methods: {
			getArticleDetail() {
				this.$u.api.getArticleDetail({
					ids: 42,
				}).then(res => {
					this.content = res.data.content
				})
			},
		}
	}
</script>

<style lang="scss">
.u-content {
	padding: 35rpx 20rpx;
}
</style>
