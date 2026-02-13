<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Article\Source;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class Products implements OptionSourceInterface
{
    /** @var ProductCollectionFactory */
    private $collectionFactory;

    public function __construct(ProductCollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->setOrder('name', 'asc');
        $collection->setPageSize(500);

        $options = [];
        foreach ($collection as $product) {
            $options[] = [
                'value' => (string) $product->getId(),
                'label' => $product->getName(),
            ];
        }
        return $options;
    }
}
