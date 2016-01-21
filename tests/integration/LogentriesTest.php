<?php

class LogentriesTest extends TestCase
{
    public function testLogentriesClass()
    {
        if (env('LOGENTRIES_ENABLE')) {
            $handlers = Log::getMonolog()->getHandlers();
            $this->assertEquals('Logentries\Handler\LogentriesHandler', get_class($handlers[0]));
        }
    }

    public function testLogentriesToken()
    {
        if (env('LOGENTRIES_ENABLE')) {
            $this->assertNotEmpty(env('LOGENTRIES_TOKEN'), 'LOGENTRIES_TOKEN is empty');
        }
    }
}
