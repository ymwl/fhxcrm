<template>
	<view style="overflow:hidden">
		<view class="set-box">
			<u-form :model="form" ref="uForm" label-position="top" :error-type="errorType">
				<u-form-item required label="产品名称:" label-width="160" prop="name">
					<u-input v-model="form.name" :border="true" />
				</u-form-item>
				<u-form-item required label="产品分类:" label-width="160">
					<view class="u-flex" style="width:100%">
						<u-input type="select" class="u-flex-1 u-m-r-15" :border="true" :select-open="selectShow" v-model="classifyName" placeholder="请选择分类" @click="selectShow = true" />
						<u-button size="medium" @click="addClassify">添加分类</u-button>
					</view>
				</u-form-item>
				<u-form-item label="产品属性:" label-width="160" >
					<view class="fa-array">
						<view class="u-flex">
							<view class="u-flex-5">
								<view class="title">属性名</view>
							</view>
							<view class="u-flex-5 u-p-l-10">
								<view class="title">属性值</view>
							</view>
						</view>
						<view class="u-flex u-m-t-15" v-for="(s, x) in form.prop" :key="x">
							<view class="u-flex-5"><u-input disabled v-model="s.title" :trim="true" :border="true" /></view>
							<view class="u-m-l-15 u-m-r-15 u-flex-5"><u-input v-model="s.value" :trim="true" :border="true" /></view>
						</view>
					</view>
				</u-form-item>
				<u-form-item required label="规格:" label-width="160" prop="specification">
					<u-input v-model="form.specification" :border="true" />
				</u-form-item>
				<u-form-item required label="单位:" label-width="160" prop="unit">
					<view class="u-flex" style="width:100%">
						<u-input type="select" class="u-flex-1 u-m-r-15" :border="true" :select-open="unitShow" v-model="form.unit" placeholder="请选择单位" @click="unitShow = true" />
						<u-button size="medium" @click="addUnitShow = true">添加单位</u-button>
					</view>
				</u-form-item>
				<u-form-item label="编码" label-width="160" >
					<u-input v-model="form.sku" :border="true" />
				</u-form-item>
				<u-form-item label="库存:" label-width="160" >
					<u-input v-model="form.inventory" type="digit" :border="true" />
				</u-form-item>
				<u-form-item label="价格:" label-width="160" >
					<u-input v-model="form.price" type="digit" :border="true" />
				</u-form-item>
				<u-form-item label="商品说明:" label-width="160" >
					<u-input v-model="form.remark" :border="true" />
				</u-form-item>
			</u-form>
			<view class="u-m-t-40" style="text-align: center;">
				<u-button class="u-m-l-15" type="success"  @click="submit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">提交</u-button>
			</view>
		</view>
		<u-modal v-model="show" :content="content"></u-modal>
		<u-calendar v-model="dateShow" :min-date="minDate" :max-date="maxDate" mode="range" @change="change"></u-calendar>
		<!-- 选择产品分类 -->
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
			<!-- <u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="onClassifySearch"></u-search> -->
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
						<u-loadmore :status="status" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
		<!-- 选择单位 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="unitShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">选择单位</text> 
				<view class="" @click="unitShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<!-- <u-search margin="30rpx 20rpx" shape="square" v-model="keyword" :show-action="false" :clearabled="true"  placeholder="输入名称搜索" @change="onSearch"></u-search> -->
			<scroll-view scroll-y style="height: 660rpx;width: 100%;">
				<view class="list">
					<block v-if="unitList.length > 0">
						<view class="u-m-b-45">
							<view class="item u-flex u-border-bottom" v-for="(item,index) in unitList" :key="index" @click="onUnitItem(item,index)">
								<view class="title">
									<u-parse :html="item.name"></u-parse>
								</view>
								<view class="check-icon">
									<u-icon v-if="item.checked" name="checkmark" color="#2979ff" size="38"></u-icon>
								</view>
							</view>
						</view>
						<u-loadmore :status="status" ></u-loadmore>
					</block>
					<u-empty text="暂无数据" v-else  margin-top="100" mode="list"></u-empty>
				</view>
			</scroll-view>
		</u-popup>
		<!-- 添加单位 -->
		<u-popup class="popup-content" mode="bottom" border-radius="38"  v-model="addUnitShow" >
			<view class="popup-title u-border-bottom">
				<view class=""  style="width: 45px;">
					<!-- <u-icon name="close"  color="#909399" size="30"></u-icon> -->
				</view>
				<text class="">添加单位</text> 
				<view class="" @click="addUnitShow = false" style="width: 45px;">
					<u-icon name="close"  color="#909399" size="30"></u-icon>
				</view>
			</view>
			<view class="unit" style="height: 660rpx;width: 100%;">
				<view class="u-flex u-m-t-25">
					<view class="u-m-r-15">名称：</view>
					<u-input class="u-flex-1" v-model="unitText" placeholder="请填写单位名称" :border="true"  />
				</view>
				<u-button class="u-m-l-15 u-m-t-50" type="success"  @click="onAddUnit" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定</u-button>
			</view>
		</u-popup>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				selectShow:false,
				unitShow: false,
				addUnitShow: false,
				keyword: '',
				status: 'loadmore',
				classifyName: '',
				unitText: '',
				page: 0,
				pageSize: 10,
				lastPage: false,
				classifyList: [],
				unitList: [],
				selectList: [],
				type: '',
				product_id: '',
				minDate: '',
				maxDate: '',
				dateShow: false,
				show: false,
				content: '',
				form: {
					name: '',
					product_type_id: '',
					product_unit_id: '',
					unit: '',
					specification: '',
					sku: '',
					inventory: '',
					price: '',
					remark: '',
					prop: [],
				},
				timeText: '',
				errorType: ['message','toast'],
				statusList: [
					{
						name: 1,
						text: '正常',
					},
					{
						name: 2,
						text: '隐藏',
					},
				],
				rules: {
					name: [
						{
							required: true,
							message: '请输入商品名字',
							trigger: ['change','blur']
						},
					],
					specification: [
						{
							required: true,
							message: '请输入产品规格',
							trigger: ['change','blur']
						},
					],
					unit: [
						{
							required: true,
							message: '请选择单位',
							trigger: ['change','blur']
						},
					],
				}
			};
		},
		onLoad(e) {
			this.type = e.type
			if(e.id) {
				this.product_id = e.id
				this.getDetails()
			} else {
				this.getClassify()
				this.getProductUnit()
			}
			if(this.type == "edit") {
				uni.setNavigationBarTitle({
					title: '编辑产品'
				});
			}
		},
		onShow() {
		
		},
		// 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
		onReady() {
			this.$refs.uForm.setRules(this.rules);
		},
		methods: {
			// 获取商品详情
			getDetails() {
				this.$u.api.getProductEdit({
					id: this.product_id,
				}).then(res => {
					if(res.code == 1 ) {
						this.form.id = res.data.id
						this.form.name = res.data.name
						this.form.product_type_id = res.data.product_type_id
						this.form.product_unit_id = res.data.product_unit_id
						this.form.unit = res.data.unit
						this.form.specification = res.data.specification
						this.form.sku = res.data.sku
						this.form.inventory = res.data.inventory
						this.form.price = res.data.price
						this.form.remark = res.data.remark
						this.form.prop = res.data.prop ? JSON.parse(res.data.prop) : []
						this.getClassify()
						this.getProductUnit()
					}
				})
			},
			// 搜索
			onClassifySearch() {
				this.getClassify()
			},
			// 添加分类
			addClassify() {
				this.$u.route('pages/product/classify/classify');
			},
			// 添加单位
			onAddUnit() {
				if(this.unitText == '') {
					uni.showToast({
						title: "请输入单位名称",
						icon: 'none',
						duration: 2000
					})
					return
				}
				this.$u.api.onProductUnitAdd({
					name: this.unitText
				}).then(res => {
					if(res.code == 1 ) {
						// 提示
						uni.showToast({
							title: "添加成功",
							icon: 'success',
							duration: 2000
						})
						this.getProductUnit()
						this.addUnitShow = false
					}
				})
			},
			// 获取分类列表
			getClassify(isNextPage,pages) {
				this.$u.api.getProductTypeList({
					showField:"name",
					keyField:"id",
					searchField:"name"
				}).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastPage = true
						} 
						//不够一页
						if (res.data.list.length < 10) {
							this.status = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.classifyList = this.classifyList.concat(res.data.list)
							return 
						}
						this.classifyList = res.data.list
						// 编辑 获取赋值分类名称
						if(this.type == "edit") {
							this.classifyList.forEach((item,index) => {
								if(this.form.product_type_id == item.id) {
									item.checked = true
									this.classifyName = item.name.replace(/&nbsp;/ig, "")
								}
							})
						}
						
					}
				})
			},
			// 滚动到底部加载更多
			reachBottom() {
				if(this.lastPage || this.status == 'loading') return;
				this.status = 'loading'
				setTimeout(() => {
					if(this.lastPage) return ;
					this.getClassify(true,++this.page)
					if(this.classifyList.length >= 10) this.status = 'loadmore';
					else this.status = 'loading';
				}, 1200)
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
				this.form.product_type_id = val.id
				this.classifyName = val.name.replace(/&nbsp;/ig, "")
				this.selectShow = false
				this.getProductTypeProp(val.id)
			},
			// 获取分类属性
			getProductTypeProp(id) {
				this.$u.api.getProductTypeProp({
					type_id: id,
				}).then(res => {
					if(res.code == 1 ) {
						let prop = res.data.prop ? JSON.parse(res.data.prop) : []
						prop.forEach((item,index)=>{
							item.value = ''
						})
						this.form.prop = prop
					}
				})
			},
			// 获取单位
			getProductUnit(isNextPage,pages) {
				this.$u.api.getProductUnitList({
					showField:"name",
					keyField:"id",
					searchField:"name"
				}).then(res => {
					if(res.code == 1 ) {
						// 最后一页
						if(res.data.list.length == 0) {
							this.lastPage = true
						} 
						//不够一页
						if (res.data.list.length < 10) {
							this.status = 'nomore'
						}
						// 第二页开始
						if(isNextPage) {
							this.unitList = this.unitList.concat(res.data.list)
							return 
						}
						this.unitList = res.data.list
					}
				})
			},
			// 选择单位
			onUnitItem(val,i) {
				this.unitList.forEach((item,index) => {
					if(val.id == item.id) {
						item.checked = true
					} else {
						item.checked = false
					}
				})
				this.form.product_unit_id = val.id
				this.form.unit = val.name
				this.unitShow = false
			},
			// 修确认提交
			submit() {
				// 深度克隆
				let param = this.$u.deepClone(this.form);
				if(this.form.prop.length > 0){
					param.prop = JSON.stringify(this.form.prop)
				}
				this.$refs.uForm.validate(valid => {
					if (valid) {
						if(this.type == 'add') {
							this.$u.api.onProductAdd(param).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: "添加成功",
										icon: 'success',
										duration: 2000
									})
									setTimeout(() => {
										uni.navigateBack();
									}, 1000);
								}
							})
						} else {
							this.$u.api.onProductEdit(param).then((res) => {
								if(res.code == 1) {
									// 提示
									uni.showToast({
										title: "修改成功",
										icon: 'success',
										duration: 2000
									})
									setTimeout(() => {
										uni.navigateBack();
									}, 1000);
								}
							})
						}
					} else {
						console.log('验证失败');
					}
				});
				
			},
			// 查看提示
			open(val) {
				this.show = !this.show
				this.content = this.hint[val]
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
		margin-bottom: 45rpx;
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
/deep/ .u-size-medium {
	font-size: 26rpx;
	padding: 0 34rpx !important;
}
.unit {
	padding: 0rpx 22rpx;
}
.fa-array .title {
	font-size: 30rpx;
	font-weight: bold;
}
// /deep/ .u-form-item {
// 	padding: 0 !important;
// }
</style>
