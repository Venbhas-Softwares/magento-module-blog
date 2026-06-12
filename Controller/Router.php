<?php
declare(strict_types=1);

namespace Venbhas\Blog\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Blog\Model\ArticleFactory;
use Venbhas\Blog\Model\CategoryFactory;
use Venbhas\Blog\Model\Config;
use Venbhas\Blog\Model\Config\ModuleEnabledGuard;
use Venbhas\Blog\Model\ResourceModel\Article as ArticleResource;
use Venbhas\Blog\Model\ResourceModel\Category as CategoryResource;

class Router implements RouterInterface
{
    /** @var ActionFactory */
    private $actionFactory;

    /** @var ArticleFactory */
    private $articleFactory;

    /** @var ArticleResource */
    private $articleResource;

    /** @var CategoryFactory */
    private $categoryFactory;

    /** @var CategoryResource */
    private $categoryResource;

    /** @var Config */
    private $config;

    /** @var ModuleEnabledGuard */
    private $moduleEnabledGuard;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** Request param set before Forward to avoid re-matching and 100-iteration loop */
    private const ROUTER_FORWARDED_FLAG = '__article_router_forwarded';

    /**
     * Constructor.
     *
     * @param ActionFactory $actionFactory
     * @param ArticleFactory $articleFactory
     * @param ArticleResource $articleResource
     * @param CategoryFactory $categoryFactory
     * @param CategoryResource $categoryResource
     * @param Config $config
     * @param ModuleEnabledGuard $moduleEnabledGuard
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ActionFactory $actionFactory,
        ArticleFactory $articleFactory,
        ArticleResource $articleResource,
        CategoryFactory $categoryFactory,
        CategoryResource $categoryResource,
        Config $config,
        ModuleEnabledGuard $moduleEnabledGuard,
        StoreManagerInterface $storeManager
    ) {
        $this->actionFactory = $actionFactory;
        $this->articleFactory = $articleFactory;
        $this->articleResource = $articleResource;
        $this->categoryFactory = $categoryFactory;
        $this->categoryResource = $categoryResource;
        $this->config = $config;
        $this->moduleEnabledGuard = $moduleEnabledGuard;
        $this->storeManager = $storeManager;
    }

    /**
     * Match request using store config: Article List URL Key and Category List URL Key.
     *
     * Single segment: list routes. Multi-segment: Article List URL Key + /category/... or + /post-url.
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ActionInterface|null
     */
    public function match(RequestInterface $request)
    {
        if ($request->getParam(self::ROUTER_FORWARDED_FLAG)) {
            return null;
        }

        $path = trim((string) $request->getPathInfo(), '/');
        $pathParts = $path !== '' ? explode('/', $path) : [];
        $first = $pathParts[0] ?? '';

        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
        } catch (\Throwable $e) {
            $storeId = null;
        }

        if (!$this->moduleEnabledGuard->isEnabled($storeId)) {
            return null;
        }

        $articleListRoute = $this->config->getArticleListRoute($storeId);
        $categoryListRoute = $this->config->getCategoryListRoute($storeId);

        // Single-segment path: match configured list routes (from store config)
        if (count($pathParts) === 1) {
            if ($first === $articleListRoute) {
                $request->setParam(self::ROUTER_FORWARDED_FLAG, true);
                $request->setModuleName('article')
                    ->setControllerName('index')
                    ->setActionName('index');
                return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
            }
            if ($first === $categoryListRoute) {
                $request->setParam(self::ROUTER_FORWARDED_FLAG, true);
                $request->setModuleName('article')
                    ->setControllerName('category')
                    ->setActionName('index');
                return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
            }
        }

        // Multi-segment: first segment must match Article List URL Key (e.g. /article/category/foo, /article/post-url)
        if ($first !== $articleListRoute) {
            return null;
        }

        array_shift($pathParts);

        // Comment POST uses standard route article/comment/post (see routes.xml).
        if (($pathParts[0] ?? '') === 'comment') {
            return null;
        }

        if (empty($pathParts)) {
            $request->setParam(self::ROUTER_FORWARDED_FLAG, true);
            $request->setModuleName('article')->setControllerName('index')->setActionName('index');
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        if ($pathParts[0] === 'search' && count($pathParts) === 1) {
            $request->setParam(self::ROUTER_FORWARDED_FLAG, true);
            $request->setModuleName('article')->setControllerName('search')->setActionName('index');
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        if ($pathParts[0] === 'category') {
            array_shift($pathParts);
            $urlKey = implode('/', $pathParts);
            if ($urlKey === '') {
                return null;
            }
           
            $category = $this->categoryFactory->create();
            $this->categoryResource->load($category, $urlKey, 'url_key');
            if (!$category->getId() || !$category->getData('status')) {
                
                return null;
            }
            $request->setParam(self::ROUTER_FORWARDED_FLAG, true);
            $request->setModuleName('article')
                ->setControllerName('category')
                ->setActionName('view')
                ->setParam('url_key', $urlKey)
                ->setParam('category_id', $category->getId());
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        $urlKey = implode('/', $pathParts);
        $article = $this->articleFactory->create();
        $this->articleResource->load($article, $urlKey, 'url_key');
        if (!$article->getId() || (int) $article->getData('status') !== 1) {
            return null;
        }
        $request->setParam(self::ROUTER_FORWARDED_FLAG, true);
        $request->setModuleName('article')
            ->setControllerName('article')
            ->setActionName('view')
            ->setParam('url_key', $urlKey)
            ->setParam('article_id', $article->getId());
        return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
    }
}
