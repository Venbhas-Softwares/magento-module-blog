<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Frontend\Article;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\ScopeInterface;

/**
 * Shared toolbar wiring for article listing blocks.
 */
trait ToolbarAwareTrait
{
    /**
     * @return string
     */
    public function getToolbarHtml(): string
    {
        $toolbar = $this->getArticleToolbarBlock();
        if (!$toolbar instanceof Toolbar) {
            return '';
        }

        return (string) $toolbar->setIsBottom(false)->toHtml();
    }

    /**
     * @return string
     */
    public function getBottomToolbarHtml(): string
    {
        $toolbar = $this->getArticleToolbarBlock();
        if (!$toolbar instanceof Toolbar) {
            return '';
        }

        return (string) $toolbar->setIsBottom(true)->toHtml();
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $collection = $this->getToolbarCollection();
        if ($collection instanceof AbstractCollection && $collection->getSize()) {
            $this->configureArticleToolbar($collection);
        }

        return parent::_beforeToHtml();
    }

    /**
     * @param AbstractCollection $collection
     * @return void
     */
    protected function configureArticleToolbar(AbstractCollection $collection): void
    {
        $toolbar = $this->getArticleToolbarBlock();
        if (!$toolbar instanceof Toolbar) {
            return;
        }

        $limit = $this->getPageLimit();
        $page = max(1, (int) $this->getRequest()->getParam('p', 1));

        if ($collection->isLoaded()) {
            $collection->clear();
        }

        $collection->setPageSize($limit)->setCurPage($page);

        $toolbar->setCollection($collection);
        $toolbar->setAvailableOrders($this->getToolbarSortOptions());
        $toolbar->setDefaultOrder($this->getCurrentSortOrder());
        $this->setChild('toolbar', $toolbar);
    }

    /**
     * @return int
     */
    protected function getPageLimit(): int
    {
        $storeId = $this->getStoreId();
        $limits = $this->config->getAvailablePageLimits($storeId);
        $defaultLimit = $this->config->getArticlesPerPage($storeId);
        $limit = (int) $this->getRequest()->getParam('limit', $defaultLimit);

        if (!isset($limits[$limit])) {
            $limit = $defaultLimit;
        }

        return $limit > 0 ? $limit : $defaultLimit;
    }

    /**
     * @return int
     */
    protected function getStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    /**
     * @return array
     */
    protected function getToolbarSortOptions(): array
    {
        return $this->config->getSortOptionsForFrontend();
    }

    /**
     * @param AbstractCollection $collection
     * @return void
     */
    protected function applyToolbarSort(AbstractCollection $collection): void
    {
        $sort = $this->config->getSortOrderFieldAndDirection($this->getCurrentSortOrder());
        $collection->setOrder($sort['field'], $sort['direction']);
    }

    /**
     * @return Toolbar|null
     */
    protected function getArticleToolbarBlock(): ?Toolbar
    {
        $blockName = (string) ($this->getData('toolbar_block_name') ?: 'article_list_toolbar');
        $toolbar = $this->getChildBlock($blockName);
        if ($toolbar instanceof Toolbar) {
            return $toolbar;
        }

        $toolbar = $this->getLayout()->getBlock($blockName);
        return $toolbar instanceof Toolbar ? $toolbar : null;
    }

    /**
     * @return AbstractCollection|array|null
     */
    abstract protected function getToolbarCollection();
}
