<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend\Article;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Venbhas\Article\Model\ResourceModel\Article\CollectionFactory;
use Venbhas\Article\Model\ResourceModel\Category\CollectionFactory as categoryCollectionFactory;
class ListBlock extends Template
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var categoryCollectionFactory */
    private $categoryCollectionFactory;

    public function __construct(
        Context $context, 
    CollectionFactory $collectionFactory,
    categoryCollectionFactory $categoryCollectionFactory,
    array $data = []
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getArticles()
    {
        if (!$this->hasData('articles')) {

            $page  = (int) $this->getRequest()->getParam('p', 1);
            $limit = (int) $this->getRequest()->getParam('limit', 10);

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('is_active', 1);
            $collection->addFieldToFilter('status', 1);
            $collection->setOrder('created_at', 'desc');

            $collection->setCurPage($page);
            $collection->setPageSize($limit);

            $this->setData('articles', $collection);
    }

    return $this->getData('articles');
}


    public function getCategories(){
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('status', 1);
        $collection->setOrder('updated_at', 'desc');
        return $collection;
    }

    /**
     * Article detail URL (uses /article/url_key path for view).
     */
    public function getArticleUrl(string $urlKey): string
    {
        return $this->getUrl('article/' . $urlKey);
    }

    public function getCategoryUrl($category): string
    {
        return $this->getUrl('article/category/' . $category->getUrlKey());
    }

    protected function _prepareLayout()
    {
        $pager = $this->getLayout()->createBlock('Magento\Theme\Block\Html\Pager', 'articles_list.pager');
        $pager->setCollection($this->getArticles());
        $this->setChild('pager', $pager);
        return parent::_prepareLayout();
    }

    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }


}
