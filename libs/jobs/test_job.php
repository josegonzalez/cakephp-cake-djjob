<?php
class CakeDjjob_TestJob extends CakeJob {

    function perform() {
        $this->out('Test Job');

        $this->out('Loading Job Model');
        $this->loadModel('Job');

        $this->out(sprintf('Loaded %s Model', $this->Job->name));

        $count = $this->Job->find('count');
        $this->out(sprintf('There are %s jobs in the queue', $count));

        $this->out('Sending an email');
        $this->loadComponent('Email');
        $this->Email->_set(array(
            'to' => 'postmaster@localhost',
            'from' => 'noreply@example.com',
            'subject' => 'Cake SMTP test',
            'replyTo' => 'noreply@example.com',
            'template' => null,
            'sendAs' => 'both',
        ));
        $this->Email->send('This is the body of the message');
        $this->out('Email sent');

        $this->out("Job completed");
    }

}