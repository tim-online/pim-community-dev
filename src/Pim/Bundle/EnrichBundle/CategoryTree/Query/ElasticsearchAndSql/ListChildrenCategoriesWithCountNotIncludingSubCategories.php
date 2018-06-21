<?php

declare(strict_types=1);

namespace Pim\Bundle\EnrichBundle\CategoryTree\Query\ElasticsearchAndSql;

use Akeneo\Channel\Component\Model\LocaleInterface;
use Akeneo\Tool\Bundle\ElasticsearchBundle\Client;
use Akeneo\UserManagement\Component\Model\UserInterface;
use Doctrine\DBAL\Connection;
use Pim\Component\Enrich\CategoryTree\Query\ListChildrenCategoriesWithCount;
use Pim\Component\Enrich\CategoryTree\ReadModel\ChildCategory;

/**
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ListChildrenCategoriesWithCountNotIncludingSubCategories implements ListChildrenCategoriesWithCount
{
    /** @var Connection */
    private $connection;

    /** @var Client */
    private $client;

    /** @var string */
    private $indexType;

    /**
     * @param Connection $connection
     * @param Client     $client
     * @param string     $indexType
     */
    public function __construct(Connection $connection, Client $client, string $indexType)
    {
        $this->connection = $connection;
        $this->client = $client;
        $this->indexType = $indexType;
    }

    /**
     * {@inheritdoc}
     */
    public function list(
        LocaleInterface $translationLocale,
        UserInterface $user,
        int $categoryIdToExpand,
        ?int $categoryIdSelectedAsFilter
    ): array {
        $categoryIdsInPath = null !== $categoryIdSelectedAsFilter ?
            $this->fetchCategoriesBetween($categoryIdToExpand, $categoryIdSelectedAsFilter) : [$categoryIdToExpand];

        return $this->getRecursivelyCategories($categoryIdsInPath, $translationLocale, $categoryIdSelectedAsFilter);
    }

    /**
     * Get recursively a tree until the selected category choosed as filter.
     * If category ids in path to expand are [A, B, C], it means you want to list
     * all children of A until the category C.
     *
     *     B
     *     |--C
     *     |  |
     *     |  |
     *     |  |
     *     |  |
     *     |  C'
     *     |
     *     B'
     *
     *
     * It executes 1 SQL query and 1 ES query per level of depth of the category tree.
     * In the above example:
     * - it execute two requests(SQL +ES) to get children of A
     * - then, two requests to get children of B
     *
     * @param array           $categoryIdsInPath
     * @param LocaleInterface $translationLocale
     * @param int|null        $categoryIdToFilterWith
     *
     * @return ChildCategory[]
     */
    private function getRecursivelyCategories(
        array $categoryIdsInPath,
        LocaleInterface $translationLocale,
        ?int $categoryIdToFilterWith
    ) : array {
        $parentCategoryId = array_shift($categoryIdsInPath);
        $subchildCategoryId = $categoryIdsInPath[0] ?? null;

        $categoriesWithoutCount = $this->fetchChildrenCategories($parentCategoryId, $translationLocale);
        $categoriesWithCount = $this->countProductInCategories($categoriesWithoutCount);


        $categories = [];
        foreach ($categoriesWithCount as $category) {
            $childrenCategoriesToExpand = null !== $subchildCategoryId && $subchildCategoryId === (int) $category['child_id'] ?
                $this->getRecursivelyCategories($categoryIdsInPath, $translationLocale, $categoryIdToFilterWith): [];

            $isUsedAsFilter = null !== $categoryIdToFilterWith ? (int) $category['child_id'] === $categoryIdToFilterWith: false;

            $categories[] = new ChildCategory(
                (int) $category['child_id'],
                $category['child_code'],
                $category['label'],
                $isUsedAsFilter,
                $category['is_leaf'],
                $category['count'],
                $childrenCategoriesToExpand
            );
        }

        return $categories;
    }

    /**
     * @param int             $parentCategoryId
     * @param LocaleInterface $translationLocale
     *
     * @return array
     * [
     *     [
     *         'child_id' => 1,
     *         'child_code' => 'code',
     *         'is_leaf' = true,
     *         'label' => 'label'
     *     ]
     * ]
     */
    private function fetchChildrenCategories(
        int $parentCategoryId,
        LocaleInterface $translationLocale
    ): array {
        $this->connection->exec('SET SESSION group_concat_max_len = 1000000')->execute();

        $sql = <<<SQL
            SELECT 
                child.id as child_id,
                child.code as child_code,
                CASE 
                    WHEN child.lft + 1 = child.rgt THEN 1
                    ELSE 0
                END AS is_leaf,
                COALESCE(ct.label, child.code) as label
            FROM 
                pim_catalog_category child
                LEFT JOIN pim_catalog_category_translation ct ON ct.foreign_key = child.id AND ct.locale = 'en_US'
            WHERE 
                child.parent_id = :parent_category_id;
SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            [
                'parent_category_id' => $parentCategoryId,
                'locale' => $translationLocale->getCode()
            ]
        )->fetchAll();

        $categories = [];
        foreach ($rows as $row) {
            $row['is_leaf'] = 1 === (int) $row['is_leaf'];
            $categories[] = $row;
        }

        return $categories;
    }

    /**
     * @param int array $categoriesWithoutCount
     *
     * [
     *     [
     *         'child_id' => 1,
     *         'child_code' => 'code',
     *         'is_leaf = true,
     *         'label' => 'label'
     *     ]
     * ]
     *
     * @return array
     * [
     *     [
     *         'child_id' => 1,
     *         'child_code' => 'code',
     *         'label' => 'label',
     *         'is_leaf = true,
     *         'count' => 1
     *     ]
     * ]
     */
    private function countProductInCategories(array $categoriesWithoutCount): array
    {
        if (empty($categoriesWithoutCount)) {
            return [];
        }

        $body = [];
        foreach ($categoriesWithoutCount as $category) {
            $body[] = [];
            $body[] = [
                'size' => 0,
                'query' => [
                    'constant_score' => [
                        'filter' => [
                            'terms' => [
                                'categories' => [$category['child_code']]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $rows = $this->client->msearch($this->indexType, $body);

        $categoriesWithCount = [];
        $index = 0;
        foreach ($categoriesWithoutCount as $category) {
            $category['count'] = $rows['responses'][$index]['hits']['total'] ?? -1;
            $categoriesWithCount[] = $category;
            $index++;
        }

        return $categoriesWithCount;
    }


    /**
     * Returns all category ids between the category to expand (parent) and the category to filter with (subchild).
     * Example:
     *
     *          A
     *         / \
     *        B   C
     *       /     \
     *      D      E
     *
     * If category to expand is A and category to filter is D, it returns [A, B, D]
     *
     *
     * @param int $fromCategoryId
     * @param int $toCategoryId
     *
     * @return string[]
     */
    private function fetchCategoriesBetween(int $fromCategoryId, int $toCategoryId)
    {
        $sql = <<<SQL
            SELECT 
                category_path.id
            FROM 
                pim_catalog_category parent
                JOIN pim_catalog_category category_path on category_path.lft BETWEEN parent.lft AND parent.rgt AND parent.root = category_path.root
                JOIN pim_catalog_category subchild on category_path.lft <= subchild.lft AND category_path.rgt >= subchild.lft AND parent.root = subchild.root
            WHERE 
                parent.id = :category_to_expand 
                AND subchild.id = :category_to_filter_with
            ORDER BY 
                category_path.lft
SQL;

        $rows = $this->connection->executeQuery(
            $sql,
            [
                'category_to_expand' => $fromCategoryId,
                'category_to_filter_with' => $toCategoryId,
            ]
        )->fetchAll();

        $codes = array_map(function ($row) {
            return (int) $row['id'];
        }, $rows);

        return $codes;
    }
}
