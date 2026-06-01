<?php
declare(strict_types=1);

namespace Venbhas\Blog\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CommentArticleLink extends Column
{
    public const URL_PATH_EDIT = 'blog/article/edit';

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var Escaper */
    private $escaper;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['article_id'])) {
                continue;
            }

            $label = $this->escaper->escapeHtml((string)($item['article_title'] ?? $item['article_id']));
            $url = $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['article_id' => $item['article_id']]);

            $item[$this->getData('name')] = sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                $this->escaper->escapeUrl($url),
                $label
            );
        }

        return $dataSource;
    }
}

