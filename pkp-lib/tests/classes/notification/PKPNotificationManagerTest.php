<?php

/**
 * @file tests/classes/notification/PKPNotificationManagerTest.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPNotificationManagerTest
 * @ingroup tests_classes_notification
 *
 * @see Config
 *
 * @brief Tests for the PKPNotificationManager class.
 */


import('lib.pkp.tests.PKPTestCase');

use APP\core\Application;
use APP\notification\Notification;
use Illuminate\Support\Facades\App;
use PKP\core\PKPRequest;
use PKP\core\Registry;
use PKP\db\DAORegistry;
use PKP\mail\MailTemplate;
use PKP\notification\NotificationDAO;
use PKP\notification\NotificationSettingsDAO;
use PKP\notification\PKPNotification;
use PKP\notification\PKPNotificationManager;
use PKP\site\Site;
use PKP\user\User;

define('NOTIFICATION_ID', 1);

class PKPNotificationManagerTest extends PKPTestCase
{
    private $notificationMgr;

    /**
     * @covers PKPNotificationManager::getNotificationMessage
     */
    public function testGetNotificationMessage()
    {
        $notification = $this->getTrivialNotification();
        $notification->setType(PKPNotification::NOTIFICATION_TYPE_REVIEW_ASSIGNMENT);

        $requestDummy = $this->getMockBuilder(PKPRequest::class)->getMock();
        $result = $this->notificationMgr->getNotificationMessage($requestDummy, $notification);

        $this->assertEquals(__('notification.type.reviewAssignment'), $result);
    }

    /**
     * @covers PKPNotificationManager::createNotification
     * @dataProvider trivialNotificationDataProvider
     */
    public function testCreateNotification($notification, $notificationParams = [])
    {
        $notificationMgrStub = $this->getMgrStubForCreateNotificationTests();
        $this->injectNotificationDaoMock($notification);

        if (!empty($notificationParams)) {
            $this->injectNotificationSettingsDaoMock($notificationParams);
        }

        $result = $this->exerciseCreateNotification($notificationMgrStub, $notification, $notificationParams);

        $this->assertEquals($notification, $result);
    }

    /**
     * @covers PKPNotificationManager::createNotification
     */
    public function testCreateNotificationBlocked()
    {
        $trivialNotification = $this->getTrivialNotification();

        $blockedNotificationTypes = [$trivialNotification->getType()];
        $notificationMgrStub = $this->getMgrStubForCreateNotificationTests($blockedNotificationTypes);

        $result = $this->exerciseCreateNotification($notificationMgrStub, $trivialNotification);

        $this->assertEquals(null, $result);
    }

    /**
     * @covers PKPNotificationManager::createNotification
     * @dataProvider trivialNotificationDataProvider
     */
    public function testCreateNotificationEmailed($notification, $notificationParams = [])
    {
        $nonTrivialNotification = $notification;

        // Make the notification non trivial.
        $nonTrivialNotification->setLevel(Notification::NOTIFICATION_LEVEL_NORMAL);

        // Setup any assoc type and id that have content definition in notification manager,
        // so we can check it later when sending the email.
        $nonTrivialNotification->setType(PKPNotification::NOTIFICATION_TYPE_NEW_ANNOUNCEMENT);
        $nonTrivialNotification->setAssocType(ASSOC_TYPE_ANNOUNCEMENT);

        $fixtureObjects = $this->getFixtureCreateNotificationSendEmail($nonTrivialNotification);
        [$notificationMgrStub, $requestStub] = $fixtureObjects;
        $this->injectNotificationDaoMock($nonTrivialNotification);

        if (!empty($notificationParams)) {
            $this->injectNotificationSettingsDaoMock($notificationParams);
        }

        $result = $this->exerciseCreateNotification($notificationMgrStub, $nonTrivialNotification, $notificationParams, $requestStub);

        $this->assertEquals($nonTrivialNotification, $result);
    }

    /**
     * @covers PKPNotificationManager::createNotification
     */
    public function testCreateNotificationTrivialNotEmailed()
    {
        // Trivial notifications should never be emailed.
        $trivialNotification = $this->getTrivialNotification();
        $emailedNotificationTypes = [$trivialNotification->getType()];

        $notificationMgrStub = $this->getMgrStubForCreateNotificationTests([], $emailedNotificationTypes, ['sendNotificationEmail']);
        // Make sure the sendNotificationEmail method will never be called.
        $notificationMgrMock = $notificationMgrStub;
        $notificationMgrMock->expects($this->never())
            ->method('sendNotificationEmail');

        $this->injectNotificationDaoMock($trivialNotification);

        $result = $this->exerciseCreateNotification($notificationMgrMock, $trivialNotification);

        $this->assertEquals($trivialNotification, $result);
    }

    /**
     * @covers PKPNotificationManager::createTrivialNotification
     * @dataProvider trivialNotificationDataProvider
     */
    public function testCreateTrivialNotification($notification, $notificationParams = [])
    {
        $trivialNotification = $notification;
        // Adapt the notification to the expected result.
        $trivialNotification->unsetData('assocId');
        $trivialNotification->unsetData('assocType');
        $trivialNotification->setType(PKPNotification::NOTIFICATION_TYPE_SUCCESS);

        $this->injectNotificationDaoMock($trivialNotification);
        if (!empty($notificationParams)) {
            $this->injectNotificationSettingsDaoMock($notificationParams);
        }

        $result = $this->notificationMgr->createTrivialNotification($trivialNotification->getUserId());

        $this->assertEquals($trivialNotification, $result);
    }

    /**
     * Provides data to be used by tests that expects two cases:
     * 1 - a trivial notification
     * 2 - a trivial notification and its parameters.
     *
     * @return array
     */
    public function trivialNotificationDataProvider()
    {
        $trivialNotification = $this->getTrivialNotification();
        $notificationParams = ['param1' => 'param1Value'];
        $data = [];

        $data[] = [$trivialNotification];
        $data[] = [$trivialNotification, $notificationParams];

        return $data;
    }

    //
    // Protected methods.
    //
    /**
     * @see PKPTestCase::getMockedDAOs()
     */
    protected function getMockedDAOs()
    {
        return ['NotificationDAO', 'NotificationSettingsDAO'];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationMgr = new PKPNotificationManager();
    }

    //
    // Helper methods.
    //
    /**
     * Exercise the system for all test methods that covers the
     * PKPNotificationManager::createNotification() method.
     *
     * @param PKPNotificationManager $notificationMgr An instance of the
     * notification manager.
     * @param PKPNotification $notificationToCreate
     * @param array $notificationToCreateParams
     * @param mixed $request (optional)
     */
    private function exerciseCreateNotification($notificationMgr, $notificationToCreate, $notificationToCreateParams = [], $request = null)
    {
        if (is_null($request)) {
            $request = $this->getMockBuilder(PKPRequest::class)->getMock();
        }

        return $notificationMgr->createNotification(
            $request,
            $notificationToCreate->getUserId(),
            $notificationToCreate->getType(),
            $notificationToCreate->getContextId(),
            $notificationToCreate->getAssocType(),
            $notificationToCreate->getAssocId(),
            $notificationToCreate->getLevel(),
            $notificationToCreateParams
        );
    }
    /**
     * Setup the fixture for all tests that covers the
     * PKPNotificationManager::createNotification() method in
     * a send email scenario.
     *
     * @runInSeparateProcess
     *
     * @return array Fixture objects.
     */
    private function getFixtureCreateNotificationSendEmail($expectedNotification)
    {
        // Add the notification type to the emailed notifications set.
        $emailedNotifications = [$expectedNotification->getType()];
        $notificationMgrStub = $this->getMgrStubForCreateNotificationTests([], $emailedNotifications, ['getMailTemplate']);

        // Stub a PKPRequest object.
        $requestStub = $this->getMockBuilder(PKPRequest::class)
            ->onlyMethods(['getSite', 'getContext', 'getUserVar', 'getDispatcher'])
            ->getMock();

        // Some site, user and notification data are required for composing the email.
        // Retrieve/define them so we can check later.
        $siteTitle = 'Site title';
        $siteContactName = 'Site contact name';
        $siteEmail = 'site@email.com';
        $userFirstName = 'FirstName';
        $userLastName = 'UserLastName';
        $userEmail = 'user@email.com';
        $notificationContents = $notificationMgrStub->getNotificationContents($requestStub, $expectedNotification);
        $contextTitle = 'Context title';

        // Build a test user object.
        $testUser = new User();
        $testUser->setId($expectedNotification->getUserId());
        $testUser->setGivenName($userFirstName, 'en_US');
        $testUser->setFamilyName($userLastName, 'en_US');
        $testUser->setEmail($userEmail);

        // Get the user full name to check.
        $userFullName = $testUser->getFullName(true, false, 'en_US');

        // Stub context.
        $application = Application::get();

        $mockApplication = $this->getMockBuilder(Application::class)
            ->onlyMethods(['getContextDepth', 'getContextList'])
            ->getMock();

        // Set up the getContextDepth() method
        $mockApplication->expects($this->any())
            ->method('getContextDepth')
            ->will($this->returnValue(2));

        // Set up the getContextList() method
        $mockApplication->expects($this->any())
            ->method('getContextList')
            ->will($this->returnValue(['firstContext', 'secondContext']));

        $dispatcher = $mockApplication->getDispatcher(); // this also adds the component router
        $dispatcher->addRouterName('\PKP\core\PKPPageRouter', 'page');
        $dispatcher->setApplication($mockApplication);

        $contextDao = $application->getContextDAO();
        $contextStub = $this->getMockBuilder(get_class($contextDao->newDataObject()))
            ->onlyMethods(['getLocalizedName', 'getContactName', 'getContactEmail'])
            ->getMock();
        $contextStub->expects($this->any())
            ->method('getLocalizedName')
            ->will($this->returnValue($contextTitle));
        $contextStub->expects($this->any())
            ->method('getContactName')
            ->will($this->returnValue($siteContactName));
        $contextStub->expects($this->any())
            ->method('getContactEmail')
            ->will($this->returnValue($siteEmail));

        // Inject context stub into our request stub.
        $requestStub->expects($this->any())
            ->method('getContext')
            ->will($this->returnValue($contextStub));
        $requestStub->expects($this->any())
            ->method('getUserVar')
            ->will($this->returnValue(null));
        $requestStub->expects($this->any())
            ->method('getDispatcher')
            ->will($this->returnValue($dispatcher));
        Registry::set('request', $requestStub);

        // Stub site.
        $siteStub = $this->getMockBuilder(Site::class)
            ->onlyMethods(['getLocalizedContactName', 'getLocalizedTitle', 'getLocalizedContactEmail', 'getPrimaryLocale'])
            ->getMock();

        $siteStub->expects($this->any())
            ->method('getLocalizedContactName')
            ->will($this->returnValue($siteContactName));
        $siteStub->expects($this->any())
            ->method('getLocalizedTitle')
            ->will($this->returnValue($siteTitle));
        $siteStub->expects($this->any())
            ->method('getLocalizedContactEmail')
            ->will($this->returnValue($siteEmail));
        $siteStub->expects($this->any())
            ->method('getPrimaryLocale')
            ->will($this->returnValue('en_US'));
        // Inject site stub into our request stub.
        $requestStub->expects($this->any())
            ->method('getSite')
            ->will($this->returnValue($siteStub));

        // Mock MailTemplate class so we can verify
        // notification manager interaction with it. Avoid
        // calling the mail template original constructor.
        $mailTemplateMock = $this->getMockBuilder(MailTemplate::class)
            ->onlyMethods(['setReplyTo', 'addRecipient', 'assignParams', 'send'])
            ->setConstructorArgs([null, 'en_US', $contextStub])
            ->getMock();

        $mailTemplateMock->expects($this->any())
            ->method('setReplyTo')
            ->with($this->equalTo($siteEmail), $this->equalTo($siteContactName));
        $mailTemplateMock->expects($this->any())
            ->method('addRecipient')
            ->with($this->equalTo($userEmail), $this->equalTo($userFullName));
        $mailTemplateMock->expects($this->any())
            ->method('assignParams')
            ->with($this->logicalAnd($this->containsEqual($notificationContents), $this->containsEqual($contextTitle)));
        $mailTemplateMock->expects($this->once())
            ->method('send')
            ->will($this->returnValue(true));

        // Inject our MailTemplate mock in notification manager.
        $notificationMgrStub->expects($this->any())
            ->method('getMailTemplate')
            ->will($this->returnValue($mailTemplateMock));

        // Register a UserDao stub to return the test user.
        App::instance(\PKP\user\DAO::class, \Mockery::mock(\PKP\user\DAO::class, function ($mock) use ($testUser) {
            $mock->shouldReceive('get')->with($testUser->getId(), true)->andReturn($testUser);
        }));

        return [$notificationMgrStub, $requestStub];
    }

    /**
     * Get the notification manager stub for tests that
     * covers the PKPNotificationManager::createNotification() method.
     *
     * @param array $blockedNotifications (optional) Each notification type
     * that is blocked by user. Will be used as return value for the
     * getUserBlockedNotifications method.
     * @param array $emailedNotifications (optional) Each notification type
     * that user will be also notified by email.
     * @param array $extraOpToStub (optional) Method names to be stubbed.
     * Its expectations can be set on the returned object.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getMgrStubForCreateNotificationTests($blockedNotifications = [], $emailedNotifications = [], $extraOpToStub = [])
    {
        $notificationMgrStub = $this->getMockBuilder(PKPNotificationManager::class)
            ->onlyMethods(array_merge($extraOpToStub, ['getUserBlockedNotifications', 'getNotificationUrl']))
            ->getMock();

        $notificationMgrStub->expects($this->any())
            ->method('getUserBlockedNotifications')
            ->will($this->returnValue($blockedNotifications));

        $notificationMgrStub->expects($this->any())
            ->method('getNotificationUrl')
            ->will($this->returnValue('anyNotificationUrl'));

        return $notificationMgrStub;
    }

    /**
     * Setup NotificationDAO mock and register it.
     *
     * @param PKPNotification $notification A notification that is
     * expected to be inserted by the DAO.
     */
    private function injectNotificationDaoMock($notification)
    {
        $notificationDaoMock = $this->getMockBuilder(NotificationDAO::class)
            ->onlyMethods(['insertObject'])
            ->getMock();
        $notificationDaoMock->expects($this->once())
            ->method('insertObject')
            ->with($this->equalTo($notification))
            ->will($this->returnValue(NOTIFICATION_ID));

        DAORegistry::registerDAO('NotificationDAO', $notificationDaoMock);
    }

    /**
     * Setup NotificationSettingsDAO mock and register it.
     *
     * @param array $notificationParams Notification parameters.
     */
    private function injectNotificationSettingsDaoMock($notificationParams)
    {
        // Mock NotificationSettingsDAO.
        $notificationSettingsDaoMock = $this->getMockBuilder(NotificationSettingsDAO::class)->getMock();
        $notificationSettingsDaoMock->expects($this->any())
            ->method('updateNotificationSetting')
            ->with(
                $this->equalTo(NOTIFICATION_ID),
                $this->equalTo(key($notificationParams)),
                $this->equalTo(current($notificationParams))
            );

        // Inject notification settings DAO mock.
        DAORegistry::registerDAO('NotificationSettingsDAO', $notificationSettingsDaoMock);
    }

    /**
     * Get a trivial notification filled with test data.
     *
     * @return PKPNotification
     */
    private function getTrivialNotification()
    {
        $notificationDao = DAORegistry::getDAO('NotificationDAO');
        $notification = $notificationDao->newDataObject();
        $anyTestInteger = 1;
        $notification->setUserId($anyTestInteger);
        $notification->setType($anyTestInteger);
        $notification->setContextId(\PKP\core\PKPApplication::CONTEXT_ID_NONE);
        $notification->setAssocType($anyTestInteger);
        $notification->setAssocId($anyTestInteger);
        $notification->setLevel(Notification::NOTIFICATION_LEVEL_TRIVIAL);

        return $notification;
    }
}
