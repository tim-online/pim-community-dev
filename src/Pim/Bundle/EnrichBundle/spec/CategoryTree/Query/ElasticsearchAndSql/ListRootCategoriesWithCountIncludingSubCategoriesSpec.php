<?php

namespace spec\Pim\Bundle\EnrichBundle\CategoryTree\Query\ElasticsearchAndSql;

use Akeneo\Tool\Bundle\ElasticsearchBundle\Client;
use Doctrine\DBAL\Connection;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\EnrichBundle\CategoryTree\Query\ElasticsearchAndSql\ListRootCategoriesWithCountIncludingSubCategories;
use Pim\Component\Enrich\CategoryTree\Query\ListRootCategoriesWithCount;

class ListRootCategoriesWithCountIncludingSubCategoriesSpec extends ObjectBehavior
{
    function let(Connection $connection, Client $client)
    {
        $this->beConstructedWith($connection, $client, 'index');
    }

    function it_lists_children_categories_with_count()
    {
        $this->shouldImplement(ListRootCategoriesWithCount::class);
    }

    function it_lists_children_categories_with_count_including_sub_categories()
    {
        $this->shouldHaveType(ListRootCategoriesWithCountIncludingSubCategories::class);
    }
}
