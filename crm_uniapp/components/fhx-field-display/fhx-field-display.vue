<template>
	<view class="field-wrap" v-if="fields && fields.length > 0">
		<view class="field-item" v-for="(field, fIndex) in fields" :key="fIndex" v-if="shouldShowField(field, item[field.field])">
			<text class="field-label">{{field.title}}：</text>
			<!-- 图片类型 -->
			<template v-if="getFieldType(field) === 'image' || getFieldType(field) === 'images'">
				<view class="field-images">
					<image 
						v-for="(img, imgIdx) in getFieldUrls(field, item[field.field])" 
						:key="imgIdx"
						:src="img" 
						class="field-thumb" 
						mode="aspectFill"
						@click.stop="previewImage(img, getFieldUrls(field, item[field.field]))">
					</image>
				</view>
			</template>
			<!-- 文件类型 -->
			<template v-else-if="getFieldType(field) === 'file' || getFieldType(field) === 'files'">
				<view class="field-files">
					<view 
						v-for="(file, fileIdx) in getFieldFiles(field, item[field.field])" 
						:key="fileIdx"
						class="file-item"
						@click.stop="downloadFile(file)">
						<u-icon name="file-text" size="32" color="#666"></u-icon>
						<text class="file-name">{{file.name}}</text>
					</view>
				</view>
			</template>
			<!-- 文本类型 -->
			<template v-else>
				<text class="field-value">{{getFieldText(field, item[field.field],item)}}</text>
			</template>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'fa-field-display',
		props: {
			fields: {
				type: Array,
				default: () => []
			},
			item: {
				type: Object,
				default: () => ({})
			}
		},
		methods: {
			// 判断字段是否应该显示
			shouldShowField(field, value) {
				if (value === '' || value === null || value === undefined) {
					return false;
				}
				const formtype = field.formtype || 'text';
				if ((formtype === 'datetime' || formtype === 'date') && value == 0) {
					return false;
				}
				return true;
			},
			// 获取字段类型
			getFieldType(field) {
				return field.formtype || 'text';
			},
			// 获取字段显示文本
			getFieldText(field, value,item) {
				const result = this.$realFieldVal(field, value,item);
				return result.text;
			},
			// 获取图片URL数组
			getFieldUrls(field, value) {
				const result = this.$realFieldVal(field, value);
				return result.urls || [];
			},
			// 获取文件数组
			getFieldFiles(field, value) {
				const result = this.$realFieldVal(field, value);
				return result.files || [];
			},
			// 预览图片
			previewImage(current, urls) {
				uni.previewImage({
					current: current,
					urls: urls
				});
			},
			// 下载文件
			downloadFile(file) {
				uni.showLoading({ title: '下载中...' });
				uni.downloadFile({
					url: file.url,
					success: (res) => {
						uni.hideLoading();
						if (res.statusCode === 200) {
							uni.openDocument({
								filePath: res.tempFilePath,
								success: () => {
									console.log('文件打开成功');
								},
								fail: (err) => {
									uni.showToast({ title: '文件打开失败', icon: 'none' });
								}
							});
						}
					},
					fail: () => {
						uni.hideLoading();
						uni.showToast({ title: '下载失败', icon: 'none' });
					}
				});
			}
		}
	}
</script>

<style lang="scss" scoped>
.field-wrap {
	display: flex;
	flex-wrap: wrap;
}
.field-item {
	min-width: 50%;
	box-sizing: border-box;
	padding: 8rpx 20rpx 8rpx 0;
	font-size: 26rpx;
	.field-label {
		color: #303133;
	}
	.field-value {
		color: #333;
		word-break: break-all;
	}
	.field-images {
		display: flex;
		flex-wrap: wrap;
		gap: 8rpx;
	}
	.field-thumb {
		width: 80rpx;
		height: 80rpx;
		border-radius: 8rpx;
		background-color: #f5f5f5;
	}
	.field-files {
		display: flex;
		flex-direction: column;
		gap: 8rpx;
	}
	.file-item {
		display: flex;
		align-items: center;
		gap: 8rpx;
	}
	.file-name {
		color: #2979ff;
		font-size: 24rpx;
		max-width: 300rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
}
</style>
