<?php
declare(strict_types=1);

namespace Venbhas\Blog\Ui\DataProvider\Form\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Ui\Component\Listing\Columns\Price;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Venbhas\Blog\Model\ResourceModel\RelatedProductsResourceInterface;

/**
 * Adds Magento-style related products grid to blog admin forms.
 */
class RelatedProducts implements ModifierInterface
{
    public const DATA_SCOPE_RELATED = 'related';
    private const GROUP_RELATED = 'related_products';

    /** @var RequestInterface */
    private $request;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var ImageHelper */
    private $imageHelper;

    /** @var Status */
    private $status;

    /** @var AttributeSetRepositoryInterface */
    private $attributeSetRepository;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var RelatedProductsResourceInterface */
    private $relatedProductsResource;

    /** @var string */
    private $scopeName;

    /** @var string */
    private $entityIdField;

    /** @var Phrase */
    private $sectionContent;

    /** @var Price|null */
    private $priceModifier;

    /**
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param ImageHelper $imageHelper
     * @param Status $status
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param StoreManagerInterface $storeManager
     * @param RelatedProductsResourceInterface $relatedProductsResource
     * @param string $scopeName
     * @param string $entityIdField
     * @param string|null $sectionContent
     */
    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder,
        ProductRepositoryInterface $productRepository,
        ImageHelper $imageHelper,
        Status $status,
        AttributeSetRepositoryInterface $attributeSetRepository,
        StoreManagerInterface $storeManager,
        RelatedProductsResourceInterface $relatedProductsResource,
        string $scopeName = '',
        string $entityIdField = '',
        ?string $sectionContent = null
    ) {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->status = $status;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->storeManager = $storeManager;
        $this->relatedProductsResource = $relatedProductsResource;
        $this->scopeName = $scopeName;
        $this->entityIdField = $entityIdField;
        $defaultSectionContent = 'Related products are shown to customers '
            . 'in addition to the item the customer is looking at.';
        $this->sectionContent = __($sectionContent ?: $defaultSectionContent);
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data): array
    {
        $storeId = 0;
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
        } catch (\Exception $e) {
            $storeId = 0;
        }

        $entityId = (int) $this->request->getParam($this->entityIdField);
        $priceModifier = $this->getPriceModifier();
        $priceModifier->setData('name', 'price');

        foreach ($data as &$item) {
            if (!is_array($item)) {
                continue;
            }

            $item['links'][self::DATA_SCOPE_RELATED] = [];
            $item['current_product_id'] = 0;
            $item['current_store_id'] = $storeId;

            if ($entityId <= 0) {
                continue;
            }

            $productIds = $this->relatedProductsResource->getRelatedProductIds($entityId);
            $position = 0;
            foreach ($productIds as $productId) {
                try {
                    $linkedProduct = $this->productRepository->getById((int) $productId, false, $storeId);
                } catch (\Exception $e) {
                    continue;
                }
                $item['links'][self::DATA_SCOPE_RELATED][] = $this->fillData($linkedProduct, ++$position);
            }

            if (!empty($item['links'][self::DATA_SCOPE_RELATED])) {
                $dataMap = $priceModifier->prepareDataSource([
                    'data' => [
                        'items' => $item['links'][self::DATA_SCOPE_RELATED],
                    ],
                ]);
                $item['links'][self::DATA_SCOPE_RELATED] = $dataMap['data']['items'];
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
        $meta[self::GROUP_RELATED] = [
            'children' => [
                'button_set' => $this->getButtonSet(
                    $this->sectionContent,
                    __('Add Related Products')
                ),
                'modal' => $this->getGenericModal(__('Add Related Products')),
                self::DATA_SCOPE_RELATED => $this->getGrid(),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Related Products'),
                        'collapsible' => true,
                        'componentType' => Fieldset::NAME,
                        'dataScope' => '',
                        'sortOrder' => 30,
                    ],
                ],
            ],
        ];

        return $meta;
    }

    /**
     * Build row data for the related products grid.
     *
     * @param ProductInterface $linkedProduct
     * @param int $position
     * @return array
     */
    private function fillData(ProductInterface $linkedProduct, int $position): array
    {
        return [
            'id' => $linkedProduct->getId(),
            'thumbnail' => $this->imageHelper->init($linkedProduct, 'product_listing_thumbnail')->getUrl(),
            'name' => $linkedProduct->getName(),
            'status' => $this->status->getOptionText($linkedProduct->getStatus()),
            'attribute_set' => $this->attributeSetRepository
                ->get($linkedProduct->getAttributeSetId())
                ->getAttributeSetName(),
            'sku' => $linkedProduct->getSku(),
            'price' => $linkedProduct->getPrice(),
            'position' => $position,
        ];
    }

    /**
     * Lazy-load catalog price modifier for product grid cells.
     *
     * @return Price
     */
    private function getPriceModifier(): Price
    {
        if (!$this->priceModifier) {
            $this->priceModifier = ObjectManager::getInstance()->get(Price::class);
        }

        return $this->priceModifier;
    }

    /**
     * Build UI config for the related-products picker button.
     *
     * @param Phrase $content
     * @param Phrase $buttonTitle
     * @return array
     */
    private function getButtonSet(Phrase $content, Phrase $buttonTitle): array
    {
        $scope = self::DATA_SCOPE_RELATED;
        $modalTarget = $this->scopeName . '.' . self::GROUP_RELATED . '.modal';
        $listingTarget = $scope . '_product_listing';

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
     * Build modal UI config for product assignment.
     *
     * @param Phrase $title
     * @return array
     */
    private function getGenericModal(Phrase $title): array
    {
        $scope = self::DATA_SCOPE_RELATED;
        $listingTarget = $scope . '_product_listing';

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
                                    'text' => __('Add Selected Products'),
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
                                'selectionsProvider' => $listingTarget . '.' . $listingTarget . '.product_columns.ids',
                                'ns' => $listingTarget,
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => true,
                                'dataLinks' => [
                                    'imports' => false,
                                    'exports' => true,
                                ],
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'productId' => '${ $.provider }:data.current_product_id',
                                    'storeId' => '${ $.provider }:data.current_store_id',
                                    '__disableTmpl' => ['productId' => false, 'storeId' => false],
                                ],
                                'exports' => [
                                    'productId' => '${ $.externalProvider }:params.current_product_id',
                                    'storeId' => '${ $.externalProvider }:params.current_store_id',
                                    '__disableTmpl' => ['productId' => false, 'storeId' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build dynamic-rows grid config for assigned products.
     *
     * @return array
     */
    private function getGrid(): array
    {
        $listingDataProvider = 'data.' . self::DATA_SCOPE_RELATED . '_product_listing';

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
                            'id' => 'entity_id',
                            'name' => 'name',
                            'status' => 'status_text',
                            'attribute_set' => 'attribute_set_text',
                            'sku' => 'sku',
                            'price' => 'price',
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
     * Define columns for the related-products dynamic grid.
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
            'name' => $this->getTextColumn('name', false, __('Name'), 20),
            'status' => $this->getTextColumn('status', true, __('Status'), 30),
            'attribute_set' => $this->getTextColumn('attribute_set', false, __('Attribute Set'), 40),
            'sku' => $this->getTextColumn('sku', true, __('SKU'), 50),
            'price' => $this->getTextColumn('price', true, __('Price'), 60),
            'actionDelete' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'additionalClasses' => 'data-grid-actions-cell',
                            'componentType' => 'actionDelete',
                            'dataType' => Text::NAME,
                            'label' => __('Actions'),
                            'sortOrder' => 70,
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
                            'sortOrder' => 80,
                            'visible' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build a text column definition for the related-products grid.
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
