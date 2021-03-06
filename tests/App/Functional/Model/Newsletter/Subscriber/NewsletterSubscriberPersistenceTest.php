<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Newsletter\Subscriber;

use App\Model\Newsletter\NewsletterSubscriber;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use Tests\App\Test\TransactionFunctionalTestCase;

class NewsletterSubscriberPersistenceTest extends TransactionFunctionalTestCase
{
    public function testPersistence(): void
    {
        $newsletterSubscriber = new NewsletterSubscriber(
            'no-reply@shopsys.com',
            new DateTimeImmutable('2018-02-06 15:15:48'),
            1
        );

        $em = $this->getEntityManager();
        $em->persist($newsletterSubscriber);
        $em->flush();
        $em->clear();

        $found = $em->createQueryBuilder()
        ->select('ns')
        ->from(NewsletterSubscriber::class, 'ns')
        ->where('ns.email = :email')
        ->andWhere('ns.domainId = :domainId')
        ->setParameters(['email' => 'no-reply@shopsys.com', 'domainId' => 1])
        ->getQuery()->getOneOrNullResult();

        Assert::assertEquals($newsletterSubscriber, $found);
        Assert::assertNotSame($newsletterSubscriber, $found);
    }
}
