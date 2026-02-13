<?php
declare(strict_types=1);

namespace Venbhas\Article\Block\Adminhtml\Article\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    /** @var Context */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getArticleId(): ?int
    {
        return (int) $this->context->getRequest()->getParam('article_id') ?: null;
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
