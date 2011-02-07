<?php
class CakeDjjob_TestJob extends CakeJob {

    function perform() {
        $this->out('Test Job');

        $this->out('Loading Job Model');
        $this->loadModel('Job');

        $this->out(sprintf('Loaded %s Model', $this->Job->name));

        $count = $this->Job->find('count');
        $this->out(sprintf('There are %s jobs in the queue', $count));
        $this->out("Job completed");
    }

}