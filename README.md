CakePHP Scheduler Plugin
========================

Makes scheduling tasks in CakePHP much simpiler.

Author
------
Trent Richardson [http://trentrichardson.com]

Developed as part of the CarBounce project [http://carbounce.com]

License
-------
Copyright 2013 Trent Richardson

You may use this project under MIT license.
http://trentrichardson.com/Impromptu/MIT-LICENSE.txt

How It Works
------------
SchedulerShell works by scheduling one cron (SchedulerShell) for your project. Then in bootstrap.php you can create intervals for your all your tasks.  Deploying new sheduled tasks are now much easier, one crontab entry results in all your tasks.

Install
-------

This Shell was developed for CakePHP 2.

Copy the Scheduler plugin into your App/Plugin folder.

Load the plugin in your bootstrap.php file using either:

```php
CakePlugin::loadAll();
```

or

```php
CakePlugin::load('Scheduler');
```

Schedule a single system cron by the shortest interval you need for SchedulerShell.php.  For example, if you have 5 tasks and the most often run is every 5 minutes, then schedule the this cron to run at least every 5 minutes. For more help see [Shells as Cron Jobs](http://book.cakephp.org/2.0/en/console-and-shells/cron-jobs.html).

Example cron job:

````
*/5 * * * * cd /path/to/app && Console/cake Scheduler.Scheduler
````

This would run the Scheduler shell every 5 minutes.

Now once this shell is sheduled we are able to add our entries to bootstrap.php.  Lets say we want to schedule a CleanUp task daily at 5am and a NewsletterTask for every 15 minutes.

```php
Configure::write('SchedulerShell.jobs', array(
		'CleanUp' => array('interval'=>'next day 5:00','task'=>'CleanUp'),// tomorrow at 5am
		'Newsletters' => array('interval'=>'PT15M','task'=>'Newsletter') //every 15 minutes
	));
```

The key to each entry will be used to store the previous run.  *These must be unique*!

**interval** is set one of two ways.  
1) For set times of day we use php's [relative time formats](http://www.php.net/manual/en/datetime.formats.relative.php): "next day 5:00". 

2) To use a interval to achieve "every 15 minutes" we use [DateInterval](http://www.php.net/manual/en/class.dateinterval.php) string format: "PT15M".

**task** is simply the name of the Task.

There are a couple optional arguments you may pass: "action" and "pass".

**action** defaults to "execute", which is the method name to call in the task you specify.

**pass** defaults to array(), which is the array of arguments to pass to your "action".

```php
Configure::write('SchedulerShell.jobs', array(
		'CleanUp' => array('interval'=>'next day 5:00','task'=>'CleanUp', 'action'=>'execute', 'pass'=>array()),
		'Newsletters' => array('interval'=>'PT15M','task'=>'Newsletter', 'action'=>'execute', 'pass'=>array())
	));
```

Storage of Results
------------------
SchedulerShell keeps track of each run in json file.  By default this is stored in TMP and is named "cron_scheduler.json".

If you need to change either of these you may use:

```php
// change the file name
Configure::write('SchedulerShell.storeFile', "scheduler_results.json");
// change the path (note the ending /)
Configure::write('SchedulerShell.storePath', "/path/to/save/");
```


Other Notes/Known Issues
------------------------
- The optional pass arguments have not been thoroughly tested
- While this is ideal for most tasks, if you have long running critical tasks, you may fair better to schedule your own separate cron job.  This is because of potential overlap between runs.  I think this could be resolved with more appropriate storage of runs.  Feel free to help with this :).