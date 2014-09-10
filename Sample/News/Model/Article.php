<?php
/**
 * Sample_News extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @category       Sample
 * @package        Sample_News
 * @copyright      Copyright (c) 2014
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */
namespace Sample\News\Model;

class Article
    extends \Magento\Framework\Model\AbstractModel
    implements \Magento\Framework\Object\IdentityInterface {
    const XML_URL_PREFIX_PATH = 'sample_news/article/url_prefix';
    const XML_URL_SUFFIX_PATH = 'sample_news/article/url_suffix';
    /**
     * cache tag
     */
    const CACHE_TAG = 'sample_news_article';

    /**
     * @var string
     */
    protected $_cacheTag = 'sample_news_article';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'sample_news_article';
    /**
     * @var \Sample\News\Helper\Article
     */
    protected  $_articleHelper;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $_filter;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    /**
     * @var
     */
    protected $_productCollection;
    /**
     * @var
     */
    protected $_categoryCollection;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param \Sample\News\Helper\Article $articleHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Filter\FilterManager $filter
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Model\Resource\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\Db $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Sample\News\Helper\Article $articleHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filter\FilterManager $filter,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\Resource\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\Db $resourceCollection = null,
        array $data = array()
    ) {
        $this->_productFactory = $productFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_articleHelper = $articleHelper;
        $this->_filter = $filter;
        $this->_urlBuilder = $urlBuilder;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context, $registry, $resource, $resourceCollection);
    }
    /**
     * @access public
     * @return void
     */
    protected function _construct() {
        $this->_init('Sample\News\Model\Resource\Article');
    }

    /**
     * Get identities
     * @access public
     * @return array
     */
    public function getIdentities() {
        return array(self::CACHE_TAG . '_' . $this->getId());
    }

    /**
     * @access public
     * @return string
     */
    public function getArticleUrl() {
        if ($this->getIdentifier()) {
            $identifier = '';
            if ($prefix = $this->_scopeConfig->getValue(self::XML_URL_PREFIX_PATH)) {
                $identifier .= $prefix.'/';
            }
            $identifier .= $this->getIdentifier();
            if ($suffix = $this->_scopeConfig->getValue(self::XML_URL_SUFFIX_PATH)) {
                $identifier .= '.'.$suffix;
            }
            return $this->_urlBuilder->getUrl('', array('_direct' => $identifier));
        }
        return $this->_urlBuilder->getUrl('sample_news/article/view', array('id' => $this->getId()));
    }

    /**
     * @access public
     * @param $identifier
     * @param $storeId
     * @return mixed
     */
    public function checkIdentifier($identifier, $storeId) {
        return $this->_getResource()->checkIdentifier($identifier, $storeId);
    }

    /**
     * @access public
     * @return array|mixed
     */
    public function getProductsPosition() {
        if (!$this->getId()) {
            return array();
        }
        $array = $this->getData('products_position');
        if (is_null($array)) {
            $array = $this->getResource()->getProductsPosition($this);
            $this->setData('products_position', $array);
        }
        return $array;
    }

    /**
     * @access public
     * @param string $attributes
     * @return object
     */
    public function getSelectedProductsCollection($attributes = '*') {
        if (is_null($this->_productCollection)) {
            $collection = $this->_productFactory->create()->getResourceCollection();
            $collection->addAttributeToSelect($attributes);
            $collection->joinField('position',
                'sample_news_article_product',
                'position',
                'product_id=entity_id',
                '{{table}}.article_id='.$this->getId(),
                'inner');
            $this->_productCollection = $collection;
        }
        return $this->_productCollection;
    }

    /**
     * @param string $attributes
     * @return \Magento\Catalog\Model\Resource\Category\Collection
     */
    public function getSelectedCategoriesCollection($attributes = '*') {
        if (is_null($this->_categoryCollection)) {
            $collection = $this->_categoryFactory->create()->getResourceCollection();
            $collection->addAttributeToSelect($attributes);
            $collection->joinField('position',
                'sample_news_article_category',
                'position',
                'category_id=entity_id',
                '{{table}}.article_id='.$this->getId(),
                'inner');
            $this->_categoryCollection = $collection;
        }
        return $this->_categoryCollection;
    }

    /**
     * @access protected
     * @return array
     */
    public function getCategoryIds() {
        if (!$this->hasData('category_ids')) {
            $ids = $this->_getResource()->getCategoryIds($this);
            $this->setData('category_ids', $ids);
        }
        return (array) $this->_getData('category_ids');
    }

    /**
     * @access public
     * @return array
     */
    public function getDefaultValues() {
        return array(
            'status' => 1,
        );
    }

    /**
     * format the url key
     * @param $string
     * @return string
     */
    public function formatUrlKey($string) {
        return $this->_filter->translitUrl($string);
    }
}
