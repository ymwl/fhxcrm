<template>
	<view>
		<!-- 自定义字段 -->
		<block v-for="(item,index) in fields" :key="index">
			<!-- 字符 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'input' || item.formtype == 'tel'"
			>
			<u-input type="text" :border="border" :placeholder="'请填写' + item.title" v-model="forms[item.field]"></u-input>

			</u-form-item>



			<!-- 文本 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'textarea'"
			>
				<u-input type="textarea" :border="border" :placeholder="'请填写' + item.title" @input="textareaInput(item.field)" :value="forms[item.field]" @blur="textareaBlur"></u-input>
			</u-form-item>
			<!-- 数字 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'number'"
			>
				<u-input type="digit" :border="border" :placeholder="'请填写' + item.title" v-model="forms[item.field]"></u-input>
			</u-form-item>
			<!-- 多选框 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'checkbox'"
			>
				<fa-check-radio :faList="item.content_list" v-model="forms[item.field]" :checkValue="item.value || item.default"></fa-check-radio>
			</u-form-item>
			<!-- 单选框 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'radio'"
			>
				<fa-check-radio
					:faList="item.content_list"
					type="radio"
					v-model="forms[item.field]"
					:checkValue="item.value || item.default"
				></fa-check-radio>
			</u-form-item>
			<!-- 编辑器 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'editor'"
			>
				<u-input type="textarea" :border="border" @input="textareaInput(item.field)" :value="forms[item.field]" @blur="textareaBlur" />
			</u-form-item>
			<!-- 日期 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'date'"
			>
				<u-input
					:border="border"
					type="select"
					:select-open="showPicker && mode == 'date'"
					v-model="forms[item.field]"
					:placeholder="'请选择' + item.title"
					@click="selectPicker('date', item.field)"
				></u-input>
			</u-form-item>
			<!-- 时间 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'time'"
			>
				<u-input
					:border="border"
					type="select"
					:select-open="showPicker && mode == 'time'"
					v-model="forms[item.field]"
					:placeholder="'请选择' + item.title"
					@click="selectPicker('time', item.field)"
				></u-input>
			</u-form-item>
			<!-- 日期时间 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'datetime'"
			>
				<u-input
					:border="border"
					type="select"
					:select-open="showPicker && mode == 'datetime'"
					v-model="forms[item.field]"
					:placeholder="'请选择' + item.title"
					@click="selectPicker('datetime', item.field)"
				></u-input>
			</u-form-item>
			<!-- 日期区间 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'datetimerange'"
			>
				<u-input
					:border="border"
					type="select"
					:select-open="calendarShow"
					v-model="forms[item.field]"
					:placeholder="'请选择' + item.title"
					@click="
						calendarShow = true;
						time_field = item.field;
					"
				></u-input>
			</u-form-item>
			<!-- 关联城市 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':'"
				v-if="item.formtype == 'city' || item.formtype == 'district'"
			>
				<u-input
					:border="border"
					type="select"
					:select-open="cityShow"
					v-model="forms[item.field]"
					:placeholder="'请选择' + item.title"
					@click="
						cityShow = true;
						city_field = item.field;
					"
				></u-input>
			</u-form-item>
			<!-- 列表单选 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':'"
				v-if="item.formtype == 'select'"
			>
				<fa-selects
					:fa-list="item.content_list"
					:title="item.title"
					:checkeType="item.formtype"
					:showValue="item.value || item.default"
					v-model="forms[item.field]"
				></fa-selects>
			</u-form-item>
			<!-- 列表多选 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':'"
				v-if="item.formtype == 'selects'"
			>
				<fa-selects
					:fa-list="item.content_list"
					:title="item.title"
					:checkeType="item.formtype"
					:showValue="item.value || item.default"
					v-model="forms[item.field]"
				></fa-selects>
			</u-form-item>
			<!-- 关联单选 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':'"
				v-if="item.formtype == 'lselect'"
			>
				<fa-selectpages
					:fa-id="item.id"
					:title="item.title"
					:checkeType="item.formtype"
					:showField="item.foreign_key"
					:keyField="item.relationship_primary_key"
					:showValue="(forms[item.field] ? forms[item.field] : item.value) || item.default"
					v-model="forms[item.field]"
				></fa-selectpages>
			</u-form-item>
			<!-- 关联多选 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':'"
				v-if="item.formtype == 'selectpages'"
			>
				<fa-selectpages
					:fa-id="item.id"
					:title="item.title"
					:checkeType="item.formtype"
					:showField="item.foreign_key"
					:keyField="item.relationship_primary_key"
					:showValue="(forms[item.field] ? forms[item.field] : item.value) || item.default"
					v-model="forms[item.field]"
				></fa-selectpages>
			</u-form-item>
			<!-- 单图 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':'"
				v-if="item.formtype == 'image'"
			>
				<fa-upload-image v-model="forms[item.field]" :file-list="item.value"></fa-upload-image>
			</u-form-item>
			<!-- 多图 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':'"
				v-if="item.formtype == 'images'"
			>
				<fa-upload-image v-model="forms[item.field]" imgType="many" :file-list="item.value"></fa-upload-image>
			</u-form-item>
			<!-- 单文件 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':'"
				v-if="item.formtype == 'file'"
			>
				<fa-upload-file v-model="forms[item.field]" :isDom="true" :showValue="item.value"></fa-upload-file>
			</u-form-item>
			<!-- 多文件 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':'"
				v-if="item.formtype == 'files'"
			>
				<fa-upload-file v-model="forms[item.field]" fileType="many" :isDom="true" :showValue="item.value"></fa-upload-file>
			</u-form-item>
			<!-- 开关 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':'"
				v-if="item.formtype == 'switch'"
			>
				<fa-switch v-model="forms[item.field]" :defvalue="item.value || 0"></fa-switch>
			</u-form-item>
			<!-- 数组 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.field"
				:required="item.rule.indexOf('require') != -1"
				:label="item.title + ':' "
				v-if="item.formtype == 'array'"
			>
				<fa-array
					:faKey="item.setting.key"
					:faVal="item.setting.value"
					v-model="forms[item.field]"
					:showValue="item.value || item.content_list"
				></fa-array>
			</u-form-item>
		</block>
		<!-- 时间选择 -->
		<u-picker v-model="showPicker" mode="time" :params="params" @confirm="pickerResult"></u-picker>
		<!-- 日期区间 -->
		<u-calendar v-model="calendarShow" mode="range" @change="calendarResult" max-date="3000-01-01"></u-calendar>
		<!-- 城市 -->
		<fa-citys v-model="cityShow" @city-change="cityResult"></fa-citys>
	</view>
</template>

<script>
import Emitter from '@/uview-ui/libs/util/emitter.js';
export default {
	name: 'fa-fields',
	mixins: [Emitter],
	props: {
		fields:{
			type:[String, Object,Array],
			default:{}
		},
		form: {
			type:[Object,Array],
			default:{}
		},
		rules: {
			type:[String, Object,Array],
			default:''
		},
		labelPosition: {
			type:[String],
			default:''
		},
		border: {
			type:[Boolean],
			default:'false'
		}
	},
	data() {
		return {
			calendarShow: false,
			cityShow: false,
			showPicker: false,
			params: {},
			city_field: '',
			time_field: '',
			forms: {
      },
			textareaName: '',
		};
	},
	watch: {
		forms:{
		  handler(val){
		    console.log('forms',val)
        this.$emit("input",this.forms)
      },
      deep:true
    }
	},
	created() {
		// 子组件不能修改props传过来的值，需要替换成data数据
	this.forms = this.form;
    /*	  console.log('fa-fields----form=',this.form);*/
		// 子组件通过v-modal改变父组件中的值
    this.$emit("input",this.forms)
	},
	methods: {
		// 获取 textarea 当前字段名称
		textareaInput(val){
			this.textareaName = val
		},
		// 优化微信小程序input、textarea快速删除时光标会跳到最后 处理：改用 textarea 失去焦点触发修改
		textareaBlur(val) {
			this.forms[this.textareaName] = val
		},
		//时间显示
		selectPicker(mode, field) {
			this.mode = mode;
			this.time_field = field;
			switch (mode) {
				case 'date':
					this.params = {
						year: true,
						month: true,
						day: true,
						hour: false,
						minute: false,
						second: false
					};
					break;
				case 'time':
					this.params = {
						year: false,
						month: false,
						day: false,
						hour: true,
						minute: true,
						second: true
					};
					break;
				case 'datetime':
					this.params = {
						year: true,
						month: true,
						day: true,
						hour: true,
						minute: true,
						second: true
					};
					break;
			}
			this.showPicker = true;
		},
		//时间的选择结果
		pickerResult(e) {
			switch (this.mode) {
				case 'date':
					this.$set(this.forms, this.time_field, e.year + '-' + e.month + '-' + e.day);
					break;
				case 'time':
					this.$set(this.forms, this.time_field, e.hour + ':' + e.minute + ':' + e.second);
					break;
				case 'datetime':
					this.$set(this.forms, this.time_field, e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute + ':' + e.second);
					break;
			}
		},
		//时间范围选择的结果
		calendarResult(e) {
			this.$set(this.forms, this.time_field, e.startDate + ' 00:00:00 - ' + e.endDate + ' 23:59:59');
		},
		//城市选择
		cityResult(e) {
			this.$set(this.forms, this.city_field, e[0].label + '/' + e[1].label+ '/' + e[2].label)
		},
	}
};
</script>

<style lang="scss"></style>
