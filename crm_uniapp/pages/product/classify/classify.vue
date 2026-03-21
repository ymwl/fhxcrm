<template>
	<view >
		<view class="set-box">
			<u-form :model="form" ref="uForm" :error-type="errorType">
				<u-form-item label="上级:"  label-width="180" >
					<u-input v-model="pidName" type="select"  :select-open="selectShow" placeholder="选择上级"  @click="selectShow = true"/>
				</u-form-item>
				<u-form-item required label="名称:"  label-width="180" prop="name" >
					<u-input v-model="form.name" placeholder="请填写分类名称"  />
				</u-form-item>
				<u-form-item label="分类属性:" label-position="top"  label-width="180" >
					<view>
						<view class="u-m-b-15 u-flex"  v-for="(item,index) in propList" :key="index">
							<u-input class="u-flex-1 u-m-r-15" :border="true"  v-model="item.title" placeholder="请填写属性" />
							<u-button size="medium" @click="delProp(index)" type="primary">删除</u-button>
						</view>
					</view>
					<view class="">
						<u-button size="medium" @click="addProp">添加</u-button>
					</view>
				</u-form-item>
				<u-form-item label="图片:"  label-width="180">
					<u-upload :custom-btn="true" ref="uUpload" :before-upload="beforeUpload" :form-data="param" @on-choose-complete="changeList"  :show-upload-list="true" max-count="1" :action="action" @on-uploaded="finish" :auto-upload="false">
						<view slot="addBtn" class="slot-btn" hover-class="slot-btn__hover" hover-stay-time="150">
							<u-icon name="camera" size="60" color="#606266"></u-icon>
							<view class="text">选择图片</view>
						</view>
					</u-upload>
				</u-form-item>
				<u-form-item label="权重:"  label-width="180" prop="weigh">
					<u-input v-model="form.weigh"  />
				</u-form-item>
			</u-form>
			<view class="u-m-t-80" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定</u-button>
			</view>
		</view>
		<!-- 选择分类 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="selectShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择分类</text> 
				<view class="" @click="selectShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<scroll-view scroll-y style="height: 660rpx;width: 100%;">
				<view class="list">
					<block v-if="classifyList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in classifyList" :key="index" @click="onItem(item,index)">
								<view class="title">
									<u-parse :html="item.name"></u-parse>
								</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="listStatus" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
	</view>
</template>

<script>
	import {baseUrl,api_v1} from '@/common/config'
	export default {
		data() {
			return {
				listStatus: 'nomore',
				classifyList: [
					{
						name: '无',
						id: '',
					}
				],
				selectShow: false,
				nextTimeShow: false,
				selectList: [],
				lists: [],
				action: baseUrl + api_v1 + '/ajax/upload',
				param: {
					token: '',
				},
				pidName: '无',
				show: false,
				content: '',
				record_type_name: '',
				form: {
					pid: '',
					name: '',
					prop: '',
					image: '',
					weigh: '50',
				},
				propList: [],
				classify_image: [],
				errorType: ['message','toast'],
				rules: {
					name: [
						{
							required: true,
							message: '请填写分类名称',
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
			this.$refs.uForm.setRules(this.rules);
		},
		onLoad(e) {
			// 上传文件参数
			this.param.token = this.vuex_token
			this.getClassify()
		},
		methods: {
			// 获取分类列表
			getClassify() {
				this.$u.api.getProductType({
					sort: 'weigh',
					order: 'desc',
				}).then(res => {
					console.log(res)
					if(res.code == 1 ) {
						this.classifyList = this.classifyList.concat(res.data.rows)
					}
				})
			},
			// 选择分类
			onItem(val,i) {
				this.classifyList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.form.pid = val.id
				this.pidName = val.name.replace(/&nbsp;/ig, "")
				this.selectShow = false
			},
			// 添加属性
			addProp(){
				let obj = {
					title: '',
				}
				this.propList.push(obj)
			},
			// 删除属性
			delProp(index) {
				this.propList.splice(index,1)
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
				this.classify_image = []
				this.lists.forEach((item,index) => {
					this.classify_image.push(item.response.data.fullurl)
				});
				// 开始提交 
				this.onSubmit()
			},
			// 提交
			submit() {
				// 进行必须填数据验证
				this.$refs.uForm.validate(valid => {
					if (valid) {
						// 只选择了图片
						if(this.lists.length > 0 ) {
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
			// 开始提交
			onSubmit(){
				this.form.prop = JSON.stringify(this.propList) 
				console.log(this.form)
				// 进行必须填数据验证
				this.form.image = this.classify_image.join(",")
				this.$u.api.onProductTypeAdd(this.form).then((res) => {
					console.log(res)
					if(res.code == 1){
						// 提示
						uni.showToast({
							title: '添加成功',
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
		.item:first-child {
			color: #777;
		}
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
/deep/ .u-form-item--right__content__slot  {
	display: block;
}
</style>
