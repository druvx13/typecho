<?php

namespace Widget\Options;

use Typecho\Db\Exception;
use Typecho\I18n\GetText;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Basic settings widget
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class General extends Options implements ActionInterface
{
    use EditTrait;

    /**
     * 检查是否在语言列表中
     *
     * @param string $lang
     * @return bool
     */
    public function checkLang(string $lang): bool
    {
        $langs = self::getLangs();
        return isset($langs[$lang]);
    }

    /**
     * 获取语言列表
     *
     * @return array
     */
    public static function getLangs(): array
    {
        $dir = defined('__TYPECHO_LANG_DIR__') ? __TYPECHO_LANG_DIR__ : __TYPECHO_ROOT_DIR__ . '/usr/langs';
        $files = glob($dir . '/*.mo');
        $langs = ['zh_CN' => 'Simplified Chinese (简体中文)'];

        if (!empty($files)) {
            foreach ($files as $file) {
                $getText = new GetText($file, false);
                [$name] = explode('.', basename($file));
                $title = $getText->translate('lang', $count);
                $langs[$name] = $count > - 1 ? $title : $name;
            }

            ksort($langs);
        }

        return $langs;
    }

    /**
     * 过滤掉可执行的后缀名
     *
     * @param string $ext
     * @return boolean
     */
    public function removeShell(string $ext): bool
    {
        return !preg_match("/^(php|php4|php5|sh|asp|jsp|rb|py|pl|dll|exe|bat)$/i", $ext);
    }

    /**
     * Execute update action
     *
     * @throws Exception
     */
    public function updateGeneralSettings()
    {
        /** Validate form */
        if ($this->form()->validate()) {
            $this->response->goBack();
        }

        $settings = $this->request->from(
            'title',
            'description',
            'keywords',
            'allowRegister',
            'allowXmlRpc',
            'lang',
            'timezone'
        );
        $settings['attachmentTypes'] = $this->request->getArray('attachmentTypes');

        if (!defined('__TYPECHO_SITE_URL__')) {
            $settings['siteUrl'] = rtrim($this->request->get('siteUrl'), '/');
        }

        $attachmentTypes = [];
        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@image@')) {
            $attachmentTypes[] = '@image@';
        }

        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@media@')) {
            $attachmentTypes[] = '@media@';
        }

        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@doc@')) {
            $attachmentTypes[] = '@doc@';
        }

        $attachmentTypesOther = $this->request->filter('trim', 'strtolower')->get('attachmentTypesOther');
        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@other@') && !empty($attachmentTypesOther)) {
            $types = implode(
                ',',
                array_filter(array_map('trim', explode(',', $attachmentTypesOther)), [$this, 'removeShell'])
            );

            if (!empty($types)) {
                $attachmentTypes[] = $types;
            }
        }

        $settings['attachmentTypes'] = implode(',', $attachmentTypes);
        foreach ($settings as $name => $value) {
            $this->update(['value' => $value], $this->db->sql()->where('name = ?', $name));
        }

        Notice::alloc()->set(_t("Your settings have been saved."), 'success');
        $this->response->goBack();
    }

    /**
     * Output form structure
     *
     * @return Form
     */
    public function form(): Form
    {
        /** Build form */
        $form = new Form($this->security->getIndex('/action/options-general'), Form::POST_METHOD);

        /** 站点名称 */
        $title = new Form\Element\Text('title', null, $this->options->title, _t('Site name'), _t('Site name will be displayed in the title of the web page.'));
        $title->input->setAttribute('class', 'w-100');
        $form->addInput($title->addRule('required', _t('Please fill in the name of the site'))
            ->addRule('xssCheck', _t('Please do not use special characters in the name of the site')));

        /** 站点地址 */
        if (!defined('__TYPECHO_SITE_URL__')) {
            $siteUrl = new Form\Element\Url(
                'siteUrl',
                null,
                $this->options->originalSiteUrl,
                _t('Site URL Address'),
                _t('Site address is used to generate permanent links to content.') . ($this->options->originalSiteUrl == $this->options->rootUrl ?
                    '' : '</p><p class="message notice mono">'
                    . _t('The URL <strong>%s</strong> is not the same with the one you typed above', $this->options->rootUrl))
            );
            $siteUrl->input->setAttribute('class', 'w-100 mono');
            $form->addInput($siteUrl->addRule('required', _t('Please fill in the site address'))
                ->addRule('url', _t('Please enter a valid URL address')));
        }

        /** 站点描述 */
        $description = new Form\Element\Text(
            'description',
            null,
            $this->options->description,
            _t('Site description'),
            _t('Site description will be displayed in the header of the web page.')
        );
        $form->addInput($description->addRule('xssCheck', _t('Please do not use special characters in the site description')));

        /** 关键词 */
        $keywords = new Form\Element\Text(
            'keywords',
            null,
            $this->options->keywords,
            _t('Keywords'),
            _t('Please split multiple keywords with comma ",".')
        );
        $form->addInput($keywords->addRule('xssCheck', _t('Please do not use special characters in your tags')));

        /** 注册 */
        $allowRegister = new Form\Element\Radio(
            'allowRegister',
            ['0' => _t('Forbid'), '1' => _t('Allow')],
            $this->options->allowRegister,
            _t('Whether Allow registration'),
            _t('Allow users to register at your site. By default registered users cannot post.')
        );
        $form->addInput($allowRegister);

        /** XMLRPC */
        $allowXmlRpc = new Form\Element\Radio(
            'allowXmlRpc',
            ['0' => _t('Close.'), '1' => _t('Turn off the Pingback interface only'), '2' => _t('Open')],
            $this->options->allowXmlRpc,
            _t('XMLRPC interface')
        );
        $form->addInput($allowXmlRpc);

        /** 语言项 */
        // hack 语言扫描
        _t('lang');

        $langs = self::getLangs();

        if (count($langs) > 1) {
            $lang = new Form\Element\Select('lang', $langs, $this->options->lang, _t('Language'));
            $form->addInput($lang->addRule([$this, 'checkLang'], _t('The selected language pack does not exist')));
        }

        /** 时区 */
        $timezoneList = [
            "0"      => _t('Greenwich Mean Time （GMT）'),
            "3600"   => _t('Central European Time, West African Time  (GMT +1)'),
            "7200"   => _t('Eastern European Time, Central African Time (GMT +2)'),
            "10800"  => _t('Moscow Standard Time, Eastern African Time (GMT +3)'),
            "14400"  => _t('Gulf Standard Time, Samara Standard Time (GMT +4)'),
            "18000"  => _t('Pakistan Standard Time, Yekaterinburg Standard Time (GMT +5)'),
            "21600"  => _t(' Bangladesh Time, Bhutan Time, Novosibirsk Standard Time (GMT +6)'),
            "25200"  => _t('Indochina Time, Krasnoyarsk Standard Time (GMT +7)'),
            "28800"  => _t('Chinese Standard Time, Australian Western Standard Time, Irkutsk Standard Time (GMT +8)'),
            "32400"  => _t('Japan Standard Time, Korea Standard Time, Chita Standard Time  (GMT +9)'),
            "36000"  => _t('Australian Eastern Standard Time, Vladivostok Standard Time (GMT +10)'),
            "39600"  => _t('Solomon Island Time, Magadan Standard Time (GMT +11)'),
            "43200"  => _t('New Zealand Time, Fiji Time, Kamchatka Standard Time (GMT +12)'),
            "-3600"  => _t('Azores Standard Time, Cape Verde Time, Eastern Greenland Time (GMT -1)'),
            "-7200"  => _t('Fernando de Noronha Time, South Georgia &amp; the South Sandwich Islands Time (GMT -2)'),
            "-10800" => _t('Amazon Standard Time, Central Greenland Time  (GMT -3)'),
            "-14400" => _t('Atlantic Standard Time (GMT -4)'),
            "-18000" => _t('Eastern Standard Time (GMT -5)'),
            "-21600" => _t('Central Standard Time (GMT -6)'),
            "-25200" => _t('Mountain Standard Time (GMT -7)'),
            "-28800" => _t('Pacific Standard Time (GMT -8)'),
            "-32400" => _t('Alaska Standard Time, Gambier Island Time (GMT -9)'),
            "-36000" => _t('Hawaii-Aleutian Standard Time, Cook Island Time  (GMT -10)'),
            "-39600" => _t('Niue Time, Samoa Standard Time (GMT -11)'),
            "-43200" => _t('Baker Island Time  (GMT -12)')
        ];

        $timezone = new Form\Element\Select('timezone', $timezoneList, $this->options->timezone, _t('Timezone'));
        $form->addInput($timezone);

        /** 扩展名 */
        $attachmentTypesOptionsResult = (null != trim($this->options->attachmentTypes)) ?
            array_map('trim', explode(',', $this->options->attachmentTypes)) : [];
        $attachmentTypesOptionsValue = [];

        if (in_array('@image@', $attachmentTypesOptionsResult)) {
            $attachmentTypesOptionsValue[] = '@image@';
        }

        if (in_array('@media@', $attachmentTypesOptionsResult)) {
            $attachmentTypesOptionsValue[] = '@media@';
        }

        if (in_array('@doc@', $attachmentTypesOptionsResult)) {
            $attachmentTypesOptionsValue[] = '@doc@';
        }

        $attachmentTypesOther = array_diff($attachmentTypesOptionsResult, $attachmentTypesOptionsValue);
        $attachmentTypesOtherValue = '';
        if (!empty($attachmentTypesOther)) {
            $attachmentTypesOptionsValue[] = '@other@';
            $attachmentTypesOtherValue = implode(',', $attachmentTypesOther);
        }

        $attachmentTypesOptions = [
            '@image@' => _t('Image files') . ' <code>(gif jpg jpeg png tiff bmp webp avif)</code>',
            '@media@' => _t('Media files') . ' <code>(mp3 mp4 mov wmv wma rmvb rm avi flv ogg oga ogv)</code>',
            '@doc@'   => _t('Common archival files') . ' <code>(txt doc docx xls xlsx ppt pptx zip rar pdf)</code>',
            '@other@' => _t(
                'Other format %s',
                ' <input type="text" class="w-50 text-s mono" name="attachmentTypesOther" value="'
                . htmlspecialchars($attachmentTypesOtherValue) . '" />'
            ),
        ];

        $attachmentTypes = new Form\Element\Checkbox(
            'attachmentTypes',
            $attachmentTypesOptions,
            $attachmentTypesOptionsValue,
            _t('File types allowed to upload.'),
            _t('Split file extensions with comma ",", e.g. %s', '<code>cpp, h, mak</code>')
        );
        $form->addInput($attachmentTypes->multiMode());

        /** Submit button */
        $submit = new Form\Element\Submit('submit', null, _t('Save settings.'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * Bind action
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->isPost())->updateGeneralSettings();
        $this->response->redirect($this->options->adminUrl);
    }
}
