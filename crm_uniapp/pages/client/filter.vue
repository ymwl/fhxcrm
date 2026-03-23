<template>
	<view>
		<view class="slot-content">
			<u-cell-group>
				<u-cell-item  title="所属客户"  :value="formName.scopeName" @click="scopeShow = true"></u-cell-item>
			</u-cell-group>
			<!-- 自定义字段搜索区域 -->
				<u-cell-group v-if="fields.length > 0">
					<!-- input/tel/textarea/number 类型 - 直接显示输入框 -->
					<block v-for="field in fields" :key="field.id">
						<view v-if="['input', 'tel', 'textarea', 'number'].includes(field.formtype)" class="search-input-item u-border-bottom">
							<view class="label">{{ field.title }}</view>
							<u-input v-model="searchForm[field.field]" :placeholder="'请输入' + field.title" :border="false" clearable />
						</view>
						<!-- select/radio 类型 -->
						<u-cell-item v-else-if="['select', 'radio'].includes(field.formtype)" 
							:title="field.title" 
							:value="searchFormName[field.field]" 
							@click="onSearchSelectClick(field)">
						</u-cell-item>
						<!-- checkbox 类型 -->
						<u-cell-item v-else-if="field.formtype === 'checkbox'" 
							:title="field.title" 
							:value="searchFormName[field.field]" 
							@click="onSearchCheckboxClick(field)">
						</u-cell-item>
						<!-- datetime/date 类型 -->
						<view v-else-if="['datetime', 'date'].includes(field.formtype)" class="time u-border-bottom">
							<view class="title">{{ field.title }}</view>
							<view class="u-flex">
								<view class="item u-flex-1" @click="onSearchDateClick(field, 'start')">
									<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
									<text class="u-font-26">{{ searchFormName[field.field + '_start'] }}</text>
								</view>
								<view class="line"></view>
								<view class="item u-flex-1" @click="onSearchDateClick(field, 'end')">
									<u-icon class="u-m-r-10" name="calendar" color="#2979ff" size="28"></u-icon>
									<text class="u-font-26">{{ searchFormName[field.field + '_end'] }}</text>
								</view>
							</view>
						</view>
						<!-- city/district 类型 -->
						<u-cell-item v-else-if="['city', 'district'].includes(field.formtype)" 
							:title="field.title" 
							:value="searchFormName[field.field]" 
							@click="onSearchCityClick(field)">
						</u-cell-item>
					</block>
				</u-cell-group>

			<view class="bottom-btn">
				<u-button class="u-m-r-15" type="default" size="medium" @click="reset">重置</u-button>
				<u-button type="primary" size="medium" @click="onConfirm" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}" :ripple="true">确定</u-button>
			</view>
		</view>
    <!-- 选择客户场景 -->
    <u-action-sheet :list="scopeList" v-model="scopeShow" @click="scopeClick"></u-action-sheet>
	<!-- 自定义字段搜索 - 输入弹窗 -->
		<u-popup mode="center" v-model="searchFieldShow" border-radius="14">
			<view class="search-input-popup">
				<view class="popup-title">{{ currentSearchField ? currentSearchField.title : '输入' }}</view>
				<u-input v-model="searchInputValue" :placeholder="'请输入' + (currentSearchField ? currentSearchField.title : '')" border="surround" clearable />
				<view class="popup-btns">
					<u-button type="default" size="medium" @click="searchFieldShow = false">取消</u-button>
					<u-button type="primary" size="medium" @click="onSearchInputConfirm" :custom-style="{backgroundColor: vuex_theme.color, color: vuex_theme.bgColor}">确定</u-button>
				</view>
			</view>
		</u-popup>
		<!-- 自定义字段搜索 - 下拉选择弹窗 -->
		<u-action-sheet :list="searchFieldPickerList" v-model="searchFieldPickerShow" @click="onSearchPickerClick" @close="onSearchPickerClose"></u-action-sheet>
		<!-- 自定义字段搜索 - 时间选择器 -->
		<u-picker v-model="searchDatePickerShow" :hour="searchDateType === 'datetime'" mode="time" :params="searchDateParams" @confirm="onSearchDateConfirm" @cancel="onSearchDateCancel"></u-picker>
		<!-- 自定义字段搜索 - 省市区选择器 -->
		<fa-citys v-model="searchCityPickerShow" @city-change="onSearchCityConfirm"></fa-citys>
	</view>
</template>

<script>
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
				params: {
					year: true,
					month: true,
					day: true,
					hour: true,
					minute: true,
					second: false
				},
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
				searchFieldShow: false,
				currentSearchField: null,
				searchFieldPickerShow: false,
				searchFieldPickerList: [],
				searchDatePickerShow: false,
				searchDateType: '',
				searchDateStart: '',
				searchDateEnd: '',
				searchInputValue: '',
				searchCityPickerShow: false,
			};
		},
		computed: {
			searchDateParams() {
				return {
					year: true,
					month: true,
					day: true,
					hour: this.searchDateType === 'datetime',
					minute: this.searchDateType === 'datetime',
					second: false
				};
			}
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
      console.log('this.vuex_filter',this.vuex_filter)
			if(!this.$u.test.isEmpty(this.vuex_filter.filter)) {
				// 已选数据合并（固定筛选项）
				this.form = Object.assign(this.form,this.vuex_filter.filter)
				this.formName = Object.assign(this.formName,this.vuex_filter.formName)
				this.bselectdata();
				// 自定义字段的回显在 getFields 回调的 restoreSearchFields 方法中处理
			}
		},
		
		methods: {
      getFields() {
        this.$u.api.getFields({table: 'crm_customer', source: 'search'}).then((res) => {
          if(res.code == 1){
            this.fields = res.data.fields || [];
            // 初始化 searchForm 和 searchFormName
            this.fields.forEach(field => {
              this.$set(this.searchForm, field.field, '');
              // 时间类型需要分开存储开始和结束
              if (field.formtype === 'datetime' || field.formtype === 'date') {
                this.$set(this.searchFormName, field.field + '_start', '选择');
                this.$set(this.searchFormName, field.field + '_end', '选择');
              } else {
                this.$set(this.searchFormName, field.field, '选择');
              }
            });
            // 回显已选的自定义字段值
            this.restoreSearchFields();
          }
        })
      },
      // 回显已选的自定义字段值
      restoreSearchFields() {
        if (this.vuex_filter && this.vuex_filter.filter) {
          this.fields.forEach(field => {
            const key = field.field;
            if (this.vuex_filter.filter[key]) {
              this.searchForm[key] = this.vuex_filter.filter[key];
              // 恢复显示名
              if (field.formtype === 'datetime' || field.formtype === 'date') {
                const range = this.vuex_filter.filter[key].split(' - ');
                if (range.length === 2) {
                  this.searchFormName[key + '_start'] = range[0];
                  this.searchFormName[key + '_end'] = range[1];
                  this.searchDateStart = range[0];
                  this.searchDateEnd = range[1];
                }
              } else {
                const formNameKey = 'search_' + key;
                if (this.vuex_filter.formName && this.vuex_filter.formName[formNameKey]) {
                  this.searchFormName[key] = this.vuex_filter.formName[formNameKey];
                } else {
                  this.searchFormName[key] = this.vuex_filter.filter[key];
                }
              }
            }
          });
        }
      },
			//绑定输入框的值
			bselectdata(){
					if(this.form.next_time){
						this.formName.next_time_start=this.form.next_time.slice(0, 16);
						this.formName.next_time_end=this.form.next_time.slice(19, 35);
					}
					if(this.form.last_up_time){
						this.formName.last_up_time_start=this.form.last_up_time.slice(0, 16);
						this.formName.last_up_time_end=this.form.last_up_time.slice(19, 35);
					}
	
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

			// json 转化
			onJson(data) {
				let arr = []
				for (const key in data) {
					if (Object.hasOwnProperty.call(data, key)) {
						let obj = {}
						obj.id = data[key]['name']
						obj.text = data[key]['name']
						arr.push(obj)
					}
				}
				return arr
			},
			// 重置
			reset() {
				for (const key in this.form) {
					if (this.form.hasOwnProperty.call(this.form, key)) {
						if(!this.$u.test.isEmpty(this.form[key])){
							switch (key) {
								case 'scope':
									this.scopeList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'rank':
									this.rankList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'hangye':
									this.hangyeList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'source':
									this.sourceList.forEach((item,index)=>{
										if(this.form[key] == item.id) {
											item.color = ""
										}
									})
									break;
								case 'expire_type':
									break;
								default:
									break;
							}
						}
					}
				}
				this.form = {
					scope: ''
				}
				this.formName = {
					scopeName: '选择',
				}
				// 重置自定义字段搜索
				this.fields.forEach(field => {
					this.searchForm[field.field] = '';
					if (field.formtype === 'datetime' || field.formtype === 'date') {
						this.searchFormName[field.field + '_start'] = '选择';
						this.searchFormName[field.field + '_end'] = '选择';
					} else {
						this.searchFormName[field.field] = '选择';
					}
				});
				this.searchDateStart = '';
				this.searchDateEnd = '';
			},
			// 确定
			onConfirm() {
				let filterData = {
          scope: this.form.scope,
          scopeName: this.formName.scopeName,
					filter: {},
					op: {},
					formName: {}
				}


				// 自定义字段搜索项
				for (const key in this.searchForm) {
					if (this.searchForm.hasOwnProperty.call(this.searchForm, key)) {
						const value = this.searchForm[key];
						if (!this.$u.test.isEmpty(value)) {
							// 查找字段定义获取操作符
							const fieldDef = this.fields.find(f => f.field === key);
							const formtype = fieldDef ? fieldDef.formtype : 'input';
							filterData.filter[key] = value;
							filterData.op[key] = this.getFieldOperator(formtype);
						}
					}
				}
				// 自定义字段显示名
				for (const key in this.searchFormName) {
					if (this.searchFormName[key] != '选择' && this.searchFormName[key] != '输入') {
						filterData.formName['search_' + key] = this.searchFormName[key];
					}
				}
				// 储存
				this.$u.vuex('vuex_filter', filterData)
				uni.navigateBack();
			},
			// ========== 自定义字段搜索方法 ==========
			// 下拉选择类型点击
			onSearchSelectClick(field) {
				this.currentSearchField = field;
				// 解析 option 或 content_list
				let options = [];
				if (field.content_list && field.content_list.length > 0) {
					options = field.content_list.map(item => ({
						text: item.name || item,
						id: item.value || item.name || item
					}));
				} else if (field.option) {
					const optArr = field.option.split(',');
					options = optArr.map(item => ({
						text: item.trim(),
						id: item.trim()
					}));
				}
				this.searchFieldPickerList = options;
				// 标记当前选中项
				const currentVal = this.searchForm[field.field];
				this.searchFieldPickerList.forEach(item => {
					item.color = item.id === currentVal ? '#2979ff' : '';
				});
				this.searchFieldPickerShow = true;
			},
			// 下拉选择点击
			onSearchPickerClick(index) {
				if (this.currentSearchField) {
					const field = this.currentSearchField.field;
					const selected = this.searchFieldPickerList[index];
					this.searchForm[field] = selected.id;
					this.searchFormName[field] = selected.text;
					// 更新选中状态
					this.searchFieldPickerList.forEach((item, i) => {
						item.color = i === index ? '#2979ff' : '';
					});
				}
			},
			// 下拉选择关闭
			onSearchPickerClose() {
				// 清空选择
				if (this.currentSearchField) {
					const field = this.currentSearchField.field;
					this.searchForm[field] = '';
					this.searchFormName[field] = '选择';
				}
				this.searchFieldPickerList.forEach(item => {
					item.color = '';
				});
			},
			// 多选类型点击
			onSearchCheckboxClick(field) {
				this.currentSearchField = field;
				// 解析 option 或 content_list
				let options = [];
				if (field.content_list && field.content_list.length > 0) {
					options = field.content_list.map(item => ({
						text: item.name || item,
						id: item.value || item.name || item
					}));
				} else if (field.option) {
					const optArr = field.option.split(',');
					options = optArr.map(item => ({
						text: item.trim(),
						id: item.trim()
					}));
				}
				this.searchFieldPickerList = options;
				// 多选场景暂时用单选，后续可改为多选弹窗
				this.searchFieldPickerShow = true;
			},
			// 时间类型点击
			onSearchDateClick(field, type) {
				this.currentSearchField = field;
				this.searchDateType = type;
				this.searchDatePickerShow = true;
			},
			// 时间选择确认
			onSearchDateConfirm(e) {
				if (this.currentSearchField) {
					const field = this.currentSearchField.field;
					const time = e.year + '-' + e.month + '-' + e.day + (this.searchDateType === 'datetime' ? ' ' + e.hour + ':' + e.minute : '');
					if (this.searchDateType === 'start') {
						this.searchDateStart = time;
						this.searchFormName[field + '_start'] = time;
					} else {
						this.searchDateEnd = time;
						this.searchFormName[field + '_end'] = time;
					}
					// 更新 searchForm 中的值（时间范围用 - 连接）
					if (this.searchDateStart && this.searchDateEnd) {
						this.searchForm[field] = this.searchDateStart + ' - ' + this.searchDateEnd;
					} else if (this.searchDateStart) {
						this.searchForm[field] = this.searchDateStart;
					} else if (this.searchDateEnd) {
						this.searchForm[field] = this.searchDateEnd;
					}
				}
			},
			// 时间选择取消
			onSearchDateCancel() {
				if (this.currentSearchField) {
					const field = this.currentSearchField.field;
					if (this.searchDateType === 'start') {
						this.searchFormName[field + '_start'] = '选择';
						this.searchDateStart = '';
					} else {
						this.searchFormName[field + '_end'] = '选择';
						this.searchDateEnd = '';
					}
					// 重新计算时间范围值
					if (this.searchDateStart && this.searchDateEnd) {
						this.searchForm[field] = this.searchDateStart + ' - ' + this.searchDateEnd;
					} else {
						this.searchForm[field] = '';
					}
				}
			},
			// 城市选择点击
			onSearchCityClick(field) {
				this.currentSearchField = field;
				this.searchCityPickerShow = true;
			},
			// 城市选择确认
			onSearchCityConfirm(result) {
				if (this.currentSearchField && result && result.length === 3) {
					const field = this.currentSearchField.field;
					// 存储选中的值：省/市/区
					const cityValue = result[0].label + '/' + result[1].label + '/' + result[2].label;
					this.searchForm[field] = cityValue;
					this.searchFormName[field] = result[0].label + result[1].label + result[2].label;
				}
				this.searchCityPickerShow = false;
			},
			// 获取字段对应的操作符
			getFieldOperator(formtype) {
				switch (formtype) {
					case 'input':
					case 'tel':
					case 'textarea':
					case 'city':
					case 'district':
						return '%*%'; // 模糊搜索
					case 'datetime':
					case 'date':
						return 'RANGE'; // 范围搜索
					case 'number':
					case 'select':
					case 'radio':
					case 'checkbox':
					default:
						return '='; // 精确匹配
				}
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
	.search-input-item {
		display: flex;
		align-items: center;
		padding: 12rpx 16rpx;
		background-color: #fff;
		.label {
			width: 160rpx;
			flex-shrink: 0;
			font-size: 28rpx;
			color: #303133;
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
.search-input-popup {
	padding: 30rpx;
	width: 600rpx;
	.popup-title {
		font-size: 32rpx;
		font-weight: 600;
		text-align: center;
		margin-bottom: 30rpx;
	}
	.popup-btns {
		display: flex;
		justify-content: space-between;
		margin-top: 30rpx;
	}
}
</style>
