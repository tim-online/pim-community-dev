<?php

declare(strict_types=1);

namespace Pim\Component\Enrich\CategoryTree;

use Akeneo\Channel\Component\Model\LocaleInterface;
use Akeneo\UserManagement\Component\Model\UserInterface;

/**
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ListChildrenCategoriesWithCount
{
    /** @var int */
    private $childrenCategoryIdToExpand;

    /** @var int */
    private $categoryIdSelectedAsFilter;

    /** @var bool */
    private $countIncludingSubCategories;

    /** @var UserInterface */
    private $user;

    /** @var LocaleInterface */
    private $translationLocale;

    /**
     * @param int             $childrenCategoryIdToExpand
     * @param int             $categoryIdSelectedAsFilter
     * @param bool            $countByIncludingSubCategories
     * @param UserInterface   $user
     * @param LocaleInterface $translationLocale
     */
    public function __construct(
        int $childrenCategoryIdToExpand,
        int $categoryIdSelectedAsFilter,
        bool $countByIncludingSubCategories,
        UserInterface $user,
        LocaleInterface $translationLocale
    ) {
        $this->childrenCategoryIdToExpand = $childrenCategoryIdToExpand;
        $this->categoryIdSelectedAsFilter = $categoryIdSelectedAsFilter;
        $this->countIncludingSubCategories = $countByIncludingSubCategories;
        $this->user = $user;
        $this->translationLocale = $translationLocale;
    }

    /**
     * The category to display is the category that is choosed by the user to be expanded.
     *
     * Do note that the user can expand a category without selecting it as a filter.
     * Therefore, the category to expand can be different from the selected category.
     *
     * @return int
     */
    public function childrenCategoryIdToExpand(): int
    {
        return $this->childrenCategoryIdToExpand;
    }

    /**
     * This category is the category that is selected by the user to filter the product grid.
     * It is useful when:
     *  - the user display the tree
     *  - select a category as filter
     *  - go on another page
     *  - the user go back ont the page to display the tree
     *
     * The tree has to be displayed with the category selected as filter, in order to not loose filters when browsing the application.
     *
     * So, we have to return all the children recursively until this selected category.
     * The correct solution would be to not reload entirely the tree on the front-end part and keep a state of it.
     *
     * @return int
     */
    public function categoryIdSelectedAsFilter(): int
    {
        return $this->categoryIdSelectedAsFilter;
    }

    /**
     * @return bool
     */
    public function countIncludingSubCategories(): bool
    {
        return $this->countIncludingSubCategories;
    }

    /**
     * @return UserInterface
     */
    public function user(): UserInterface
    {
        return $this->user;
    }

    /**
     * @return LocaleInterface
     */
    public function translationLocale(): LocaleInterface
    {
        return $this->translationLocale;
    }
}
