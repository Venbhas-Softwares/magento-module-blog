<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Adminhtml\Category\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    /** @var Context */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getCategoryId(): ?int
    {
        return (int) $this->context->getRequest()->getParam('category_id') ?: null;
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
