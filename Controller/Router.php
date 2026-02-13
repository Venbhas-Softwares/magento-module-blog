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
    public const ROUTE_FRONT_NAME = 'article';

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
     * Match request: configurable article list route, post list route, or legacy /article/... paths.
     */
    public function match(RequestInterface $request)
    {
        if (!$this->config->isModuleEnabled()) {
            return null;
        }

        $path = trim((string) $request->getPathInfo(), '/');
        $pathParts = $path !== '' ? explode('/', $path) : [];
        $first = $pathParts[0] ?? '';

        $storeId = null;
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $articleListRoute = $this->config->getArticleListRoute($storeId);
        $categoryListRoute = $this->config->getCategoryListRoute($storeId);

        // Single-segment path: match configured list routes
        if (count($pathParts) === 1) {
            if ($first === $articleListRoute) {
                $request->setModuleName('article')
                    ->setControllerName('index')
                    ->setActionName('index');
                return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
            }
            if ($first === $categoryListRoute) {
                $request->setModuleName('article')
                    ->setControllerName('category')
                    ->setActionName('index');
                return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
            }
        }

        // Legacy /article and /article/... paths
        if ($first !== self::ROUTE_FRONT_NAME) {
            return null;
        }

        array_shift($pathParts);

        if (empty($pathParts)) {
            $request->setModuleName('article')->setControllerName('index')->setActionName('index');
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
        $request->setModuleName('article')
            ->setControllerName('article')
            ->setActionName('view')
            ->setParam('url_key', $urlKey)
            ->setParam('article_id', $article->getId());
        return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
    }
}
