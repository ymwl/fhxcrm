<template>
	<view>
		<view class="set-box">
			<u-form :model="form" ref="uForm" >
				<u-form-item label="客户名称:" label-width="160" prop="number">
					<u-input disabled v-model="form.name" />
				</u-form-item>
				<u-form-item label="联系电话:" label-width="160">
					<u-input disabled v-model="form.mobile"  placeholder="暂无" />
				</u-form-item>
				<u-form-item label="客户等级:" label-width="160" >
					<u-input disabled v-model="form.level"   placeholder="暂无"  />
				</u-form-item>
				<u-form-item label="客户行业:" label-width="160" prop="money">
					<u-input disabled v-model="form.industry" placeholder="暂无" />
				</u-form-item>
				<u-form-item label="客户来源:" label-width="160" prop="money">
					<u-input disabled v-model="form.source" placeholder="暂无" />
				</u-form-item>
				<u-form-item label="客户标签:" label-width="160" >
					<u-input disabled v-model="form.tags" placeholder="暂无"/>
				</u-form-item>
				<u-form-item label="地址:" label-width="160" >
					<u-input disabled v-model="addressName" placeholder="暂无"  />
				</u-form-item>
				<u-form-item label="详细地址:" label-width="160" >
					<u-input disabled v-model="form.detail_address" placeholder="暂无"  />
				</u-form-item>
				<u-form-item label="备注:"  label-width="160" prop="sort">
					<u-input disabled v-model="form.remark" type="textarea" placeholder="暂无"  />
				</u-form-item>
				<!-- 自定义字段详细组件 -->
				<f-details :fields="fields" :form="form"></f-details>
			</u-form>
		</view>
	</view>
</template>

<script>
	import { processingImages,getImgUrl,get_date} from '@/common/mUtils'
	import {baseUrl,api_v1} from '@/common/config'


	export default {
		data() {
			return {
				labelPosition: 'left',
				border: false,
				fields: [],
				id: '',
				addressName: '',
				form: {},
			};
		},
		onLoad(e) {
			if(e.id) {
				this.id = e.id
				// 获取详情
				this.getData()
			}
		},
		filters: {
			changeImgUrl(value){
				if(value) {
					return processingImages(value)
				}
			},
		},
		methods: {
			// 获取线索详情
			getData() {
				this.$u.api.getCluesEdit({ids: this.id}).then(res => {
					if(res.code == 1 ) {
						this.form = res.data
						// 获取自定义字段
						this.getFields()
						this.getBaseConfig()
						this.addProvince()
					}
				})
			},
			// 自定义字段
			getFields() {
				let arr = []
				this.$u.api.getFields({table: 'clues',id: ''}).then((res) => {
					if(res.code == 1){
						res.data.fields.forEach((item,index)=>{
							// 复选框 数据格式化
							if(item.type == 'checkbox'|| item.type == 'radio') {
								let arr = []
								let valArr = []
								// 获取对应字段数据
								if(this.form[item.name]) {
									valArr = this.form[item.name].split(',')
								}
								for (const key in item.content_list) {
									if (Object.hasOwnProperty.call(item.content_list, key)) {
										valArr.forEach((i,s) => {
											if(i == key) {
												arr.push(item.content_list[key]) 
											}
										});
									}
								}
								item.values = arr.join(',')
							}
							// 数据赋值
							if(this.form[item.name]) {
							 if ((item.type == 'image' || item.type == 'files') || (item.type == 'images' || item.type == 'files')) {
									let arr = []
									 this.form[item.name].split(',').forEach((u,index) =>{
										arr.push(getImgUrl(u))
									})
									item.fileArr = arr 
								} 
								if(item.type == 'switch'){
									item.values = this.form[item.name] == 1 ? true : false
								}
								if((item.type == 'select' || item.type == 'selects') || (item.type == 'selectpage' || item.type == 'selectpages')){
									let arrs = this.form[item.name].split(',')
									let narrs = []
									arrs.forEach((r,x) => {
										narrs.push(item.content_list[r]) 
									});
									item.values = narrs.join(',')
								}
								if(item.type == 'array') {
									let val = this.form[item.name]
									if (val) {
										if (this.$u.test.object(val)) {
											let arr = [];
											for (let i in val) {
												arr.push({
													key: i,
													value: val[i]
												});
											}
											if (arr.length > 0) {
												this.list = arr;
											}
										} else {
											let o = JSON.parse(val);
											let arr = [];
											for (let i in o) {
												arr.push({
													key: i,
													value: o[i]
												});
											}
											if (arr.length > 0) {
												item.arrList = arr;
											}
										}
									} else {
										item.arrList = []
									}
								}
							} else {
								item.values = ''
							}
						})
						this.fields = res.data.fields
					}
				})
			},
			// 获取配置字段
			getBaseConfig() {
				this.$u.api.getBaseConfig().then((res) => {
					if(res.code == 1){
						this.form.level = res.data.levelList[this.form.level]
						this.form.industry = res.data.industryList[this.form.industry]
						this.form.source = res.data.sourceList[this.form.source]
					}
				})
			},
			// 地区获取赋值
			addProvince() {
				let name = ''
				// 获取省
				this.$u.api.getArea({province: '',city:''}).then((res) => {
					if(res.code == 1){
						res.data.forEach((item,index)=>{
							if(item.value == this.form.province){
								name = item.name
								// 获取市
								this.$u.api.getArea({province: item.value,city:''}).then((resc) => {
									if(res.code == 1){
										resc.data.forEach((i,idx)=>{
											if(i.value == this.form.city){
												name = name + i.name
												// 获取区
												this.$u.api.getArea({province: item.value,city: i.value}).then((resd) => {
													if(res.code == 1){
														resd.data.forEach((c,idc)=>{
															if(c.value == this.form.area){
																name = name + c.name
																this.addressName = name
															}
														})
													}
												})
											}
										})
									}
								})
							}
						})
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
	margin: 0 15rpx 15rpx 0;  
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
