<?php

namespace Adyen\Core\Tests\Infrastructure\TaskExecution;

use Adyen\Core\Infrastructure\Http\HttpClient;
use Adyen\Core\Infrastructure\Logger\Logger;
use Adyen\Core\Infrastructure\ORM\RepositoryRegistry;
use Adyen\Core\Infrastructure\Serializer\Serializer;
use Adyen\Core\Infrastructure\TaskExecution\AsyncProcessStarterService;
use Adyen\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Adyen\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Adyen\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;
use Adyen\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Adyen\Core\Infrastructure\TaskExecution\Process;
use Adyen\Core\Infrastructure\TaskExecution\TaskRunner;
use Adyen\Core\Infrastructure\TaskExecution\TaskRunnerStarter;
use Adyen\Core\Infrastructure\TaskExecution\TaskRunnerStatus;
use Adyen\Core\Infrastructure\Utility\GuidProvider;
use Adyen\Core\Tests\Infrastructure\Common\BaseInfrastructureTestWithServices;
use Adyen\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Adyen\Core\Tests\Infrastructure\Common\TestComponents\ORM\MemoryStorage;
use Adyen\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestRunnerStatusStorage;
use Adyen\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunner;
use Adyen\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Adyen\Core\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Adyen\Core\Tests\Infrastructure\Common\TestComponents\Utility\TestGuidProvider;
use Adyen\Core\Tests\Infrastructure\Common\TestServiceRegister;
use Exception;

/**
 * Class TaskRunnerStarterTest
 *
 * @package Adyen\Core\Tests\Infrastructure\TaskExecution
 */
class TaskRunnerStarterTest extends BaseInfrastructureTestWithServices
{
    /** @var AsyncProcessService */
    private $asyncProcessStarter;
    /** @var \Adyen\Core\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunner */
    private $taskRunner;
    /** @var TestRunnerStatusStorage */
    private $runnerStatusStorage;
    /** @var TestGuidProvider */
    private $guidProvider;
    /** @var TaskRunnerStarter */
    private $runnerStarter;
    /** @var string */
    private $guid;

    public function testTaskRunnerIsStartedWithProperGuid()
    {
        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $setGuidCallHistory = $this->taskRunner->getMethodCallHistory('setGuid');
        $this->assertCount(1, $runCallHistory, 'Run call must start runner.');
        $this->assertCount(1, $setGuidCallHistory, 'Run call must set runner guid.');
        $this->assertEquals($this->guid, $setGuidCallHistory[0]['guid'], 'Run call must set runner guid.');
    }

    /**
     * @throws \Adyen\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Adyen\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \Exception
     */
    public function testRunningTaskRunnerWhenExpired()
    {
        // Arrange
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $expiredTimestamp = $currentTimestamp - TaskRunnerStatus::MAX_ALIVE_TIME - 1;
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus($this->guid, $expiredTimestamp));

        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $this->assertCount(0, $runCallHistory, 'Run call must fail when runner is expired.');
        $this->assertStringContainsString(
            'Failed to run task runner',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when runner is expired'
        );
        $this->assertStringContainsString(
            'Runner is expired.',
            $this->shopLogger->data->getMessage(),
            'Debug message must be logged when trying to run expired task runner.'
        );
    }

    /**
     * @throws \Adyen\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Adyen\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \Exception
     */
    public function testRunningTaskRunnerWithActiveGuidDoNotMatchGuidGeneratedWithWakeup()
    {
        // Arrange
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus('different_active_guid', $currentTimestamp));

        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $this->assertCount(0, $runCallHistory, 'Run call must fail when runner guid is not set as active runner guid.');
        $this->assertStringContainsString(
            'Failed to run task runner.',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when runner guid is not set as active runner guid.'
        );
        $this->assertStringContainsString(
            'Runner guid is not set as active.',
            $this->shopLogger->data->getMessage(),
            'Debug message must be logged when trying to run task runner with guid that is not set as active runner guid.'
        );
    }

    public function testRunWhenRunnerStatusServiceIsUnavailable()
    {
        $this->runnerStatusStorage->setExceptionResponse(
            'getStatus',
            new TaskRunnerStatusStorageUnavailableException('Simulation for unavailable storage exception.')
        );

        // Act
        $this->runnerStarter->run();

        $this->assertStringContainsString(
            'Failed to run task runner.',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when runner status storage is unavailable.'
        );
        $this->assertStringContainsString('Runner status storage unavailable.', $this->shopLogger->data->getMessage());
    }

    public function testRunInCaseOfUnexpectedException()
    {
        $this->runnerStatusStorage->setExceptionResponse(
            'getStatus',
            new Exception('Simulation for unexpected exception.')
        );

        // Act
        $this->runnerStarter->run();
        $this->assertStringContainsString(
            'Failed to run task runner.',
            $this->shopLogger->data->getMessage(),
            'Run call must throw TaskRunnerRunException when unexpected exception occurs.'
        );
        $this->assertStringContainsString('Unexpected error occurred.', $this->shopLogger->data->getMessage());
    }

    public function testTaskStarterMustBeRunnableAfterDeserialization()
    {
        // Arrange
        /** @var TaskRunnerStarter $unserializedRunnerStarter */
        $unserializedRunnerStarter = Serializer::unserialize(Serializer::serialize($this->runnerStarter));

        // Act
        $unserializedRunnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $setGuidCallHistory = $this->taskRunner->getMethodCallHistory('setGuid');
        $this->assertCount(1, $runCallHistory, 'Run call must start runner.');
        $this->assertCount(1, $setGuidCallHistory, 'Run call must set runner guid.');
        $this->assertEquals($this->guid, $setGuidCallHistory[0]['guid'], 'Run call must set runner guid.');
    }

    /**
     * @throws \Adyen\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Adyen\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        RepositoryRegistry::registerRepository(Process::CLASS_NAME, MemoryRepository::getClassName());

        $runnerStatusStorage = new TestRunnerStatusStorage();
        $taskRunner = new TestTaskRunner();
        $guidProvider = TestGuidProvider::getInstance();

        TestServiceRegister::registerService(
            AsyncProcessService::CLASS_NAME,
            function () {
                return AsyncProcessStarterService::getInstance();
            }
        );
        TestServiceRegister::registerService(
            TaskRunnerStatusStorage::CLASS_NAME,
            function () use ($runnerStatusStorage) {
                return $runnerStatusStorage;
            }
        );
        TestServiceRegister::registerService(
            TaskRunner::CLASS_NAME,
            function () use ($taskRunner) {
                return $taskRunner;
            }
        );
        TestServiceRegister::registerService(
            GuidProvider::CLASS_NAME,
            function () use ($guidProvider) {
                return $guidProvider;
            }
        );
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () {
                return new TestHttpClient();
            }
        );
        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () {
                return new TestTaskRunnerWakeupService();
            }
        );

        Logger::resetInstance();

        $this->asyncProcessStarter = AsyncProcessStarterService::getInstance();
        $this->runnerStatusStorage = $runnerStatusStorage;
        $this->taskRunner = $taskRunner;
        $this->guidProvider = $guidProvider;

        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->guid = 'test_runner_guid';
        $this->runnerStarter = new TaskRunnerStarter($this->guid);
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus($this->guid, $currentTimestamp));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        AsyncProcessStarterService::resetInstance();
        MemoryStorage::reset();
        parent::tearDown();
    }
}
