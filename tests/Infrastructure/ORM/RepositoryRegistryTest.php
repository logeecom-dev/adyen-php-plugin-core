<?php

namespace Adyen\Core\Tests\Infrastructure\ORM;

use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Adyen\Core\Infrastructure\ORM\RepositoryRegistry;
use Adyen\Core\Infrastructure\TaskExecution\QueueItem;
use Adyen\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Adyen\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use PHPUnit\Framework\TestCase;

/***
 * Class RepositoryRegistryTest
 * @package Adyen\Core\Tests\Infrastructure\ORM
 */
class RepositoryRegistryTest extends TestCase
{
    /**
     * @throws \Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisterRepository()
    {
        RepositoryRegistry::registerRepository('test', MemoryRepository::getClassName());

        $repository = RepositoryRegistry::getRepository('test');
        $this->assertInstanceOf(MemoryRepository::getClassName(), $repository);
    }

    /**
     * @throws \Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisterRepositoryWrongRepo()
    {
        RepositoryRegistry::registerRepository('test', MemoryQueueItemRepository::getClassName());

        $repository = RepositoryRegistry::getRepository('test');
        $this->assertNotEquals(MemoryRepository::getClassName(), $repository);
    }

    public function testRegisterRepositoryWrongRepoClass()
    {
        $this->expectException(RepositoryClassException::class);

        RepositoryRegistry::registerRepository('test', '\PHPUnit\Framework\TestCase');
    }

    public function testRegisterRepositoryNotRegistered()
    {
        $this->expectException(RepositoryNotRegisteredException::class);

        RepositoryRegistry::getRepository('test2');
    }

    /**
     * @throws \Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testGetQueueItemRepository()
    {
        RepositoryRegistry::registerRepository(QueueItem::getClassName(), MemoryQueueItemRepository::getClassName());

        $repository = RepositoryRegistry::getQueueItemRepository();
        $this->assertInstanceOf(MemoryQueueItemRepository::getClassName(), $repository);
    }

    /**
     * Test isRegistered method.
     *
     * @throws \Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    public function testIsRegistered()
    {
        RepositoryRegistry::registerRepository('test', MemoryRepository::getClassName());
        $this->assertTrue(RepositoryRegistry::isRegistered('test'));
        $this->assertFalse(RepositoryRegistry::isRegistered('test2'));
    }

    /**
     * @throws \Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testGetQueueItemRepositoryException()
    {
        $this->expectException(RepositoryClassException::class);

        RepositoryRegistry::registerRepository(QueueItem::getClassName(), MemoryRepository::getClassName());

        RepositoryRegistry::getQueueItemRepository();
    }
}
