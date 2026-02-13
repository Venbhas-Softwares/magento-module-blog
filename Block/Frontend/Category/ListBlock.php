<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Frontend\Category;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Venbhas\Article\Model\ResourceModel\Category\CollectionFactory;

class ListBlock extends Template
{
    /** @var CollectionFactory */
    private $collectionFactory;

    public function __construct(Context $context, CollectionFactory $collectionFactory, array $data = [])
    {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $data);
    }

    public function getCategories()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', 1);
        $collection->setOrder('name', 'asc');
        return $collection;
    }

    public function getCategoryUrl(string $urlKey): string
    {
        return $this->getUrl('article/category/' . $urlKey);
    }
}
