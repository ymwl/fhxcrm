<template>
	<view>
		<view class="slot-content">
			<u-cell-group>
				<u-cell-item  title="客户场景"  :value="formName.scopeName" @click="sceneShow = true"></u-cell-item>
			</u-cell-group>

			<!-- 自定义字段搜索组件 -->
			<fhx-field-search 
				ref="fieldSearch"
				:fields="fields"
				:value="searchForm"
				:value-name="searchFormName"
				:theme="vuex_theme"
				@change="onFieldSearchChange">
			</fhx-field-search>

			<view class="bottom-btn">
				<u-button class="u-m-r-15" type="default" size="medium" @click="reset">重置</u-button>
				<u-button type="primary" size="medium" @click="onConfirm" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定</u-button>
			</view>
		</view>

		<!-- 选择所属客户场景 -->
		<u-action-sheet :list="sceneList" v-model="sceneShow" @click="sceneClick"></u-action-sheet>
	</view>
</template>

<script>
	import { getFieldOperator } from '@/common/mUtils.js';

	export default {
		data() {
			return {
				sceneShow: false,
				sceneList: [
					{
						text: '我的',
						id: 1
					},
					{
						text: '下属的',
						id: 2
					},
					{
						text: '全部',
						id: 3
					}
				],
				form: {
					scope: '1',
				},
				formName: {
					scopeName: '我的',
				},
				// 自定义字段搜索相关
				fields: [],
				searchForm: {},
				searchFormName: {},
			};
		},
		onLoad(e) {
			const tempscene = uni.getStorageSync('contacts_scene');
			if(tempscene){
				for (var i in tempscene) {
				    this.sceneList.push({id:i,text:tempscene[i]}); 
				}
			}
			this.getFields();
		},
		
		methods: {
			// 获取自定义字段
			getFields() {
				this.$u.get('fields/get_fields', { table: 'crm_customer_contacts', source: 'search' }).then(res => {
					if (res.code == 1) {
						this.fields = res.data.fields || [];
					}
					// 字段加载完成后再回显已保存的筛选条件
					this.$nextTick(() => {
						this.restoreFilter();
					});
				});
			},
			// 回显已保存的筛选条件
			restoreFilter() {
				const filterData = uni.getStorageSync('contacts_filter');
				if (filterData) {
					// 回显固定字段（scope/scopeName 存储在顶层）
					if (filterData.scope) {
						this.form.scope = filterData.scope;
					}
					if (filterData.scopeName && filterData.scopeName !== '选择') {
						this.formName.scopeName = filterData.scopeName;
					}
					// 高亮场景列表
					this.sceneList.forEach((item, i) => {
						item.color = (item.id == this.form.scope) ? '#2979ff' : '';
					});
					// 回显自定义字段 - 直接调用子组件 restoreData 避免 prop/watch 时序问题
					if (filterData.filter && Object.keys(filterData.filter).length > 0) {
						let restoredForm = {};
						let restoredFormName = {};
						for (let key in filterData.filter) {
							restoredForm[key] = filterData.filter[key];
							const formNameKey = 'search_' + key;
							if (filterData.formName && filterData.formName[formNameKey]) {
								restoredFormName[key] = filterData.formName[formNameKey];
							}
						}
						// 同步到父组件状态
						this.searchForm = restoredForm;
						this.searchFormName = restoredFormName;
						// 直接调用子组件方法确保 UI 同步
						if (this.$refs.fieldSearch) {
							this.$refs.fieldSearch.restoreData(restoredForm, restoredFormName);
						}
					}
				}
			},
			// 自定义字段搜索值变化
			onFieldSearchChange(form, formName) {
				this.searchForm = form;
				this.searchFormName = formName;
			},
			
			// 选择场景
			sceneClick(index) {
				this.formName.scopeName = this.sceneList[index].text;
				this.form.scope = this.sceneList[index].id;
				this.sceneList.forEach((item, i) => {
					item.color = (index == i) ? '#2979ff' : '';
				});
			},
			// 重置
			reset() {
				this.form = {
					scope: '1',
				};
				this.formName = {
					scopeName: '选择',
				};
				// 高亮默认选中项「我的」
				this.sceneList.forEach((item) => {
					item.color = (item.id == '1') ? '#2979ff' : '';
				});
				// 重置自定义字段
				this.searchForm = {};
				this.searchFormName = {};
				if (this.$refs.fieldSearch) {
					this.$refs.fieldSearch.reset();
				}
				// 清除本地存储的筛选条件
				uni.removeStorageSync('contacts_filter');
			},
			// 确定
			onConfirm() {
				// 从子组件直接读取最新数据，避免 input @change 失焦才触发的延迟问题
				let searchData = { form: {}, formName: {} };
				if (this.$refs.fieldSearch) {
					searchData = this.$refs.fieldSearch.getSearchData();
				}
				const latestForm = searchData.form;
				const latestFormName = searchData.formName;

				let filterData = {
          scope: this.form.scope,
          scopeName: this.formName.scopeName,
					filter: {},
					op: {},
					formName: {}
				};
				
				// 处理固定字段 - 所属客户

				if (this.formName.scopeName && this.formName.scopeName !== '选择') {
					filterData.scopeName = this.formName.scopeName;
				}

				// 处理自定义字段
				if (latestForm && Object.keys(latestForm).length > 0) {
					for (let key in latestForm) {
						if (latestForm[key] !== '' && latestForm[key] !== undefined && latestForm[key] !== null) {
							filterData.filter[key] = latestForm[key];
							// 找到对应字段获取操作符
							let field = this.fields.find(f => f.field === key);
							if (field) {
								filterData.op[key] = getFieldOperator(field.formtype);
							}
							// 保存显示名
							if (latestFormName[key]) {
								filterData.formName['search_' + key] = latestFormName[key];
							}
						}
					}
				}

				console.log(filterData);
				// 同步到父组件状态
				this.searchForm = latestForm;
				this.searchFormName = latestFormName;
				// 储存
				uni.setStorageSync('contacts_filter', filterData);
				uni.navigateBack();
			}
		},
		
	}
</script>

<style lang="scss">
.slot-content{
	background-color: #fff;
	.time {
		padding: 26rpx 32rpx;
		.title {
			margin-bottom: 15rpx;
		}
		.line {
			width: 20rpx;
			height: 1px;
			background-color: #dcdfe6;
			margin: 0 12rpx;
		}
		.item {
			display: flex;
			align-items: center;
			min-height: 35px;
			padding: 0 25rpx;
			border-radius: 10rpx;
			border: 1px solid #dcdfe6;
		}
	}
	.bottom-btn {
		text-align: right;
		padding: 68rpx 25rpx;
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
		padding-bottom: 45rpx;
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
</style>
