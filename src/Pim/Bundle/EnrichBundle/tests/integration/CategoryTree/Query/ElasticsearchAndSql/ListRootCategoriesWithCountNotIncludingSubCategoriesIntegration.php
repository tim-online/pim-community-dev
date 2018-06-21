<?php

declare(strict_types=1);

namespace Pim\Bundle\EnrichBundle\tests\integration\CategoryTree\Query\ElasticsearchAndSql;

use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\Integration\TestCase;
use PHPUnit\Framework\Assert;
use Pim\Component\Enrich\CategoryTree\ReadModel\RootCategory;

class ListRootCategoriesWithCountNotIncludingSubCategoriesIntegration extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $fixturesLoader = new CategoryTreeFixturesLoader($this->testKernel->getContainer());
        $fixturesLoader->givenTheCategoryTrees([
            'tree_1' => [
                'tree_1_child_1_level_1' => [
                    'tree_1_child_1_level_2' => [
                        'tree_1_child_1_level_3' => []
                    ],
                    'tree_1_child_2_level_2' => [],
                ],
                'tree_1_child_2_level_1' => [],
                'tree_1_child_3_level_1' => [],
            ],
            'tree_2' => [
                'tree_2_child_1_level_1' => [
                    'tree_2_child_1_level_2' => [],
                    'tree_2_child_2_level_2' => [],
                    'tree_2_child_2_level_3' => [],
                ]
            ]
        ]);
        $fixturesLoader->givenTheProductsWithCategories([
            'product_1' => ['tree_1_child_1_level_1', 'tree_1_child_1_level_2'],
            'product_2' => ['tree_1_child_1_level_3', 'tree_2'],
            'product_3' => ['tree_2_child_2_level_3', 'tree_1_child_2_level_1']
        ]);
    }

    /**
     * @test
     */
    public function listRootCategoriesWithUserPermissionsApplied()
    {
        $query = $this->get('pim_enrich.category_tree.query.list_root_categories_with_count_not_including_sub_categories');
        $locale = $this->get('pim_catalog.repository.locale')->findOneByIdentifier('en_US');
        $user = $this->get('pim_user.repository.user')->findOneByIdentifier('admin');
        $rootCategoryIdToExpand = $this->get('pim_catalog.repository.category')->findOneByIdentifier('tree_1');

        $result = $query->list($locale, $user, $rootCategoryIdToExpand->getId());

        $expectedCategories = [
            new RootCategory(1, 'master', 'Master catalog', 0, false),
            new RootCategory(1, 'tree_1', 'Tree_1', 0, true),
            new RootCategory(3, 'tree_2', 'Tree_2', 1, false),
        ];

        $this->assertSameListOfRootCategories($expectedCategories, $result);
    }

    /**
     * @param RootCategory[] $expectedCategories
     * @param RootCategory[] $categories
     */
    private function assertSameListOfRootCategories(array $expectedCategories, array $categories): void
    {
        $i = 0;
        foreach ($expectedCategories as $expectedCategory) {
            $this->assertSameRootCategory($expectedCategory, $categories[$i]);
            $i++;
        }
    }

    /**
     * @param RootCategory $expectedCategory
     * @param RootCategory $category
     */
    private function assertSameRootCategory(RootCategory $expectedCategory, RootCategory $category): void
    {
        Assert::assertEquals($expectedCategory->code(), $category->code());
        Assert::assertEquals($expectedCategory->selected(), $category->selected());
        Assert::assertEquals($expectedCategory->label(), $category->label());
        Assert::assertEquals($expectedCategory->numberProductsInCategory(), $category->numberProductsInCategory());
    }

    /**
     * @inheritDoc
     */
    protected function getConfiguration(): Configuration
    {
        return $this->catalog->useMinimalCatalog();
    }
}

