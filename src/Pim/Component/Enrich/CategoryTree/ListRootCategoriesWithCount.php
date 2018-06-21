<?php

declare(strict_types=1);

namespace Pim\Component\Enrich\CategoryTree;

use Akeneo\Channel\Component\Model\LocaleInterface;
use Akeneo\UserManagement\Component\Model\UserInterface;

/**
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ListRootCategoriesWithCount
{
    /** @var int */
    private $categoryIdSelectedAsFilter;

    /** @var bool */
    private $countIncludingSubCategories;

    /** @var  UserInterface */
    private $user;

    /** @var LocaleInterface */
    private $translationLocale;

    /**
     * @param int             $categoryIdSelectedAsFilter
     * @param bool            $countIncludingSubCategories
     * @param UserInterface   $user
     * @param LocaleInterface $translationLocale
     */
    public function __construct(
        int $categoryIdSelectedAsFilter,
        bool $countIncludingSubCategories,
        UserInterface $user,
        LocaleInterface $translationLocale
    ) {
        $this->categoryIdSelectedAsFilter = $categoryIdSelectedAsFilter;
        $this->countIncludingSubCategories = $countIncludingSubCategories;
        $this->user = $user;
        $this->translationLocale = $translationLocale;
    }

    /**
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
