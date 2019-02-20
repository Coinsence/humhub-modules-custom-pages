<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\custom_pages\models\forms;


use Yii;
use yii\base\Model;

class SettingsForm extends Model
{
    const DEFAULT_VIEW_PATH_PAGES = '@custom_pages/views/custom/global_pages/';
    const DEFAULT_VIEW_PATH_SNIPPETS = '@custom_pages/views/custom/global_snippets/';
    const DEFAULT_VIEW_PATH_CONTAINER_PAGES = '@custom_pages/views/custom/container_pages/';
    const DEFAULT_VIEW_PATH_CONTAINER_SNIPPETS = '@custom_pages/views/custom/container_snippets/';
    const DEFAULT_ALLOWED_HTML = '*[style],*[class],div,p,br,b,strong,i,em,u,s,a[href|target],ul,li,ol,span,h1,h2,h3,h4,h5,h6,sub,sup,blockquote,pre,img[src|alt],hr,font[size|color]';
    const DEFAULT_ALLOWED_CSS_PROPERTIES = 'color,background-color,width,height,border-radius';

    /**
     * @var integer
     */
    public $phpPagesActive;

    /**
     * @var string
     */
    public $phpGlobalPagePath;

    /**
     * @var string
     */
    public $phpGlobalSnippetPath;

    /**
     * @var string
     */
    public $phpContainerSnippetPath;

    /**
     * @var string
     */
    public $phpContainerPagePath;

    /**
     * @var string
     */
    public $htmlContainerPageAllowedHTML;

    /**
     * @var string
     */
    public $htmlContainerPageAllowedCSSProperties;

    /**
     * @var \humhub\components\SettingsManager
     */
    public $settings;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->settings = Yii::$app->getModule('custom_pages')->settings;
        $this->phpPagesActive = intval($this->settings->get('phpPagesActive', 0));
        $this->phpGlobalPagePath = $this->settings->get('phpGlobalPagePath', static::DEFAULT_VIEW_PATH_PAGES);
        $this->phpGlobalSnippetPath = $this->settings->get('phpGlobalSnippetPath', static::DEFAULT_VIEW_PATH_SNIPPETS);
        $this->phpContainerPagePath = $this->settings->get('phpContainerPagePath', static::DEFAULT_VIEW_PATH_CONTAINER_PAGES);
        $this->phpContainerSnippetPath = $this->settings->get('phpContainerSnippetPath', static::DEFAULT_VIEW_PATH_CONTAINER_SNIPPETS);
        $this->htmlContainerPageAllowedHTML = $this->settings->get('htmlContainerPageAllowedHTML', static::DEFAULT_ALLOWED_HTML);
        $this->htmlContainerPageAllowedCSSProperties = $this->settings->get('htmlContainerPageAllowedCSSProperties', static::DEFAULT_ALLOWED_CSS_PROPERTIES);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['phpPagesActive', 'integer'],
            [['phpGlobalPagePath', 'phpGlobalPagePath', 'phpGlobalSnippetPath', 'phpContainerSnippetPath', 'phpContainerPagePath'], 'validateViewPath'],
            ['htmlContainerPageAllowedHTML', 'validateAllowedHTML'],
            ['htmlContainerPageAllowedCSSProperties', 'validateAllowedCSSProperties'],
        ];
    }

    /**
     * Validates the view path.
     *
     * @param $attribute
     * @param $params
     */
    public function validateViewPath($attribute, $params)
    {
        if(!is_dir(Yii::getAlias($this->$attribute))) {
            $this->addError($attribute, Yii::t('CustomPagesModule.models_SettignsForm', 'The given view file path does not exist.'));
        }
    }

    /**
     * Validates the allowed HTML.
     *
     * @param $attribute
     * @param $params
     */
    public function validateAllowedHTML($attribute, $params)
    {
        $allowedHTMLRegExPattern = '/^(?:(?:\*|\w+)(?:\[\w+(?:\|\w+)*\])?,?)+$/';

        $strippedAttribute = $this->stripAttribute($this->$attribute);
        $isMatched = preg_match($allowedHTMLRegExPattern, $strippedAttribute, $output_array);
        if(!$isMatched) {
            $this->addError($attribute, Yii::t('CustomPagesModule.models_SettignsForm', 'The given whitelist is incorrect. please consider checking it carefully.'));
        }
    }

    /**
     * Validates the allowed CSS properties.
     *
     * @param $attribute
     * @param $params
     */
    public function validateAllowedCSSProperties($attribute, $params)
    {
        $allowedCSSPropertiesRegExPattern = '/^(?:-?(?:\w+),?)+$/';

        $strippedAttribute = $this->stripAttribute($this->$attribute);
        $isMatched = preg_match($allowedCSSPropertiesRegExPattern, $strippedAttribute, $output_array);
        if(!$isMatched) {
            $this->addError($attribute, Yii::t('CustomPagesModule.models_SettignsForm', 'The given whitelist is incorrect. please consider checking it carefully.'));
        }
    }

    /**
     * Strip the attribute from whitespaces.
     *
     * @param $attribute
     * @return string
     */
    public function stripAttribute($attribute)
    {
        return preg_replace('/\s/', '', $attribute);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'phpPagesActive' => Yii::t('CustomPagesModule.models_SettignsForm','Activate PHP based Pages and Snippets'),
            'phpGlobalPagePath' => Yii::t('CustomPagesModule.models_SettignsForm','PHP view path for global custom pages'),
            'phpGlobalSnippetPath' => Yii::t('CustomPagesModule.models_SettignsForm','PHP view path for global custom snippets'),
            'phpContainerPagePath' => Yii::t('CustomPagesModule.models_SettignsForm','PHP view path for custom space pages'),
            'phpContainerSnippetPath' => Yii::t('CustomPagesModule.models_SettignsForm','PHP view path for custom space snippets'),
            'htmlContainerPageAllowedHTML' => Yii::t('CustomPagesModule.models_SettignsForm','HTMLPurifier allowed HTML'),
            'htmlContainerPageAllowedCSSProperties' => Yii::t('CustomPagesModule.models_SettignsForm','HTMLPurifier allowed CSS properties'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'phpPagesActive' => Yii::t('CustomPagesModule.models_SettignsForm','If disabled, existing php pages will still be online, but can\'t be created.'),
        ];
    }

    /**
     * Saves the settings in case the validation succeeds.
     */
    public function save()
    {
        if(!$this->validate()) {
            return false;
        }

        $this->settings->set('phpPagesActive', $this->phpPagesActive);

        if(empty($this->phpGlobalPagePath)) {
            $this->settings->delete('phpGlobalPagePath');
            $this->phpGlobalPagePath = static::DEFAULT_VIEW_PATH_PAGES;
        } else {
            $this->settings->set('phpGlobalPagePath', $this->phpGlobalPagePath);
        }

        if(empty($this->phpGlobalSnippetPath)) {
            $this->settings->delete('phpGlobalSnippetPath');
            $this->phpGlobalSnippetPath = static::DEFAULT_VIEW_PATH_SNIPPETS;
        } else {
            $this->settings->set('phpGlobalSnippetPath', $this->phpGlobalSnippetPath);
        }

        if(empty($this->phpContainerPagePath)) {
            $this->settings->delete('phpContainerPagePath');
            $this->phpContainerPagePath = static::DEFAULT_VIEW_PATH_SNIPPETS;
        } else {
            $this->settings->set('phpContainerPagePath', $this->phpContainerPagePath);
        }

        if(empty($this->phpContainerSnippetPath)) {
            $this->settings->delete('phpContainerSnippetPath');
            $this->phpContainerSnippetPath = static::DEFAULT_VIEW_PATH_CONTAINER_SNIPPETS;
        } else {
            $this->settings->set('phpContainerSnippetPath', $this->phpContainerSnippetPath);
        }

        if(empty($this->htmlContainerPageAllowedHTML)) {
            $this->settings->delete('htmlContainerPageAllowedHTML');
            $this->htmlContainerPageAllowedHTML = static::DEFAULT_ALLOWED_HTML;
        } else {
            $this->settings->set('htmlContainerPageAllowedHTML', $this->stripAttribute($this->htmlContainerPageAllowedHTML));
        }

        if(empty($this->htmlContainerPageAllowedCSSProperties)) {
            $this->settings->delete('htmlContainerPageAllowedCSSProperties');
            $this->htmlContainerPageAllowedCSSProperties = static::DEFAULT_ALLOWED_CSS_PROPERTIES;
        } else {
            $this->settings->set('htmlContainerPageAllowedCSSProperties', $this->stripAttribute($this->htmlContainerPageAllowedCSSProperties));
        }

        return true;
    }
}