<?php

namespace fulicommons\util\html;

/**
 * 表单元素生成
 * @class Form
 * @package util\html
 * @method string token() static 生成Token
 * @method string label(string $name, string $value = null, array $options = []) static label标签
 * @method string input($type, $name, string $value = null, array $options = []) static 按类型生成文本框
 * @method string text(string $name, string $value = null, array $options = []) static 普通文本框
 * @method string password(string $name, array $options = []) static 密码文本框
 * @method string hidden(string $name, string $value = null, array $options = []) static 隐藏文本框
 * @method string email(string $name, string $value = null, array $options = []) static Email文本框
 * @method string url(string $name, string $value = null, array $options = []) static URL文本框
 * @method string file(string $name, array $options = []) static 文件上传组件
 * @method string textarea(string $name, string $value = null, array $options = []) static 多行文本框
 * @method string editor(string $name, string $value = null, array $options = []) static 富文本编辑器
 * @method string select(string $name, array $list = [], string $selected = null, array $options = []) static 下拉列表组件
 * @method string selects(string $name, array $list = [], string $selected = null, array $options = []) static 下拉列表组件(多选)
 * @method string selectpicker(string $name, array $list = [], string $selected = null, array $options = []) static 下拉列表组件(友好)
 * @method string selectpickers(string $name, array $list = [], string $selected = null, array $options = []) static 下拉列表组件(友好)(多选)
 * @method string selectpage(string $name, string $value, string $url, string $field = null, string $primaryKey = null, array $options = []) static 动态下拉列表组件
 * @method string selectpages(string $name, string $value, string $url, string $field = null, string $primaryKey = null, array $options = []) static 动态下拉列表组件(多选)
 * @method string citypicker(string $name, string $value, array $options = []) static 城市选择组件
 * @method string switcher(string $name, string $value, array $options = []) static 切换组件
 * @method string datepicker(string $name, string $value, array $options = []) static 日期选择组件
 * @method string timepicker(string $name, string $value, array $options = []) static 时间选择组件
 * @method string datetimepicker(string $name, string $value, array $options = []) static 日期时间选择组件
 * @method string daterange(string $name, string $value, array $options = []) static 日期区间组件
 * @method string timerange(string $name, string $value, array $options = []) static 时间区间组件
 * @method string datetimerange(string $name, string $value, array $options = []) static 日期时间区间组件
 * @method string fieldlist(string $name, string $value, string $title = null, string $template = null, array $options = []) static 字段列表组件
 * @method string cxselect(string $url, array $names = [], array $values = [], array $options = []) static 联动组件
 * @method string selectRange(string $name, string $begin, string $end, string $selected = null, array $options = []) static 选择数字区间
 * @method string selectYear(string $name, string $begin, string $end, string $selected = null, array $options = []) static 选择年
 * @method string selectMonth(string $name, string $selected = null, array $options = [], string $format = '%m') static 选择月
 * @method string checkbox(string $name, string $value = '1', string $checked = null, array $options = []) static 单个复选框
 * @method string checkboxs(string $name, array $list = [], string $checked = null, array $options = []) static 一组复选框
 * @method string radio(string $name, string $value = null, string $checked = null, array $options = [])) static 单个单选框
 * @method string radios(string $name, array $list = [], string $checked = null, array $options = [])) static 一组单选框
 * @method string image(string $name, string $value, array $inputAttr = [], array $uploadAttr = [], array $chooseAttr = [], array $previewAttr = []) static 上传图片组件
 * @method string images(string $name, string $value, array $inputAttr = [], array $uploadAttr = [], array $chooseAttr = [], array $previewAttr = []) static 上传图片组件(多图)）
 * @method string upload(string $name, string $value, array $inputAttr = [], array $uploadAttr = [], array $chooseAttr = [], array $previewAttr = []) static 上传文件组件
 * @method string uploads(string $name, string $value, array $inputAttr = [], array $uploadAttr = [], array $chooseAttr = [], array $previewAttr = []) static 上传文件组件(多文件)）
 * @method string button(string $value, array $options = []) static 表单button
 */
class Form
{

    public function __construct()
    {

    }

    /**
     * @param $name
     * @param $arguments
     * @return FormBuilder
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([FormBuilder::instance(), $name], $arguments);
    }

}
