<?php
declare(strict_types=1);

namespace Venbhas\Blog\Block\Adminhtml\Category;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Venbhas\Blog\Model\Category;
use Venbhas\Blog\Model\CategoryFactory;
use Venbhas\Blog\Model\ResourceModel\Category\Tree as CategoryTree;

/**
 * Blog category tree block (catalog-style admin tree).
 */
class Tree extends AbstractCategory
{
    /** @var string */
    protected $_template = 'Venbhas_Blog::category/tree.phtml';

    /** @var Session */
    private $backendSession;

    /** @var EncoderInterface */
    private $jsonEncoder;

    /** @var SecureHtmlRenderer */
    private $secureRenderer;

    /**
     * Initialize category tree block dependencies.
     *
     * @param Context $context
     * @param CategoryTree $categoryTree
     * @param Registry $registry
     * @param CategoryFactory $categoryFactory
     * @param EncoderInterface $jsonEncoder
     * @param Session $backendSession
     * @param SecureHtmlRenderer $secureRenderer
     * @param array $data
     */
    public function __construct(
        Context $context,
        CategoryTree $categoryTree,
        Registry $registry,
        CategoryFactory $categoryFactory,
        EncoderInterface $jsonEncoder,
        Session $backendSession,
        SecureHtmlRenderer $secureRenderer,
        array $data = []
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->backendSession = $backendSession;
        $this->secureRenderer = $secureRenderer;
        parent::__construct($context, $categoryTree, $registry, $categoryFactory, $data);
    }

    /** @var int */
    private $useAjax = 0;

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setUseAjax(0);
    }

    /**
     * Enable or disable AJAX tree loading.
     *
     * @param int $useAjax
     * @return $this
     */
    public function setUseAjax(int $useAjax)
    {
        $this->useAjax = $useAjax;
        return $this;
    }

    /**
     * Whether the category tree loads via AJAX.
     *
     * @return int
     */
    public function getUseAjax(): int
    {
        return $this->useAjax;
    }

    /**
     * @inheritdoc
     */
    protected function _beforeToHtml()
    {
        $this->assign([
            'secureRenderer' => $this->getSecureRenderer(),
        ]);

        return parent::_beforeToHtml();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        $addUrl = $this->getUrl('*/*/add', ['_current' => false, 'parent' => null, '_query' => false]);

        $this->addChild(
            'add_sub_button',
            Button::class,
            [
                'label' => __('Add Subcategory'),
                'onclick' => "addNew('" . $addUrl . "', false)",
                'class' => 'add',
                'id' => 'add_subcategory_button',
                'style' => $this->canAddSubCategory() ? '' : 'display: none;',
            ]
        );

        $this->addChild(
            'add_root_button',
            Button::class,
            [
                'label' => __('Add Root Category'),
                'onclick' => "addNew('" . $addUrl . "', true)",
                'class' => 'add',
                'id' => 'add_root_category_button',
            ]
        );

        return parent::_prepareLayout();
    }

    /**
     * Render add root category button HTML.
     *
     * @return string
     */
    public function getAddRootButtonHtml(): string
    {
        return $this->getChildHtml('add_root_button');
    }

    /**
     * Render add subcategory button HTML.
     *
     * @return string
     */
    public function getAddSubButtonHtml(): string
    {
        return $this->getChildHtml('add_sub_button');
    }

    /**
     * URL used to load category tree JSON.
     *
     * @return string
     */
    public function getLoadTreeUrl(): string
    {
        $params = ['_current' => true, 'id' => null];
        if ($this->backendSession->getIsTreeWasExpanded()) {
            $params['expand_all'] = true;
        }

        return $this->getUrl('*/*/categoriesJson', $params);
    }

    /**
     * URL for drag-and-drop category move requests.
     *
     * @return string
     */
    public function getMoveUrl(): string
    {
        return $this->getUrl('*/*/move');
    }

    /**
     * Whether the admin category tree was previously expanded.
     *
     * @return bool
     */
    public function getIsWasExpanded(): bool
    {
        return (bool) $this->backendSession->getIsTreeWasExpanded();
    }

    /**
     * Build nested category tree array for the admin UI.
     *
     * @param Node|null $parentNodeCategory
     * @return array
     */
    public function getTree(?Node $parentNodeCategory = null): array
    {
        $rootArray = $this->getNodeJson($this->getRoot($parentNodeCategory));

        return $rootArray['children'] ?? [];
    }

    /**
     * Encode category tree children as JSON.
     *
     * @param Node|null $parentNodeCategory
     * @return string
     */
    public function getTreeJson(?Node $parentNodeCategory = null): string
    {
        $rootArray = $this->getNodeJson($this->getRoot($parentNodeCategory));

        return $this->jsonEncoder->encode($rootArray['children'] ?? []);
    }

    /**
     * Convert a tree node to JSON-serializable array data.
     *
     * @param Node $node
     * @param int $level
     * @return array
     */
    public function getNodeJson(Node $node, int $level = 0): array
    {
        $item = [];
        $item['text'] = $this->buildNodeName($node);
        $item['id'] = (int) $node->getId();
        $item['path'] = (string) $node->getData('path');
        $item['a_attr'] = [
            'class' => (int) $node->getData('status') === 1 ? 'active-category' : 'not-active-category',
        ];
        $isRoot = (int) $node->getId() === Category::TREE_ROOT_ID;
        $allowMove = !$isRoot;
        $item['allowDrop'] = $allowMove;
        $item['allowDrag'] = $allowMove;
        if ($isRoot) {
            $item['state'] = ['disabled' => true];
        }

        if ((int) $node->getData('children_count') > 0) {
            $item['children'] = [];
        }

        $isParent = $this->isParentSelectedCategory($node);

        if ($node->hasChildren()) {
            $item['children'] = [];
            if (!($this->getUseAjax() && (int) $node->getData('level') > 1 && !$isParent)) {
                foreach ($node->getChildren() as $child) {
                    $item['children'][] = $this->getNodeJson($child, $level + 1);
                }
            }
        }

        if ($isParent || (int) $node->getData('level') < 1) {
            $item['expanded'] = true;
        }

        return $item;
    }

    /**
     * Build display label for a category tree node.
     *
     * @param Node $node
     * @return string
     */
    public function buildNodeName(Node $node): string
    {
        if ((int) $node->getId() === Category::TREE_ROOT_ID) {
            return (string) __('Blog Categories');
        }

        return $this->escapeHtml((string) $node->getData('name')) . ' (ID: ' . (int) $node->getId() . ')';
    }

    /**
     * Whether the node is on the path of the selected category.
     *
     * @param Node $node
     * @return bool
     */
    private function isParentSelectedCategory(Node $node): bool
    {
        $category = $this->getCategory();
        if (!$category || !$category->getId()) {
            return false;
        }

        return in_array((int) $node->getId(), $category->getPathIds(), true);
    }

    /**
     * Whether subcategories can be added to the current selection.
     *
     * @return bool
     */
    public function canAddSubCategory(): bool
    {
        return true;
    }

    /**
     * Get secure HTML renderer for admin templates.
     *
     * @return SecureHtmlRenderer
     */
    public function getSecureRenderer(): SecureHtmlRenderer
    {
        return $this->secureRenderer;
    }
}
