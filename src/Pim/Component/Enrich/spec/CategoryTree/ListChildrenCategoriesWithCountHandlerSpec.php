<?php

namespace spec\Pim\Component\Enrich\CategoryTree;

use Akeneo\Channel\Component\Model\LocaleInterface;
use Akeneo\Tool\Component\Classification\Repository\CategoryRepositoryInterface;
use Akeneo\UserManagement\Bundle\Context\UserContext;
use Akeneo\UserManagement\Component\Model\UserInterface;
use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Model\CategoryInterface;
use Pim\Component\Enrich\CategoryTree\ListChildrenCategoriesWithCount;
use Pim\Component\Enrich\CategoryTree\ListChildrenCategoriesWithCountHandler;
use Pim\Component\Enrich\CategoryTree\Query;
use Pim\Component\Enrich\CategoryTree\ReadModel\ChildCategory;

class ListChildrenCategoriesWithCountHandlerSpec extends ObjectBehavior
{
    function let(
        CategoryRepositoryInterface $categoryRepository,
        UserContext $userContext,
        Query\ListChildrenCategoriesWithCount $listIncludingSubCategories,
        Query\ListChildrenCategoriesWithCount $listNotIncludingSubCategories
    ) {
        $this->beConstructedWith(
            $categoryRepository,
            $userContext,
            $listIncludingSubCategories,
            $listNotIncludingSubCategories
        );
    }

    function it_is_an_handler()
    {
        $this->shouldHaveType(ListChildrenCategoriesWithCountHandler::class);
    }

    function it_list_children_categories_with_count_including_sub_categories(
        $categoryRepository,
        $listIncludingSubCategories,
        UserInterface $user,
        LocaleInterface $locale,
        CategoryInterface $categoryToExpand
    ) {
        $categoryRepository->find(1)->willReturn($categoryToExpand);
        $categoryToExpand->getId()->willReturn(1);

        $listIncludingSubCategories->list($locale, $user, 1, null)->willReturn([
            new ChildCategory(1, 'code', 'label', true, true, 10, [])
        ]);

        $query = new ListChildrenCategoriesWithCount(1, -1, true, $user->getWrappedObject(), $locale->getWrappedObject());
        $this->list($query)->shouldBeLike([
            new ChildCategory(1, 'code', 'label', true, true, 10, [])
        ]);
    }

    function it_list_children_categories_with_count_not_including_sub_categories(
        $categoryRepository,
        $listNotIncludingSubCategories,
        UserInterface $user,
        LocaleInterface $locale,
        CategoryInterface $categoryToExpand
    ) {
        $categoryRepository->find(1)->willReturn($categoryToExpand);
        $categoryToExpand->getId()->willReturn(1);

        $listNotIncludingSubCategories->list($locale, $user, 1, null)->willReturn([
            new ChildCategory(1, 'code', 'label', true, true, 10, [])
        ]);

        $query = new ListChildrenCategoriesWithCount(1, -1, false, $user->getWrappedObject(), $locale->getWrappedObject());
        $this->list($query)->shouldBeLike([
            new ChildCategory(1, 'code', 'label', true, true, 10, [])
        ]);
    }

    function it_list_children_categories_of_user_product_category_tree_when_no_category_selected_as_filter(
        $userContext,
        $listNotIncludingSubCategories,
        UserInterface $user,
        LocaleInterface $locale,
        CategoryInterface $treeToExpand
    ) {
        $userContext->getUserProductCategoryTree()->willReturn($treeToExpand);
        $treeToExpand->getId()->willReturn(1);

        $listNotIncludingSubCategories->list($locale, $user, 1, null)->willReturn([
            new ChildCategory(1, 'code', 'label', true, true, 10, [])
        ]);

        $query = new ListChildrenCategoriesWithCount(-1, -1, false, $user->getWrappedObject(), $locale->getWrappedObject());
        $this->list($query)->shouldBeLike([
            new ChildCategory(1, 'code', 'label', true, true, 10, [])
        ]);
    }

    function it_list_children_categories_with_category_selected_as_filter(
        $categoryRepository,
        $listNotIncludingSubCategories,
        UserInterface $user,
        LocaleInterface $locale,
        CategoryInterface $categoryToExpand,
        CategoryInterface $categoryToFilterWith
    ) {
        $categoryRepository->find(1)->willReturn($categoryToExpand);
        $categoryToExpand->getId()->willReturn(1);

        $categoryRepository->isAncestor($categoryToExpand, $categoryToFilterWith)->willReturn(true);
        $categoryRepository->find(3)->willReturn($categoryToFilterWith);
        $categoryToFilterWith->getId()->willReturn(3);

        $listNotIncludingSubCategories->list($locale, $user, 1, 3)->willReturn([
            new ChildCategory(1, 'code', 'label', true, true, 10, [])
        ]);

        $query = new ListChildrenCategoriesWithCount(1, 3, false, $user->getWrappedObject(), $locale->getWrappedObject());
        $this->list($query)->shouldBeLike([
            new ChildCategory(1, 'code', 'label', true, true, 10, [])
        ]);
    }
}
