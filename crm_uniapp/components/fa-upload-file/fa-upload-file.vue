<template>
	<view>
		<!-- #ifdef APP-PLUS -->
		<l-file ref="lFile" @up-success="onAppSuccess"></l-file>
		<!-- #endif -->
		<view class="u-flex u-flex-wrap" v-if="isDom">
			<view class="u-m-r-20 u-m-b-20" v-for="(item, index) in fileList" :key="index">
				<view class="fa-file fa-flex u-row-right">
					<view class="u-delete-icon" @click="delfile(index)"><u-icon name="close" color="#ffffff" size="20"></u-icon></view>
					<text class="fa-file-text u-tips-color u-m-b-15" v-text="getFileType(item)"></text>
				</view>
			</view>
			<view class="u-m-r-20 u-m-b-20" v-if="(fileType == 'many' && fileList.length >= 1) || fileList.length == 0">
				<view class="fa-file fa-flex u-row-center u-col-center u-p-t-10" @click="onUpload">
					<u-icon name="plus" size="40" color="#606266"></u-icon>
					<view class="select-color">选择文件</view>
				</view>
			</view>
		</view>
		<view ref="input" class="input" style="display: none;"></view>
	</view>
</template>

<script>
import Emitter from '@/uview-ui/libs/util/emitter.js';
import {baseUrl,api_v1} from '@/common/config'
export default {
	name: 'fa-upload-file',
	mixins: [Emitter],
	props: {
		value:{
			type:String,
			default:''
		},
		fileType: {
			type: String,
			default: 'single'
		},
		//页面展示
		isDom:{
			type:Boolean,
			default:false
		},		
		showValue:{
			type:Array,
			default(){
				return []
			}
		}
	},
	watch: {
		fileList:{
			// immediate: true,
			deep:true,
			handler:function(val){
				let value = val.join(',')
				this.$emit('input', value);
				setTimeout(() => {
					this.dispatch('u-form-item', 'on-form-blur', value);
				}, 50);
			}
		},
		showValue:{
			immediate:true,
			handler:function(newValue){
				if(newValue.length){
					this.fileList = newValue;
				}
			}
		}
	},
	computed:{
		getFileType(){
			return item=>{
				var index = item.lastIndexOf("."); // 找到最后一个 . 的位置
    		var fileType = item.substr(index + 1); // substr() 截取剩余的字符，即文件名doc
    		return '.' + fileType;
			}
		}
	},
	data() {
		return {
			fileList: [],
			action: baseUrl + api_v1 + '/ajax/upload',
		};
	},
	mounted() {
		// #ifdef H5
		var input = document.createElement('input')
		input.type = 'file'
		input.onchange = (event) => {
			// this.$u.api.goUpload({
			// 	 file:event.target.files[0]
			//  }).then(res=>{
			//  	this.onSuccess(res)
			//  })
			 uni.uploadFile({
				url: this.action,
				file: event.target.files[0],
				name: 'file',
				formData: {
					'token': this.vuex_token
				},
				success: (uploadFileRes) => {
					// 判断是否json字符串，将其转为json格式
					let data = this.$u.test.jsonString(uploadFileRes.data) ? JSON.parse(uploadFileRes.data) : uploadFileRes.data;
					if (![200, 201, 204].includes(uploadFileRes.statusCode)) {
						this.uploadError()
					} else {
						if(data.code == 1) {
							this.onSuccess(data)
						} else {
							this.uploadError()
						}
					}
				},
				fail: (res) => {
					console.log(res)
				}
			})
		}
		this.$refs.input.$el.appendChild(input)
		// #endif
	},
	methods: {
		/* 上传 */
		onUpload() {
			// #ifdef MP-WEIXIN
				this.wxChooseFile();
			// #endif
			// #ifdef H5
				this.h5ChooseFile()
			// #endif
			// #ifdef APP-PLUS
				var formData = {};
				let isObj = this.$u.test.object(this.vuex_config.upload.multipart);
				if (isObj) {
					formData = this.vuex_config.upload.multipart;
				}
				this.$refs.lFile.upload({
					// nvue页面使用时请查阅nvue获取当前webview的api，当前示例为vue窗口
					currentWebview: this.$mp.page.$getAppWebview(),
					//调试时ios有跨域，需要后端开启跨域并且接口地址不要使用http://localhost/
					url: this.vuex_config.upload.uploadurl,
					//默认file,上传文件的key
					name: 'file',
					header: {
						token: this.vuex_token || '',
						uid: this.vuex_user.id || 0
					},
					formData: formData
					//...其他参数
				});
			// #endif
		},
		//移除文件
		delfile(index) {
			this.fileList.splice(index,1);
		},
		// #ifdef MP-WEIXIN
			wxChooseFile() {
				wx.chooseMessageFile({
					count: 1,
					type: 'file',
					success: ({tempFiles}) => {
						let [{path:filePath,name:fileName}] = tempFiles;

						// 创建上传对象
						const task = uni.uploadFile({
							url: this.action,
							filePath: filePath,
							name: 'file',
							formData: {
								token: this.vuex_token
							},
							success: res => {
								// 判断是否json字符串，将其转为json格式
								let data = this.$u.test.jsonString(res.data) ? JSON.parse(res.data) : res.data;
								if (![200, 201, 204].includes(res.statusCode)) {
									this.uploadError()
								} else {
									if(data.code == 1) {
										this.onSuccess(data)
									} else {
										this.uploadError()
									}
								}
							},
							fail: e => {
								
							},
							complete: res => {
							
							}
						});
						task.onProgressUpdate(res => {
							// if (res.progress > 0) {
							// 	this.lists[index].progress = res.progress;
							// 	this.$emit('on-progress', res, index, this.lists, this.index);
							// }
						});
						// this.$u.api.goUpload({file:filePath,}).then(res=>{
						// 	this.onSuccess(res)
						// })
					},
					fail:function(err){
						console.log(err)
					}
				})
			},
		// #endif
		// #ifdef H5
			h5ChooseFile(){
				this.$refs.input.$el.firstChild.click();
			},
		// #endif
		// #ifndef APP-PLUS
			onSuccess(res) {
				this.$u.toast(res.msg)
				if(res.code){
					if(this.isDom){
						this.fileList.push(res.data.fullurl);
					}else{
						this.$emit('success',res.data.fullurl);
					}
				}
			},
		// #endif
		// #ifdef APP-PLUS
			onAppSuccess(res){
				if(this.$u.test.jsonString(res.data)){
					res = JSON.parse(res.data);
					this.$u.toast(res.msg)
					if(res.code){
						if(this.isDom){
							this.fileList.push(res.data.url);
						}else{
							this.$emit('success',res.data.url);
						}
					}
				}else{
					this.$u.toast('上传失败！');
				}
			},
		// #endif
		uploadError(){
			this.$u.toast('上传失败！');
		}
	}
};
</script>

<style lang="scss">
.fa-file {
	background-color: #f4f5f6;
	width: 150rpx;
	height: 150rpx;
	border-radius: 10rpx;
	.select-color {
		color: #606266;
	}
	.fa-file-text {
		font-size: 40rpx;
	}
}
.fa-flex {
	display: flex;
	flex-direction: column;
	align-items: center;
	position: relative;
	.u-delete-icon {
		background-color: #fa3534;
		width: 45rpx;
		height: 45rpx;
		border-radius: 100px;
		display: flex;
		align-items: center;
		justify-content: center;
		position: absolute;
		top: 15rpx;
		right: 15rpx;
	}
}
</style>
