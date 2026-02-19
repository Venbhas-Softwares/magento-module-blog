<?php
declare(strict_types=1);

namespace Venbhas\Article\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CategoryActions extends Column
{
    public const URL_PATH_EDIT = 'venbhas_article/category/edit';
    public const URL_PATH_DELETE = 'venbhas_article/category/delete';

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var Escaper */
    private $escaper;

    /**
     * Constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
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

    /**
     * Prepare data source for actions column.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['category_id'])) {
                    $name = $this->escaper->escapeHtml($item['name'] ?? '');
                    $editUrl = $this->urlBuilder->getUrl(
                        self::URL_PATH_EDIT,
                        ['category_id' => $item['category_id']]
                    );
                    $deleteUrl = $this->urlBuilder->getUrl(
                        self::URL_PATH_DELETE,
                        ['category_id' => $item['category_id']]
                    );
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $editUrl,
                            'label' => __('Edit'),
                        ],
                        'delete' => [
                            'href' => $deleteUrl,
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete %1', $name),
                                'message' => __('Are you sure you want to delete this category?'),
                            ],
                            'post' => true,
                        ],
                    ];
                }
            }
        }
        return $dataSource;
    }
}
