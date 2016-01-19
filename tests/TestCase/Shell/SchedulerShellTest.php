<?php
namespace Scheduler\Test\TestCase\Shell;

use Scheduler\Shell\SchedulerShell;
use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use Cake\TestSuite\TestCase;
use \DateTime;
use \DateInterval;

/**
 * SchedulerShell test case.
 */
class SchedulerShellTest extends TestCase
{

    /**
     * setup method.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);
        
        $this->fileDir = TMP;

        Configure::write('SchedulerShell', [
            'storePath'=>$this->fileDir,
            'jobs'=>[]
        ]);

        $this->Shell = new SchedulerShell($io);
        $this->Shell->initialize();



    }

    /**
     * Teardown method.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Shell);

        $processingFlag = new File($this->fileDir . '.scheduler_running_flag');
        $storeFile = new File($this->fileDir . 'cron_scheduler.json');
        
        $processingFlag->delete();
        $storeFile->delete();
    }

    /**
     * Read the contents of the scheduler file
     *
     * @return stdClass
     */
    private function readSchedulerFile(){
        $file = new File($this->fileDir . 'cron_scheduler.json');
        $this->assertTrue($file->exists(), 'Verify the file was created');
        $results = json_decode($file->read());
        $file->close();
        unset($file);

        return $results;
    }

    /**
     * Read the contents of the scheduler file
     *
     * @param array $contents - assoc array of scheduler data
     * @return void
     */
    private function writeSchedulerFile($contents){
        $file = new File($this->fileDir . 'cron_scheduler.json');
        $file->write(json_encode($contents));
        $file->close();
        unset($file);
    }

    /**
     * Test that the tests are running
     *
     * @return void
     */
    public function testAlive()
    {
        $this->assertTrue(true, 'Not alive');
    }

    /**
     * Test running with no tasks
     *
     * @return void
     */
    public function testNoTasks()
    {
        $this->Shell->main();

        $result = $this->readSchedulerFile();
        $this->assertEmpty($result);
    }

    /**
     * Test running basic task
     *
     * @return void
     */
    public function testBasicTasks()
    {
        Configure::write('SchedulerShell.jobs', [
            'CleanUp' => array('interval' => 'next day 5:00', 'task' => 'CleanUp'),// tomorrow at 5am
        ]);

        $this->Shell->main();

        $result = $this->readSchedulerFile();
        
        $this->assertNotEmpty($result);
        $this->assertObjectHasAttribute('CleanUp', $result);
        $this->assertEquals('CleanUp Done', $result->CleanUp->lastResult);
    }

    /**
     * Test running basic task
     *
     * @return void
     */
    public function testConnectTasks()
    {
        $this->Shell->connect('CleanUp', 'next day 5:00', 'CleanUp');
        $this->Shell->main();

        $result = $this->readSchedulerFile();
        
        $this->assertNotEmpty($result);
        $this->assertObjectHasAttribute('CleanUp', $result);
        $this->assertEquals('CleanUp Done', $result->CleanUp->lastResult);
    }

    /**
     * Test running basic task
     *
     * @return void
     */
    public function testBasicTaskIntervalRuns()
    {
        $fiveMinAgo = new DateTime('16 minutes ago');
        $sample = [
                'CleanUp'=>[
                    'name'=>'CleanUp',
                    'task'=>'CleanUp',
                    'action'=>'main',
                    'interval'=>'PT15M', // every 15 minutes
                    'lastRun'=>$fiveMinAgo->format('Y-m-d H:i:s'),
                    'lastResult'=>'Old Result'
                ]
            ];

        $this->writeSchedulerFile($sample);

        Configure::write('SchedulerShell.jobs', $sample);

        $this->Shell->main();

        $result = $this->readSchedulerFile();
        
        $this->assertNotEmpty($result);
        $this->assertObjectHasAttribute('CleanUp', $result);
        $this->assertEquals('CleanUp Done', $result->CleanUp->lastResult);
    }

    /**
     * Test running basic task
     *
     * @return void
     */
    public function testBasicTaskIntervalDoesntRun()
    {
        $fiveMinAgo = new DateTime('14 minutes ago');
        $sample = [
                'CleanUp'=>[
                    'name'=>'CleanUp',
                    'task'=>'CleanUp',
                    'action'=>'main',
                    'interval'=>'PT15M', // every 15 minutes
                    'lastRun'=>$fiveMinAgo->format('Y-m-d H:i:s'),
                    'lastResult'=>'Old Result'
                ]
            ];

        $this->writeSchedulerFile($sample);

        Configure::write('SchedulerShell.jobs', $sample);

        $this->Shell->main();

        $result = $this->readSchedulerFile();
        
        $this->assertNotEmpty($result);
        $this->assertObjectHasAttribute('CleanUp', $result);
        $this->assertEquals('Old Result', $result->CleanUp->lastResult);
    }

    /**
     * Test running basic task
     *
     * @return void
     */
    public function testBasicTaskRelativeDateRuns()
    {
        $next = new DateTime();
        $yesterday = new DateTime('24 hours ago');
        $sample = [
                'CleanUp'=>[
                    'name'=>'CleanUp',
                    'task'=>'CleanUp',
                    'action'=>'main',
                    'interval'=>'next day '. $next->format('H:i'), // should run $now
                    'lastRun'=>$yesterday->format('Y-m-d H:i:s'),
                    'lastResult'=>'Old Result'
                ]
            ];

        $this->writeSchedulerFile($sample);

        Configure::write('SchedulerShell.jobs', $sample);

        $this->Shell->main();

        $result = $this->readSchedulerFile();
        
        $this->assertNotEmpty($result);
        $this->assertObjectHasAttribute('CleanUp', $result);
        $this->assertEquals('CleanUp Done', $result->CleanUp->lastResult);
    }

    /**
     * Test running basic task
     *
     * @return void
     */
    public function testBasicTaskRelativeDateDoesntRun()
    {
        $next = new DateTime('+1 minute');
        $yesterday = new DateTime('24 hours ago');
        $sample = [
                'CleanUp'=>[
                    'name'=>'CleanUp',
                    'task'=>'CleanUp',
                    'action'=>'main',
                    'interval'=>'next day '. $next->format('H:i'), // should run $now
                    'lastRun'=>$yesterday->format('Y-m-d H:i:s'),
                    'lastResult'=>'Old Result'
                ]
            ];

        $this->writeSchedulerFile($sample);

        Configure::write('SchedulerShell.jobs', $sample);

        $this->Shell->main();

        $result = $this->readSchedulerFile();
        
        $this->assertNotEmpty($result);
        $this->assertObjectHasAttribute('CleanUp', $result);
        $this->assertEquals('Old Result', $result->CleanUp->lastResult);
    }

    /**
     * Test running basic task
     *
     * @return void
     */
    public function testTaskWithModel()
    {
        $this->Shell->connect('Colors', 'PT15M', 'Colors');
        $this->Shell->main();

        $result = $this->readSchedulerFile();
        
        $this->assertNotEmpty($result);
        $this->assertObjectHasAttribute('Colors', $result);
        $this->assertEquals('Colors Exist', $result->Colors->lastResult);

    }

}