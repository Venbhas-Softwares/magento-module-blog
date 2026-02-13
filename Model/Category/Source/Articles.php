<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Category\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Venbhas\Article\Model\ResourceModel\Article\CollectionFactory as ArticleCollectionFactory;

class Articles implements OptionSourceInterface
{
    /** @var ArticleCollectionFactory */
    private $collectionFactory;

    public function __construct(ArticleCollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->collectionFactory->create();
        $collection->setOrder('title', 'asc');
        $collection->setPageSize(500);
        foreach ($collection as $article) {
            $options[] = [
                'value' => (string) $article->getId(),
                'label' => $article->getTitle(),
            ];
        }
        return $options;
    }
}
