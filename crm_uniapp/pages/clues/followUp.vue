<template>
	<view >
		<view class="set-box">
			<u-form :model="form" ref="uForm" :error-type="errorType">
				<u-form-item label="跟进线索:"  label-width="180" prop="name">
					<u-input v-model="form.name" disabled />
				</u-form-item>
				<u-form-item label="跟进方式:"  label-width="180" prop="record_type">
					<u-input v-model="record_type_name" type="select"  :select-open="selectShow" placeholder="选择跟进方式"  @click="selectShow = true"/>
				</u-form-item>
				<u-form-item label="下次跟进:"  label-width="180" >
					<u-input type="select" :select-open="nextTimeShow" v-model="form.next_time" placeholder="选择跟进时间" @click="nextTimeShow = true" />
				</u-form-item>
				<u-form-item label="跟进内容:" label-width="180" prop="content">
					<u-input type="textarea" :border="true" height="200" :auto-height="true" v-model="form.content" />
				</u-form-item>
				<u-form-item label="相关图片:"  label-width="180">
					<u-upload :custom-btn="true" ref="uUpload" :before-upload="beforeUpload" :form-data="param" @on-choose-complete="changeList"  :show-upload-list="true" max-count="5" :action="action" @on-uploaded="finish" :auto-upload="false">
						<view slot="addBtn" class="slot-btn" hover-class="slot-btn__hover" hover-stay-time="150">
							<u-icon name="camera" size="60" color="#606266"></u-icon>
							<view class="text">选择图片</view>
						</view>
					</u-upload>
				</u-form-item>
			</u-form>
			<view class="u-m-t-80" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">立即提交</u-button>
			</view>
			<!-- <u-select v-model="selectShow" :list="lists"></u-select> -->
		</view>
		<!-- 选择跟进方式 -->
		<u-action-sheet :list="selectList" v-model="selectShow" @click="recordTypeClick"></u-action-sheet>
		<!-- 跟进时间选择 -->
		<u-picker v-model="nextTimeShow" :hour="true" mode="time" :params="params" @confirm="nextTimeChange"></u-picker>
	</view>
</template>

<script>
	import {baseUrl,api_v1} from '@/common/config'
	export default {
		data() {
			return {
				businessData: {},
				selectShow: false,
				nextTimeShow: false,
				selectList: [],
				lists: [],
				action: baseUrl + api_v1 + '/ajax/upload',
				param: {
					token: '',
				},
				show: false,
				content: '',
				record_type_name: '',
				form: {
					name: '',
					next_time: '',
					record_type: '',
					content: '',
				},
				params: {
					year: true,
					month: true,
					day: true,
					hour: true,
					minute: true,
					second: false
				},
				clues_id: '',
				business_image: [],
				errorType: ['message','toast'],
				rules: {
					name: [
						{
							required: false,
							message: '请填写客户名称',
							trigger: ['change','blur']
						},
					],
					record_type: [
						{
							required: true,
							message: '请选择跟进方式',
							trigger: ['change','blur']
						},
					],
					content: [
						{
							required: true,
							message: '请填写跟进内容',
							trigger: ['change','blur']
						},
					],
				}
			};
		},
		// 只有onReady生命周期才能调用refs操作组件
		onReady() {
			// 得到整个组件对象，内部图片列表变量为"lists"
			this.lists = this.$refs.uUpload.lists;
		},
		onLoad(e) {
			// 上传文件参数
			this.param.token = this.vuex_token
			this.clues_id = e.id ? e.id: ''
			this.getData()
			this.getBaseConfig()
		},
		methods: {
			// 获取详情
			getData() {
				this.$u.api.getCluesEdit({
					ids: this.clues_id
				}).then(res => {
					if(res.code == 1 ) {
						this.businessData = res.data
						this.form.name = this.businessData.name
					}
				})
			},
			// 获取配置字段
			getBaseConfig() {
				this.$u.api.getBaseConfig().then((res) => {
					if(res.code == 1){
						this.selectList = this.onJson(res.data.recordTypeList)
					}
				})
			},
			// json 转化
			onJson(data) {
				let arr = []
				for (const key in data) {
					if (Object.hasOwnProperty.call(data, key)) {
						let obj = {}
						obj.id = key
						obj.text = data[key]
						arr.push(obj)
					}
				}
				return arr
			},
			// 选择跟进方式
			recordTypeClick(index) {
				this.selectList.forEach((item,i) =>{
					if(index == i) {
						item.color = "#1B8CFE"
					} else {
						item.color = ""
					}
				})
				this.record_type_name = this.selectList[index].text
				this.form.record_type = this.selectList[index].id
			},
				// 选择时间
			nextTimeChange(e){
				this.form.next_time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
			},
			//上传前的钩子
			beforeUpload(index, list) {
				console.log(list)
			},
			// 每次选择图片后触发，只是让外部可以得知每次选择后，内部的文件列表
			changeList(lists, name) {
				// 往已选文件的列表lists添加额外参数
				this.lists.forEach((item,index) => {
					// 文件名称
					item.name = this.sku + this.$u.timeFormat('', 'yyyymmddhhMMss') + this.$u.random(100, 999) 
					// 用户重命名
					item.rename = '' 
				});
			},
			// 所有图片上传完成
			finish(data, index, lists, name){
				// 数据初始化，防止重复添加
				this.business_image = []
				this.lists.forEach((item,index) => {
					this.business_image.push(item.response.data.fullurl)
				});
				// 开始提交跟进 
				this.onSubmit()
			},
			// 提交跟进
			submit() {
				// 进行必须填数据验证
				this.$refs.uForm.validate(valid => {
					if (valid) {
						// 只选择了图片
						if(this.lists.length > 0) {
							this.$refs.uUpload.upload();
							return false
						}
						// 都图片和视频都没有选择直接提交
						this.onSubmit()
					} else {
						console.log('验证失败');
					}
				});
			},
			// 开始提交跟进
			onSubmit(){
				// 进行必须填数据验证
				this.form.image = this.business_image.join(",")
				let param = {
					'row[name]': this.form.name,
					'row[next_time]': this.form.next_time,
					'row[record_type]': this.form.record_type,
					'row[content]': this.form.content,
					'row[image]': this.form.image,
					ids: this.clues_id,
				} 
				this.$u.api.onCluesRecord(param).then((res) => {
					console.log(res)
					if(res.code == 1){
						// 提示
						uni.showToast({
							title: '提交成功',
							icon: 'success',
							duration: 2000
						})
						setTimeout(() => {
							uni.navigateBack();
						}, 1500);
					}
				})
			},
			
		},
	}
</script>

<style lang="scss">
.set-box {
    padding: 0rpx 22rpx;
    margin-bottom: 80rpx;
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

.slot-btn__hover {
	background-color: rgb(235, 236, 238);
}
.delete-icon {
	position: absolute;
	top: 10rpx;
	right: 10rpx;
	z-index: 10;
	background-color: #fa3534;
	border-radius: 100rpx;
	width: 44rpx;
	height: 44rpx;
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
}   
video {width: 100%;}
</style>
