import {imgBaseUrl,} from './config'

//获取图片真实地址
export const getImgUrl = url => {
  if(url.substr(0,7).toLowerCase() == "http://"||url.substr(0,8).toLowerCase() == "https://"){
      return url;
  }else{
      return imgBaseUrl + url;
  }
}

// 拼接flieURL
export function processingImages(str_img) {
  if (str_img.indexOf(",") >= 0) {
    let arr_img = str_img.split(",")
    arr_img.forEach((item, index) => {
      if (item.indexOf("http") < 0) {
        arr_img[index] = imgBaseUrl + item
      }
    })
    return arr_img
  } else {
    let arr_img = []
    if (str_img.indexOf("http") > -1) {
      arr_img.push(str_img)
      return arr_img
    } else {
      arr_img.push(imgBaseUrl + '/' + str_img)
      return arr_img
    }
  }
  // return imgBaseUrl + str_img
}

//判断对象，参数是否定义
export function isDefine(para) {
	if (typeof para == 'undefined' || para == "" || para == null || para == undefined || para == 'undefined' || para == 0)
			return false;
	else
			return true;
}

export function filterGrade(grade){
	if (typeof(grade) == 'string') {
			grade = parseFloat(grade);
	}
	return (Math.round(grade * 100) / 100);
}

/**
 * 验证手机格式
 */
export function checkPhone(value) {
	return /^1[23456789]\d{9}$/.test(value)
}

// 截取url参数  name:参数名 url:地址
export function getQueryVariable(name, url) {
  var index= url.lastIndexOf("\?");
  url = url.substring(index+1,url.length);
  var vars = url.split("&");
  for (var i=0;i<vars.length;i++) {
    var pair = vars[i].split("=");
    if(pair[0] == name){return pair[1]}
  }
  return(false);
}

// 隐藏部分姓名或者电话号码  frontLen: 前面需要保留几位    endLen: 后面需要保留几位
export function hidden(str,frontLen,endLen) { 
  var len = str.length-frontLen-endLen;
  var xing = '';
  for (var i=0;i<len;i++) {
    xing+='*';
  }
  return str.substring(0,frontLen)+xing+str.substring(str.length-endLen);
}


/**
 * 微信小程序 scene解码
 */
 export function scene_decode(e) {
  if (e === undefined)
    return {};
  let scene = decodeURIComponent(e),
    params = scene.split(','),
    data = {};
  for (let i in params) {
    var val = params[i].split(':');
    val.length > 0 && val[0] && (data[val[0]] = val[1] || null)
  }
  return data;
}


export function addZero(num){//补0
  if(parseInt(num) < 10){
    num = '0'+num;
  }
  return num;
}

/**
 * 获取指定时间的日期
 * @params 正是今天之后的日期、负是今天前的日期
 * @return 2020-08-22
 * */
 export function get_date(num) {
  var date1 = new Date();
  //今天时间
  var time1 = date1.getFullYear() + "-" + (date1.getMonth() + 1) + "-" + date1.getDate();
  var date2 = new Date(date1);
  date2.setDate(date1.getDate() + num);
  //num是正数表示之后的时间，num负数表示之前的时间，0表示今天
  var time2 = addZero(date2.getFullYear()) + "-" + addZero((date2.getMonth() + 1)) + "-" + addZero(date2.getDate());
  return time2;
}

/**
 * 根据指定时间的日期-获取
 * @params 2020-8-22
 * @return 周六
 * */
 export function get_week(datestr){
  var weekArray = new Array("周日", "周一", "周二", "周三", "周四", "周五", "周六");
  var week = weekArray[new Date(datestr).getDay()];
  console.log(week);
  return week;
}


//得到本月、上月、下月的起始、结束日期  type为字符串类型，有两种选择，"s"代表开始,"e"代表结束，months为数字类型，不传或0代表本月，-1代表上月，1代表下月
export function getMonth(type, months) {
  var d = new Date();
  var year = d.getFullYear();
  var month = d.getMonth() + 1;
  if (Math.abs(months) > 12) {
      months = months % 12;
  };
  if (months != 0) {
      if (month + months > 12) {
          year++;
          month = (month + months) % 12;
      } else if (month + months < 1) {
          year--;
          month = 12 + month + months;
      } else {
          month = month + months;
      };
  };
  month = month < 10 ? "0" + month: month;
  var date = d.getDate();
  var firstday = year + "-" + month + "-" + "01";
  var lastday = "";
  if (month == "01" || month == "03" || month == "05" || month == "07" || month == "08" || month == "10" || month == "12") {
      lastday = year + "-" + month + "-" + 31;
  } else if (month == "02") {
      if ((year % 4 == 0 && year % 100 != 0) || (year % 100 == 0 && year % 400 == 0)) {
          lastday = year + "-" + month + "-" + 29;
      } else {
          lastday = year + "-" + month + "-" + 28;
      };
  } else {
      lastday = year + "-" + month + "-" + 30;
  };
  var day = "";
  if (type == "s") {
      day = firstday;
  } else {
      day = lastday;
  };
  return day;
};

// 得到今年、去年、明年的开始、结束日期  type为字符串类型，有两种选择，"s"代表开始,"e"代表结束，dates为数字类型，不传或0代表今年，-1代表去年，1代表明年
export function getYear(type, dates) {
  var dd = new Date();
  var n = dates || 0;
  var year = dd.getFullYear() + Number(n);
  if (type == "s") {
      var day = year + "-01-01";
  };
  if (type == "e") {
      var day = year + "-12-31";
  };
  if (!type) {
      var day = year + "-01-01/" + year + "-12-31";
  };
  return day;
};


/**
 *
 *  判断是否在微信浏览器 true是
 */
 export function isWeiXinBrowser() {
	// #ifdef H5	
	let ua = window.navigator.userAgent.toLowerCase()	
	if (ua.match(/MicroMessenger/i) == 'micromessenger') {
		return true
	} else {
		return false
	}
	// #endif
	return false
}


/**
 * 获取url参数
 * 
 */
 export function getQueryString(name, url) {
  var url = url || window.location.href
  var reg = new RegExp('(^|&|/?)' + name + '=([^&|/?]*)(&|/?|$)', 'i')
  var r = url.substr(1).match(reg)
  if (r != null) {
    return r[2]
  }
  return null
}

/**
 * 处理字段真实值（对应 PHP real_field_val）
 * @param {Object} field - 字段配置对象 {formtype, option, field, title}
 * @param {Any} value - 字段值
 * @param {String} imgBaseUrl - 图片基础URL
 * @returns {Object} {type, text, urls, files}
 *   - type: 'text'|'image'|'images'|'file'|'files'|'datetime'|'date'
 *   - text: 文本显示值
 *   - urls: 图片URL数组（用于预览）
 *   - files: 文件数组 [{name, url}]
 */
export function realFieldVal(field, value, imgBaseUrl) {
  if (value === '' || value === null || value === undefined) {
    return { type: 'text', text: '' }
  }

  const formtype = field.formtype || 'text'
  let result = { type: 'text', text: value, urls: [], files: [] }

  switch (formtype) {
    case 'datetime':
      // 时间戳转 Y-m-d H:i:s
      result.type = 'datetime'
      // 判断是否为数字（时间戳）
      if (value && !isNaN(value) && !isNaN(parseFloat(value)) && isFinite(value) && String(value).length <= 10) {
        const date = new Date(parseInt(value) * 1000)
        const Y = date.getFullYear()
        const m = String(date.getMonth() + 1).padStart(2, '0')
        const d = String(date.getDate()).padStart(2, '0')
        const H = String(date.getHours()).padStart(2, '0')
        const i = String(date.getMinutes()).padStart(2, '0')
        const s = String(date.getSeconds()).padStart(2, '0')
        result.text = `${Y}-${m}-${d} ${H}:${i}:${s}`
      } else if (value && typeof value === 'string') {
        // 已经是可读时间字符串，直接返回
        result.text = value
      } else {
        result.text = ''
      }
      break

    case 'date':
      // 时间戳转 Y-m-d
      result.type = 'date'
      // 判断是否为数字（时间戳）
      if (value && !isNaN(value) && !isNaN(parseFloat(value)) && isFinite(value) && String(value).length <= 10) {
        const date = new Date(parseInt(value) * 1000)
        const Y = date.getFullYear()
        const m = String(date.getMonth() + 1).padStart(2, '0')
        const d = String(date.getDate()).padStart(2, '0')
        result.text = `${Y}-${m}-${d}`
      } else if (value && typeof value === 'string') {
        // 已经是可读日期字符串，直接返回
        result.text = value
      } else {
        result.text = ''
      }
      break

    case 'image':
      // 单图
      result.type = 'image'
      if (value) {
        const url = getFullUrl(value, imgBaseUrl)
        result.urls = [url]
        result.text = '[图片]'
      }
      break

    case 'images':
      // 多图（逗号或竖线分隔）
      result.type = 'images'
      if (value) {
        const urls = String(value).split(/[,|]/).filter(v => v.trim())
        result.urls = urls.map(url => getFullUrl(url.trim(), imgBaseUrl))
        result.text = `[${result.urls.length}张图片]`
      }
      break

    case 'file':
      // 单文件
      result.type = 'file'
      if (value) {
        const url = getFullUrl(value, imgBaseUrl)
        const name = getFileName(value)
        result.files = [{ name, url }]
        result.text = name
      }
      break

    case 'files':
      // 多文件（逗号或竖线分隔）
      result.type = 'files'
      if (value) {
        const files = String(value).split(/[,|]/).filter(v => v.trim())
        result.files = files.map(f => {
          const url = getFullUrl(f.trim(), imgBaseUrl)
          return { name: getFileName(f), url }
        })
        result.text = `[${result.files.length}个文件]`
      }
      break

    case 'select':
    case 'radio':
      // 选项类型：根据 option 转换
      result.type = 'select'
      if (field.option && value !== '') {
        const options = parseOptions(field.option)
        const opt = options.find(o => o.value == value)
        result.text = opt ? opt.label : value
      }
      break

    default:
      result.type = 'text'
      result.text = value
  }

  return result
}

/**
 * 获取完整URL
 */
function getFullUrl(url, baseUrl) {
  if (!url) return ''
  if (url.substr(0, 7).toLowerCase() === 'http://' || url.substr(0, 8).toLowerCase() === 'https://') {
    return url
  }
  return baseUrl + (url.charAt(0) === '/' ? '' : '/') + url
}

/**
 * 从路径中提取文件名
 */
function getFileName(path) {
  if (!path) return '文件'
  const parts = path.split('/')
  return parts[parts.length - 1] || '文件'
}

/**
 * 解析选项字符串为数组
 * 格式：value1:label1,value2:label2 或 JSON
 */
function parseOptions(optionStr) {
  if (!optionStr) return []
  try {
    // 尝试JSON解析
    if (optionStr.trim().startsWith('[') || optionStr.trim().startsWith('{')) {
      const parsed = JSON.parse(optionStr)
      if (Array.isArray(parsed)) {
        return parsed.map(item => ({
          value: item.value !== undefined ? item.value : item,
          label: item.label !== undefined ? item.label : item
        }))
      }
    }
    // value:label 格式
    const options = []
    const items = String(optionStr).split(/[\r\n,]+/)
    items.forEach(item => {
      const [value, label] = item.split(':')
      if (value !== undefined) {
        options.push({
          value: value.trim(),
          label: (label !== undefined ? label : value).trim()
        })
      }
    })
    return options
  } catch (e) {
    return []
  }
}