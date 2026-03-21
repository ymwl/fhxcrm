<template>
	<view class="fa-details">
		<!-- 自定义字段 -->
		<block v-for="(item,index) in fields" :key="index">
			<!-- 字符 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'string'"
			>
				<u-input type="text" disabled :border="border" placeholder="暂无" v-model="form[item.name]"></u-input>
			</u-form-item>
			<!-- 文本 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'text'"
			>
				<u-input type="textarea" disabled :border="border" placeholder="暂无" v-model="form[item.name]"></u-input>
			</u-form-item>
			<!-- 编辑器 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'editor'"
			>
				<u-input type="textarea" disabled :border="border" placeholder="暂无" v-model="form[item.name]"></u-input>
			</u-form-item>
			<!-- 地区 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'city'"
			>
				<u-input type="text" disabled :border="border" placeholder="暂无" v-model="form[item.name]"></u-input>
			</u-form-item>
			<!-- 日期 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'date'"
			>
				<u-input type="text" disabled :border="border" placeholder="暂无" v-model="form[item.name]"></u-input>
			</u-form-item>
			<!-- 日期区间 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'datetimerange'"
			>
				<u-input type="text" disabled :border="border" placeholder="暂无" v-model="form[item.name]"></u-input>
			</u-form-item>
			<!-- 日期时间 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'datetime'"
			>
				<u-input type="text" disabled :border="border" placeholder="暂无" v-model="form[item.name]"></u-input>
			</u-form-item>
			<!-- 数字 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'number'"
			>
				<u-input type="text" disabled :border="border" placeholder="暂无" v-model="form[item.name]"></u-input>
			</u-form-item>
			<!-- 列表，列表多选 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'select' || item.type == 'selects'"
			>
				<u-input type="text" disabled :border="border" placeholder="暂无" v-model="item.values"></u-input>
			</u-form-item>
			<!-- 关联单选 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.name"
				:label="item.title + ':'"
				v-if="item.type == 'selectpage'"
			>
				<fa-selectpages
					:fa-id="item.id"
					:title="item.title"
					:disabled="true"
					:checkeType="item.type"
					:showField="item.setting.field"
					:keyField="item.setting.primarykey"
					:showValue="(form[item.name] ? form[item.name] : item.value) || item.defaultvalue"
					v-model="form[item.name]"
				></fa-selectpages>
			</u-form-item>
			<!-- 关联多选 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:prop="item.name"
				
				:label="item.title + ':'"
				v-if="item.type == 'selectpages'"
			>
				<fa-selectpages
					:fa-id="item.id"
					:title="item.title"
					:checkeType="item.type"
					:disabled="true"
					:showField="item.setting.field"
					:keyField="item.setting.primarykey"
					:showValue="(form[item.name] ? form[item.name] : item.value) || item.defaultvalue"
					v-model="form[item.name]"
				></fa-selectpages>
			</u-form-item>
			<!-- 单选框 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'radio'"
			>
				<u-input type="text" disabled :border="border" placeholder="暂无" v-model="item.values"></u-input>
			</u-form-item>
			<!-- 多选框 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'checkbox'"
			>
				<u-input type="text" disabled :border="border" placeholder="暂无" v-model="item.values"></u-input>
			</u-form-item>
			<!-- 图片-->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'image' || item.type == 'images'"
			>
				<view class="u-flex u-flex-wrap" >
					<view  class="slot-btn" v-for="(m,s) in item.fileArr" :key="s" >
						<u-image width="200" height="200" :src="m" @click="previewImage(m)"></u-image>
					</view>
					<view v-if="form[item.name] == '' " style="color:#c0c4cc">暂无</view>
				</view>
			</u-form-item>
			<!-- 文件 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'file' || item.type == 'files' "
			>
				<view class="">
					<fa-download :item="item"></fa-download>
					<view v-if="form[item.name] == '' " style="color:#c0c4cc">暂无</view>
				</view>
			</u-form-item>
			<!-- 开关 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'switch'"
			>
				<u-switch :disabled="true" v-model="item.values"></u-switch>
			</u-form-item>
			<!-- 数组 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'array'"
			>
				<view class="fa-array">
					<view class="u-flex">
						<view class="u-flex-5">
							<view class="">{{ item.setting.key }}</view>
						</view>
						<view class="u-flex-6 u-p-l-10">
							<view class="">{{ item.setting.value }}</view>
						</view>
					</view>
					<view class="u-flex u-m-t-15" v-for="(s, x) in item.arrList" :key="x">
						<view class="u-flex-5"><u-input disabled v-model="s.key" :trim="true" :border="true" /></view>
						<view class="u-m-l-15 u-m-r-15 u-flex-5"><u-input disabled v-model="s.value" :trim="true" :border="true" /></view>
					</view>
				</view>
			</u-form-item>
			<!-- 自定义 -->
			<u-form-item
				:label-position="labelPosition"
				label-width="160"
				:label="item.title + ':'"
				v-if="item.type == 'custom'"
			>
				<u-input type="text" disabled :border="border" placeholder="暂无" v-model="form[item.name]"></u-input>
			</u-form-item>
		</block>
	</view>
</template>

<script>
export default {
	name: 'fa-details',
	props: {
		fields:{
			type:[String, Object,Array],
			default:''
		},
		form: {
			type:[String, Object,Array],
			default:''
		}
	},
	data() {
		return {
			labelPosition: 'left',
			border: false,
		}
	},
	computed:{
		getFileType(){
			return item=>{
				var index = item.lastIndexOf("."); // 找到最后一个 . 的位置
    		var fileType = item.substr(index + 1); // substr() 截取剩余的字符，即文件名doc
    		return '.' + fileType;
			}
		}
	},
	methods: {
		// 查看图片
		previewImage(url) {
			let arr = [url]
			uni.previewImage({
				urls: arr,
				longPressActions: {
					itemList: ['发送给朋友', '保存图片', '收藏'],
					success: function(data) {
						console.log(data);
					},
					fail: function(err) {
						console.log(err.errMsg);
					}
				}
			});
		},
	}
};
</script>

<style lang="scss" scoped>
.fa-details{
	width: 100%;
}
.close {
	background: #2c3e50;
	border-radius: 10rpx;
}
.fa-file-text {
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 40rpx;
	width: 200rpx;
	height: 200rpx;
	background-color: rgb(243, 244, 246);
	text-align: center;
}
</style>
