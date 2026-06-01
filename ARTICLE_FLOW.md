# Venbhas Article 

## 

## 1\) Database schema (tables and relations)

Declared in: `etc/db\_schema.xml`

### A) Categories

Table: `venbhas\_article\_category`

* `category\_id` (PK)
* content + SEO fields: `name`, `url\_key`, `description`, `short\_description`, meta fields, `featured\_image`
* status: `status` (active flag)
* `related\_articles` (text; stores related article ids)

### B) Articles

Table: `venbhas\_article`

* `article\_id` (PK)
* `title`, `url\_key`
* content + SEO fields: `description`, `short\_description`, meta fields, `featured\_image`
* status flags:

  * `is\_active` (1/0)
  * `status` (0=draft, 1=published, 2=archived)
* optional `author`

### C) Article ↔ Category relation (many-to-many)

Table: `venbhas\_article\_category\_relation`

* `article\_id` → FK to `venbhas\_article.article\_id` (CASCADE)
* `category\_id` → FK to `venbhas\_article\_category.category\_id` (CASCADE)

### D) Comments

Table: `venbhas\_article\_comment`

* `comment\_id` (PK)
* `article\_id` → FK to `venbhas\_article.article\_id` (CASCADE)
* `user\_name`, `user\_email`, `comment`, `reply`
* moderation: `status` (0=pending, 1=approved, 2=rejected)

### E) Related products

Table: `venbhas\_article\_related\_products`

* `article\_id` → FK to `venbhas\_article`
* `product\_id` → FK to `catalog\_product\_entity`

Table: `venbhas\_article\_category\_related\_products`

* `category\_id` → FK to `venbhas\_article\_category`
* `product\_id` → FK to `catalog\_product\_entity`





## 2\) Admin (backend) 

## Menu:

* `etc/adminhtml/menu.xml`

  * Content → Article → Articles / Categories / Comments / Configuration

Admin routes:

* `etc/adminhtml/routes.xml` (controller area `adminhtml`)

Admin grids \& forms:

* Data providers + UI component collections are wired in `etc/di.xml`

  * `venbhas\_article\_listing\_data\_source` → `Model/ResourceModel/Article/Grid/Collection`
  * `venbhas\_article\_category\_listing\_data\_source` → `Model/ResourceModel/Category/Grid/Collection`
  * `venbhas\_article\_comment\_listing\_data\_source` → `Model/ResourceModel/Comment/Grid/Collection`

Admin controllers (selected):

* Articles:

  * `Controller/Adminhtml/Article/Index.php`, `Edit.php`, `Save.php`, `Delete.php`
  * mass actions: `MassEnable.php`, `MassDisable.php`, `MassDelete.php`
  * image upload: `Controller/Adminhtml/Article/Upload.php`
* Categories:

  * `Controller/Adminhtml/Category/Index.php`, `Edit.php`, `Save.php`, `Delete.php`, `NewAction.php`
  * mass actions: `MassEnable.php`, `MassDisable.php`, `MassDelete.php`
  * image upload: `Controller/Adminhtml/Category/Upload.php`
* Comments:

  * `Controller/Adminhtml/Comment/Index.php`, `Edit.php`, `Save.php`, `Delete.php`



## 3\) Store configuration 

## System config UI:

* `etc/adminhtml/system.xml` (`Stores → Configuration → Venbhas → Article`)

Defaults:

* `etc/config.xml`

  * enabled: `venbhas\_article/general/enabled`
  * comments: `venbhas\_article/general/comments\_enabled`
  * routes (important):

    * `venbhas\_article/general/article\_list\_route` default `articles`
    * `venbhas\_article/general/category\_list\_route` default `categories`
  * pagination:

    * `venbhas\_article/general/articles\_per\_page`
  * default sort:

    * `venbhas\_article/general/default\_sort\_order`
  * related products slider count:

    * `venbhas\_article/general/related\_products\_limit`
  * default meta robots for category and post:

    * `venbhas\_article/meta\_robots/category`, `venbhas\_article/meta\_robots/post`



## 4\) Frontend routing: 

## The module declares a standard frontName route:

* `etc/frontend/routes.xml` → frontName `article`

But the main user-facing URLs are handled by a **custom router**:

* `Controller/Router.php`
* Enabled by adding it to `Magento\\Framework\\App\\RouterList`:

  * `etc/frontend/di.xml` (sortOrder `25`)

### Routing behavior (high level)

The router reads the store config routes:

* `article\_list\_route` (default `articles`)
* `category\_list\_route` (default `categories`)

Then it matches incoming paths:

#### A) Single segment paths

* `/{articles}` → forwards to:

  * module: `article`, controller: `index`, action: `index`
  * controller: `Controller/Index/Index.php`
* `/{categories}` → forwards to:

  * module: `article`, controller: `category`, action: `index`
  * controller: `Controller/Category/Index.php`

#### B) Multi segment paths (must start with `/{articles}`)

Examples:

* `/{articles}/search` → forward to `Controller/Search/Index.php`
* `/{articles}/comment/post` (or `/comment/<action>`) → forward to comment controller
* `/{articles}/category/<category-url-key...>` → resolve category by `url\_key`, forward to `Controller/Category/View.php`
* `/{articles}/<article-url-key...>` → resolve article by `url\_key`, forward to `Controller/Article/View.php`

Loop protection:

* Router sets an internal request param `\_\_article\_router\_forwarded` so the same request won’t be matched repeatedly.

\---

## 5\) Frontend controllers: 

### Article list

* Controller: `Controller/Index/Index.php` (renders a Page result)
* Layout handle: `view/frontend/layout/article\_index\_index.xml`

  * Sidebar categories block: `Block/Frontend/Category/ListCategory` (`category/leftnav.phtml`)
  * Content block: `Block/Frontend/Article/ListBlock` (`article/list.phtml`)
  * Search form in sidebar: `Block/Frontend/Search` (`search/form.phtml`)

### Article detail

* Controller: `Controller/Article/View.php`

  * loads by `url\_key` or `article\_id`
  * checks `is\_active`
  * registers `current\_article` in registry
  * forwards to `noroute` if not found/inactive
* Layout handle: `view/frontend/layout/article\_article\_view.xml`

  * Main block: `Block/Frontend/Article/View` (`article/view.phtml`)
  * Comments list + form:

    * `Block/Frontend/Article/CommentList` (`article/comment/list.phtml`)
    * `Block/Frontend/Article/CommentForm` (`article/comment/form.phtml`)
  * Related products:

    * `Block/Frontend/RelatedProducts` (`article/related\_products.phtml`)

### Category view

* Controller: `Controller/Category/View.php`

  * loads by `url\_key` or `id`
  * checks `status`
  * registers `current\_article\_category` in registry
* Layout handle: `view/frontend/layout/article\_category\_view.xml`

  * Category view: `Block/Frontend/Category/View` (`category/view.phtml`)
  * Related products: `Block/Frontend/RelatedProducts` (`article/related\_products.phtml`)

### Category list

* Controller: `Controller/Category/Index.php`
* Layout handle: `view/frontend/layout/article\_category\_index.xml`

### Search

* Controller: `Controller/Search/Index.php`
* Layout handle: `view/frontend/layout/article\_search\_index.xml`

  * Search result block: `Block/Frontend/SearchResult` (`search/result.phtml`)

\---

## 6\) Comments: 

## Frontend posting controller:

* `Controller/Article/Comment/Post.php`

Rules:

* must be POST
* checks store config `comments\_enabled` (via `Model/Config`)
* validates required fields:

  * `article\_id`, `user\_name`, `user\_email`, `comment`
* validates email format
* saves a `Comment` row with status **pending**

  * `\\Venbhas\\Blog\\Model\\Comment::STATUS\_PENDING`
* redirects back to the article URL using:

  * store config `article\_list\_route` + `article.url\_key`

Moderation:

* Admin Comments grid and edit/save actions are under `Controller/Adminhtml/Comment/\*`
* Approved/pending/rejected states are stored in `venbhas\_article\_comment.status`





