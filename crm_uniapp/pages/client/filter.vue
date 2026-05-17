<template>
	<view>
		<view class="slot-content">
			<u-cell-group>
				<u-cell-item  title="客户场景"  :value="formName.scopeName" @click="scopeShow = true"></u-cell-item>
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
    <!-- 选择客户场景 -->
    <u-action-sheet :list="scopeList" v-model="scopeShow" @click="scopeClick"></u-action-sheet>
	</view>
</template>

<script>
	import { getFieldOperator } from '@/common/mUtils.js';

	export default {
		data() {
			return {
				scopeShow: false,
				scopeList: [
					{
						text: '我的客户',
						id: 1
					},
					{
						text: '下属客户',
						id: 2
					},
					{
						text: '全部客户',
						id: 3
					},
				],
				form: {
					scope: '',
				},
				formName: {
					scopeName: '选择',
				},
				// 自定义字段搜索相关
				fields: [],
				searchForm: {},
				searchFormName: {},
			};
		},
		onLoad(e) {
      this.getFields();
		},
		onShow(){
      if(!this.$u.test.isEmpty(this.vuex_filter.scopeName)) {
        this.formName.scopeName  = this.vuex_filter.scopeName
        this.form.scope = this.vuex_filter.scope
      }else {
        this.formName.scopeName  = '我的客户'
        this.form.scope = 1
      }

      this.scopeList.forEach((item,i)=>{
        if(item.id == this.form.scope) {
          item.color = '#2979ff'
        } else {
          item.color = ''
        }
      })
			// 自定义字段的回显由 getFields 中统一处理（$nextTick + restoreData）
		},
		
		methods: {
      getFields() {
        this.$u.get('fields/get_fields',{table: 'crm_customer', source: 'search'}).then((res) => {
          if(res.code == 1){
            this.fields = res.data.fields || [];
            // 等待子组件 initSearchForm 完成后再回显（避免值被覆盖）
            this.$nextTick(() => {
              if (this.vuex_filter && this.vuex_filter.filter) {
                const form = {};
                const formName = {};
                this.fields.forEach(field => {
                  const key = field.field;
                  if (this.vuex_filter.filter[key]) {
                    form[key] = this.vuex_filter.filter[key];
                    const formNameKey = 'search_' + key;
                    if (this.vuex_filter.formName && this.vuex_filter.formName[formNameKey]) {
                      formName[key] = this.vuex_filter.formName[formNameKey];
                    } else {
                      formName[key] = this.vuex_filter.filter[key];
                    }
                  }
                });
                // 同步到父组件状态
                this.searchForm = form;
                this.searchFormName = formName;
                // 直接调用子组件方法确保 UI 同步
                if (this.$refs.fieldSearch) {
                  this.$refs.fieldSearch.restoreData(form, formName);
                }
              }
            });
          }
        })
      },
			// 选择客户场景
			scopeClick(index) {
				this.formName.scopeName =  this.scopeList[index].text
				this.form.scope = this.scopeList[index].id
				this.scopeList.forEach((item,i)=>{
					if(index == i) {
						item.color = '#2979ff'
					} else {
						item.color = ''
					}
				})
			},

			// 自定义字段搜索变更回调
			onFieldSearchChange(form, formName) {
				this.searchForm = form;
				this.searchFormName = formName;
			},
			// 重置
			reset() {
				// 重置固定字段
				// this.form = { scope: '1' };
				// this.formName = { scopeName: '我的客户' };
				// 高亮默认选中项「我的客户」
				this.scopeList.forEach(item => {
					item.color = (item.id == '1') ? '#2979ff' : '';
				});
				// 重置自定义字段
				this.searchForm = {};
				this.searchFormName = {};
				if (this.$refs.fieldSearch) {
					this.$refs.fieldSearch.reset();
				}
				// 清除 Vuex 持久化的筛选条件
				this.$u.vuex('vuex_filter', null);
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
				}

				// 自定义字段搜索项
				for (const key in latestForm) {
					if (latestForm.hasOwnProperty.call(latestForm, key)) {
						const value = latestForm[key];
						if (!this.$u.test.isEmpty(value)) {
							// 查找字段定义获取操作符
							const fieldDef = this.fields.find(f => f.field === key);
							const formtype = fieldDef ? fieldDef.formtype : 'input';
							filterData.filter[key] = value;
							filterData.op[key] = getFieldOperator(formtype);
						}
					}
				}
				// 自定义字段显示名
				for (const key in latestFormName) {
					if (latestFormName[key] != '选择' && latestFormName[key] != '输入') {
						filterData.formName['search_' + key] = latestFormName[key];
					}
				}
				// 同步到父组件状态
				this.searchForm = latestForm;
				this.searchFormName = latestFormName;
				// 储存
				this.$u.vuex('vuex_filter', filterData)
				uni.navigateBack();
			},
		
		},
		
	}
</script>

<style lang="scss">
.slot-content{
  .u-cell{
    padding: 12rpx 16rpx;
  }
  .u-border-bottom:after{border: none;}
	background-color: #fff;
	.bottom-btn {
		text-align: right;
		padding: 68rpx 25rpx;
	}
	.custom-fields-section {
		margin-top: 20rpx;
		.section-title {
			padding: 20rpx 32rpx;
			font-size: 28rpx;
			font-weight: 600;
			color: #606266;
			background-color: #f5f7fa;
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
