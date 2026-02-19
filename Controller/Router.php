<?php
declare(strict_types=1);

namespace Venbhas\Article\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Venbhas\Article\Model\ArticleFactory;
use Venbhas\Article\Model\CategoryFactory;
use Venbhas\Article\Model\Config;
use Venbhas\Article\Model\ResourceModel\Article as ArticleResource;
use Venbhas\Article\Model\ResourceModel\Category as CategoryResource;

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
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ActionFactory $actionFactory,
        ArticleFactory $articleFactory,
        ArticleResource $articleResource,
        CategoryFactory $categoryFactory,
        CategoryResource $categoryResource,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->actionFactory = $actionFactory;
        $this->articleFactory = $articleFactory;
        $this->articleResource = $articleResource;
        $this->categoryFactory = $categoryFactory;
        $this->categoryResource = $categoryResource;
        $this->config = $config;
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
        if (!$this->config->isModuleEnabled()) {
            return null;
        }

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

        // Forward article/comment/post to Article\Controller\Article\Comment\Post (flag avoids loop).
        if ($pathParts[0] === 'comment') {
            $actionName = $pathParts[1] ?? 'post';
            $request->setParam(self::ROUTER_FORWARDED_FLAG, true);
            $request->setModuleName('article')
                ->setControllerName('article_comment')
                ->setActionName($actionName);
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
        if (!$article->getId() || !$article->getData('is_active')) {
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
