<?php

namespace humhub\modules\custom_pages\models;

use humhub\modules\custom_pages\models\forms\SettingsForm;
use Yii;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\search\interfaces\Searchable;
use humhub\modules\custom_pages\components\Container;
use humhub\modules\custom_pages\modules\template\models\Template;
use humhub\modules\custom_pages\models\CustomContentContainer;
use yii\helpers\HtmlPurifier;

/**
 * This is the model class for table "custom_pages_container_page".
 *
 * A container page is space related custom page container.
 *
 * @property integer $id
 * @property integer $type
 * @property string $title
 * @property string $icon
 * @property string $page_content
 * @property integer $in_new_window
 * @property integer $sort_order
 * @property integer $admin_only
 * @property string $cssClass
 */
class ContainerPage extends ContentActiveRecord implements Searchable, CustomContentContainer
{
    /**
     * @inheritdoc
     */
    public $streamChannel = null;

    /**
     * @inheritdoc
     */
    public $autoAddToWall = false;

    /**
     * @inheritdoc
     */
    public $wallEntryClass = 'humhub\modules\custom_pages\widgets\WallEntry';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            ['class' => Container::className()],
        ];
    }

    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'custom_pages_container_page';
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function rules()
    {
        $rules = $this->defaultRules();
        $rules[] = ['in_new_window', 'integer'];
	    $rules[] = [['page_content'], 'safe', 'when' => array($this, 'isNotHtml')];
	    $rules[] = [['page_content'], 'filter', 'when' => array($this, 'isHtml'), 'filter' => array($this, 'purifyFilter')];

        return $rules;
    }

	/**
	 * @param ContainerPage $model the HTML code container
	 * @return bool whether it is an html type container
	 */
	public function isHtml($model)
	{
		return $model->type == Container::TYPE_HTML;
	}

	/**
	 * @param ContainerPage $model the HTML code
	 * @return bool whether it is not an html type container
	 */
	public function isNotHtml($model)
	{
		return $model->type != Container::TYPE_HTML;
	}

	/**
	 * Purify the HTML code only if its container type is html (conditional validation)
	 *
	 * @param string $html the HTML code to be purified
	 * @return string the purified HTML code
	 */
    public function purifyFilter($html)
    {
	    return HtmlPurifier::process($html, $this->purifierConfig('test'));
    }

	/**
	 * Returns HTMLPurifier configuration set.
	 *
	 * @param string $type set name
	 * @return array configuration
	 */
    private function purifierConfig($type = '')
    {
	    // TODO: make workable safe config presets
	    // test preset is only for testing
	    // other presets will be discussed

	    $config = [];

	    switch ($type) {
		    case 'test':
		    case 'full':
			    $config = [
					    'HTML.Allowed' => '*[style],*[class],div,p,br,b,strong,i,em,u,s,a[href|target],ul,li,ol,span,h1,h2,h3,h4,h5,h6,sub,sup,blockquote,pre,img[src|alt],hr,font[size|color]',
					    'CSS.Proprietary' => true,
					    'CSS.AllowedProperties' => 'color,background-color,width,height,border-radius',
			    ];
			    break;
		    case 'minimal':
			    $config = [
					    'HTML.Allowed' => '*[style],*[class],p,br,b,strong,i,em,u,s,a[href|target],ul,li,ol,hr',
					    'CSS.Proprietary' => true,
					    'CSS.AllowedProperties' => 'color,background-color,width,height',
			    ];
			    break;
		    case 'default':
		    default:
		    	$config = [
					    'HTML.Allowed' => '*[style],*[class],p,br,b,strong,i,em,u,s,a[href|target],ul,li,ol,hr,h1,h2,h3,h4,h5,h6,span,pre,code,table,tr,td,th,blockquote,img[src|alt]',
					    'CSS.Proprietary' => true,
					    'CSS.AllowedProperties' => 'color,background-color,width,height',
			    ];
			    break;
	    }

	    return $config;
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $result = $this->defaultAttributeLabels();
        $result['in_new_window'] = Yii::t('CustomPagesModule.models_ContainerPage', 'Open in new window');

        if($this->isType(Container::TYPE_PHP)) {
            $contentLabel = Yii::t('CustomPagesModule.models_Page', 'View');
        } else {
            $contentLabel = Yii::t('CustomPagesModule.components_Container', 'Content');
        }

        $result['page_content'] = $contentLabel;
        $result['admin_only'] = Yii::t('CustomPagesModule.models_ContainerPage', 'Only visible for space admins');
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getContentName()
    {
        return 'Page';
    }

    /**
     * @inheritdoc
     */
    public function getPageContentProperty() {
        return 'page_content';
    }

    /**
     * @inheritdoc
     */
    public function getContentDescription()
    {
        return $this->title;
    }

    /**
     * @inheritdoc
     */
    public function getSearchAttributes()
    {
        return [
            'title' => $this->title,
            'content' => $this->page_content,
        ];
    }

    /**
     * Returns the view url of this page.
     */
    public function getUrl()
    {
        return $this->content->container->createUrl('/custom_pages/container/view', ['id' => $this->id]);
    }

    /**
     * @inheritdoc
     */
    public function getContentTypes()
    {
        return [
            Container::TYPE_MARKDOWN,
            Container::TYPE_LINK,
            Container::TYPE_IFRAME,
            Container::TYPE_TEMPLATE,
            Container::TYPE_HTML,
            Container::TYPE_PHP
        ];
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return Yii::t('CustomPagesModule.models_ContainerPage', 'page');
    }
    
    /**
     * @inheritdoc
     */
    public function getPageContent()
    {
        return $this->page_content;
    }

    /**
     * @inheritdoc
     */
    public function getAllowedTemplateSelection()
    {
        return Template::getSelection(['type' => Template::TYPE_LAYOUT, 'allow_for_spaces' => 1]);
    }

    /**
     * @inheritdoc
     */
    public function getPhpViewPath()
    {
        $settings = new SettingsForm();
        return $settings->phpContainerPagePath;
    }

}
