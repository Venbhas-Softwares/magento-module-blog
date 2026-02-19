<?php
declare(strict_types=1);

namespace Venbhas\Article\Model\ResourceModel\Comment\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\Search\AggregationInterface;
use Venbhas\Article\Model\ResourceModel\Comment\Collection as CommentCollection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Psr\Log\LoggerInterface;

/**
 * Comment grid collection.
 */
class Collection extends CommentCollection implements SearchResultInterface
{
    /** @var AggregationInterface|null */
    protected $aggregations;

    /** @var string */
    private $model;

    /** @var string */
    private $resourceModel;

    /**
     * Initialize grid collection.
     *
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param string $mainTable
     * @param string $eventPrefix
     * @param string $eventObject
     * @param string $resourceModel
     * @param string $model
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = Document::class,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->resourceModel = $resourceModel;
        $this->model = $model;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($this->model, $this->resourceModel);
        $this->setMainTable($mainTable);
    }

    /**
     * Get aggregations.
     *
     * @return AggregationInterface|null
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Set aggregations.
     *
     * @param AggregationInterface|null $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * Get search criteria.
     *
     * @return null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(?\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items.
     *
     * @param array|null $items
     * @return $this
     */
    public function setItems(?array $items = null)
    {
        return $this;
    }

    /**
     * Reset state.
     *
     * @return void
     */
    public function _resetState(): void
    {
        parent::_resetState();
        $this->_init($this->model, $this->resourceModel);
    }
}
