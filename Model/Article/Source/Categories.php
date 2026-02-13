<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Article\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Venbhas\Article\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class Categories implements OptionSourceInterface
{
    /** @var CategoryCollectionFactory */
    private $collectionFactory;

    public function __construct(CategoryCollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        $collection = $this->collectionFactory->create();
        $collection->setOrder('name', 'asc');
        foreach ($collection as $category) {
            $options[] = [
                'value' => (string) $category->getId(),
                'label' => $category->getName(),
            ];
        }
        return $options;
    }
}
