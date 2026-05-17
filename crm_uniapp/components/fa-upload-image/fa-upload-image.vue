<template>
	<view>
		<u-upload
			:action="action"
			:file-list="fileList"
			:header="header"
			:form-data="formdata"
			@on-uploaded="successUpload"
			@on-error="errorUpload"
			@on-remove="remove"
			:max-count="imgType=='single'?1:99"
      :show-progress="true"
			width="150"
			height="150"
		></u-upload>
	</view>
</template>

<script>
import Emitter from '@/uview-ui/libs/util/emitter.js';
import {baseUrl,api_v1} from '@/common/config'
import Login from "../../pages/login";
export default {
	name: 'fa-upload-image',
	mixins: [Emitter],
	props: {
		value:{
			type:String,
			default:''
		},
		imgType: {
			type: String,
			default: 'single'
		},		
		fileList:{
			type:Array,
			default(){
				return []
			}
		}
	},
	created() {
		this.header = {
			token: this.vuex_token || '',
			uid: this.vuex_user.id || 0
		};
		// let isObj = this.$u.test.object(this.vuex_upload.multipart);
		// if (isObj) {
		// 	this.formdata = this.vuex_upload.multipart;
		// }
	},
	data() {
		return {
			action: baseUrl + api_v1 + '/ajax/upload',
			header: {},
			formdata: {},
			
		};
	},
	methods: {
		successUpload(e) {
			console.log(e)
			this.changes(e)
		},
		remove(index, lists, name){
			this.changes(lists)
		},
		changes(e){
			let urls = [];
			e.map(item => {
				if (item.response.code) {
					urls.push(item.response.data.url);
				}
			});
			let value = urls.join('|');
      console.log('上传触发赋值给表单',value)
			this.$emit('input', value);
			setTimeout(() => {
				this.dispatch('u-form-item', 'on-form-blur', value);
			}, 50);			
		},
		errorUpload(e) {
      console.log(e)
      console.log(e.msg)
			this.$u.toast(e.msg);
		}
	}
};
</script>

<style lang="scss"></style>
