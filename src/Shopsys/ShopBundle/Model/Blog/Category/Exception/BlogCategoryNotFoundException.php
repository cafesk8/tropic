<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Category\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlogCategoryNotFoundException extends NotFoundHttpException implements BlogCategoryException
{
}
