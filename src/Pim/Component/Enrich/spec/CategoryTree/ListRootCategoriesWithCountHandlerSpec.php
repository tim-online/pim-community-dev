<?php

namespace spec\Pim\Component\Enrich\CategoryTree;

use Akeneo\Channel\Component\Model\LocaleInterface;
use Akeneo\Tool\Component\Classification\Repository\CategoryRepositoryInterface;
use Akeneo\UserManagement\Bundle\Context\UserContext;
use Akeneo\UserManagement\Component\Model\UserInterface;
use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Model\CategoryInterface;
use Pim\Component\Enrich\CategoryTree\ListRootCategoriesWithCount;
use Pim\Component\Enrich\CategoryTree\ListRootCategoriesWithCountHandler;
use Pim\Component\Enrich\CategoryTree\Query;
use Pim\Component\Enrich\CategoryTree\ReadModel\RootCategory;

class ListRootCategoriesWithCountHandlerSpec extends ObjectBehavior
{
    function let(
        CategoryRepositoryInterface $categoryRepository,
        UserContext $userContext,
        Query\ListRootCategoriesWithCount $listIncludingSubCategories,
        Query\ListRootCategoriesWithCount $listNotIncludingSubCategories
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
        $this->shouldHaveType(ListRootCategoriesWithCountHandler::class);
    }

    function it_list_root_categories_with_count_including_sub_categories(
        $categoryRepository,
        $listIncludingSubCategories,
        UserInterface $user,
        LocaleInterface $locale,
        CategoryInterface $categoryToFilterWith
    ) {
        $categoryRepository->find(2)->willReturn($categoryToFilterWith);
        $categoryToFilterWith->getRoot()->willReturn(1);

        $listIncludingSubCategories->list($locale, $user, 1, null)->willReturn([
            new RootCategory(1, 'code', 'label', 10, true)
        ]);

        $query = new ListRootCategoriesWithCount(2, true, $user->getWrappedObject(), $locale->getWrappedObject());
        $this->list($query)->shouldBeLike([
            new RootCategory(1, 'code', 'label', 10, true)
        ]);
    }

    function it_list_root_categories_with_count_not_including_sub_categories(
        $categoryRepository,
        $listNotIncludingSubCategories,
        UserInterface $user,
        LocaleInterface $locale,
        CategoryInterface $categoryToFilterWith
    ) {
        $categoryRepository->find(2)->willReturn($categoryToFilterWith);
        $categoryToFilterWith->getRoot()->willReturn(1);

        $listNotIncludingSubCategories->list($locale, $user, 1, null)->willReturn([
            new RootCategory(1, 'code', 'label', 10, true)
        ]);

        $query = new ListRootCategoriesWithCount(2, false, $user->getWrappedObject(), $locale->getWrappedObject());
        $this->list($query)->shouldBeLike([
            new RootCategory(1, 'code', 'label', 10, true)
        ]);
    }

    function it_list_root_categories_by_selecting_user_product_category_tree_when_no_category_selected_as_filter(
        $userContext,
        $listNotIncludingSubCategories,
        UserInterface $user,
        LocaleInterface $locale,
        CategoryInterface $treeToExpand
    ) {
        $userContext->getUserProductCategoryTree()->willReturn($treeToExpand);
        $treeToExpand->getRoot()->willReturn(1);

        $listNotIncludingSubCategories->list($locale, $user, 1, null)->willReturn([
            new RootCategory(1, 'code', 'label', 10, true)
        ]);

        $query = new ListRootCategoriesWithCount(-1, false, $user->getWrappedObject(), $locale->getWrappedObject());
        $this->list($query)->shouldBeLike([
            new RootCategory(1, 'code', 'label', 10, true)
        ]);
    }
}
