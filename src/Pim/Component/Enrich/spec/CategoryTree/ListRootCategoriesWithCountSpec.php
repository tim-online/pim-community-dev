<?php

namespace spec\Pim\Component\Enrich\CategoryTree;

use Akeneo\Channel\Component\Model\LocaleInterface;
use Akeneo\UserManagement\Component\Model\UserInterface;
use PhpSpec\ObjectBehavior;
use Pim\Component\Enrich\CategoryTree\ListRootCategoriesWithCount;

class ListRootCategoriesWithCountSpec extends ObjectBehavior
{
    function let(UserInterface $user, LocaleInterface $locale)
    {
        $this->beConstructedWith(1, true, $user, $locale);
    }

    function it_is_a_query()
    {
        $this->shouldHaveType(ListRootCategoriesWithCount::class);
    }

    function it_has_the_category_id_of_the_category_selected_as_filter_in_the_product_datagrid()
    {
        $this->categoryIdSelectedAsFilter()->shouldReturn(1);
    }

    function it_counts_including_sub_categories()
    {
        $this->countIncludingSubCategories()->shouldReturn(true);
    }

    function it_has_the_user_used_to_apply_permission($user)
    {
        $this->user()->shouldReturn($user);
    }

    function it_has_the_locale_to_translate_the_label_of_the_categories($locale)
    {
        $this->translationLocale()->shouldReturn($locale);
    }
}
