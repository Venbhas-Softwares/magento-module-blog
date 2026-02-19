<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\Comment\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Venbhas\Article\Model\ResourceModel\Article\CollectionFactory as ArticleCollectionFactory;

class Articles implements OptionSourceInterface
{
    /** @var ArticleCollectionFactory */
    private $collectionFactory;

    /**
     * Constructor.
     *
     * @param ArticleCollectionFactory $collectionFactory
     */
    public function __construct(ArticleCollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Return article options for select.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        $collection = $this->collectionFactory->create();
        $collection->setOrder('title', 'asc');
        foreach ($collection as $article) {
            $options[] = [
                'value' => (string) $article->getId(),
                'label' => $article->getTitle(),
            ];
        }
        return $options;
    }
}
