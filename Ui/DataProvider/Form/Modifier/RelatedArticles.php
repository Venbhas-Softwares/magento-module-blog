<?php
declare(strict_types=1);

namespace Venbhas\Blog\Ui\DataProvider\Form\Modifier;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Blog\Model\Article\Source\Status as ArticleStatus;
use Venbhas\Blog\Model\ArticleFactory;
use Venbhas\Blog\Model\ResourceModel\RelatedArticlesResourceInterface;

/**
 * Magento-style articles grid for blog category admin (catalog category products pattern).
 */
class RelatedArticles implements ModifierInterface
{
    public const DATA_SCOPE_CATEGORY_ARTICLE = 'category_article';

    private const GROUP_ARTICLES = 'articles_in_category';

    /** @var RequestInterface */
    private $request;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var ArticleFactory */
    private $articleFactory;

    /** @var ArticleStatus */
    private $articleStatus;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var RelatedArticlesResourceInterface */
    private $relatedArticlesResource;

    /** @var string */
    private $scopeName;

    /** @var string */
    private $entityIdField;

    /** @var Phrase */
    private $sectionContent;

    /**
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     * @param ArticleFactory $articleFactory
     * @param ArticleStatus $articleStatus
     * @param StoreManagerInterface $storeManager
     * @param RelatedArticlesResourceInterface $relatedArticlesResource
     * @param string $scopeName
     * @param string $entityIdField
     * @param string|null $sectionContent
     */
    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder,
        ArticleFactory $articleFactory,
        ArticleStatus $articleStatus,
        StoreManagerInterface $storeManager,
        RelatedArticlesResourceInterface $relatedArticlesResource,
        string $scopeName = '',
        string $entityIdField = '',
        ?string $sectionContent = null
    ) {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
        $this->articleFactory = $articleFactory;
        $this->articleStatus = $articleStatus;
        $this->storeManager = $storeManager;
        $this->relatedArticlesResource = $relatedArticlesResource;
        $this->scopeName = $scopeName;
        $this->entityIdField = $entityIdField;
        $defaultSectionContent = 'Assign articles to this category. '
            . 'They will appear on the category page on the storefront.';
        $this->sectionContent = __($sectionContent ?: $defaultSectionContent);
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data): array
    {
        $entityId = (int) $this->request->getParam($this->entityIdField);

        foreach ($data as &$item) {
            if (!is_array($item)) {
                continue;
            }

            $item['links'][self::DATA_SCOPE_CATEGORY_ARTICLE] = [];

            if ($entityId <= 0) {
                continue;
            }

            $articleIds = $this->relatedArticlesResource->getRelatedArticleIds($entityId);
            $position = 0;
            foreach ($articleIds as $articleId) {
                $article = $this->articleFactory->create()->load((int) $articleId);
                if (!$article->getId()) {
                    continue;
                }
                $item['links'][self::DATA_SCOPE_CATEGORY_ARTICLE][] = $this->fillData($article, ++$position);
            }
        }
        unset($item);

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta): array
    {
        $meta[self::GROUP_ARTICLES] = [
            'children' => [
                'button_set' => $this->getButtonSet(
                    $this->sectionContent,
                    __('Add Articles')
                ),
                'modal' => $this->getGenericModal(__('Add Articles')),
                self::DATA_SCOPE_CATEGORY_ARTICLE => $this->getGrid(),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Articles in Category'),
                        'collapsible' => true,
                        'componentType' => Fieldset::NAME,
                        'dataScope' => '',
                        'sortOrder' => 25,
                    ],
                ],
            ],
        ];

        return $meta;
    }

    /**
     * Map an article model to dynamic-rows grid data.
     *
     * @param \Venbhas\Blog\Model\Article $article
     * @param int $position
     * @return array
     */
    private function fillData($article, int $position): array
    {
        $statusLabel = '';
        foreach ($this->articleStatus->toOptionArray() as $option) {
            if ((int) $option['value'] === (int) $article->getStatus()) {
                $statusLabel = (string) $option['label'];
                break;
            }
        }

        return [
            'id' => $article->getId(),
            'thumbnail' => $this->getThumbnailUrl((string) $article->getData('featured_image')),
            'name' => $article->getTitle(),
            'url_key' => $article->getUrlKey(),
            'status' => $statusLabel,
            'position' => $position,
        ];
    }

    /**
     * Build media URL for article featured image.
     *
     * @param string $imagePath
     * @return string
     */
    private function getThumbnailUrl(string $imagePath): string
    {
        $imagePath = trim($imagePath);
        if ($imagePath === '') {
            return '';
        }

        try {
            return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . ltrim($imagePath, '/');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Build UI config for the related-articles picker button.
     *
     * @param Phrase $content
     * @param Phrase $buttonTitle
     * @return array
     */
    private function getButtonSet(Phrase $content, Phrase $buttonTitle): array
    {
        $scope = self::DATA_SCOPE_CATEGORY_ARTICLE;
        $modalTarget = $this->scopeName . '.' . self::GROUP_ARTICLES . '.modal';
        $listingTarget = $scope . '_article_listing';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'label' => false,
                        'content' => $content,
                        'template' => 'ui/form/components/complex',
                    ],
                ],
            ],
            'children' => [
                'button_' . $scope => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/button',
                                'actions' => [
                                    [
                                        'targetName' => $modalTarget,
                                        'actionName' => 'toggleModal',
                                    ],
                                    [
                                        'targetName' => $modalTarget . '.' . $listingTarget,
                                        'actionName' => 'render',
                                    ],
                                ],
                                'title' => $buttonTitle,
                                'provider' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build modal UI config for article assignment.
     *
     * @param Phrase $title
     * @return array
     */
    private function getGenericModal(Phrase $title): array
    {
        $scope = self::DATA_SCOPE_CATEGORY_ARTICLE;
        $listingTarget = $scope . '_article_listing';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'dataScope' => '',
                        'options' => [
                            'title' => $title,
                            'buttons' => [
                                [
                                    'text' => __('Cancel'),
                                    'actions' => [
                                        'closeModal',
                                    ],
                                ],
                                [
                                    'text' => __('Add Selected Articles'),
                                    'class' => 'action-primary',
                                    'actions' => [
                                        [
                                            'targetName' => 'index = ' . $listingTarget,
                                            'actionName' => 'save',
                                        ],
                                        'closeModal',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'children' => [
                $listingTarget => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => false,
                                'componentType' => 'insertListing',
                                'dataScope' => $listingTarget,
                                'externalProvider' => $listingTarget . '.' . $listingTarget . '_data_source',
                                'selectionsProvider' => $listingTarget . '.' . $listingTarget
                                    . '.venbhas_blog_article_picker_columns.ids',
                                'ns' => $listingTarget,
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => true,
                                'dataLinks' => [
                                    'imports' => false,
                                    'exports' => true,
                                ],
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build dynamic-rows grid config for assigned articles.
     *
     * @return array
     */
    private function getGrid(): array
    {
        $listingDataProvider = 'data.' . self::DATA_SCOPE_CATEGORY_ARTICLE . '_article_listing';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__field-wide',
                        'componentType' => DynamicRows::NAME,
                        'label' => null,
                        'columnsHeader' => false,
                        'columnsHeaderAfterRender' => true,
                        'renderDefaultRecord' => false,
                        'template' => 'ui/dynamic-rows/templates/grid',
                        'component' => 'Magento_Catalog/js/components/reset-dynamic-rows-grid-row-position-on-delete',
                        'addButton' => false,
                        'recordTemplate' => 'record',
                        'dataScope' => 'links',
                        'deleteButtonLabel' => __('Remove'),
                        'dataProvider' => $listingDataProvider,
                        'map' => [
                            'id' => 'article_id',
                            'name' => 'title',
                            'status' => 'status',
                            'url_key' => 'url_key',
                            'thumbnail' => 'thumbnail_src',
                        ],
                        'links' => [
                            'insertData' => '${ $.provider }:${ $.dataProvider }',
                            '__disableTmpl' => ['insertData' => false],
                        ],
                        'sortOrder' => 2,
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'isTemplate' => true,
                                'is_collection' => true,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'dataScope' => '',
                            ],
                        ],
                    ],
                    'children' => $this->fillMeta(),
                ],
            ],
        ];
    }

    /**
     * Define columns for the related-articles dynamic grid.
     *
     * @return array
     */
    private function fillMeta(): array
    {
        return [
            'id' => $this->getTextColumn('id', false, __('ID'), 0),
            'thumbnail' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => Field::NAME,
                            'formElement' => Input::NAME,
                            'elementTmpl' => 'ui/dynamic-rows/cells/thumbnail',
                            'dataType' => Text::NAME,
                            'dataScope' => 'thumbnail',
                            'fit' => true,
                            'label' => __('Thumbnail'),
                            'sortOrder' => 10,
                        ],
                    ],
                ],
            ],
            'name' => $this->getTextColumn('name', false, __('Title'), 20),
            'url_key' => $this->getTextColumn('url_key', true, __('URL Key'), 30),
            'status' => $this->getTextColumn('status', true, __('Status'), 40),
            'actionDelete' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'additionalClasses' => 'data-grid-actions-cell',
                            'componentType' => 'actionDelete',
                            'dataType' => Text::NAME,
                            'label' => __('Actions'),
                            'sortOrder' => 50,
                            'fit' => true,
                        ],
                    ],
                ],
            ],
            'position' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'dataType' => Number::NAME,
                            'formElement' => Input::NAME,
                            'componentType' => Field::NAME,
                            'dataScope' => 'position',
                            'sortOrder' => 60,
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build a text column definition for the related-articles grid.
     *
     * @param string $dataScope
     * @param bool $fit
     * @param Phrase $label
     * @param int $sortOrder
     * @return array
     */
    private function getTextColumn(string $dataScope, bool $fit, Phrase $label, int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'elementTmpl' => 'ui/dynamic-rows/cells/text',
                        'component' => 'Magento_Ui/js/form/element/text',
                        'dataType' => Text::NAME,
                        'dataScope' => $dataScope,
                        'fit' => $fit,
                        'label' => $label,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];
    }
}
