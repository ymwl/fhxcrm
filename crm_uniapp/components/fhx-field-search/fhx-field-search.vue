<template>
	<view class="fhx-field-search">
		<!-- 自定义字段搜索区域 -->
		<u-cell-group v-if="fieldList.length > 0">
			<block v-for="field in fieldList" :key="field.id">
				
				<!-- select/radio 类型 -->
				<u-cell-item v-if="['select', 'radio','lselect'].includes(field.formtype)"
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
							<u-icon class="u-m-r-10" name="calendar" :color="themeColor" size="28"></u-icon>
							<text class="u-font-26">{{ searchFormName[field.field + '_start'] }}</text>
						</view>
						<view class="line"></view>
						<view class="item u-flex-1" @click="onSearchDateClick(field, 'end')">
							<u-icon class="u-m-r-10" name="calendar" :color="themeColor" size="28"></u-icon>
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
                <!-- input/tel/textarea/number 类型 - 直接显示输入框 -->
				<view v-else class="search-input-item u-border-bottom">
					<view class="label">{{ field.title }}</view>
					<u-input v-model="searchForm[field.field]" :placeholder="'请输入' + field.title" :border="false" clearable @change="onInputChange(field)" />
				</view>
			</block>
		</u-cell-group>

		<!-- checkbox 多选弹窗 -->
		<u-popup mode="center" v-model="checkboxPopupShow" border-radius="14">
			<view class="checkbox-popup">
				<view class="popup-title">{{ currentSearchField ? currentSearchField.title : '多选' }}</view>
				<scroll-view scroll-y class="checkbox-list" style="max-height: 600rpx;">
					<view v-for="item in searchFieldPickerList" :key="item.id" 
						class="checkbox-item" 
						:class="{active: checkboxSelectedIds.includes(item.id)}"
						@click="onCheckboxToggle(item)">
						<text>{{ item.text }}</text>
						<u-icon v-if="checkboxSelectedIds.includes(item.id)" name="checkbox-mark" color="#2979ff" size="28"></u-icon>
					</view>
				</scroll-view>
				<view class="popup-btns">
					<u-button type="default" size="medium" @click="checkboxPopupShow = false">取消</u-button>
					<u-button type="primary" size="medium" @click="onCheckboxConfirm" :custom-style="{backgroundColor: themeColor, color: themeBgColor}">确定</u-button>
				</view>
			</view>
		</u-popup>
		<!-- 下拉选择弹窗 -->
		<u-action-sheet :list="searchFieldPickerList" v-model="searchFieldPickerShow" @click="onSearchPickerClick" @close="onSearchPickerClose"></u-action-sheet>
		<!-- 时间选择器 -->
		<u-picker v-model="searchDatePickerShow" :hour="searchDateType === 'datetime'" mode="time" :params="searchDateParams" @confirm="onSearchDateConfirm" @cancel="onSearchDateCancel"></u-picker>
		<!-- 省市区选择器 -->
		<fa-citys v-model="searchCityPickerShow" @city-change="onSearchCityConfirm"></fa-citys>
	</view>
</template>

<script>
	export default {
		name: 'fhx-field-search',
		props: {
			fields: {
				type: Array,
				required: true,
				default: () => []
			},
			value: {
				type: Object,
				default: () => ({})
			},
			valueName: {
				type: Object,
				default: () => ({})
			},
			theme: {
				type: Object,
				default: () => ({})
			}
		},
		data() {
			return {
				searchForm: {},
				searchFormName: {},
				currentSearchField: null,
				searchFieldPickerShow: false,
				searchFieldPickerList: [],
				searchDatePickerShow: false,
				searchDateType: '',
				searchDateStart: '',
				searchDateEnd: '',
				searchCityPickerShow: false,
				checkboxPopupShow: false,
				checkboxSelectedIds: [],
				fieldList: []
			};
		},
		computed: {
			themeColor() {
				return (this.theme && this.theme.color) || '#2979ff';
			},
			themeBgColor() {
				return (this.theme && this.theme.bgColor) || '#fff';
			},
			searchDateParams() {
				const isDatetime = this.currentSearchField && this.currentSearchField.formtype === 'datetime';
				return {
					year: true,
					month: true,
					day: true,
					hour: isDatetime,
					minute: isDatetime,
					second: isDatetime
				};
			}
		},
		watch: {
			fields: {
				handler(newFields) {
					this.fieldList = newFields || [];
					this.initSearchForm();
				},
				immediate: true,
				deep: true
			},
			value: {
				handler(newVal) {
					if (newVal && Object.keys(newVal).length > 0) {
						this.restoreData(newVal, this.valueName);
					}
				},
				deep: true
			},
			valueName: {
				handler(newVal) {
					if (this.value && Object.keys(this.value).length > 0) {
						this.restoreData(this.value, newVal);
					}
				},
				deep: true
			}
		},
		methods: {
			// ========== 工具方法 ==========
			getTodayDateStr() {
				const now = new Date();
				const year = now.getFullYear();
				const month = (now.getMonth() + 1).toString().padStart(2, '0');
				const day = now.getDate().toString().padStart(2, '0');
				return `${year}-${month}-${day}`;
			},
			// ========== 初始化 ==========
			initSearchForm() {
				this.fieldList.forEach(field => {
					this.$set(this.searchForm, field.field, '');
					if (field.formtype === 'datetime' || field.formtype === 'date') {
						this.$set(this.searchFormName, field.field + '_start', '选择');
						this.$set(this.searchFormName, field.field + '_end', '选择');
					} else {
						this.$set(this.searchFormName, field.field, '选择');
					}
				});
			},

			// ========== 暴露给父组件的方法 ==========
			// 获取当前搜索数据
			getSearchData() {
				return {
					form: { ...this.searchForm },
					formName: { ...this.searchFormName }
				};
			},
			// 重置所有搜索字段
			reset() {
				this.fieldList.forEach(field => {
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
				this.$emit('reset');
			},
			// 恢复/回显搜索数据
			restoreData(form, formName) {
				if (!form) return;
				this.fieldList.forEach(field => {
					const key = field.field;
					if (form.hasOwnProperty(key) && form[key] !== '' && form[key] !== undefined && form[key] !== null) {
						this.$set(this.searchForm, key, form[key]);
						if (field.formtype === 'datetime' || field.formtype === 'date') {
							const range = form[key].split(' - ');
							if (range.length === 2) {
								this.$set(this.searchFormName, key + '_start', range[0]);
								this.$set(this.searchFormName, key + '_end', range[1]);
								this.searchDateStart = range[0];
								this.searchDateEnd = range[1];
							}
						} else {
							if (formName && formName[key]) {
								this.$set(this.searchFormName, key, formName[key]);
							} else {
								this.$set(this.searchFormName, key, form[key]);
							}
						}
					}
				});
			},

			// ========== 输入变化 ==========
			onInputChange(field) {
				this.$emit('change', { ...this.searchForm }, { ...this.searchFormName });
			},

			// ========== 公共：将字段的 selectList / option 转为选项数组 ==========
			buildOptions(field) {
				let options = [];
				if (field.selectList) {
					if (Array.isArray(field.selectList) && field.selectList.length > 0) {
						// 数组格式：[{name,value}] 或 ['str']
						options = field.selectList.map(item => ({
							text: item.name || item,
							id: item.value || item.name || item
						}));
					} else if (typeof field.selectList === 'object') {
						// 对象格式：{key: value}
						options = Object.keys(field.selectList).map(key => ({
							text: field.selectList[key] || key,
							id: key
						}));
					}
				} else if (field.option) {
					const optArr = field.option.split(',');
					options = optArr.map(item => ({
						text: item.trim(),
						id: item.trim()
					}));
				}
				return options;
			},

			// ========== 下拉选择类型 ==========
			onSearchSelectClick(field) {
				this.currentSearchField = field;
				this.searchFieldPickerList = this.buildOptions(field);
				const currentVal = this.searchForm[field.field];
				this.searchFieldPickerList.forEach(item => {
					item.color = item.id === currentVal ? this.themeColor : '';
				});
				this.searchFieldPickerShow = true;
			},
			onSearchPickerClick(index) {
				if (this.currentSearchField) {
					const field = this.currentSearchField.field;
					const selected = this.searchFieldPickerList[index];
					this.searchForm[field] = selected.id;
					this.searchFormName[field] = selected.text;
					this.searchFieldPickerList.forEach((item, i) => {
						item.color = i === index ? this.themeColor : '';
					});
					this.$emit('change', { ...this.searchForm }, { ...this.searchFormName });
				}
			},
			onSearchPickerClose() {
				if (this.currentSearchField) {
					const field = this.currentSearchField.field;
					this.searchForm[field] = '';
					this.searchFormName[field] = '选择';
				}
				this.searchFieldPickerList.forEach(item => {
					item.color = '';
				});
				this.$emit('change', { ...this.searchForm }, { ...this.searchFormName });
			},

			// ========== 多选类型 ==========
			onSearchCheckboxClick(field) {
				this.currentSearchField = field;
				this.searchFieldPickerList = this.buildOptions(field);
				// 解析当前已选值
				const currentVal = this.searchForm[field.field];
				this.checkboxSelectedIds = currentVal ? currentVal.split(',') : [];
				this.checkboxPopupShow = true;
			},
			onCheckboxToggle(item) {
				const index = this.checkboxSelectedIds.indexOf(item.id);
				if (index > -1) {
					this.checkboxSelectedIds.splice(index, 1);
				} else {
					this.checkboxSelectedIds.push(item.id);
				}
			},
			onCheckboxConfirm() {
				if (this.currentSearchField) {
					const field = this.currentSearchField.field;
					const selectedIds = this.checkboxSelectedIds;
					const selectedNames = this.searchFieldPickerList
						.filter(item => selectedIds.includes(item.id))
						.map(item => item.text);
					this.$set(this.searchForm, field, selectedIds.join(','));
					this.$set(this.searchFormName, field, selectedNames.join(',') || '选择');
					this.$emit('change', { ...this.searchForm }, { ...this.searchFormName });
				}
				this.checkboxPopupShow = false;
			},

			// ========== 时间类型 ==========
			onSearchDateClick(field, type) {
				this.currentSearchField = field;
				this.searchDateType = type;
				// datetime 类型：用户首次点击时自动填入今天的默认时间范围
				if (field.formtype === 'datetime') {
					const today = this.getTodayDateStr();
					if (type === 'start' && !this.searchDateStart) {
						this.searchDateStart = today + ' 00:00:00';
						this.$set(this.searchFormName, field.field + '_start', this.searchDateStart);
					} else if (type === 'end' && !this.searchDateEnd) {
						this.searchDateEnd = today + ' 23:59:59';
						this.$set(this.searchFormName, field.field + '_end', this.searchDateEnd);
					}
					// 两端都设置后，更新 searchForm 为范围字符串
					if (this.searchDateStart && this.searchDateEnd) {
						this.$set(this.searchForm, field.field, this.searchDateStart + ' - ' + this.searchDateEnd);
					}
				}
				this.searchDatePickerShow = true;
			},
			onSearchDateConfirm(e) {
				if (this.currentSearchField) {
					const field = this.currentSearchField.field;
					const formtype = this.currentSearchField.formtype;
					const time = e.year + '-' + e.month + '-' + e.day + (formtype === 'datetime' ? ' ' + e.hour + ':' + e.minute + ':' + e.second : '');
					if (this.searchDateType === 'start') {
						this.searchDateStart = time;
						this.$set(this.searchFormName, field + '_start', time);
					} else {
						this.searchDateEnd = time;
						this.$set(this.searchFormName, field + '_end', time);
					}
					if (this.searchDateStart && this.searchDateEnd) {
						this.$set(this.searchForm, field, this.searchDateStart + ' - ' + this.searchDateEnd);
					} else if (this.searchDateStart) {
						this.$set(this.searchForm, field, this.searchDateStart);
					} else if (this.searchDateEnd) {
						this.$set(this.searchForm, field, this.searchDateEnd);
					}
					this.$emit('change', { ...this.searchForm }, { ...this.searchFormName });
				}
			},
			onSearchDateCancel() {
				if (this.currentSearchField) {
					const field = this.currentSearchField.field;
					if (this.searchDateType === 'start') {
						this.$set(this.searchFormName, field + '_start', '选择');
						this.searchDateStart = '';
					} else {
						this.$set(this.searchFormName, field + '_end', '选择');
						this.searchDateEnd = '';
					}
					if (this.searchDateStart && this.searchDateEnd) {
						this.$set(this.searchForm, field, this.searchDateStart + ' - ' + this.searchDateEnd);
					} else {
						this.$set(this.searchForm, field, '');
					}
					this.$emit('change', { ...this.searchForm }, { ...this.searchFormName });
				}
			},

			// ========== 城市选择 ==========
			onSearchCityClick(field) {
				this.currentSearchField = field;
				this.searchCityPickerShow = true;
			},
			onSearchCityConfirm(result) {
				if (this.currentSearchField && result && result.length === 3) {
					const field = this.currentSearchField.field;
					const cityValue = result[0].label + '/' + result[1].label + '/' + result[2].label;
					this.$set(this.searchForm, field, cityValue);
					this.$set(this.searchFormName, field, result[0].label + result[1].label + result[2].label);
					this.$emit('change', { ...this.searchForm }, { ...this.searchFormName });
				}
				this.searchCityPickerShow = false;
			}
		}
	}
</script>

<style lang="scss" scoped>

.fhx-field-search {
	::v-deep .u-cell_title{
		color: #303133;
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
	.time {
		padding: 26rpx 32rpx;
		.title {
			margin-bottom: 15rpx;
			color: #303133;
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
.checkbox-popup {
	padding: 30rpx;
	width: 600rpx;
	.popup-title {
		font-size: 32rpx;
		font-weight: 600;
		text-align: center;
		margin-bottom: 30rpx;
	}
	.checkbox-list {
		.checkbox-item {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 20rpx 16rpx;
			border-bottom: 1px solid #f2f2f2;
			&.active {
				color: #2979ff;
				background-color: #f0f6ff;
			}
		}
	}
	.popup-btns {
		display: flex;
		justify-content: space-between;
		margin-top: 30rpx;
	}
}
</style>
