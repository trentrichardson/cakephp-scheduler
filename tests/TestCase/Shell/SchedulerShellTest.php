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
        $this->storeFile = 'cron_scheduler.json';
        $this->processingFlagFile = '.cron_scheduler_processing_flag';

        Configure::write('SchedulerShell', [
            'storePath'=>$this->fileDir,
            'storeFile'=>$this->storeFile,
            'processingFlagFile'=>$this->processingFlagFile,
            'processingFlagFile'=>'.cron_scheduler_processing_flag',
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

        $processingFlag = new File($this->fileDir . $this->processingFlagFile);
        $storeFile = new File($this->fileDir . $this->storeFile);
        
        $processingFlag->delete();
        $storeFile->delete();
    }

    /**
     * Read the contents of the scheduler file
     *
     * @param boolean $assert - whether or not to assert if the file exists
     * @return mixed - stdClass or false if it doesn't exist
     */
    private function readSchedulerFile($assert = true){
        $results = false;
        $file = new File($this->fileDir . $this->storeFile);
        if($file->exists()){
            $results = json_decode($file->read());
        }
        $file->close();
        unset($file);
        
        if($assert){
            $this->assertTrue($results !== false, 'Verify the file was created');
        }
        return $results;
    }

    /**
     * Read the contents of the scheduler file
     *
     * @param array $contents - assoc array of scheduler data
     * @return void
     */
    private function writeSchedulerFile($contents){
        $file = new File($this->fileDir . $this->storeFile);
        $file->write(json_encode($contents));
        $file->close();
        unset($file);
    }

    /**
     * Create processing flag file
     *
     * @return void
     */
    private function createProcessingFlagFile(){
        $file = new File($this->fileDir . $this->processingFlagFile, true);
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

    /**
     * Test that it will not run if a flag file exists
     *
     * @return void
     */
    public function testExistingProcessingRun()
    {
        $this->createProcessingFlagFile();

        $this->Shell->connect('CleanUp', 'PT15M', 'CleanUp');
        $this->Shell->main();

        $result = $this->readSchedulerFile(false);
        
        $this->assertFalse($result);
    }

    /**
     * Test that it will run if timeout has expired on existing run
     *
     * @return void
     */
    public function testExistingProcessingRunTimeout()
    {
        Configure::write('SchedulerShell.processingTimeout', 0.5);

        $this->createProcessingFlagFile();
        
        sleep(1); // sleep 2 seconds should timeout the flag file

        $this->Shell->connect('CleanUp', 'PT15M', 'CleanUp');
        $this->Shell->main();

        $result = $this->readSchedulerFile();
        
        $this->assertNotEmpty($result);
        $this->assertObjectHasAttribute('CleanUp', $result);
    }

}