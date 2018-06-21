<?php

namespace Pim\Bundle\EnrichBundle\CategoryTree\Controller;

use Akeneo\UserManagement\Bundle\Context\UserContext;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Pim\Component\Enrich\CategoryTree\ListChildrenCategoriesWithCount;
use Pim\Component\Enrich\CategoryTree\ListChildrenCategoriesWithCountHandler;
use Pim\Component\Enrich\CategoryTree\ListRootCategoriesWithCount;
use Pim\Component\Enrich\CategoryTree\ListRootCategoriesWithCountHandler;
use Pim\Component\Enrich\CategoryTree\Normalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Controller to list the categories in the product grid.
 *
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductGridCategoryTreeController
{
    /** @var ListRootCategoriesWithCountHandler */
    protected $listRootCategoriesWithCount;

    /** @var ListChildrenCategoriesWithCountHandler */
    protected $listChildrenCategoriesWithCount;

    /** @var Normalizer\RootCategory */
    protected $rootCategoryNormalizer;

    /** @var Normalizer\ChildCategory */
    protected $childCategoryNormalizer;

    /** @var UserContext */
    protected $userContext;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ListRootCategoriesWithCountHandler     $listRootCategoriesWithCount
     * @param ListChildrenCategoriesWithCountHandler $listChildrenCategoriesWithCount
     * @param Normalizer\RootCategory                $rootCategoryNormalizer
     * @param Normalizer\ChildCategory               $childCategoryNormalizer
     * @param UserContext                            $userContext
     * @param SecurityFacade                         $securityFacade
     */
    public function __construct(
        ListRootCategoriesWithCountHandler $listRootCategoriesWithCount,
        ListChildrenCategoriesWithCountHandler $listChildrenCategoriesWithCount,
        Normalizer\RootCategory $rootCategoryNormalizer,
        Normalizer\ChildCategory $childCategoryNormalizer,
        UserContext $userContext,
        SecurityFacade $securityFacade
    ) {
        $this->listRootCategoriesWithCount = $listRootCategoriesWithCount;
        $this->listChildrenCategoriesWithCount = $listChildrenCategoriesWithCount;
        $this->rootCategoryNormalizer = $rootCategoryNormalizer;
        $this->childCategoryNormalizer = $childCategoryNormalizer;
        $this->userContext = $userContext;
        $this->securityFacade = $securityFacade;
    }

    /**
     * The select_node_id request parameter
     * allows to send back the tree where the node belongs with a selected  attribute
     *
     * @param Request $request
     *
     * @throws AccessDeniedException
     *
     * @return array
     */
    public function listTreeAction(Request $request)
    {
        if (false === $this->securityFacade->isGranted('pim_enrich_product_category_list')) {
            throw new AccessDeniedException();
        }

        $user = $this->userContext->getUser();
        $translationLocale = $this->userContext->getCurrentLocale();

        $parameters = new ListRootCategoriesWithCount(
            $request->query->getInt('select_node_id', -1),
            $request->query->getBoolean('include_sub', false),
            $user,
            $translationLocale
        );
        $rootCategories = $this->listRootCategoriesWithCount->list($parameters);
        $normalizedData = $this->rootCategoryNormalizer->normalizeList($rootCategories);

        return new JsonResponse($normalizedData);
    }

    /**
     * List children of a category.
     *
     * The category to expand is provided via its id ('id' request parameter).
     * The category selected as filter is given by 'select_node_id' request parameter.
     *
     * If the category selected as filter is a direct child of the category to expand, the tree
     * is expanded until the category selected as filter is found among the children.
     *
     * @param Request $request
     *
     * @throws AccessDeniedException
     *
     * @return Response
     */
    public function listChildrenAction(Request $request)
    {
        if (false === $this->securityFacade->isGranted('pim_enrich_product_category_list')) {
            throw new AccessDeniedException();
        }

        $user = $this->userContext->getUser();
        $translationLocale = $this->userContext->getCurrentLocale();

        $parameters = new ListChildrenCategoriesWithCount(
            $request->query->getInt('id', -1),
            $request->query->getInt('select_node_id', -1),
            $request->query->getBoolean('include_sub', false),
            $user,
            $translationLocale
        );

        $categories = $this->listChildrenCategoriesWithCount->list($parameters);
        $normalizedData = $this->childCategoryNormalizer->normalizeList($categories);

        return new JsonResponse($normalizedData);
    }
}
