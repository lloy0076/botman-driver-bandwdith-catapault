<?php

namespace Tests;

use BotMan\BotMan\Http\Curl;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Nexmo\NexmoDriver;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;

class NexmoDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = Request::create('', 'POST', $responseData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new NexmoDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('Nexmo', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([
            'to' => '41766013098',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_messages_by_reference()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $hash = spl_object_hash($driver->getMessages()[0]);

        $this->assertSame($hash, spl_object_hash($driver->getMessages()[0]));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getText());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hey there',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hey there',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertSame('491762012309022505', $driver->getMessages()[0]->getSender());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertSame('4176260130298', $driver->getMessages()[0]->getRecipient());
    }

    /** @test */
    public function it_returns_the_user_object()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);

        $message = $driver->getMessages()[0];
        $user = $driver->getUser($message);

        $this->assertSame($user->getId(), '491762012309022505');
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
        $this->assertNull($user->getUsername());
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new NexmoDriver($request, [
            'nexmo' => [
                'app_key' => 'key',
                'app_secret' => 'secret',
            ]
        ], $htmlInterface);
        $this->assertTrue($driver->isConfigured());
    }

    /** @test */
    public function it_can_build_payload()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new NexmoDriver($request, [
            'nexmo' => [
                'app_key' => 'key',
                'app_secret' => 'secret',
            ]
        ], $htmlInterface);
        
        $incomingMessage = new IncomingMessage('text', '123456', '987654');

        $message = 'string';
        $payload = $driver->buildServicePayload($message, $incomingMessage);
        
        $this->assertSame([
            'api_key' => 'key',
            'api_secret' => 'secret',
            'to' => '123456',
            'from' => '987654',
            'text' => 'string'
        ], $payload);

        $message = new OutgoingMessage('message object');
        $payload = $driver->buildServicePayload($message, $incomingMessage);
        
        $this->assertSame([
            'api_key' => 'key',
            'api_secret' => 'secret',
            'to' => '123456',
            'from' => '987654',
            'text' => 'message object'
        ], $payload);

        $message = new Question('question object');
        $payload = $driver->buildServicePayload($message, $incomingMessage);
        
        $this->assertSame([
            'api_key' => 'key',
            'api_secret' => 'secret',
            'to' => '123456',
            'from' => '987654',
            'text' => 'question object'
        ], $payload);
    }

    /** @test */
    public function it_can_send_payload()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new NexmoDriver($request, [
            'nexmo' => [
                'app_key' => 'key',
                'app_secret' => 'secret',
            ]
        ], $htmlInterface);

        $payload = [
            'api_key' => 'key',
            'api_secret' => 'secret',
            'to' => '123456',
            'from' => '987654',
            'text' => 'string'
        ];

        $htmlInterface->shouldReceive('post')
            ->once()
            ->with('https://rest.nexmo.com/sms/json?'.http_build_query($payload));
        
        $driver->sendPayload($payload);
    }

    /** @test */
    public function it_can_send_requests()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new NexmoDriver($request, [
            'nexmo' => [
                'app_key' => 'key',
                'app_secret' => 'secret',
            ]
        ], $htmlInterface);

        $parameters = [
            'to' => '123456',
            'from' => '987654',
            'text' => 'string'
        ];

        $payload = [
            'api_key' => 'key',
            'api_secret' => 'secret',
            'to' => '123456',
            'from' => '987654',
            'text' => 'string'
        ];

        $htmlInterface->shouldReceive('post')
            ->once()
            ->with('https://rest.nexmo.com/foo/json?'.http_build_query($payload));
        
        $incomingMessage = new IncomingMessage('text', '123456', '987654');
        $driver->sendRequest('foo/json', $payload, $incomingMessage);
    }

    /** @test */
    public function it_can_get_conversation_answers()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new NexmoDriver($request, [
            'nexmo' => [
                'app_key' => 'key',
                'app_secret' => 'secret',
            ]
        ], $htmlInterface);
        
        $incomingMessage = new IncomingMessage('text', '123456', '987654');
        $answer = $driver->getConversationAnswer($incomingMessage);

        $this->assertSame('text', $answer->getText());
    }
}
