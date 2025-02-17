<?php
namespace BitsnBolts\Flysystem\Adapter\MSGraph\Test;

use League\Flysystem\Filesystem;
use BitsnBolts\Flysystem\Adapter\Plugins\GetUrl;
use BitsnBolts\Flysystem\Adapter\Plugins\InviteUser;
use BitsnBolts\Flysystem\Adapter\Plugins\CreateDrive;
use BitsnBolts\Flysystem\Adapter\Plugins\DeleteDrive;
use BitsnBolts\Flysystem\Adapter\MSGraphAppSharepoint;

class SharepointTest extends TestBase
{
    private $fs;

    private $filesToPurge = [];

    protected function setUp(): void
    {
        parent::setUp();
        $adapter = new MSGraphAppSharepoint();
        $adapter->authorize(TENANT_ID, APP_ID, APP_PASSWORD);
        $adapter->initialize(SHAREPOINT_SITE_ID, SHAREPOINT_DRIVE_NAME);

        $this->fs = new Filesystem($adapter);
    }

    public function testWrite()
    {
        $this->assertEquals(true, $this->fs->write(TEST_FILE_PREFIX . 'testWrite.txt', 'testing'));
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testWrite.txt';
    }

    public function testWriteToDirectory()
    {
        $this->assertEquals(true, $this->fs->write('testDir/' . TEST_FILE_PREFIX . 'testWriteInDir.txt', 'testing'));
        $this->filesToPurge[] = 'testDir/' . TEST_FILE_PREFIX . 'testWriteInDir.txt';
    }

    public function testWriteStream()
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'testing');
        rewind($stream);

        $this->assertEquals(true, $this->fs->writeStream(TEST_FILE_PREFIX . 'testWriteStream.txt', $stream));
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testWriteStream.txt';
    }

    public function testDelete()
    {
        // Create file
        $this->fs->write(TEST_FILE_PREFIX . 'testDelete.txt', 'testing');
        // Ensure it exists
        $this->assertEquals(true, $this->fs->has(TEST_FILE_PREFIX . 'testDelete.txt'));
        // Now delete
        $this->assertEquals(true, $this->fs->delete(TEST_FILE_PREFIX . 'testDelete.txt'));
        // Ensure it no longer exists
        $this->assertEquals(false, $this->fs->has(TEST_FILE_PREFIX . 'testDelete.txt'));
    }

    public function testHas()
    {
        // Test that file does not exist
        $this->assertEquals(false, $this->fs->has(TEST_FILE_PREFIX . 'testHas.txt'));

        // Create file
        $this->fs->write(TEST_FILE_PREFIX . 'testHas.txt', 'testing');
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testHas.txt';

        // Test that file exists
        $this->assertEquals(true, $this->fs->has(TEST_FILE_PREFIX . 'testHas.txt'));
    }

    /** @group test */
    public function testHasWorksWithADirectoryWhenTheDriveIsNotSetOnInitialize()
    {
        // Test that file does not exist
        $this->assertEquals(false, $this->fs->has(TEST_FILE_PREFIX . 'testHasWithDirectory.txt'));

        // Create file
        $this->fs->write(TEST_FILE_PREFIX . 'testHasWithDirectory.txt', 'testing');
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testHasWithDirectory.txt';

        // Test that file exists
        $this->assertEquals(true, $this->fs->has(TEST_FILE_PREFIX . 'testHasWithDirectory.txt'));

        $adapter = new MSGraphAppSharepoint();
        $adapter->authorize(TENANT_ID, APP_ID, APP_PASSWORD);
        $adapter->initialize(SHAREPOINT_SITE_ID);

        $fs = new Filesystem($adapter);
        // Test that file exists
        $this->assertEquals(true, $fs->has(SHAREPOINT_DRIVE_NAME . '/' . TEST_FILE_PREFIX . 'testHasWithDirectory.txt'));
    }

    public function testRead()
    {
        // Create file
        $this->fs->write(TEST_FILE_PREFIX . 'testRead.txt', 'testing read functionality');
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testRead.txt';

        // Call read
        $this->assertEquals("testing read functionality", $this->fs->read(TEST_FILE_PREFIX . 'testRead.txt'));
    }

    /** @group test2 */
    public function testReadWorksWithADirectoryWhenTheDriveIsNotSetOnInitialize()
    {
        // Create file
        $this->fs->write(TEST_FILE_PREFIX . 'testReadWithDirectory.txt', 'testing read functionality');
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testReadWithDirectory.txt';

        // Call read
        $this->assertEquals("testing read functionality", $this->fs->read(TEST_FILE_PREFIX . 'testReadWithDirectory.txt'));

        $adapter = new MSGraphAppSharepoint();
        $adapter->authorize(TENANT_ID, APP_ID, APP_PASSWORD);
        $adapter->initialize(SHAREPOINT_SITE_ID);

        $fs = new Filesystem($adapter);
        // Test that file exists
        $this->assertEquals("testing read functionality", $fs->read(SHAREPOINT_DRIVE_NAME . '/' . TEST_FILE_PREFIX . 'testReadWithDirectory.txt'));
    }

    public function testGetUrl()
    {
        // Create file
        $this->fs->write(TEST_FILE_PREFIX . 'testGetUrl.txt', 'testing getUrl functionality');
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testGetUrl.txt';

        // Get url
        $this->assertNotEmpty($this->fs->getAdapter()->getUrl(TEST_FILE_PREFIX . 'testGetUrl.txt'));
    }

    public function testGetUrlPlugin()
    {
        $this->fs->addPlugin(new GetUrl());

        $this->fs->write(TEST_FILE_PREFIX . 'testGetUrlPlugin.txt', 'testing getUrl plugin functionality');
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testGetUrlPlugin.txt';

        // Get url
        $this->assertNotEmpty($this->fs->getAdapter()->getUrl(TEST_FILE_PREFIX . 'testGetUrlPlugin.txt'));
    }

    public function testCreateAndDeleteDrive()
    {
        $this->fs->addPlugin(new CreateDrive());
        $this->fs->addPlugin(new DeleteDrive());

        $adapter = new MSGraphAppSharepoint();
        $adapter->authorize(TENANT_ID, APP_ID, APP_PASSWORD);
        $adapter->initialize(SHAREPOINT_SITE_ID);

        $this->fs->createDrive('testNewDrive');

        $this->assertNotNull($adapter);

        $this->fs->deleteDrive('testNewDrive');
    }

    public function testInviteUser()
    {
        $this->fs->addPlugin(new InviteUser());

        $adapter = new MSGraphAppSharepoint();
        $adapter->authorize(TENANT_ID, APP_ID, APP_PASSWORD);
        $adapter->initialize(SHAREPOINT_SITE_ID);

        $this->assertEquals(true, $this->fs->write(TEST_FILE_PREFIX . 'testInvite.txt', 'testing'));
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testInvite.txt';

        $invite = $this->fs->inviteUser(TEST_FILE_PREFIX . 'testInvite.txt', SHAREPOINT_INVITE_USER);
    }

    public function testGetMetadata()
    {
        // Create file
        $this->fs->write(TEST_FILE_PREFIX . 'testMetadata.txt', 'testing metadata functionality');
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testMetadata.txt';

        // Call metadata
        $metadata = $this->fs->getMetadata(TEST_FILE_PREFIX.'testMetadata.txt');
        $this->assertEquals("testMetadata.txt", $metadata['path']);
    }

    public function testTimestamp()
    {
        // Create file
        $this->fs->write(TEST_FILE_PREFIX . 'testTimestamp.txt', 'testing metadata functionality');
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testTimestamp.txt';

        // Call metadata
        $this->assertIsInt($this->fs->getTimestamp(TEST_FILE_PREFIX.'testTimestamp.txt'));
    }

    public function testMimetype()
    {
        // Create file
        $this->fs->write(TEST_FILE_PREFIX . 'testMimetype.txt', 'testing metadata functionality');
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testMimetype.txt';

        // Call metadata
        $this->assertEquals('text/plain', $this->fs->getMimetype(TEST_FILE_PREFIX.'testMimetype.txt'));
    }

    public function testSize()
    {
        // Create file
        $this->fs->write(TEST_FILE_PREFIX . 'testSize.txt', 'testing metadata functionality');
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testSize.txt';

        // Get the file size
        $this->assertEquals(30, $this->fs->getSize(TEST_FILE_PREFIX.'testSize.txt'));
    }

    /**
     * @return void
     */
    public function testLargeFileUploads()
    {
        // Create file
        $path = __DIR__ . '/files/50MB.bin';
        $this->fs->writeStream(TEST_FILE_PREFIX . 'testLargeUpload.txt', fopen($path, 'r'));
        fclose($path);
        $this->filesToPurge[] = TEST_FILE_PREFIX . 'testLargeUpload.txt';

        // Get the file size
        $this->assertEquals(30, $this->fs->getSize(TEST_FILE_PREFIX.'testLargeUpload.txt'));
    }

    /**
     * Tears down the test suite by attempting to delete all files written, clearing things up
     *
     * @todo Implement functionality
     */
    protected function tearDown(): void
    {
        foreach ($this->filesToPurge as $path) {
            try {
                $this->fs->delete($path);
            } catch (\Exception $e) {
                // Do nothing, just continue. We obviously can't clean it
            }
        }
        $this->filesToPurge = [];
    }
}
