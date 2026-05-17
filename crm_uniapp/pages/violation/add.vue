<template>
  <view>
    <view class="set-box">
      <u-form :model="form" ref="uForm" :error-type="errorType">

        <u-form-item label="违章证件:" label-width="160" prop="zhengjianbianhao">
          <u-input v-model="form.zhengjianbianhao" :border="true" :disabled="true" />
        </u-form-item>

        <u-form-item label="违章时间:"  label-width="160" :required="true">
          <u-input type="select" :select-open="timeShow" v-model="form.time" placeholder="选择违章时间" @click="timeShow = true" />
        </u-form-item>

        <u-form-item required label="违章事件:" label-width="160" prop="event">
          <u-input v-model="form.event" :border="true" />
        </u-form-item>

        <u-form-item label="违章图片:"  label-width="160">
          <u-upload :custom-btn="true" ref="uUpload" :header="param"  :show-upload-list="true" max-count="1" :action="action" @on-uploaded="finish" :auto-upload="true" :file-list="lists">
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
    </view>
    <u-picker v-model="timeShow" :hour="true" mode="time" :params="params" @confirm="timeChange"></u-picker>
  </view>
</template>

<script>
import {baseUrl,api_v1} from '@/common/config'
export default {
  data() {
    return {
      action: baseUrl + api_v1 + '/ajax/upload',
      param: {
        token: '',
      },
      lists: [],
      images: [],
      timeShow:false,
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
      id: '',
      minDate: '',
      maxDate: '',
      dateShow: false,
      show: false,
      content: '',
      params: {
        year: true,
        month: true,
        day: true,
        hour: true,
        minute: true,
        second: false
      },
      form: {
        zhengjianbianhao: '',
        time: '',
        event: '',
        image: ''
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
            message: '请输入记录名字',
            trigger: ['change','blur']
          },
        ],
        specification: [
          {
            required: true,
            message: '请输入记录规格',
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
    // 上传文件参数
    this.param.token = this.vuex_token
    this.type = e.type
    console.log('e',e)
    if(e.id) {
      this.id = e.id
      this.getDetails()
    }else {
      if(!e.zhengshu_id){
        this.$reuse.showError('访问非法');
        return;
      }
      this.getAdd(e.zhengshu_id)
    }
    if(this.type == "edit") {
      uni.setNavigationBarTitle({
        title: '编辑记录'
      });
    }
  },
  onShow() {

  },
  // 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
  onReady() {
    this.$refs.uForm.setRules(this.rules);
    // 得到整个组件对象，内部图片列表变量为"lists"
  },
  methods: {
    // 所有图片上传完成
    finish(lists){
      // 数据初始化，防止重复添加
      this.images = []
      // this.lists = lists;
      // console.log('完成触发的finish',lists)
      // console.log('完成触发的finishthis.$refs.uUpload.lists',this.$refs.uUpload.lists)
      // console.log('完成触发的finish-lists',this.lists)
      lists.forEach((item,index) => {
        if(item.response.code){
          this.images.push(item.response.data.url);
        }

      });
    },
    timeChange(e){
      this.form.time = e.year + '-' + e.month + '-' + e.day + ' ' + e.hour + ':' + e.minute
    },
    timeFormats(val) {
      if(val){
        return this.$u.timeFormat(val, 'yyyy-mm-dd hh:MM')
      } else {
        return ''
      }
    },
    // 获取记录详情
    getDetails() {
      this.$u.get('violation/edit', {
        id: this.id,
      }).then(res => {
        if(res.code == 1 ) {
          this.form = res.data
          this.lists=[{url:baseUrl+res.data.image}];
          console.log('this.lists=',this.lists)
          this.form.time=this.timeFormats(this.form.time);
        }
      })
    }, getAdd(zhengshu_id) {
      this.$u.get('violation/add', {
        zhengshu_id: zhengshu_id,
      }).then(res => {
        if(res.code == 1 ) {
          this.form = res.data
          // this.lists=[{url:baseUrl+res.data.image}];
          // console.log('this.lists=',this.lists)
          // this.form.time=this.timeFormats(this.form.time);
        }else{
          this.$reuse.showError(res.msg);
        }
      })
    },
    // 修确认提交
    submit() {

      this.form.image = this.images.join("|")
      // 深度克隆

      let param = this.$u.deepClone(this.form);

      this.$refs.uForm.validate(valid => {
        console.log('验证后');
        if (valid) {
          if(this.type == 'add') {
            this.$u.post('violation/add', param).then((res) => {
              if(res.code == 1) {
                // 提示
                uni.showToast({
                  title: "添加成功",
                  icon: 'success',
                  duration: 2000
                })
                setTimeout(() => {
                  this.$u.route('/pages/violation/index');
                }, 1000);
              }else{
                this.$u.toast(res.msg);
              }
            })
          } else {
            this.$u.post('violation/edit', param).then((res) => {
              if(res.code == 1) {
                // 提示
                uni.showToast({
                  title: "修改成功",
                  icon: 'success',
                  duration: 2000
                })
              /*  setTimeout(() => {
                  uni.navigateBack();
                }, 1000);*/
              }else{
                this.$u.toast(res.msg);
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
</style>
